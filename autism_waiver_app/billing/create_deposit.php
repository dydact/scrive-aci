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
if (!isset($_POST['deposit_number']) || !isset($_POST['deposit_date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$deposit_number = trim($_POST['deposit_number']);
$deposit_date = $_POST['deposit_date'];
$bank_account = $_POST['bank_account'] ?? null;

if (empty($deposit_number) || empty($deposit_date)) {
    echo json_encode(['success' => false, 'message' => 'Deposit number and date are required']);
    exit();
}

try {
    // Check if deposit number already exists
    $check_stmt = $db->prepare("SELECT id FROM batch_deposits WHERE deposit_number = ?");
    $check_stmt->execute([$deposit_number]);
    if ($check_stmt->fetch()) {
        throw new Exception('Deposit number already exists');
    }
    
    // Create new deposit
    $insert_stmt = $db->prepare("
        INSERT INTO batch_deposits (deposit_number, deposit_date, bank_account, created_by, created_at)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $insert_stmt->execute([
        $deposit_number,
        $deposit_date,
        $bank_account,
        $_SESSION['user_id'],
        date('Y-m-d H:i:s')
    ]);
    
    $deposit_id = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'deposit_id' => $deposit_id,
        'deposit_number' => $deposit_number,
        'deposit_date' => date('m/d/Y', strtotime($deposit_date))
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error creating deposit: ' . $e->getMessage()
    ]);
}