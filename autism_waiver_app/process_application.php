<?php
/**
 * APPLICATION PROCESSOR - American Caregivers Inc
 * 
 * Processes service applications and forwards them to contact@acgcares.com
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers for AJAX requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Format phone number
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 10) {
        return sprintf('(%s) %s-%s', substr($phone, 0, 3), substr($phone, 3, 3), substr($phone, 6));
    }
    return $phone;
}

try {
    // Collect and sanitize form data
    $clientData = [
        'first_name' => sanitizeInput($_POST['client_first_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['client_last_name'] ?? ''),
        'dob' => sanitizeInput($_POST['client_dob'] ?? ''),
        'gender' => sanitizeInput($_POST['client_gender'] ?? ''),
        'address' => sanitizeInput($_POST['client_address'] ?? ''),
        'city' => sanitizeInput($_POST['client_city'] ?? ''),
        'zip' => sanitizeInput($_POST['client_zip'] ?? ''),
        'phone' => sanitizeInput($_POST['client_phone'] ?? '')
    ];
    
    $guardianData = [
        'first_name' => sanitizeInput($_POST['guardian_first_name'] ?? ''),
        'last_name' => sanitizeInput($_POST['guardian_last_name'] ?? ''),
        'email' => sanitizeInput($_POST['guardian_email'] ?? ''),
        'phone' => sanitizeInput($_POST['guardian_phone'] ?? ''),
        'relationship' => sanitizeInput($_POST['relationship'] ?? '')
    ];
    
    $medicalData = [
        'primary_diagnosis' => sanitizeInput($_POST['primary_diagnosis'] ?? ''),
        'secondary_diagnosis' => sanitizeInput($_POST['secondary_diagnosis'] ?? ''),
        'medications' => sanitizeInput($_POST['medications'] ?? ''),
        'allergies' => sanitizeInput($_POST['allergies'] ?? ''),
        'physician_name' => sanitizeInput($_POST['physician_name'] ?? '')
    ];
    
    $serviceData = [
        'programs' => $_POST['programs'] ?? [],
        'services' => $_POST['services'] ?? [],
        'preferred_hours' => sanitizeInput($_POST['preferred_hours'] ?? '')
    ];
    
    $additionalData = [
        'school_name' => sanitizeInput($_POST['school_name'] ?? ''),
        'emergency_contact_name' => sanitizeInput($_POST['emergency_contact_name'] ?? ''),
        'emergency_contact_phone' => sanitizeInput($_POST['emergency_contact_phone'] ?? ''),
        'special_needs' => sanitizeInput($_POST['special_needs'] ?? ''),
        'goals' => sanitizeInput($_POST['goals'] ?? ''),
        'how_heard' => sanitizeInput($_POST['how_heard'] ?? '')
    ];
    
    // Validate required fields
    $requiredFields = [
        'client_first_name' => $clientData['first_name'],
        'client_last_name' => $clientData['last_name'],
        'client_dob' => $clientData['dob'],
        'client_address' => $clientData['address'],
        'client_city' => $clientData['city'],
        'client_zip' => $clientData['zip'],
        'guardian_first_name' => $guardianData['first_name'],
        'guardian_last_name' => $guardianData['last_name'],
        'guardian_email' => $guardianData['email'],
        'guardian_phone' => $guardianData['phone'],
        'relationship' => $guardianData['relationship'],
        'primary_diagnosis' => $medicalData['primary_diagnosis']
    ];
    
    foreach ($requiredFields as $field => $value) {
        if (empty($value)) {
            throw new Exception("Required field missing: " . str_replace('_', ' ', $field));
        }
    }
    
    // Validate email
    if (!isValidEmail($guardianData['email'])) {
        throw new Exception("Invalid email address");
    }
    
    // Validate programs selected
    if (empty($serviceData['programs'])) {
        throw new Exception("Please select at least one program of interest");
    }
    
    // Sanitize arrays
    $serviceData['programs'] = array_map('sanitizeInput', $serviceData['programs']);
    $serviceData['services'] = array_map('sanitizeInput', $serviceData['services']);
    
    // Generate application ID
    $applicationId = 'ACI-APP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    // Create email content
    $emailSubject = "New Service Application - {$clientData['first_name']} {$clientData['last_name']} (ID: {$applicationId})";
    
    $emailBody = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background: #059669; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .section { margin-bottom: 25px; padding: 15px; border-left: 4px solid #059669; background: #f8f9fa; }
        .section h3 { margin-top: 0; color: #059669; }
        .field { margin-bottom: 10px; }
        .field strong { display: inline-block; width: 200px; }
        .programs, .services { background: #e3f2fd; padding: 10px; border-radius: 5px; }
        .footer { background: #f8f9fa; padding: 15px; text-align: center; color: #666; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>🤝 American Caregivers Inc</h1>
        <h2>New Service Application</h2>
        <p>Application ID: {$applicationId}</p>
        <p>Submitted: " . date('F j, Y \a\t g:i A') . "</p>
    </div>
    
    <div class='content'>
        <div class='section'>
            <h3>👤 Client Information</h3>
            <div class='field'><strong>Name:</strong> {$clientData['first_name']} {$clientData['last_name']}</div>
            <div class='field'><strong>Date of Birth:</strong> " . date('F j, Y', strtotime($clientData['dob'])) . "</div>
            <div class='field'><strong>Gender:</strong> " . ($clientData['gender'] ?: 'Not specified') . "</div>
            <div class='field'><strong>Address:</strong> {$clientData['address']}, {$clientData['city']}, MD {$clientData['zip']}</div>
            <div class='field'><strong>Phone:</strong> " . ($clientData['phone'] ? formatPhone($clientData['phone']) : 'Not provided') . "</div>
        </div>
        
        <div class='section'>
            <h3>👨‍👩‍👧‍👦 Parent/Guardian Information</h3>
            <div class='field'><strong>Name:</strong> {$guardianData['first_name']} {$guardianData['last_name']}</div>
            <div class='field'><strong>Email:</strong> {$guardianData['email']}</div>
            <div class='field'><strong>Phone:</strong> " . formatPhone($guardianData['phone']) . "</div>
            <div class='field'><strong>Relationship:</strong> {$guardianData['relationship']}</div>
        </div>
        
        <div class='section'>
            <h3>🏥 Medical Information</h3>
            <div class='field'><strong>Primary Diagnosis:</strong> {$medicalData['primary_diagnosis']}</div>
            " . ($medicalData['secondary_diagnosis'] ? "<div class='field'><strong>Secondary Diagnosis:</strong> {$medicalData['secondary_diagnosis']}</div>" : "") . "
            " . ($medicalData['medications'] ? "<div class='field'><strong>Medications:</strong> {$medicalData['medications']}</div>" : "") . "
            " . ($medicalData['allergies'] ? "<div class='field'><strong>Allergies:</strong> {$medicalData['allergies']}</div>" : "") . "
            " . ($medicalData['physician_name'] ? "<div class='field'><strong>Primary Care Physician:</strong> {$medicalData['physician_name']}</div>" : "") . "
        </div>
        
        <div class='section'>
            <h3>🎯 Services Requested</h3>
            <div class='field'><strong>Programs of Interest:</strong></div>
            <div class='programs'>" . implode('<br>', $serviceData['programs']) . "</div>
            
            " . (!empty($serviceData['services']) ? "
            <div class='field' style='margin-top: 15px;'><strong>Service Types of Interest:</strong></div>
            <div class='services'>" . implode('<br>', $serviceData['services']) . "</div>
            " : "") . "
            
            " . ($serviceData['preferred_hours'] ? "<div class='field' style='margin-top: 15px;'><strong>Preferred Hours per Week:</strong> {$serviceData['preferred_hours']}</div>" : "") . "
        </div>
        
        " . (array_filter($additionalData) ? "
        <div class='section'>
            <h3>📝 Additional Information</h3>
            " . ($additionalData['school_name'] ? "<div class='field'><strong>School/Educational Program:</strong> {$additionalData['school_name']}</div>" : "") . "
            " . ($additionalData['emergency_contact_name'] ? "<div class='field'><strong>Emergency Contact:</strong> {$additionalData['emergency_contact_name']}" . ($additionalData['emergency_contact_phone'] ? " - " . formatPhone($additionalData['emergency_contact_phone']) : "") . "</div>" : "") . "
            " . ($additionalData['special_needs'] ? "<div class='field'><strong>Special Needs:</strong> {$additionalData['special_needs']}</div>" : "") . "
            " . ($additionalData['goals'] ? "<div class='field'><strong>Goals for Services:</strong> {$additionalData['goals']}</div>" : "") . "
            " . ($additionalData['how_heard'] ? "<div class='field'><strong>How they heard about us:</strong> {$additionalData['how_heard']}</div>" : "") . "
        </div>
        " : "") . "
    </div>
    
    <div class='footer'>
        <p><strong>Next Steps:</strong></p>
        <p>1. Review application and client needs</p>
        <p>2. Contact guardian within 2-3 business days</p>
        <p>3. Schedule initial assessment if appropriate</p>
        <p>4. Discuss service options and availability</p>
        <br>
        <p>American Caregivers Inc | contact@acgcares.com | 301-408-0100</p>
    </div>
</body>
</html>
    ";
    
    // Email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ACI Application System <noreply@acgcares.com>',
        'Reply-To: ' . $guardianData['email'],
        'X-Mailer: PHP/' . phpversion(),
        'X-Application-ID: ' . $applicationId
    ];
    
    // Send email to ACI
    $emailSent = mail(
        'contact@acgcares.com',
        $emailSubject,
        $emailBody,
        implode("\r\n", $headers)
    );
    
    if (!$emailSent) {
        throw new Exception("Failed to send application email");
    }
    
    // Send confirmation email to guardian
    $confirmationSubject = "Application Received - American Caregivers Inc (ID: {$applicationId})";
    $confirmationBody = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .header { background: #059669; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .highlight { background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .footer { background: #f8f9fa; padding: 15px; text-align: center; color: #666; }
    </style>
</head>
<body>
    <div class='header'>
        <h1>🤝 American Caregivers Inc</h1>
        <h2>Application Confirmation</h2>
    </div>
    
    <div class='content'>
        <p>Dear {$guardianData['first_name']} {$guardianData['last_name']},</p>
        
        <p>Thank you for submitting a service application for <strong>{$clientData['first_name']} {$clientData['last_name']}</strong>.</p>
        
        <div class='highlight'>
            <h3>📋 Application Details</h3>
            <p><strong>Application ID:</strong> {$applicationId}</p>
            <p><strong>Submitted:</strong> " . date('F j, Y \a\t g:i A') . "</p>
            <p><strong>Programs Requested:</strong> " . implode(', ', $serviceData['programs']) . "</p>
        </div>
        
        <h3>📞 What Happens Next?</h3>
        <ol>
            <li>Our team will review your application within 1-2 business days</li>
            <li>We will contact you within 2-3 business days to discuss next steps</li>
            <li>If appropriate, we'll schedule an initial assessment</li>
            <li>We'll work with you to develop a personalized service plan</li>
        </ol>
        
        <h3>📞 Contact Information</h3>
        <p>If you have any questions or need to update your application, please contact us:</p>
        <ul>
            <li><strong>Email:</strong> contact@acgcares.com</li>
            <li><strong>Silver Spring Office:</strong> 301-408-0100</li>
            <li><strong>Columbia Office:</strong> 301-301-0123</li>
        </ul>
        
        <p>We look forward to working with you and {$clientData['first_name']} to provide the best possible care and support.</p>
        
        <p>Warm regards,<br>
        <strong>American Caregivers Inc Team</strong></p>
    </div>
    
    <div class='footer'>
        <p>American Caregivers Inc</p>
        <p>Silver Spring: 2301 Broadbirch Dr., Suite 135, Silver Spring, MD 20904</p>
        <p>Columbia: 10715 Charter Drive, Ste. 100, Columbia, MD 21044</p>
        <p>www.acgcares.com | contact@acgcares.com</p>
    </div>
</body>
</html>
    ";
    
    $confirmationHeaders = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: American Caregivers Inc <contact@acgcares.com>',
        'Reply-To: contact@acgcares.com',
        'X-Mailer: PHP/' . phpversion(),
        'X-Application-ID: ' . $applicationId
    ];
    
    // Send confirmation email
    mail(
        $guardianData['email'],
        $confirmationSubject,
        $confirmationBody,
        implode("\r\n", $confirmationHeaders)
    );
    
    // Log application (optional - for internal tracking)
    $logEntry = date('Y-m-d H:i:s') . " - Application {$applicationId} submitted for {$clientData['first_name']} {$clientData['last_name']} by {$guardianData['first_name']} {$guardianData['last_name']} ({$guardianData['email']})\n";
    file_put_contents('application_log.txt', $logEntry, FILE_APPEND | LOCK_EX);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully',
        'application_id' => $applicationId
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log("Application submission error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 