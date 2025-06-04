<?php
require_once '../src/init.php';

// Test access without authentication for testing
// In production, remove this and use: requireAuth(4);

try {
    $pdo = getDatabase();
    
    echo "<h2>Testing Supervisor Portal Database Requirements</h2>";
    
    // Check tables
    $tables = [
        'autism_session_notes',
        'autism_schedules', 
        'autism_staff_members',
        'autism_clients',
        'autism_time_clock',
        'autism_claims',
        'autism_users'
    ];
    
    echo "<h3>Table Check:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        echo "<li>$table: " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "</li>";
    }
    echo "</ul>";
    
    // Check if we have test data
    echo "<h3>Data Check:</h3>";
    echo "<ul>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_staff_members WHERE status = 'active'");
    $result = $stmt->fetch();
    echo "<li>Active Staff: " . $result['count'] . "</li>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_clients WHERE status = 'active'");
    $result = $stmt->fetch();
    echo "<li>Active Clients: " . $result['count'] . "</li>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_session_notes");
    $result = $stmt->fetch();
    echo "<li>Session Notes: " . $result['count'] . "</li>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_schedules");
    $result = $stmt->fetch();
    echo "<li>Schedules: " . $result['count'] . "</li>";
    
    echo "</ul>";
    
    // Test user with supervisor access
    echo "<h3>Test Supervisor Access:</h3>";
    echo "<p>To test the supervisor portal, you need a user with access_level >= 4</p>";
    
    $stmt = $pdo->query("SELECT id, username, first_name, last_name, access_level FROM autism_users WHERE access_level >= 4 LIMIT 5");
    $supervisors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($supervisors)) {
        echo "<p>❌ No supervisor accounts found. Creating test supervisor...</p>";
        
        // Create a test supervisor
        $stmt = $pdo->prepare("
            INSERT INTO autism_users (username, password, email, first_name, last_name, access_level, user_type)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'supervisor1',
            password_hash('test123', PASSWORD_DEFAULT),
            'supervisor@aci.test',
            'Test',
            'Supervisor',
            4,
            'staff'
        ]);
        
        echo "<p>✅ Created test supervisor account:</p>";
        echo "<ul>";
        echo "<li>Username: supervisor1</li>";
        echo "<li>Password: test123</li>";
        echo "<li>Access Level: 4 (Supervisor)</li>";
        echo "</ul>";
    } else {
        echo "<p>✅ Found supervisor accounts:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Access Level</th></tr>";
        foreach ($supervisors as $sup) {
            echo "<tr>";
            echo "<td>{$sup['id']}</td>";
            echo "<td>{$sup['username']}</td>";
            echo "<td>{$sup['first_name']} {$sup['last_name']}</td>";
            echo "<td>{$sup['access_level']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr>";
    echo "<p><a href='/login'>Go to Login</a> | <a href='/supervisor'>Go to Supervisor Portal</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>