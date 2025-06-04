<?php
session_start();
require_once '../auth_helper.php';
require_once '../billing_integration.php';

checkAuthorization(['admin', 'billing_specialist']);

header('Content-Type: application/json');

$conn = getConnection();

// Get claim ID or batch validation
$claim_id = $_POST['claim_id'] ?? null;
$batch_validate = $_POST['batch'] ?? false;

$response = [
    'success' => false,
    'valid' => false,
    'errors' => [],
    'warnings' => [],
    'results' => []
];

try {
    if ($batch_validate) {
        // Validate all pending claims
        $query = "SELECT bc.*, c.name as client_name, c.medicaid_id, c.date_of_birth,
                  c.address, c.city, c.state, c.zip,
                  s.name as staff_name, s.npi, s.employee_id
                  FROM billing_claims bc
                  INNER JOIN clients c ON bc.client_id = c.id
                  INNER JOIN staff s ON bc.staff_id = s.id
                  WHERE bc.status = 'pending'";
        
        $result = $conn->query($query);
        $total_claims = 0;
        $valid_claims = 0;
        
        while ($claim = $result->fetch_assoc()) {
            $total_claims++;
            $validation = validateClaim($claim);
            
            if ($validation['valid']) {
                $valid_claims++;
                // Update claim as validated
                $update_query = "UPDATE billing_claims SET validated = 1, validated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("i", $claim['id']);
                $stmt->execute();
            }
            
            $response['results'][] = [
                'claim_id' => $claim['id'],
                'claim_number' => $claim['claim_number'],
                'valid' => $validation['valid'],
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings']
            ];
        }
        
        $response['success'] = true;
        $response['message'] = "Validated $total_claims claims. $valid_claims passed validation.";
        
    } else {
        // Validate single claim
        $query = "SELECT bc.*, c.name as client_name, c.medicaid_id, c.date_of_birth,
                  c.address, c.city, c.state, c.zip,
                  s.name as staff_name, s.npi, s.employee_id
                  FROM billing_claims bc
                  INNER JOIN clients c ON bc.client_id = c.id
                  INNER JOIN staff s ON bc.staff_id = s.id
                  WHERE bc.id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $claim_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($claim = $result->fetch_assoc()) {
            $validation = validateClaim($claim);
            
            $response = array_merge($response, $validation);
            $response['success'] = true;
            
            if ($validation['valid']) {
                // Update claim as validated
                $update_query = "UPDATE billing_claims SET validated = 1, validated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("i", $claim_id);
                $stmt->execute();
            }
        } else {
            $response['errors'][] = "Claim not found";
        }
    }
    
} catch (Exception $e) {
    $response['errors'][] = "Validation error: " . $e->getMessage();
}

echo json_encode($response);

function validateClaim($claim) {
    $errors = [];
    $warnings = [];
    $valid = true;
    
    // Maryland Medicaid specific validations
    
    // 1. Required fields
    $required_fields = [
        'medicaid_id' => 'Medicaid ID',
        'date_of_birth' => 'Date of Birth',
        'service_code' => 'Service Code',
        'units' => 'Units',
        'npi' => 'Provider NPI',
        'service_date' => 'Service Date'
    ];
    
    foreach ($required_fields as $field => $label) {
        if (empty($claim[$field])) {
            $errors[] = "$label is required";
            $valid = false;
        }
    }
    
    // 2. Medicaid ID format validation (Maryland specific)
    if (!empty($claim['medicaid_id'])) {
        if (!preg_match('/^[A-Z0-9]{9}$/', $claim['medicaid_id'])) {
            $errors[] = "Invalid Medicaid ID format. Must be 9 alphanumeric characters.";
            $valid = false;
        }
    }
    
    // 3. NPI validation
    if (!empty($claim['npi'])) {
        if (!preg_match('/^\d{10}$/', $claim['npi'])) {
            $errors[] = "Invalid NPI format. Must be 10 digits.";
            $valid = false;
        }
    }
    
    // 4. Service code validation for Maryland autism waiver
    $valid_service_codes = ['W1727', 'W1728', 'W7061', 'W7060', 'W7069', 'W7235'];
    if (!in_array($claim['service_code'], $valid_service_codes)) {
        $errors[] = "Invalid service code for Maryland Autism Waiver";
        $valid = false;
    }
    
    // 5. Date validations
    $service_date = new DateTime($claim['service_date']);
    $today = new DateTime();
    
    // Future date check
    if ($service_date > $today) {
        $errors[] = "Service date cannot be in the future";
        $valid = false;
    }
    
    // Timely filing check (95 days for Maryland Medicaid)
    $days_old = $today->diff($service_date)->days;
    if ($days_old > 95) {
        $errors[] = "Service date exceeds timely filing limit (95 days)";
        $valid = false;
    } elseif ($days_old > 80) {
        $warnings[] = "Service date is approaching timely filing limit ($days_old days old)";
    }
    
    // 6. Authorization validation if required
    if (in_array($claim['service_code'], ['W1727', 'W1728'])) {
        if (empty($claim['authorization_number'])) {
            $errors[] = "Authorization required for service code {$claim['service_code']}";
            $valid = false;
        }
    }
    
    // 7. Units validation
    if ($claim['units'] <= 0) {
        $errors[] = "Units must be greater than 0";
        $valid = false;
    }
    
    // 8. Amount validation
    if ($claim['total_amount'] <= 0) {
        $errors[] = "Total amount must be greater than 0";
        $valid = false;
    }
    
    // Calculate expected amount
    $expected_amount = $claim['units'] * $claim['rate'];
    if (abs($claim['total_amount'] - $expected_amount) > 0.01) {
        $warnings[] = "Total amount doesn't match units * rate";
    }
    
    // 9. Client age validation for autism waiver
    $dob = new DateTime($claim['date_of_birth']);
    $age = $today->diff($dob)->y;
    if ($age >= 21) {
        $warnings[] = "Client is 21 or older - verify autism waiver eligibility";
    }
    
    // 10. Duplicate claim check
    // This would need to check for same client, same service, same date
    
    return [
        'valid' => $valid,
        'errors' => $errors,
        'warnings' => $warnings
    ];
}
?>