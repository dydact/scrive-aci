<?php
session_start();
require_once '../auth_helper.php';
require_once '../billing_integration.php';

checkAuthorization(['admin', 'billing_specialist']);

header('Content-Type: application/json');

$conn = getConnection();

// Get parameters
$claim_ids = $_POST['claim_ids'] ?? [];
$batch = $_POST['batch'] ?? false;
$only_validated = $_POST['only_validated'] ?? true;

$response = [
    'success' => false,
    'message' => '',
    'submitted' => 0,
    'failed' => 0,
    'results' => []
];

try {
    // Begin transaction
    $conn->begin_transaction();
    
    if ($batch) {
        // Get all pending claims for batch submission
        $query = "SELECT id FROM billing_claims WHERE status = 'pending'";
        
        if ($only_validated) {
            $query .= " AND validated = 1";
        }
        
        $result = $conn->query($query);
        $claim_ids = [];
        while ($row = $result->fetch_assoc()) {
            $claim_ids[] = $row['id'];
        }
    }
    
    if (empty($claim_ids)) {
        $response['message'] = "No claims to submit";
        echo json_encode($response);
        exit;
    }
    
    // Process each claim
    foreach ($claim_ids as $claim_id) {
        $submission_result = submitClaimToClearinghouse($conn, $claim_id);
        
        if ($submission_result['success']) {
            $response['submitted']++;
            
            // Update claim status
            $update_query = "UPDATE billing_claims 
                SET status = 'submitted', 
                    submission_date = NOW(),
                    clearinghouse_id = ?,
                    submission_response = ?
                WHERE id = ?";
            
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssi", 
                $submission_result['clearinghouse_id'],
                $submission_result['response'],
                $claim_id
            );
            $stmt->execute();
            
            // Log submission
            logClaimActivity($conn, $claim_id, 'submitted', $submission_result['message']);
            
        } else {
            $response['failed']++;
            
            // Log failed submission
            logClaimActivity($conn, $claim_id, 'submission_failed', $submission_result['message']);
        }
        
        $response['results'][] = [
            'claim_id' => $claim_id,
            'success' => $submission_result['success'],
            'message' => $submission_result['message']
        ];
    }
    
    // Commit transaction
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = "<div class='alert alert-info'>";
    $response['message'] .= "<strong>Submission Complete</strong><br>";
    $response['message'] .= "Successfully submitted: {$response['submitted']} claims<br>";
    if ($response['failed'] > 0) {
        $response['message'] .= "Failed: {$response['failed']} claims<br>";
    }
    $response['message'] .= "</div>";
    
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}

echo json_encode($response);

function submitClaimToClearinghouse($conn, $claim_id) {
    // Get claim details
    $query = "SELECT bc.*, c.name as client_name, c.medicaid_id, c.date_of_birth,
              c.address, c.city, c.state, c.zip, c.phone,
              s.name as staff_name, s.npi, s.taxonomy_code,
              o.name as org_name, o.npi as org_npi, o.tax_id,
              o.address as org_address, o.city as org_city, 
              o.state as org_state, o.zip as org_zip
              FROM billing_claims bc
              INNER JOIN clients c ON bc.client_id = c.id
              INNER JOIN staff s ON bc.staff_id = s.id
              CROSS JOIN organization_settings o
              WHERE bc.id = ? AND o.id = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $claim_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$claim = $result->fetch_assoc()) {
        return [
            'success' => false,
            'message' => 'Claim not found'
        ];
    }
    
    // Build 837P claim format for Maryland Medicaid
    $claim_data = build837PClaim($claim);
    
    // Submit to clearinghouse (mock implementation)
    // In production, this would connect to actual clearinghouse API
    $submission_result = mockClearinghouseSubmission($claim_data);
    
    return $submission_result;
}

