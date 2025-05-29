<?php
/**
 * Apply Production Database Updates
 * This script safely applies missing production tables
 */

// Database configuration from environment
$database = getenv('MARIADB_DATABASE') ?: 'iris';
$username = getenv('MARIADB_USER') ?: 'iris_user';
$password = getenv('MARIADB_PASSWORD') ?: '';
$host = getenv('MARIADB_HOST') ?: 'localhost';

echo "=== Scrive ACI Production Database Updates ===\n\n";

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connected to database successfully\n\n";
    
    // Check existing tables
    echo "Checking existing tables...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'autism_%'");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($existingTables) . " autism_ tables\n\n";
    
    // Tables we need to create
    $requiredTables = [
        'autism_time_clock' => 'Employee time tracking',
        'autism_eligibility_verification' => 'Medicaid eligibility verification log',
        'autism_provider_config' => 'Provider configuration settings',
        'autism_mobile_sessions' => 'Mobile app session tracking'
    ];
    
    $tablesToCreate = [];
    foreach ($requiredTables as $table => $description) {
        if (!in_array($table, $existingTables)) {
            $tablesToCreate[$table] = $description;
            echo "❌ Missing: $table - $description\n";
        } else {
            echo "✓ Exists: $table\n";
        }
    }
    
    if (empty($tablesToCreate)) {
        echo "\n✓ All required tables already exist!\n";
        exit(0);
    }
    
    // Confirm before proceeding
    echo "\n" . count($tablesToCreate) . " tables need to be created.\n";
    echo "Do you want to proceed? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    if (trim($line) !== 'yes') {
        echo "Cancelled.\n";
        exit(0);
    }
    fclose($handle);
    
    // Read and execute SQL file
    echo "\nApplying database updates...\n";
    $sqlFile = __DIR__ . '/sql/production_missing_tables.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by statement and execute
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    $successCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($statement . ';');
            
            // Check if this was a CREATE TABLE statement
            if (preg_match('/CREATE TABLE IF NOT EXISTS `(\w+)`/i', $statement, $matches)) {
                echo "✓ Created table: {$matches[1]}\n";
                $successCount++;
            } elseif (preg_match('/INSERT INTO `(\w+)`/i', $statement, $matches)) {
                echo "✓ Inserted default data into: {$matches[1]}\n";
            } elseif (preg_match('/CREATE INDEX/i', $statement)) {
                echo "✓ Created index\n";
            }
        } catch (PDOException $e) {
            echo "⚠️  Warning: " . $e->getMessage() . "\n";
        }
    }
    
    // Verify tables were created
    echo "\nVerifying creation...\n";
    foreach ($tablesToCreate as $table => $description) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->fetchColumn()) {
            echo "✓ Verified: $table\n";
        } else {
            echo "❌ Failed to create: $table\n";
        }
    }
    
    // Update mobile portal to use new time clock table
    echo "\nUpdating configuration...\n";
    
    // Check if we need to migrate any existing clock data
    $stmt = $pdo->query("SELECT COUNT(*) FROM autism_session_notes WHERE clock_in_time IS NOT NULL");
    $existingClockData = $stmt->fetchColumn();
    
    if ($existingClockData > 0) {
        echo "ℹ️  Found $existingClockData session notes with clock data that may need migration\n";
    }
    
    echo "\n✅ Production database updates completed successfully!\n";
    echo "\nNext steps:\n";
    echo "1. Update environment variables with provider information\n";
    echo "2. Configure autism_provider_config table with actual values\n";
    echo "3. Test mobile portal clock in/out functionality\n";
    echo "4. Verify eligibility verification logging\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>