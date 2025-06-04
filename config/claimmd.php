<?php
/**
 * Claim.MD Configuration for American Caregivers Inc
 * 
 * SECURITY WARNING: This file contains sensitive API credentials.
 * Ensure proper file permissions and never commit to public repositories.
 */

// Claim.MD API Configuration
define('CLAIMMD_ACCOUNT_KEY', '24127_YF!7zAClm!R@qS^UknmlN#jo');
define('CLAIMMD_API_URL', 'https://svc.claim.md/services/');
define('CLAIMMD_TEST_MODE', false); // Set to true for testing

// American Caregivers Organization Info
define('ACG_ORGANIZATION_NAME', 'American Caregivers Inc');
define('ACG_BILLING_ADDRESS_1', '2301 Broadbirch Drive');
define('ACG_BILLING_ADDRESS_2', 'Suite 135');
define('ACG_BILLING_CITY', 'Silver Spring');
define('ACG_BILLING_STATE', 'MD');
define('ACG_BILLING_ZIP', '20904');

// Maryland Medicaid Payer IDs
define('MD_MEDICAID_PAYER_IDS', [
    'MDMCD' => 'Maryland Medicaid',
    'SKMD0' => 'Maryland Medicaid - Alternate',
    'MCDMD' => 'Maryland Medicaid - Legacy'
]);

// Maryland Autism Waiver Service Codes and Rates (Effective 2024)
define('AUTISM_WAIVER_SERVICES', [
    'W9307' => [
        'name' => 'Regular Therapeutic Integration',
        'rate' => 9.2826,
        'unit' => '15 minutes',
        'weekly_limit' => 80,
        'daily_limit' => null,
        'annual_limit' => null
    ],
    'W9308' => [
        'name' => 'Intensive Therapeutic Integration',
        'rate' => 11.6046,
        'unit' => '15 minutes',
        'weekly_limit' => 60,
        'daily_limit' => null,
        'annual_limit' => null
    ],
    'W9306' => [
        'name' => 'Intensive Individual Support Services (IISS)',
        'rate' => 12.8046,
        'unit' => '15 minutes',
        'weekly_limit' => 160,
        'daily_limit' => null,
        'annual_limit' => null
    ],
    'W9314' => [
        'name' => 'Respite Care',
        'rate' => 9.0720,
        'unit' => '15 minutes',
        'weekly_limit' => null,
        'daily_limit' => 96,
        'annual_limit' => 1344
    ],
    'W9315' => [
        'name' => 'Family Consultation',
        'rate' => 38.0970,
        'unit' => '15 minutes',
        'weekly_limit' => null,
        'daily_limit' => 24,
        'annual_limit' => 160
    ]
]);

// Common Autism Diagnosis Codes
define('AUTISM_DIAGNOSIS_CODES', [
    'F84.0' => 'Autistic disorder',
    'F84.5' => 'Asperger\'s syndrome',
    'F84.8' => 'Other pervasive developmental disorders',
    'F84.9' => 'Pervasive developmental disorder, unspecified'
]);

// Claim submission settings
define('CLAIMMD_BATCH_SIZE', 100); // Max claims per batch
define('CLAIMMD_RATE_LIMIT', 100); // Max requests per minute
define('CLAIMMD_TIMEOUT', 300); // 5 minutes for large batches

// File paths for Claim.MD operations
define('CLAIMMD_UPLOAD_DIR', UPLOADS_DIR . '/claimmd');
define('CLAIMMD_ARCHIVE_DIR', UPLOADS_DIR . '/claimmd/archive');
define('CLAIMMD_LOG_FILE', LOGS_DIR . '/claimmd_transactions.log');

// Create directories if they don't exist
if (!file_exists(CLAIMMD_UPLOAD_DIR)) {
    mkdir(CLAIMMD_UPLOAD_DIR, 0755, true);
}
if (!file_exists(CLAIMMD_ARCHIVE_DIR)) {
    mkdir(CLAIMMD_ARCHIVE_DIR, 0755, true);
}
?>