<?php
/**
 * Process Claims for EDI Submission
 * 
 * This script processes pending claims and generates EDI 837 files
 * for submission to Maryland Medicaid
 */

session_start();
require_once __DIR__ . '/../auth_helper.php';
require_once __DIR__ . '/EDI837Generator.php';

use ScriverACI\EDI\EDI837Generator;

// Check authentication
checkAuthentication();

// Check for billing permissions
if (!hasPermission('billing_submit')) {
    die("You don't have permission to submit billing claims.");
}

// Database connection
require_once __DIR__ . '/../../config/database.php';

// Get submitter information from organization settings
function getSubmitterInfo($db) {
    $stmt = $db->prepare("
        SELECT 
            o.name as organization_name,
            o.tax_id,
            o.npi as sender_id,
            o.billing_contact_name as contact_name,
            o.billing_contact_phone as contact_phone,
            o.billing_contact_email as contact_email,
            o.edi_submitter_id as submitter_id
        FROM organization_settings o
        WHERE o.id = 1
    ");
    $stmt->execute();
    $org = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$org) {
        throw new Exception("Organization settings not found");
    }
    
    return $org;
}

// Get pending claims ready for submission
function getPendingClaims($db, $limit = 100) {
    $stmt = $db->prepare("
        SELECT 
            c.id as claim_id,
            c.claim_number,
            c.total_charge_amount as total_amount,
            c.statement_from_date,
            c.statement_to_date,
            c.facility_code,
            c.prior_auth_number,
            
            -- Patient/Subscriber info
            p.id as patient_id,
            p.medicaid_id as member_id,
            p.last_name as patient_last_name,
            p.first_name as patient_first_name,
            p.middle_name as patient_middle_name,
            p.dob,
            p.gender,
            p.address as patient_address,
            p.city as patient_city,
            p.state as patient_state,
            p.zip as patient_zip,
            
            -- Billing Provider
            bp.organization_name as billing_provider_name,
            bp.npi as billing_provider_npi,
            bp.tax_id as billing_provider_tax_id,
            bp.address_line1 as billing_provider_address1,
            bp.address_line2 as billing_provider_address2,
            bp.city as billing_provider_city,
            bp.state as billing_provider_state,
            bp.zip as billing_provider_zip,
            
            -- Rendering Provider
            rp.npi as rendering_provider_npi,
            rp.last_name as rendering_provider_last_name,
            rp.first_name as rendering_provider_first_name,
            rp.middle_name as rendering_provider_middle_name,
            rp.taxonomy_code as rendering_provider_taxonomy,
            
            -- Payer
            py.edi_payer_id as payer_id
            
        FROM billing_claims c
        JOIN patients p ON c.patient_id = p.id
        JOIN providers bp ON c.provider_id = bp.id
        LEFT JOIN providers rp ON c.rendering_provider_id = rp.id
        JOIN payers py ON c.payer_id = py.payer_id
        WHERE c.claim_status = 'ready'
        AND c.edi_status = 'pending'
        LIMIT :limit
    ");
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get diagnoses for a claim
function getClaimDiagnoses($db, $claimId) {
    $stmt = $db->prepare("
        SELECT diagnosis_code as code, diagnosis_pointer
        FROM claim_diagnoses
        WHERE claim_id = :claim_id
        ORDER BY diagnosis_pointer
    ");
    $stmt->execute([':claim_id' => $claimId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get service lines for a claim
function getClaimServiceLines($db, $claimId) {
    $stmt = $db->prepare("
        SELECT 
            line_number,
            service_date,
            procedure_code,
            modifier1,
            modifier2,
            modifier3,
            modifier4,
            place_of_service,
            units,
            charge_amount,
            diagnosis_pointer1 as diagnosis_pointer,
            prior_auth_number
        FROM claim_service_lines
        WHERE claim_id = :claim_id
        ORDER BY line_number
    ");
    $stmt->execute([':claim_id' => $claimId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Format claim data for EDI generator
function formatClaimForEDI($claim, $diagnoses, $serviceLines) {
    return [
        'claim_id' => $claim['claim_id'],
        'claim_number' => $claim['claim_number'],
        'total_amount' => $claim['total_amount'],
        'statement_from_date' => $claim['statement_from_date'],
        'statement_to_date' => $claim['statement_to_date'],
        'facility_code' => $claim['facility_code'],
        
        'billing_provider' => [
            'name' => $claim['billing_provider_name'],
            'npi' => $claim['billing_provider_npi'],
            'tax_id' => $claim['billing_provider_tax_id'],
            'address_line1' => $claim['billing_provider_address1'],
            'address_line2' => $claim['billing_provider_address2'],
            'city' => $claim['billing_provider_city'],
            'state' => $claim['billing_provider_state'],
            'zip' => $claim['billing_provider_zip']
        ],
        
        'subscriber' => [
            'member_id' => $claim['member_id'],
            'last_name' => $claim['patient_last_name'],
            'first_name' => $claim['patient_first_name'],
            'middle_name' => $claim['patient_middle_name'],
            'dob' => $claim['dob'],
            'gender' => $claim['gender'],
            'address_line1' => $claim['patient_address'],
            'city' => $claim['patient_city'],
            'state' => $claim['patient_state'],
            'zip' => $claim['patient_zip']
        ],
        
        'payer' => [
            'payer_id' => $claim['payer_id']
        ],
        
        'rendering_provider' => [
            'npi' => $claim['rendering_provider_npi'],
            'last_name' => $claim['rendering_provider_last_name'],
            'first_name' => $claim['rendering_provider_first_name'],
            'middle_name' => $claim['rendering_provider_middle_name'],
            'taxonomy_code' => $claim['rendering_provider_taxonomy']
        ],
        
        'diagnoses' => $diagnoses,
        'service_lines' => $serviceLines
    ];
}

// Process claims
try {
    // Get organization/submitter info
    $submitterInfo = getSubmitterInfo($db);
    
    // Initialize EDI generator
    $ediGenerator = new EDI837Generator($db);
    
    // Get pending claims
    $pendingClaims = getPendingClaims($db);
    
    if (empty($pendingClaims)) {
        echo json_encode([
            'success' => false,
            'message' => 'No pending claims found for submission'
        ]);
        exit;
    }
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    // Process each claim
    foreach ($pendingClaims as $claim) {
        try {
            // Get diagnoses and service lines
            $diagnoses = getClaimDiagnoses($db, $claim['claim_id']);
            $serviceLines = getClaimServiceLines($db, $claim['claim_id']);
            
            // Format claim data
            $formattedClaim = formatClaimForEDI($claim, $diagnoses, $serviceLines);
            
            // Add to batch
            if ($ediGenerator->addClaim($formattedClaim)) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Claim " . $claim['claim_number'] . ": " . implode(', ', $ediGenerator->getErrors());
            }
            
        } catch (Exception $e) {
            $errorCount++;
            $errors[] = "Claim " . $claim['claim_number'] . ": " . $e->getMessage();
        }
    }
    
    // Generate EDI if we have valid claims
    if ($successCount > 0) {
        $ediContent = $ediGenerator->generateEDI($submitterInfo);
        
        if ($ediContent !== false) {
            // Save EDI file
            $result = $ediGenerator->saveEDI($ediContent);
            
            if ($result['success']) {
                // Log the submission
                $stmt = $db->prepare("
                    INSERT INTO audit_log (
                        user_id,
                        action,
                        details,
                        created_at
                    ) VALUES (
                        :user_id,
                        'edi_submission',
                        :details,
                        NOW()
                    )
                ");
                
                $stmt->execute([
                    ':user_id' => $_SESSION['user_id'],
                    ':details' => json_encode([
                        'file_id' => $result['file_id'],
                        'filename' => $result['filename'],
                        'claim_count' => $result['claim_count'],
                        'total_amount' => $result['total_amount']
                    ])
                ]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'EDI file generated successfully',
                    'file_id' => $result['file_id'],
                    'filename' => $result['filename'],
                    'claims_processed' => $successCount,
                    'claims_failed' => $errorCount,
                    'total_amount' => $result['total_amount'],
                    'errors' => $errors
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to save EDI file',
                    'error' => $result['error']
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to generate EDI content',
                'errors' => $ediGenerator->getErrors()
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No valid claims to process',
            'errors' => $errors
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing claims',
        'error' => $e->getMessage()
    ]);
}