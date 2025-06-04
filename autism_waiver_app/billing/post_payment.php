<?php
session_start();
require_once '../simple_auth_helper.php';
require_once '../config_sqlite.php';

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!in_array($_SESSION['user_type'], ['admin', 'billing'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

// Validate required fields
$required_fields = ['payment_date', 'payment_amount'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

try {
    $db->beginTransaction();
    
    // Prepare payment data
    $payment_data = [
        'payment_date' => $_POST['payment_date'],
        'payment_amount' => floatval($_POST['payment_amount']),
        'payment_type' => $_POST['payment_type'] ?? 'insurance',
        'claim_id' => !empty($_POST['claim_id']) && $_POST['unapplied_payment'] != '1' ? $_POST['claim_id'] : null,
        'patient_id' => null,
        'check_number' => $_POST['check_number'] ?? null,
        'reference_number' => $_POST['reference_number'] ?? null,
        'payer_name' => $_POST['payer_name'] ?? null,
        'payment_method' => $_POST['payment_method'] ?? null,
        'batch_deposit_id' => !empty($_POST['batch_deposit_id']) && $_POST['batch_deposit_id'] != 'new' ? $_POST['batch_deposit_id'] : null,
        'notes' => $_POST['notes'] ?? null,
        'posted_by' => $_SESSION['user_id'],
        'status' => 'posted',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Handle adjustments
    if (isset($_POST['adjustment']) && $_POST['adjustment'] == '1') {
        $payment_data['payment_type'] = 'adjustment';
        $payment_data['adjustment_type'] = $_POST['adjustment_type'];
        $payment_data['adjustment_reason'] = $_POST['reason'];
        $payment_data['payment_amount'] = -abs(floatval($_POST['adjustment_amount'])); // Adjustments are negative
    }
    
    // If claim is selected, get patient_id
    if ($payment_data['claim_id']) {
        $claim = $db->prepare("SELECT patient_id FROM claims WHERE id = ?");
        $claim->execute([$payment_data['claim_id']]);
        $claim_info = $claim->fetch(PDO::FETCH_ASSOC);
        if ($claim_info) {
            $payment_data['patient_id'] = $claim_info['patient_id'];
        }
    }
    
    // Create payment_postings table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS payment_postings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            payment_date DATE NOT NULL,
            payment_amount DECIMAL(10,2) NOT NULL,
            payment_type VARCHAR(50) NOT NULL,
            claim_id INTEGER,
            patient_id INTEGER,
            check_number VARCHAR(100),
            reference_number VARCHAR(100),
            era_number VARCHAR(100),
            payer_name VARCHAR(255),
            payment_method VARCHAR(50),
            batch_deposit_id INTEGER,
            adjustment_type VARCHAR(50),
            adjustment_reason TEXT,
            notes TEXT,
            posted_by INTEGER NOT NULL,
            voided_by INTEGER,
            void_date DATETIME,
            void_reason TEXT,
            status VARCHAR(20) DEFAULT 'posted',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (claim_id) REFERENCES claims(id),
            FOREIGN KEY (patient_id) REFERENCES patients(id),
            FOREIGN KEY (posted_by) REFERENCES users(id),
            FOREIGN KEY (voided_by) REFERENCES users(id),
            FOREIGN KEY (batch_deposit_id) REFERENCES batch_deposits(id)
        )
    ");
    
    // Create batch_deposits table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS batch_deposits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            deposit_number VARCHAR(100) UNIQUE NOT NULL,
            deposit_date DATE NOT NULL,
            bank_account VARCHAR(100),
            total_amount DECIMAL(10,2) DEFAULT 0,
            payment_count INTEGER DEFAULT 0,
            status VARCHAR(20) DEFAULT 'open',
            created_by INTEGER NOT NULL,
            closed_by INTEGER,
            closed_date DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id),
            FOREIGN KEY (closed_by) REFERENCES users(id)
        )
    ");
    
    // Insert payment
    $columns = array_keys($payment_data);
    $placeholders = array_map(function($col) { return ":$col"; }, $columns);
    
    $sql = "INSERT INTO payment_postings (" . implode(', ', $columns) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    $stmt = $db->prepare($sql);
    foreach ($payment_data as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }
    $stmt->execute();
    $payment_id = $db->lastInsertId();
    
    // Update claim status if payment was applied
    if ($payment_data['claim_id']) {
        // Get current claim balance
        $claim_stmt = $db->prepare("
            SELECT c.billed_amount, 
                   COALESCE(SUM(pp.payment_amount), 0) as total_paid
            FROM claims c
            LEFT JOIN payment_postings pp ON c.id = pp.claim_id AND pp.status = 'posted'
            WHERE c.id = ?
            GROUP BY c.id, c.billed_amount
        ");
        $claim_stmt->execute([$payment_data['claim_id']]);
        $claim_balance = $claim_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($claim_balance) {
            $new_balance = $claim_balance['billed_amount'] - $claim_balance['total_paid'];
            
            // Update claim status based on balance
            $new_status = 'submitted'; // default
            if ($new_balance <= 0) {
                $new_status = 'paid';
            } elseif ($claim_balance['total_paid'] > 0) {
                $new_status = 'partially_paid';
            }
            
            $update_claim = $db->prepare("UPDATE claims SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $update_claim->execute([$new_status, $payment_data['claim_id']]);
        }
    }
    
    // Update batch deposit totals if applicable
    if ($payment_data['batch_deposit_id']) {
        $update_deposit = $db->prepare("
            UPDATE batch_deposits 
            SET total_amount = total_amount + ?,
                payment_count = payment_count + 1,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $update_deposit->execute([$payment_data['payment_amount'], $payment_data['batch_deposit_id']]);
    }
    
    // Log the activity
    if ($db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='audit_log'")->fetchColumn()) {
        $audit_stmt = $db->prepare("
            INSERT INTO audit_log (user_id, action, table_name, record_id, details, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $details = sprintf(
            "Posted %s payment of $%.2f%s",
            $payment_data['payment_type'],
            abs($payment_data['payment_amount']),
            $payment_data['claim_id'] ? " to claim ID " . $payment_data['claim_id'] : " (unapplied)"
        );
        
        $audit_stmt->execute([
            $_SESSION['user_id'],
            'payment_posted',
            'payment_postings',
            $payment_id,
            $details,
            date('Y-m-d H:i:s')
        ]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment posted successfully',
        'payment_id' => $payment_id
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error posting payment: ' . $e->getMessage()
    ]);
}