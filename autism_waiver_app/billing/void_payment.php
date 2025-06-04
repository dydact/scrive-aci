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

// Only admins can void payments
if ($_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only administrators can void payments']);
    exit();
}

// Validate required fields
if (!isset($_POST['payment_id']) || !isset($_POST['void_reason'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$payment_id = intval($_POST['payment_id']);
$void_reason = trim($_POST['void_reason']);

if (empty($void_reason)) {
    echo json_encode(['success' => false, 'message' => 'Void reason is required']);
    exit();
}

try {
    $db->beginTransaction();
    
    // Get payment details
    $payment_stmt = $db->prepare("
        SELECT * FROM payment_postings 
        WHERE id = ? AND status = 'posted'
    ");
    $payment_stmt->execute([$payment_id]);
    $payment = $payment_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception('Payment not found or already voided');
    }
    
    // Update payment status to voided
    $void_stmt = $db->prepare("
        UPDATE payment_postings 
        SET status = 'voided',
            voided_by = ?,
            void_date = ?,
            void_reason = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $void_stmt->execute([
        $_SESSION['user_id'],
        date('Y-m-d H:i:s'),
        $void_reason,
        $payment_id
    ]);
    
    // If payment was applied to a claim, update claim status
    if ($payment['claim_id']) {
        // Recalculate claim balance
        $claim_stmt = $db->prepare("
            SELECT c.id, c.billed_amount, 
                   COALESCE(SUM(pp.payment_amount), 0) as total_paid
            FROM claims c
            LEFT JOIN payment_postings pp ON c.id = pp.claim_id 
                AND pp.status = 'posted' 
                AND pp.id != ?
            WHERE c.id = ?
            GROUP BY c.id, c.billed_amount
        ");
        $claim_stmt->execute([$payment_id, $payment['claim_id']]);
        $claim_info = $claim_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($claim_info) {
            $new_balance = $claim_info['billed_amount'] - $claim_info['total_paid'];
            
            // Determine new claim status
            $new_status = 'submitted';
            if ($claim_info['total_paid'] > 0 && $new_balance > 0) {
                $new_status = 'partially_paid';
            } elseif ($new_balance <= 0) {
                $new_status = 'paid';
            }
            
            $update_claim = $db->prepare("
                UPDATE claims 
                SET status = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $update_claim->execute([$new_status, $payment['claim_id']]);
        }
    }
    
    // Update batch deposit if applicable
    if ($payment['batch_deposit_id']) {
        $update_deposit = $db->prepare("
            UPDATE batch_deposits 
            SET total_amount = total_amount - ?,
                payment_count = payment_count - 1,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $update_deposit->execute([$payment['payment_amount'], $payment['batch_deposit_id']]);
    }
    
    // Create reversal entry (optional - for audit trail)
    $reversal_stmt = $db->prepare("
        INSERT INTO payment_postings (
            payment_date, payment_amount, payment_type, claim_id, patient_id,
            check_number, reference_number, payer_name, notes,
            posted_by, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $reversal_notes = sprintf(
        "VOID REVERSAL: Original payment ID %d voided on %s by %s. Reason: %s",
        $payment_id,
        date('m/d/Y'),
        $_SESSION['username'],
        $void_reason
    );
    
    $reversal_stmt->execute([
        date('Y-m-d'),
        -$payment['payment_amount'], // Negative amount for reversal
        'reversal',
        $payment['claim_id'],
        $payment['patient_id'],
        $payment['check_number'],
        $payment['reference_number'],
        $payment['payer_name'],
        $reversal_notes,
        $_SESSION['user_id'],
        'posted',
        date('Y-m-d H:i:s')
    ]);
    
    // Log the void action
    if ($db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='audit_log'")->fetchColumn()) {
        $audit_stmt = $db->prepare("
            INSERT INTO audit_log (user_id, action, table_name, record_id, details, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $details = sprintf(
            "Voided payment of $%.2f (Check/Ref: %s). Reason: %s",
            $payment['payment_amount'],
            $payment['check_number'] ?: $payment['reference_number'] ?: 'N/A',
            $void_reason
        );
        
        $audit_stmt->execute([
            $_SESSION['user_id'],
            'payment_voided',
            'payment_postings',
            $payment_id,
            $details,
            date('Y-m-d H:i:s')
        ]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment voided successfully'
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error voiding payment: ' . $e->getMessage()
    ]);
}