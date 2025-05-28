<?php

/**
 * Enhanced Add Client Backend - Scrive AI-Powered Autism Waiver ERM
 * 
 * @package   Scrive
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
 * @license   MIT License
 */

// Include Scrive authentication and API
require_once 'auth.php';
require_once 'api.php';

// Initialize authentication
initScriveAuth();

// Get current user
$currentUser = getCurrentScriveUser();
$api = new OpenEMRAPI();

$response = ['success' => false, 'message' => '', 'redirect' => ''];

try {
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $required_fields = ['fname', 'lname', 'dob'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Required field '{$field}' is missing");
        }
    }

    // Sanitize and prepare basic client data
    $client_data = [
        'fname' => trim($_POST['fname']),
        'lname' => trim($_POST['lname']),
        'DOB' => $_POST['dob'],
        'sex' => $_POST['sex'] ?? '',
        'phone_home' => $_POST['phone_home'] ?? '',
        'email' => $_POST['email'] ?? '',
        'street' => $_POST['street'] ?? '',
        'city' => $_POST['city'] ?? '',
        'state' => $_POST['state'] ?? 'MD',
        'postal_code' => $_POST['postal_code'] ?? '',
        'ss' => '', // SSN - not collected in this form
        'status' => '', // Patient status
        'contact_relationship' => '',
        'mothersname' => '',
        'guardiansname' => $_POST['parent_guardian'] ?? '',
        'allow_imm_reg_use' => 'NO',
        'allow_imm_info_share' => 'NO',
        'allow_health_info_ex' => 'NO',
        'allow_patient_portal' => 'NO',
        'care_team' => 0,
        'county' => $_POST['jurisdiction'] ?? '',
        'drivers_license' => '',
        'language' => 'English',
        'ethnicity' => '',
        'race' => '',
        'religion' => '',
        'financial_review' => date('Y-m-d'),
        'family_size' => '',
        'monthly_income' => '',
        'homeless' => '',
        'interpretter' => '',
        'migrantseasonal' => '',
        'referral_source' => 'American Caregivers Inc',
        'regdate' => date('Y-m-d'),
        'providerID' => $currentUser['user_id'] ?? 1,
        'ref_providerID' => 0,
        'email_direct' => '',
        'phone_cell' => '',
        'phone_contact' => $_POST['phone_home'] ?? '',
        'phone_work' => '',
        'pharmacy_id' => 0,
        'hipaa_mail' => 'NO',
        'hipaa_voice' => 'NO',
        'hipaa_notice' => 'NO',
        'hipaa_message' => '',
        'deceased_date' => null,
        'deceased_reason' => '',
        'soap_import_status' => 1,
        'cmsportal_login' => '',
        'default_facility' => $currentUser['facility_id'] ?? 1,
        'source' => 'Scrive'
    ];

    // Prepare autism waiver data
    $waiver_data = [
        'waiver_program' => $_POST['waiver_program'] ?? '',
        'ma_number' => $_POST['ma_number'] ?? '',
        'jurisdiction' => $_POST['jurisdiction'] ?? '',
        'parent_guardian' => $_POST['parent_guardian'] ?? '',
        'guardian_phone' => $_POST['guardian_phone'] ?? '',
        'school' => $_POST['school'] ?? '',
        'case_coordinator' => $_POST['case_coordinator'] ?? '',
        'allowed_services' => $_POST['allowed_services'] ?? [],
        'weekly_units' => $_POST['weekly_units'] ?? []
    ];

    // Validate date of birth
    $dob = DateTime::createFromFormat('Y-m-d', $client_data['DOB']);
    if (!$dob) {
        throw new Exception('Invalid date of birth format');
    }

    // Check if client already exists
    $existing_check = sqlQuery(
        "SELECT pid FROM patient_data WHERE fname = ? AND lname = ? AND DOB = ?",
        [$client_data['fname'], $client_data['lname'], $client_data['DOB']]
    );

    if ($existing_check) {
        throw new Exception('A client with this name and date of birth already exists');
    }

    // Validate waiver program if provided
    if (!empty($waiver_data['waiver_program'])) {
        $valid_programs = ['AW', 'DDA', 'CFC', 'CS'];
        if (!in_array($waiver_data['waiver_program'], $valid_programs)) {
            throw new Exception('Invalid waiver program selected');
        }
    }

    // Validate allowed services if provided
    if (!empty($waiver_data['allowed_services'])) {
        $valid_services = ['IISS', 'TI', 'RESP', 'FC'];
        foreach ($waiver_data['allowed_services'] as $service) {
            if (!in_array($service, $valid_services)) {
                throw new Exception("Invalid service type: {$service}");
            }
        }
    }

    // Format phone numbers
    if (!empty($client_data['phone_home'])) {
        $client_data['phone_home'] = preg_replace('/[^0-9]/', '', $client_data['phone_home']);
    }
    if (!empty($waiver_data['guardian_phone'])) {
        $waiver_data['guardian_phone'] = preg_replace('/[^0-9]/', '', $waiver_data['guardian_phone']);
    }

    // Create enhanced client using API
    $patientId = $api->createEnhancedClient(
        $client_data, 
        $waiver_data, 
        $currentUser['user_id'] ?? 1
    );

    // Log the action for audit trail
    $audit_data = [
        'table_name' => 'patient_data',
        'record_id' => $patientId,
        'action' => 'INSERT',
        'old_values' => null,
        'new_values' => json_encode([
            'client_data' => $client_data,
            'waiver_data' => $waiver_data
        ]),
        'user_id' => $currentUser['user_id'] ?? 1,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Insert audit log if the table exists
    try {
        $audit_table_check = sqlQuery("SHOW TABLES LIKE 'autism_audit_log'");
        if ($audit_table_check) {
            $audit_fields = implode(', ', array_keys($audit_data));
            $audit_placeholders = str_repeat('?,', count($audit_data) - 1) . '?';
            $audit_sql = "INSERT INTO autism_audit_log ({$audit_fields}) VALUES ({$audit_placeholders})";
            sqlStatement($audit_sql, array_values($audit_data));
        }
    } catch (Exception $e) {
        // Log audit error but don't fail the client creation
        error_log("Audit log error: " . $e->getMessage());
    }

    // Generate success message
    $full_name = trim($client_data['fname'] . ' ' . $client_data['lname']);
    $program_text = '';
    if (!empty($waiver_data['waiver_program'])) {
        $programs = [
            'AW' => 'Autism Waiver',
            'DDA' => 'Developmental Disabilities Administration', 
            'CFC' => 'Community First Choice',
            'CS' => 'Community Supports'
        ];
        $program_text = " enrolled in {$programs[$waiver_data['waiver_program']]}";
    }

    // Success response
    $response['success'] = true;
    $response['message'] = "Client '{$full_name}' has been successfully added to the system{$program_text}.";
    
    // Add details about services if provided
    if (!empty($waiver_data['allowed_services'])) {
        $service_names = [
            'IISS' => 'Intensive Individual Support Services',
            'TI' => 'Therapeutic Integration',
            'RESP' => 'Respite Care',
            'FC' => 'Family Consultation'
        ];
        $services = array_map(function($service) use ($service_names) {
            return $service_names[$service] ?? $service;
        }, $waiver_data['allowed_services']);
        
        $response['message'] .= " Authorized services: " . implode(', ', $services) . ".";
    }
    
    $response['redirect'] = "clients.php?success=" . urlencode($response['message']);
    $response['client_id'] = $patientId;

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Log error for debugging
    error_log("Enhanced Add Client Error: " . $e->getMessage() . " - User: " . ($currentUser['username'] ?? 'unknown'));
    
    // Provide user-friendly error messages
    if (strpos($e->getMessage(), 'already exists') !== false) {
        $response['message'] = 'A client with this name and date of birth already exists in the system.';
    } elseif (strpos($e->getMessage(), 'Invalid') !== false) {
        // Keep the specific validation error
    } else {
        $response['message'] = 'There was an error adding the client. Please check all fields and try again.';
    }
}

// Handle both AJAX and form submissions
if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    // AJAX response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} else {
    // Form submission - redirect back to clients page
    if ($response['success']) {
        header('Location: ' . $response['redirect']);
    } else {
        header('Location: clients.php?error=' . urlencode($response['message']));
    }
    exit;
}

/**
 * Generate a simple UUID for client records (if needed)
 */
function generateUuid() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

?> 