function build837PClaim($claim) {
    // Build 837P Professional claim format
    // This is a simplified version - actual implementation would be much more detailed
    
    $edi_segments = [];
    
    // ISA - Interchange Control Header
    $isa = [
        'ISA', '00', '          ', '00', '          ',
        'ZZ', str_pad($claim['org_tax_id'], 15),
        'ZZ', 'MDMEDICAID     ',
        date('ymd'), date('Hi'), '^', '00501',
        str_pad(time(), 9, '0', STR_PAD_LEFT), '0', 'P', ':'
    ];
    $edi_segments[] = implode('*', $isa) . '~';
    
    // GS - Functional Group Header
    $gs = [
        'GS', 'HC', $claim['org_tax_id'], 'MDMEDICAID',
        date('Ymd'), date('Hi'), time(), 'X', '005010X222A1'
    ];
    $edi_segments[] = implode('*', $gs) . '~';
    
    // ST - Transaction Set Header
    $edi_segments[] = 'ST*837*0001*005010X222A1~';
    
    // BHT - Beginning of Hierarchical Transaction
    $edi_segments[] = 'BHT*0019*00*' . $claim['claim_number'] . '*' . date('Ymd') . '*' . date('Hi') . '*CH~';
    
    // 1000A - Submitter
    $edi_segments[] = 'NM1*41*2*' . strtoupper($claim['org_name']) . '*****46*' . $claim['org_tax_id'] . '~';
    
    // 1000B - Receiver
    $edi_segments[] = 'NM1*40*2*MARYLAND MEDICAID*****46*MDMEDICAID~';
    
    // 2010AA - Billing Provider
    $edi_segments[] = 'NM1*85*2*' . strtoupper($claim['org_name']) . '*****XX*' . $claim['org_npi'] . '~';
    $edi_segments[] = 'N3*' . strtoupper($claim['org_address']) . '~';
    $edi_segments[] = 'N4*' . strtoupper($claim['org_city']) . '*' . $claim['org_state'] . '*' . $claim['org_zip'] . '~';
    
    // 2010BA - Subscriber
    $edi_segments[] = 'NM1*IL*1*' . strtoupper($claim['client_name']) . '****MI*' . $claim['medicaid_id'] . '~';
    $edi_segments[] = 'N3*' . strtoupper($claim['address']) . '~';
    $edi_segments[] = 'N4*' . strtoupper($claim['city']) . '*' . $claim['state'] . '*' . $claim['zip'] . '~';
    $edi_segments[] = 'DMG*D8*' . date('Ymd', strtotime($claim['date_of_birth'])) . '~';
    
    // 2300 - Claim Information
    $edi_segments[] = 'CLM*' . $claim['claim_number'] . '*' . $claim['total_amount'] . '***11:B:1*Y*A*Y*Y~';
    
    if ($claim['authorization_number']) {
        $edi_segments[] = 'REF*G1*' . $claim['authorization_number'] . '~';
    }
    
    // 2310B - Rendering Provider
    $edi_segments[] = 'NM1*82*1*' . strtoupper($claim['staff_name']) . '****XX*' . $claim['npi'] . '~';
    
    // 2400 - Service Line
    $edi_segments[] = 'LX*1~';
    $edi_segments[] = 'SV1*HC:' . $claim['service_code'] . '*' . $claim['total_amount'] . 
                      '*UN*' . $claim['units'] . '***1~';
    $edi_segments[] = 'DTP*472*D8*' . date('Ymd', strtotime($claim['service_date'])) . '~';
    
    // SE - Transaction Set Trailer
    $segment_count = count($edi_segments) + 1;
    $edi_segments[] = 'SE*' . $segment_count . '*0001~';
    
    // GE - Functional Group Trailer
    $edi_segments[] = 'GE*1*' . time() . '~';
    
    // IEA - Interchange Control Trailer
    $edi_segments[] = 'IEA*1*' . str_pad(time(), 9, '0', STR_PAD_LEFT) . '~';
    
    return implode("\n", $edi_segments);
}

function mockClearinghouseSubmission($claim_data) {
    // Mock clearinghouse submission
    // In production, this would make actual API calls
    
    // Simulate processing time
    usleep(500000); // 0.5 seconds
    
    // Simulate random success/failure for testing
    $success_rate = 0.95; // 95% success rate
    $is_success = (mt_rand() / mt_getrandmax()) < $success_rate;
    
    if ($is_success) {
        return [
            'success' => true,
            'clearinghouse_id' => 'CH' . str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT),
            'response' => 'Claim accepted for processing',
            'message' => 'Successfully submitted to clearinghouse'
        ];
    } else {
        $error_reasons = [
            'Invalid provider NPI',
            'Duplicate claim submission',
            'Invalid service code for date of service',
            'Member not eligible on date of service'
        ];
        
        $reason = $error_reasons[array_rand($error_reasons)];
        
        return [
            'success' => false,
            'clearinghouse_id' => null,
            'response' => $reason,
            'message' => 'Clearinghouse rejection: ' . $reason
        ];
    }
}

function logClaimActivity($conn, $claim_id, $activity_type, $description) {
    $query = "INSERT INTO claim_activity_log (claim_id, activity_type, description, created_by, created_at)
              VALUES (?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issi", $claim_id, $activity_type, $description, $_SESSION['user_id']);
    $stmt->execute();
}
?>