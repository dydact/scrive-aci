<?php
session_start();
require_once '../auth_helper.php';
require_once '../billing_integration.php';

checkAuthorization(['admin', 'billing_specialist']);

header('Content-Type: application/json');

$conn = getConnection();

// Get parameters
$start_date = $_POST['start_date'] ?? date('Y-m-01');
$end_date = $_POST['end_date'] ?? date('Y-m-d');
$client_id = $_POST['client_id'] ?? null;
$validate_auth = $_POST['validate_auth'] ?? true;
$check_timely = $_POST['check_timely'] ?? true;

$response = [
    'success' => false,
    'message' => '',
    'claims_generated' => 0,
    'errors' => [],
    'warnings' => []
];

try {
    // Begin transaction
    $conn->begin_transaction();
    
    // Get unbilled sessions
    $query = "SELECT cs.*, c.name as client_name, c.medicaid_id, c.date_of_birth,
              s.name as staff_name, s.npi as staff_npi, s.employee_id,
              st.service_code, st.rate, st.authorization_required,
              ca.authorization_number, ca.start_date as auth_start, ca.end_date as auth_end,
              ca.authorized_units, ca.used_units
              FROM client_sessions cs
              INNER JOIN clients c ON cs.client_id = c.id
              INNER JOIN staff s ON cs.staff_id = s.id
              INNER JOIN service_types st ON cs.service_type_id = st.id
              LEFT JOIN client_authorizations ca ON c.id = ca.client_id 
                  AND ca.service_type_id = st.id
                  AND cs.service_date BETWEEN ca.start_date AND ca.end_date
              WHERE cs.billing_status = 'unbilled'
              AND cs.status = 'completed'
              AND cs.service_date BETWEEN ? AND ?";
    
    $params = [$start_date, $end_date];
    $types = "ss";
    
    if ($client_id) {
        $query .= " AND cs.client_id = ?";
        $params[] = $client_id;
        $types .= "i";
    }
    
    $query .= " ORDER BY cs.client_id, cs.service_date";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sessions_by_client = [];
    $today = new DateTime();
    
    while ($session = $result->fetch_assoc()) {
        $client_id = $session['client_id'];
        
        // Maryland Medicaid timely filing check (95 days)
        if ($check_timely) {
            $service_date = new DateTime($session['service_date']);
            $days_old = $today->diff($service_date)->days;
            
            if ($days_old > 95) {
                $response['warnings'][] = "Session {$session['id']} for {$session['client_name']} on {$session['service_date']} exceeds timely filing limit ({$days_old} days old)";
                continue;
            }
        }
        
        // Authorization validation
        if ($validate_auth && $session['authorization_required']) {
            if (!$session['authorization_number']) {
                $response['errors'][] = "No authorization found for {$session['client_name']} - {$session['service_code']} on {$session['service_date']}";
                continue;
            }
            
            // Check if authorization has available units
            if ($session['used_units'] >= $session['authorized_units']) {
                $response['errors'][] = "Authorization {$session['authorization_number']} has no available units for {$session['client_name']}";
                continue;
            }
        }
        
        if (!isset($sessions_by_client[$client_id])) {
            $sessions_by_client[$client_id] = [
                'client_info' => [
                    'id' => $client_id,
                    'name' => $session['client_name'],
                    'medicaid_id' => $session['medicaid_id'],
                    'date_of_birth' => $session['date_of_birth']
                ],
                'sessions' => []
            ];
        }
        
        $sessions_by_client[$client_id]['sessions'][] = $session;
    }
    
    // Generate claims for each client
    foreach ($sessions_by_client as $client_id => $client_data) {
        $client_info = $client_data['client_info'];
        $sessions = $client_data['sessions'];
        
        // Group sessions by month and service type for Maryland Medicaid
        $grouped_sessions = [];
        foreach ($sessions as $session) {
            $month_key = date('Y-m', strtotime($session['service_date']));
            $service_key = $session['service_code'];
            $group_key = "{$month_key}_{$service_key}";
            
            if (!isset($grouped_sessions[$group_key])) {
                $grouped_sessions[$group_key] = [
                    'sessions' => [],
                    'total_units' => 0,
                    'total_amount' => 0,
                    'service_code' => $service_key,
                    'staff_npi' => $session['staff_npi'],
                    'authorization_number' => $session['authorization_number']
                ];
            }
            
            $grouped_sessions[$group_key]['sessions'][] = $session;
            $grouped_sessions[$group_key]['total_units'] += $session['units'];
            $grouped_sessions[$group_key]['total_amount'] += ($session['units'] * $session['rate']);
        }
        
        // Create claims for each group
        foreach ($grouped_sessions as $group) {
            // Generate claim number
            $claim_number = generateClaimNumber($conn);
            
            // Get first and last service dates for the claim
            $service_dates = array_column($group['sessions'], 'service_date');
            sort($service_dates);
            $first_date = reset($service_dates);
            $last_date = end($service_dates);
            
            // Insert claim
            $claim_query = "INSERT INTO billing_claims (
                claim_number, client_id, staff_id, service_date, 
                service_start_date, service_end_date,
                service_code, units, rate, total_amount,
                authorization_number, status, payer_type,
                created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'medicaid', ?, NOW())";
            
            $stmt = $conn->prepare($claim_query);
            $stmt->bind_param("siissssddisi",
                $claim_number,
                $client_info['id'],
                $group['sessions'][0]['staff_id'],
                $first_date,
                $first_date,
                $last_date,
                $group['service_code'],
                $group['total_units'],
                $group['sessions'][0]['rate'],
                $group['total_amount'],
                $group['authorization_number'],
                $_SESSION['user_id']
            );
            
            if ($stmt->execute()) {
                $claim_id = $conn->insert_id;
                
                // Update sessions with claim ID
                $session_ids = array_column($group['sessions'], 'id');
                $placeholders = implode(',', array_fill(0, count($session_ids), '?'));
                
                $update_query = "UPDATE client_sessions 
                    SET billing_status = 'billed', claim_id = ? 
                    WHERE id IN ($placeholders)";
                
                $stmt = $conn->prepare($update_query);
                $params = array_merge([$claim_id], $session_ids);
                $types = str_repeat('i', count($params));
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                
                // Update authorization usage if applicable
                if ($group['authorization_number']) {
                    $auth_query = "UPDATE client_authorizations 
                        SET used_units = used_units + ? 
                        WHERE authorization_number = ?";
                    $stmt = $conn->prepare($auth_query);
                    $stmt->bind_param("ds", $group['total_units'], $group['authorization_number']);
                    $stmt->execute();
                }
                
                $response['claims_generated']++;
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    $response['success'] = true;
    $response['message'] = "<div class='alert alert-success'>";
    $response['message'] .= "<strong>Success!</strong> Generated {$response['claims_generated']} claims.<br>";
    
    if (!empty($response['warnings'])) {
        $response['message'] .= "<br><strong>Warnings:</strong><ul>";
        foreach ($response['warnings'] as $warning) {
            $response['message'] .= "<li>$warning</li>";
        }
        $response['message'] .= "</ul>";
    }
    
    if (!empty($response['errors'])) {
        $response['message'] .= "<br><strong>Errors (sessions skipped):</strong><ul>";
        foreach ($response['errors'] as $error) {
            $response['message'] .= "<li>$error</li>";
        }
        $response['message'] .= "</ul>";
    }
    
    $response['message'] .= "</div>";
    
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}

echo json_encode($response);

function generateClaimNumber($conn) {
    // Generate unique claim number with Maryland Medicaid format
    $prefix = "ACI";
    $date_part = date('ymd');
    
    // Get the last claim number for today
    $query = "SELECT MAX(CAST(SUBSTRING(claim_number, -4) AS UNSIGNED)) as last_num 
              FROM billing_claims 
              WHERE claim_number LIKE ?";
    $pattern = $prefix . $date_part . '%';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $next_num = ($row['last_num'] ?? 0) + 1;
    $claim_number = $prefix . $date_part . str_pad($next_num, 4, '0', STR_PAD_LEFT);
    
    return $claim_number;
}
?>