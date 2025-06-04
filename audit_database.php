<?php
/**
 * Database Audit Script - Diagnose Production Database Issues
 * This script will help identify which database aci.dydact.io is using
 */

echo "=== DATABASE CONNECTION AUDIT ===\n\n";

// First, let's check what's in the config files
echo "1. Checking Configuration Files:\n";
echo "--------------------------------\n";

// Check main config
$configFile = '/var/www/localhost/htdocs/src/config.php';
if (file_exists($configFile)) {
    echo "Found config.php\n";
    $configContent = file_get_contents($configFile);
    
    // Extract database settings
    preg_match("/define\('DB_HOST',\s*['\"]([^'\"]+)['\"]\)/", $configContent, $hostMatch);
    preg_match("/define\('DB_NAME',\s*['\"]([^'\"]+)['\"]\)/", $configContent, $dbMatch);
    preg_match("/define\('DB_USER',\s*['\"]([^'\"]+)['\"]\)/", $configContent, $userMatch);
    
    echo "DB_HOST: " . ($hostMatch[1] ?? 'Not found') . "\n";
    echo "DB_NAME: " . ($dbMatch[1] ?? 'Not found') . "\n";
    echo "DB_USER: " . ($userMatch[1] ?? 'Not found') . "\n";
} else {
    echo "ERROR: config.php not found at expected location\n";
}

// Check for OpenEMR sqlconf
$sqlconfPaths = [
    '/var/www/localhost/htdocs/sites/americancaregivers/sqlconf.php',
    '/var/www/localhost/htdocs/sites/default/sqlconf.php',
    '/var/www/localhost/htdocs/openemr/sites/americancaregivers/sqlconf.php',
    '/var/www/localhost/htdocs/openemr/sites/default/sqlconf.php'
];

echo "\n2. Checking OpenEMR SQL Configuration:\n";
echo "--------------------------------------\n";

foreach ($sqlconfPaths as $path) {
    if (file_exists($path)) {
        echo "Found sqlconf.php at: $path\n";
        include $path;
        echo "Login: $login\n";
        echo "Host: $host\n";
        echo "Database: $dbase\n";
        break;
    }
}

// Now let's try to connect and check what's in the database
echo "\n3. Testing Database Connection:\n";
echo "-------------------------------\n";

// Load the actual config
require_once '/var/www/localhost/htdocs/src/config.php';

try {
    $pdo = getDatabase();
    echo "✓ Successfully connected to database\n";
    
    // Check which database we're connected to
    $stmt = $pdo->query("SELECT DATABASE()");
    $currentDb = $stmt->fetchColumn();
    echo "Connected to database: $currentDb\n";
    
    // Check if autism_users table exists
    echo "\n4. Checking Tables:\n";
    echo "-------------------\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'autism_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($tables) . " autism_ tables\n";
    
    if (in_array('autism_users', $tables)) {
        echo "✓ autism_users table exists\n";
        
        // Check users in the table
        $stmt = $pdo->query("SELECT COUNT(*) FROM autism_users");
        $userCount = $stmt->fetchColumn();
        echo "Total users in autism_users: $userCount\n";
        
        // List users
        echo "\n5. Users in Database:\n";
        echo "---------------------\n";
        $stmt = $pdo->query("SELECT username, email, access_level FROM autism_users ORDER BY access_level DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            echo "- {$user['username']} ({$user['email']}) - Level {$user['access_level']}\n";
        }
    } else {
        echo "❌ autism_users table NOT found!\n";
    }
    
    // Check how login is handled
    echo "\n6. Checking Login Implementation:\n";
    echo "---------------------------------\n";
    
    $loginFiles = [
        '/var/www/localhost/htdocs/src/login.php',
        '/var/www/localhost/htdocs/autism_waiver_app/simple_login.php',
        '/var/www/localhost/htdocs/login.php'
    ];
    
    foreach ($loginFiles as $file) {
        if (file_exists($file)) {
            echo "Found login file: $file\n";
            $content = file_get_contents($file);
            
            // Check if it's using hardcoded credentials
            if (strpos($content, 'AdminPass123!') !== false) {
                echo "⚠️  WARNING: This file contains hardcoded credentials!\n";
            }
            
            // Check if it's using database authentication
            if (strpos($content, 'authenticateOpenEMRUser') !== false) {
                echo "✓ This file uses database authentication\n";
            }
        }
    }
    
    // Check which login URL is being used
    echo "\n7. Checking .htaccess Routing:\n";
    echo "-------------------------------\n";
    
    $htaccessFile = '/var/www/localhost/htdocs/.htaccess';
    if (file_exists($htaccessFile)) {
        $htaccess = file_get_contents($htaccessFile);
        if (preg_match('/RewriteRule.*login.*?(\S+\.php)/', $htaccess, $match)) {
            echo "Login route points to: {$match[1]}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

echo "\n\n=== DIAGNOSIS COMPLETE ===\n";
echo "\nPossible issues:\n";
echo "1. Production may be using a different database than local Docker\n";
echo "2. Login file may still be using hardcoded credentials\n";
echo "3. Database connection may be pointing to wrong host\n";
echo "4. .htaccess may be routing to wrong login file\n";
?>