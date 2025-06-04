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

// Check if file was uploaded
if (!isset($_FILES['era_file']) || $_FILES['era_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit();
}

$deposit_date = $_POST['deposit_date'] ?? date('Y-m-d');

try {
    // Read ERA file
    $era_content = file_get_contents($_FILES['era_file']['tmp_name']);
    
    // Create ERA import table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS era_imports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            era_number VARCHAR(100) UNIQUE NOT NULL,
            import_date DATE NOT NULL,
            file_name VARCHAR(255),
            payer_name VARCHAR(255),
            check_number VARCHAR(100),
            check_date DATE,
            total_amount DECIMAL(10,2),
            payment_count INTEGER DEFAULT 0,
            status VARCHAR(20) DEFAULT 'pending',
            imported_by INTEGER NOT NULL,
            processed_by INTEGER,
            processed_date DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (imported_by) REFERENCES users(id),
            FOREIGN KEY (processed_by) REFERENCES users(id)
        )
    ");
    
    // Parse ERA file (simplified example - actual implementation would need full X12 835 parser)
    // This is a mock parser for demonstration
    $era_data = parseERA($era_content);
    
    // Generate ERA number
    $era_number = 'ERA-' . date('YmdHis');
    
    // Insert ERA import record
    $import_stmt = $db->prepare("
        INSERT INTO era_imports (
            era_number, import_date, file_name, payer_name, 
            check_number, check_date, total_amount, payment_count, 
            imported_by, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $import_stmt->execute([
        $era_number,
        $deposit_date,
        $_FILES['era_file']['name'],
        $era_data['payer_name'] ?? 'Unknown Payer',
        $era_data['check_number'] ?? null,
        $era_data['check_date'] ?? $deposit_date,
        $era_data['total_amount'] ?? 0,
        count($era_data['payments'] ?? []),
        $_SESSION['user_id'],
        date('Y-m-d H:i:s')
    ]);
    
    $era_import_id = $db->lastInsertId();
    
    // Create ERA payment details table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS era_payment_details (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            era_import_id INTEGER NOT NULL,
            patient_name VARCHAR(255),
            patient_id VARCHAR(100),
            claim_number VARCHAR(100),
            service_date DATE,
            billed_amount DECIMAL(10,2),
            allowed_amount DECIMAL(10,2),
            paid_amount DECIMAL(10,2),
            adjustment_amount DECIMAL(10,2),
            adjustment_reason VARCHAR(255),
            status VARCHAR(20) DEFAULT 'pending',
            matched_claim_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (era_import_id) REFERENCES era_imports(id),
            FOREIGN KEY (matched_claim_id) REFERENCES claims(id)
        )
    ");
    
    // Insert payment details
    $payment_count = 0;
    if (isset($era_data['payments']) && is_array($era_data['payments'])) {
        $detail_stmt = $db->prepare("
            INSERT INTO era_payment_details (
                era_import_id, patient_name, patient_id, claim_number,
                service_date, billed_amount, allowed_amount, paid_amount,
                adjustment_amount, adjustment_reason
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($era_data['payments'] as $payment) {
            $detail_stmt->execute([
                $era_import_id,
                $payment['patient_name'] ?? 'Unknown',
                $payment['patient_id'] ?? null,
                $payment['claim_number'] ?? null,
                $payment['service_date'] ?? null,
                $payment['billed_amount'] ?? 0,
                $payment['allowed_amount'] ?? 0,
                $payment['paid_amount'] ?? 0,
                $payment['adjustment_amount'] ?? 0,
                $payment['adjustment_reason'] ?? null
            ]);
            $payment_count++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'ERA file imported successfully',
        'era_import_id' => $era_import_id,
        'payment_count' => $payment_count,
        'redirect' => 'era_review.php?id=' . $era_import_id
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error importing ERA file: ' . $e->getMessage()
    ]);
}

/**
 * Mock ERA parser - in production, this would be a full X12 835 parser
 */
function parseERA($content) {
    // This is a simplified mock parser
    // A real implementation would parse the X12 835 format
    
    $data = [
        'payer_name' => 'Medicare',
        'check_number' => 'CHK' . rand(100000, 999999),
        'check_date' => date('Y-m-d'),
        'total_amount' => 0,
        'payments' => []
    ];
    
    // Mock some payment data
    $mock_payments = [
        [
            'patient_name' => 'John Doe',
            'patient_id' => '123456789',
            'claim_number' => 'CLM-2024-001',
            'service_date' => date('Y-m-d', strtotime('-7 days')),
            'billed_amount' => 150.00,
            'allowed_amount' => 120.00,
            'paid_amount' => 120.00,
            'adjustment_amount' => 30.00,
            'adjustment_reason' => 'Contractual adjustment'
        ],
        [
            'patient_name' => 'Jane Smith',
            'patient_id' => '987654321',
            'claim_number' => 'CLM-2024-002',
            'service_date' => date('Y-m-d', strtotime('-14 days')),
            'billed_amount' => 200.00,
            'allowed_amount' => 180.00,
            'paid_amount' => 180.00,
            'adjustment_amount' => 20.00,
            'adjustment_reason' => 'Contractual adjustment'
        ]
    ];
    
    $data['payments'] = $mock_payments;
    $data['total_amount'] = array_sum(array_column($mock_payments, 'paid_amount'));
    
    return $data;
}