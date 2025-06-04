<?php
/**
 * EDI 837 Generator Usage Example
 * 
 * This file demonstrates how to use the EDI837Generator class
 * to create and submit professional claims for autism waiver services
 */

require_once __DIR__ . '/EDI837Generator.php';

use ScriverACI\EDI\EDI837Generator;

// Example database connection (replace with your actual connection)
try {
    $db = new PDO('mysql:host=localhost;dbname=scrive_aci', 'username', 'password');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Initialize the EDI generator
$ediGenerator = new EDI837Generator($db);

// Submitter information (your organization)
$submitterInfo = [
    'sender_id' => 'ACI12345',
    'organization_name' => 'Autism Care Institute',
    'submitter_id' => 'ACI12345',
    'contact_name' => 'John Smith',
    'contact_phone' => '4105551234',
    'contact_email' => 'billing@autismcareinstitute.com',
    'batch_id' => 'BATCH' . date('YmdHis')
];

// Example claim 1 - Therapeutic Behavioral Services
$claim1 = [
    'claim_id' => 1001,
    'claim_number' => 'ACI' . date('Ymd') . '001',
    'total_amount' => '450.00',
    'statement_from_date' => '2025-01-01',
    'statement_to_date' => '2025-01-31',
    'facility_code' => '11',  // Office
    
    // Billing Provider
    'billing_provider' => [
        'name' => 'Autism Care Institute',
        'npi' => '1234567890',
        'tax_id' => '12-3456789',
        'address_line1' => '123 Main Street',
        'address_line2' => 'Suite 100',
        'city' => 'Baltimore',
        'state' => 'MD',
        'zip' => '21201'
    ],
    
    // Subscriber (Patient)
    'subscriber' => [
        'member_id' => 'MD123456789',
        'last_name' => 'Doe',
        'first_name' => 'Jane',
        'middle_name' => 'A',
        'dob' => '2015-05-15',
        'gender' => 'F',
        'address_line1' => '456 Oak Street',
        'city' => 'Baltimore',
        'state' => 'MD',
        'zip' => '21202',
        'group_number' => '',
        'group_name' => ''
    ],
    
    // Payer
    'payer' => [
        'payer_id' => 'MDMEDICAID'
    ],
    
    // Diagnoses
    'diagnoses' => [
        ['code' => 'F84.0'],  // Autistic disorder
        ['code' => 'F70']     // Mild intellectual disabilities
    ],
    
    // Rendering Provider
    'rendering_provider' => [
        'npi' => '9876543210',
        'last_name' => 'Johnson',
        'first_name' => 'Sarah',
        'taxonomy_code' => '163WB0400X'  // Behavior Analyst
    ],
    
    // Service Lines
    'service_lines' => [
        [
            'procedure_code' => 'H2019',  // Therapeutic Behavioral Services
            'modifier1' => 'HO',
            'modifier2' => '',
            'modifier3' => '',
            'modifier4' => '',
            'charge_amount' => '150.00',
            'units' => '3',
            'service_date' => '2025-01-15',
            'place_of_service' => '12',  // Home
            'diagnosis_pointer' => '1',
            'prior_auth_number' => 'PA123456'
        ]
    ]
];

// Example claim 2 - Family Training
$claim2 = [
    'claim_id' => 1002,
    'claim_number' => 'ACI' . date('Ymd') . '002',
    'total_amount' => '200.00',
    'statement_from_date' => '2025-01-01',
    'statement_to_date' => '2025-01-31',
    'facility_code' => '11',
    
    'billing_provider' => [
        'name' => 'Autism Care Institute',
        'npi' => '1234567890',
        'tax_id' => '12-3456789',
        'address_line1' => '123 Main Street',
        'address_line2' => 'Suite 100',
        'city' => 'Baltimore',
        'state' => 'MD',
        'zip' => '21201'
    ],
    
    'subscriber' => [
        'member_id' => 'MD987654321',
        'last_name' => 'Smith',
        'first_name' => 'John',
        'middle_name' => 'B',
        'dob' => '2016-08-20',
        'gender' => 'M',
        'address_line1' => '789 Maple Avenue',
        'city' => 'Rockville',
        'state' => 'MD',
        'zip' => '20850',
        'group_number' => '',
        'group_name' => ''
    ],
    
    'payer' => [
        'payer_id' => 'MDMEDICAID'
    ],
    
    'diagnoses' => [
        ['code' => 'F84.0']  // Autistic disorder
    ],
    
    'rendering_provider' => [
        'npi' => '5551234567',
        'last_name' => 'Williams',
        'first_name' => 'Michael',
        'taxonomy_code' => '106H00000X'  // Marriage & Family Therapist
    ],
    
    'service_lines' => [
        [
            'procedure_code' => 'T1027',  // Family Training
            'modifier1' => '',
            'modifier2' => '',
            'modifier3' => '',
            'modifier4' => '',
            'charge_amount' => '100.00',
            'units' => '2',
            'service_date' => '2025-01-10',
            'place_of_service' => '11',  // Office
            'diagnosis_pointer' => '1',
            'prior_auth_number' => 'PA789012'
        ]
    ]
];

// Add claims to the batch
if (!$ediGenerator->addClaim($claim1)) {
    echo "Error adding claim 1: " . implode(', ', $ediGenerator->getErrors()) . "\n";
}

if (!$ediGenerator->addClaim($claim2)) {
    echo "Error adding claim 2: " . implode(', ', $ediGenerator->getErrors()) . "\n";
}

// Generate the EDI content
$ediContent = $ediGenerator->generateEDI($submitterInfo);

if ($ediContent === false) {
    echo "Error generating EDI: " . implode(', ', $ediGenerator->getErrors()) . "\n";
    exit;
}

// Display the generated EDI (for testing)
echo "Generated EDI 837 Professional:\n";
echo "================================\n";
echo $ediContent;
echo "\n================================\n";

// Save the EDI file
$result = $ediGenerator->saveEDI($ediContent);

if ($result['success']) {
    echo "EDI file saved successfully!\n";
    echo "File ID: " . $result['file_id'] . "\n";
    echo "Filename: " . $result['filename'] . "\n";
    echo "File path: " . $result['filepath'] . "\n";
    echo "Claims: " . $result['claim_count'] . "\n";
    echo "Total amount: $" . number_format($result['total_amount'], 2) . "\n";
} else {
    echo "Error saving EDI: " . $result['error'] . "\n";
}

// Example: Validate a single claim before adding
$invalidClaim = [
    'claim_number' => 'TEST001',
    // Missing required fields
];

if (!$ediGenerator->addClaim($invalidClaim)) {
    echo "\nValidation errors for invalid claim:\n";
    foreach ($ediGenerator->getErrors() as $error) {
        echo "- $error\n";
    }
}