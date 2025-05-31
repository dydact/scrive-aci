<?php
/**
 * Apply Billing Integration Database Updates
 * This script creates the missing billing tables and views
 */

// Database configuration from environment
$database = getenv('MARIADB_DATABASE') ?: 'iris';
$username = getenv('MARIADB_USER') ?: 'iris_user';
$password = getenv('MARIADB_PASSWORD') ?: '';
$host = getenv('MARIADB_HOST') ?: 'localhost';

echo "=== Scrive ACI Billing Integration Updates ===\n\n";

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to database successfully\n\n";
    
    // Check if autism_billing_entries table exists
    echo "Checking for billing integration tables...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'autism_billing_entries'");
    $billingTableExists = $stmt->fetchColumn();
    
    if ($billingTableExists) {
        echo "✓ autism_billing_entries table already exists\n";
    } else {
        echo "❌ autism_billing_entries table missing - will create\n";
    }
    
    // Check if time clock has been modified
    $stmt = $pdo->query("SHOW COLUMNS FROM autism_time_clock LIKE 'session_note_id'");
    $timeClockUpdated = $stmt->fetchColumn();
    
    if ($timeClockUpdated) {
        echo "✓ Time clock table already updated\n";
    } else {
        echo "❌ Time clock table needs session linkage - will update\n";
    }
    
    // Confirm before proceeding
    echo "\nThis will create/update billing integration tables.\n";
    echo "Do you want to proceed? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) !== 'yes') {
        echo "Cancelled.\n";
        exit(0);
    }
    fclose($handle);
    
    // Read and execute SQL file
    echo "\nApplying billing integration updates...\n";
    $sqlFile = __DIR__ . '/sql/billing_time_integration.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by delimiter for stored procedures
    $delimiter = ';';
    $delimiterLength = 1;
    $inDelimiter = false;
    
    $statements = [];
    $currentStatement = '';
    
    $lines = explode("\n", $sql);
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        
        // Check for DELIMITER command
        if (preg_match('/^DELIMITER\s+(.+)$/i', $trimmedLine, $matches)) {
            if (!$inDelimiter) {
                $delimiter = trim($matches[1]);
                $delimiterLength = strlen($delimiter);
                $inDelimiter = true;
            } else {
                $inDelimiter = false;
                $delimiter = ';';
                $delimiterLength = 1;
            }
            continue;
        }
        
        $currentStatement .= $line . "\n";
        
        // Check if statement ends with delimiter
        if (substr(rtrim($currentStatement), -$delimiterLength) === $delimiter) {
            $statement = substr(rtrim($currentStatement), 0, -$delimiterLength);
            if (!empty(trim($statement))) {
                $statements[] = trim($statement);
            }
            $currentStatement = '';
        }
    }
    
    // Execute statements
    $successCount = 0;
    $warningCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            
            // Identify what was executed
            if (preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`/i', $statement, $matches)) {
                echo "✓ Created/verified table: {$matches[1]}\n";
                $successCount++;
            } elseif (preg_match('/ALTER TABLE `(\w+)`/i', $statement, $matches)) {
                echo "✓ Updated table: {$matches[1]}\n";
                $successCount++;
            } elseif (preg_match('/CREATE OR REPLACE VIEW `(\w+)`/i', $statement, $matches)) {
                echo "✓ Created view: {$matches[1]}\n";
                $successCount++;
            } elseif (preg_match('/CREATE PROCEDURE/i', $statement)) {
                echo "✓ Created stored procedure\n";
                $successCount++;
            } elseif (preg_match('/CREATE TRIGGER/i', $statement)) {
                echo "✓ Created trigger\n";
                $successCount++;
            } elseif (preg_match('/INSERT INTO/i', $statement)) {
                echo "✓ Inserted service types\n";
                $successCount++;
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "ℹ️  Skipped (already exists): " . substr($e->getMessage(), 0, 50) . "...\n";
            } else {
                echo "⚠️  Warning: " . $e->getMessage() . "\n";
                $warningCount++;
            }
        }
    }
    
    // Test the integration
    echo "\nTesting billing integration...\n";
    
    // Check if billing entries can be queried
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM autism_billing_entries");
        $count = $stmt->fetchColumn();
        echo "✓ Billing entries table accessible (contains $count records)\n";
    } catch (PDOException $e) {
        echo "❌ Error accessing billing entries: " . $e->getMessage() . "\n";
    }
    
    // Check if payroll view works
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM v_payroll_hours");
        echo "✓ Payroll hours view working\n";
    } catch (PDOException $e) {
        echo "❌ Error accessing payroll view: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ Billing integration updates completed!\n";
    echo "   - $successCount operations successful\n";
    if ($warningCount > 0) {
        echo "   - $warningCount warnings (review above)\n";
    }
    
    echo "\nNext steps:\n";
    echo "1. Test the payroll report at: /autism_waiver_app/payroll_report.php\n";
    echo "2. Verify time clock entries are being created\n";
    echo "3. Generate billing entries from completed sessions\n";
    echo "4. Review the billing dashboard for aggregated data\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>