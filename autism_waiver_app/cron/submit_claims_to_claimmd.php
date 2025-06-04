<?php
/**
 * Automated claim submission to Claim.MD
 * This script should be run daily via cron to submit pending claims
 * 
 * Cron example: 0 2 * * * php /path/to/submit_claims_to_claimmd.php
 */

require_once dirname(__DIR__) . '/../src/config.php';
require_once dirname(__DIR__) . '/../config/claimmd.php';
require_once dirname(__DIR__) . '/integrations/claim_md_api.php';

// Set up logging
$logFile = CLAIMMD_LOG_FILE;
$startTime = microtime(true);

function logMessage($message, $type = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

try {
    logMessage("Starting automated claim submission process");
    
    // Initialize database connection
    $pdo = getDatabase();
    
    // Initialize Claim.MD API
    $claimMD = new ClaimMDAPI();
    
    // Get pending claims that haven't been submitted
    $sql = "
        SELECT 
            c.id,
            c.claim_number,
            c.client_id,
            c.service_date,
            c.total_amount,
            c.status,
            cl.first_name as patient_first_name,
            cl.last_name as patient_last_name,
            cl.date_of_birth as patient_dob,
            cl.ma_number,
            cl.gender as patient_gender,
            s.provider_id,
            sm.full_name as provider_name,
            sm.npi as provider_npi,
            GROUP_CONCAT(
                CONCAT(
                    cd.service_type_id, '|',
                    cd.procedure_code, '|',
                    cd.units, '|',
                    cd.amount, '|',
                    cd.service_date
                ) SEPARATOR ';'
            ) as service_lines
        FROM autism_billing_claims c
        JOIN autism_clients cl ON c.client_id = cl.id
        JOIN autism_sessions s ON c.session_id = s.id
        LEFT JOIN autism_staff_members sm ON s.provider_id = sm.id
        JOIN autism_billing_claim_details cd ON c.id = cd.claim_id
        WHERE c.status = 'pending'
        AND c.claimmd_id IS NULL
        AND c.payer_id IN ('MDMCD', 'SKMD0', 'MCDMD')
        GROUP BY c.id
        LIMIT " . CLAIMMD_BATCH_SIZE;
    
    $stmt = $pdo->query($sql);
    $pendingClaims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pendingClaims)) {
        logMessage("No pending claims to submit");
        exit(0);
    }
    
    logMessage("Found " . count($pendingClaims) . " pending claims to submit");
    
    // Convert claims to Claim.MD format
    $claimsData = [];
    foreach ($pendingClaims as $claim) {
        // Parse service lines
        $services = [];
        $serviceLines = explode(';', $claim['service_lines']);
        foreach ($serviceLines as $line) {
            list($serviceTypeId, $procCode, $units, $amount, $serviceDate) = explode('|', $line);
            
            // Get service type details
            $stmtService = $pdo->prepare("SELECT service_code FROM autism_service_types WHERE id = ?");
            $stmtService->execute([$serviceTypeId]);
            $serviceType = $stmtService->fetch(PDO::FETCH_ASSOC);
            
            $services[] = [
                'date' => $serviceDate,
                'place_of_service' => '12', // Home
                'procedure_code' => $serviceType['service_code'] ?? $procCode,
                'units' => $units,
                'charge' => $amount,
                'provider_npi' => $claim['provider_npi']
            ];
        }
        
        // Get primary diagnosis
        $diagStmt = $pdo->prepare("
            SELECT diagnosis_code 
            FROM autism_client_diagnoses 
            WHERE client_id = ? 
            AND is_primary = 1 
            LIMIT 1
        ");
        $diagStmt->execute([$claim['client_id']]);
        $diagnosis = $diagStmt->fetch(PDO::FETCH_ASSOC);
        
        $claimsData[] = [
            'id' => $claim['id'],
            'patient_account' => $claim['claim_number'],
            'service_date' => $claim['service_date'],
            'total_amount' => $claim['total_amount'],
            'patient_first_name' => $claim['patient_first_name'],
            'patient_last_name' => $claim['patient_last_name'],
            'patient_dob' => date('Ymd', strtotime($claim['patient_dob'])),
            'patient_gender' => strtoupper(substr($claim['patient_gender'], 0, 1)),
            'ma_number' => $claim['ma_number'],
            'provider_npi' => $claim['provider_npi'],
            'provider_last_name' => explode(' ', $claim['provider_name'])[1] ?? $claim['provider_name'],
            'provider_first_name' => explode(' ', $claim['provider_name'])[0] ?? '',
            'diagnosis_1' => $diagnosis['diagnosis_code'] ?? 'F84.0', // Default autism diagnosis
            'services' => $services
        ];
    }
    
    // Submit claims to Claim.MD
    logMessage("Submitting " . count($claimsData) . " claims to Claim.MD");
    
    try {
        $response = $claimMD->submitClaims($claimsData, 'JSON');
        
        if (!empty($response['result'])) {
            // Process response and update claim records
            foreach ($response['result']['claim'] ?? [] as $claimResponse) {
                $claimId = $claimResponse['remote_claimid'];
                $claimMdId = $claimResponse['claimmd_id'];
                $status = $claimResponse['status'] === 'A' ? 'submitted' : 'rejected';
                
                // Update claim with Claim.MD ID
                $updateStmt = $pdo->prepare("
                    UPDATE autism_billing_claims 
                    SET claimmd_id = ?, 
                        remote_claim_id = ?,
                        status = ?,
                        submission_date = NOW(),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$claimMdId, $claimId, $status, $claimId]);
                
                // Log submission
                $auditStmt = $pdo->prepare("
                    INSERT INTO autism_audit_log (user_id, action, entity_type, entity_id, details)
                    VALUES (0, 'claim_submitted', 'billing_claim', ?, ?)
                ");
                $auditStmt->execute([$claimId, json_encode($claimResponse)]);
                
                logMessage("Claim $claimId submitted successfully. Claim.MD ID: $claimMdId");
            }
            
            logMessage("Successfully submitted " . count($response['result']['claim'] ?? []) . " claims");
        }
        
    } catch (Exception $e) {
        logMessage("Error submitting claims: " . $e->getMessage(), 'ERROR');
        
        // Send alert email to billing team
        if (MAIL_ENABLED) {
            $subject = "Claim Submission Error - " . date('Y-m-d');
            $message = "An error occurred during automated claim submission:\n\n" . $e->getMessage();
            mail('pam.pastor@acgcares.com,yanika.crosse@acgcares.com', $subject, $message);
        }
    }
    
    // Check claim status updates
    logMessage("Checking for claim status updates");
    
    // Get last response ID
    $lastResponseStmt = $pdo->query("
        SELECT MAX(last_response_id) as last_id 
        FROM autism_billing_claims 
        WHERE last_response_id IS NOT NULL
    ");
    $lastResponse = $lastResponseStmt->fetch(PDO::FETCH_ASSOC);
    $lastResponseId = $lastResponse['last_id'] ?? '0';
    
    // Get status updates
    $statusResponse = $claimMD->getClaimStatus($lastResponseId);
    
    if (!empty($statusResponse['result']['claim'])) {
        foreach ($statusResponse['result']['claim'] as $claimUpdate) {
            $claimMdId = $claimUpdate['claimmd_id'];
            $newStatus = mapClaimMDStatus($claimUpdate['status']);
            
            // Update claim status
            $updateStmt = $pdo->prepare("
                UPDATE autism_billing_claims 
                SET status = ?,
                    last_response_id = ?,
                    updated_at = NOW()
                WHERE claimmd_id = ?
            ");
            $updateStmt->execute([$newStatus, $claimUpdate['messages']['responseid'] ?? null, $claimMdId]);
            
            logMessage("Updated status for claim $claimMdId to $newStatus");
        }
    }
    
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    logMessage("Claim submission process completed in $executionTime seconds");
    
} catch (Exception $e) {
    logMessage("Fatal error: " . $e->getMessage(), 'ERROR');
    exit(1);
}

/**
 * Map Claim.MD status codes to internal status
 */
function mapClaimMDStatus($claimMDStatus) {
    $statusMap = [
        'A' => 'accepted',
        'R' => 'rejected',
        'P' => 'paid',
        'D' => 'denied',
        'N' => 'pending'
    ];
    
    return $statusMap[$claimMDStatus] ?? 'submitted';
}
?>