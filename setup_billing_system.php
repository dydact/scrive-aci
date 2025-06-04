<?php
/**
 * Billing System Setup Script
 * Sets up the complete billing system database tables and initial data
 * 
 * Run this script after deploying the billing system code
 */

session_start();
require_once 'src/config.php';
require_once 'src/openemr_integration.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['access_level'] < 5) {
    die('Admin access required');
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Billing System Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .step { margin: 20px 0; padding: 10px; border-left: 4px solid #ccc; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>";

echo "<h1>🏥 Scrive ACI Billing System Setup</h1>";
echo "<p>Setting up comprehensive billing system components...</p>";

try {
    $pdo = getDatabase();
    $errors = [];
    $successes = [];
    
    // Step 1: Create billing system tables
    echo "<div class='step'><h3>Step 1: Creating Billing System Database Tables</h3>";
    
    $sql_file = __DIR__ . '/sql/billing_system_complete.sql';
    if (file_exists($sql_file)) {
        $sql = file_get_contents($sql_file);
        
        // Split by statements and execute
        $statements = explode(';', $sql);
        $executed = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $pdo->exec($statement);
                    $executed++;
                } catch (PDOException $e) {
                    // Ignore "already exists" errors
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        $errors[] = "SQL Error: " . $e->getMessage();
                    }
                }
            }
        }
        
        echo "<p class='success'>✓ Executed $executed SQL statements</p>";
    } else {
        $errors[] = "SQL file not found: $sql_file";
    }
    echo "</div>";
    
    // Step 2: Create billing directories
    echo "<div class='step'><h3>Step 2: Creating Directory Structure</h3>";
    
    $directories = [
        'autism_waiver_app/billing',
        'autism_waiver_app/edi',
        'autism_waiver_app/reports',
        'uploads/edi_files',
        'uploads/era_files',
        'uploads/appeals',
        'logs/billing'
    ];
    
    foreach ($directories as $dir) {
        $full_path = __DIR__ . '/' . $dir;
        if (!file_exists($full_path)) {
            if (mkdir($full_path, 0755, true)) {
                echo "<p class='success'>✓ Created directory: $dir</p>";
            } else {
                $errors[] = "Failed to create directory: $dir";
            }
        } else {
            echo "<p class='info'>• Directory exists: $dir</p>";
        }
    }
    echo "</div>";
    
    // Step 3: Insert default data
    echo "<div class='step'><h3>Step 3: Inserting Default Data</h3>";
    
    // Insert Maryland Medicaid payer if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM autism_payers WHERE payer_id = 'MDMEDICAID'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("
            INSERT INTO autism_payers (payer_name, payer_id, payer_type, edi_id, timely_filing_days, address_line1, city, state, zip)
            VALUES ('Maryland Medicaid', 'MDMEDICAID', 'medicaid', '77023', 95, '201 W Preston St', 'Baltimore', 'MD', '21201')
        ");
        $stmt->execute();
        echo "<p class='success'>✓ Added Maryland Medicaid payer</p>";
    } else {
        echo "<p class='info'>• Maryland Medicaid payer already exists</p>";
    }
    
    // Insert autism waiver service codes
    $service_codes = [
        ['W1727', 'Individual Intensive Support Services (IISS)', 35.00],
        ['W1728', 'Therapeutic Integration', 40.00],
        ['W7061', 'Respite Care', 30.00],
        ['W7060', 'Family Consultation', 50.00],
        ['W7069', 'Crisis Support Services', 45.00],
        ['W7235', 'Companion Services', 25.00]
    ];
    
    foreach ($service_codes as $code) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM autism_service_types WHERE service_code = ?");
        $stmt->execute([$code[0]]);
        if ($stmt->fetchColumn() == 0) {
            $stmt = $pdo->prepare("
                INSERT INTO autism_service_types (service_code, service_name, rate, unit_type, description)
                VALUES (?, ?, ?, 'hour', 'Maryland Autism Waiver Service')
            ");
            $stmt->execute([$code[0], $code[1], $code[2]]);
            echo "<p class='success'>✓ Added service code: {$code[0]} - {$code[1]}</p>";
        }
    }
    
    // Insert billing rates
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO autism_billing_rates (service_type_id, rate_amount, effective_date, payer_type)
        SELECT id, rate, '2024-01-01', 'Maryland Medicaid'
        FROM autism_service_types 
        WHERE service_code LIKE 'W%'
    ");
    $stmt->execute();
    echo "<p class='success'>✓ Inserted billing rates for service types</p>";
    
    echo "</div>";
    
    // Step 4: Update navigation
    echo "<div class='step'><h3>Step 4: Updating Navigation</h3>";
    
    // Check if billing links exist in dashboard
    $dashboard_files = [
        'src/dashboard.php',
        'autism_waiver_app/billing_dashboard.php'
    ];
    
    foreach ($dashboard_files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (strpos($content, 'billing_dashboard.php') !== false) {
                echo "<p class='success'>✓ Billing links found in $file</p>";
            } else {
                echo "<p class='info'>• May need to add billing links to $file</p>";
            }
        }
    }
    echo "</div>";
    
    // Step 5: Configuration check
    echo "<div class='step'><h3>Step 5: System Configuration Check</h3>";
    
    // Check PHP extensions
    $required_extensions = ['pdo_mysql', 'json', 'mbstring', 'openssl'];
    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext)) {
            echo "<p class='success'>✓ PHP extension loaded: $ext</p>";
        } else {
            $errors[] = "Required PHP extension missing: $ext";
        }
    }
    
    // Check writable directories
    $writable_dirs = ['uploads/edi_files', 'uploads/era_files', 'logs/billing'];
    foreach ($writable_dirs as $dir) {
        if (is_writable($dir)) {
            echo "<p class='success'>✓ Directory writable: $dir</p>";
        } else {
            $errors[] = "Directory not writable: $dir";
        }
    }
    echo "</div>";
    
    // Summary
    echo "<div class='step'><h3>Setup Summary</h3>";
    
    if (empty($errors)) {
        echo "<p class='success'><strong>✅ Billing System Setup Complete!</strong></p>";
        echo "<p>All components have been successfully installed and configured.</p>";
        
        echo "<h4>Next Steps:</h4>";
        echo "<ol>";
        echo "<li>Access the billing dashboard at: <a href='/autism_waiver_app/billing/billing_dashboard.php'>/autism_waiver_app/billing/billing_dashboard.php</a></li>";
        echo "<li>Configure clearinghouse settings if needed</li>";
        echo "<li>Train staff on new billing workflows</li>";
        echo "<li>Test claim generation and submission</li>";
        echo "<li>Set up payment posting procedures</li>";
        echo "</ol>";
        
        echo "<h4>System Components Available:</h4>";
        echo "<ul>";
        echo "<li><strong>Billing Dashboard</strong> - Main operations center</li>";
        echo "<li><strong>Claim Management</strong> - Generate and submit claims</li>";
        echo "<li><strong>Payment Posting</strong> - Post payments and adjustments</li>";
        echo "<li><strong>Denial Management</strong> - Track and appeal denials</li>";
        echo "<li><strong>EDI Processing</strong> - 837/835 file handling</li>";
        echo "<li><strong>Reporting</strong> - Analytics and compliance reports</li>";
        echo "</ul>";
        
    } else {
        echo "<p class='error'><strong>⚠️ Setup completed with errors:</strong></p>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li class='error'>$error</li>";
        }
        echo "</ul>";
        echo "<p>Please resolve these issues before using the billing system.</p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>Setup failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='/autism_waiver_app/billing/billing_dashboard.php'>Go to Billing Dashboard</a> | <a href='/src/dashboard.php'>Back to Main Dashboard</a></p>";
echo "</body></html>";
?>