<?php
session_start();
require_once '../auth_helper.php';
require_once '../billing_integration.php';

checkAuthorization(['admin', 'billing_specialist']);

header('Content-Type: application/json');

$conn = getConnection();

$response = [
    'success' => false,
    'message' => ''
];

try {
    $claim_id = $_POST['claim_id'] ?? 0;
    $service_code = $_POST['service_code'] ?? '';
    $units = $_POST['units'] ?? 0;
    $rate = $_POST['rate'] ?? 0;
    $authorization_number = $_POST['authorization_number'] ?? '';
    
    // Validate claim exists and is editable
    $check_query = "SELECT status FROM billing_claims WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $claim_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$row = $result->fetch_assoc()) {
        throw new Exception("Claim not found");
    }
    
    if ($row['status'] != 'pending') {
        throw new Exception("Only pending claims can be edited");
    }
    
    // Calculate new total
    $total_amount = $units * $rate;
    
    // Update claim
    $update_query = "UPDATE billing_claims 
        SET service_code = ?, 
            units = ?, 
            rate = ?, 
            total_amount = ?,
            authorization_number = ?,
            validated = 0,
            modified_by = ?,
            modified_at = NOW()
        WHERE id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sdddsii", 
        $service_code, 
        $units, 
        $rate, 
        $total_amount,
        $authorization_number,
        $_SESSION['user_id'],
        $claim_id
    );
    
    if ($stmt->execute()) {
        // Log the change
        $log_query = "INSERT INTO claim_activity_log (claim_id, activity_type, description, created_by, created_at)
                      VALUES (?, 'modified', ?, ?, NOW())";
        
        $description = "Claim updated - Service: $service_code, Units: $units, Rate: $$rate";
        $stmt = $conn->prepare($log_query);
        $stmt->bind_param("isi", $claim_id, $description, $_SESSION['user_id']);
        $stmt->execute();
        
        $response['success'] = true;
        $response['message'] = "Claim updated successfully";
    } else {
        throw new Exception("Failed to update claim");
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>