<?php
session_start();
require_once 'src/config.php';
require_once 'src/openemr_integration.php';

echo "<h2>Login Authentication Test</h2>";
echo "<pre>";

// Test hardcoded users
$test_users = [
    ['username' => 'admin', 'password' => 'AdminPass123!'],
    ['username' => 'dsp_test', 'password' => 'TestPass123!'],
    ['username' => 'cm_test', 'password' => 'TestPass123!'],
    ['username' => 'supervisor_test', 'password' => 'TestPass123!']
];

echo "Testing hardcoded users:\n";
echo "========================\n\n";

foreach ($test_users as $user) {
    echo "Testing: {$user['username']} / {$user['password']}\n";
    
    $result = authenticateOpenEMRUser($user['username'], $user['password']);
    
    if ($result) {
        echo "✅ SUCCESS - User authenticated\n";
        echo "   User ID: {$result['user_id']}\n";
        echo "   Name: {$result['first_name']} {$result['last_name']}\n";
        echo "   Access Level: {$result['access_level']}\n";
        echo "   Role: " . ($result['role_name'] ?? 'N/A') . "\n";
    } else {
        echo "❌ FAILED - Authentication failed\n";
    }
    echo "\n";
}

// Test database connection
echo "\nDatabase Connection Test:\n";
echo "========================\n";

try {
    $pdo = getDatabase();
    echo "✅ Database connected successfully\n";
    
    // Check if autism_users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'autism_users'");
    if ($stmt->fetch()) {
        echo "✅ autism_users table exists\n";
        
        // Count users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_users");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "   Total users in database: $count\n";
        
        // List users
        $stmt = $pdo->query("SELECT username, first_name, last_name, access_level FROM autism_users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($users) {
            echo "\n   Database users:\n";
            foreach ($users as $user) {
                echo "   - {$user['username']} ({$user['first_name']} {$user['last_name']}) - Level {$user['access_level']}\n";
            }
        }
    } else {
        echo "❌ autism_users table does not exist\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n\nSession Status:\n";
echo "===============\n";
echo "Session ID: " . session_id() . "\n";
echo "Session data: " . print_r($_SESSION, true);

echo "</pre>";

// Login form for manual testing
?>

<h2>Manual Login Test</h2>
<form method="POST" action="src/login.php">
    <p>
        Username: <input type="text" name="username" value="admin"><br>
        Password: <input type="password" name="password" value="AdminPass123!"><br>
        <input type="submit" value="Test Login">
    </p>
</form>

<h3>Available Test Accounts:</h3>
<ul>
    <li><strong>admin</strong> / AdminPass123! (Administrator - Level 5)</li>
    <li><strong>supervisor_test</strong> / TestPass123! (Supervisor - Level 4)</li>
    <li><strong>cm_test</strong> / TestPass123! (Case Manager - Level 3)</li>
    <li><strong>dsp_test</strong> / TestPass123! (Direct Support Professional - Level 2)</li>
</ul>

<p><a href="/login">Go to Login Page</a></p>