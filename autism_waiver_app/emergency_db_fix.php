<?php
// Emergency Database Fix - Creates missing autism_sessions table
// Access this page directly to fix the database

$config = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'dbname' => getenv('DB_NAME') ?: 'openemr',
    'username' => getenv('DB_USER') ?: 'openemr',
    'password' => getenv('DB_PASS') ?: 'openemr'
];

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Emergency Database Fix</h2>";
    echo "<pre>";
    echo "Connected to database successfully.\n\n";
    
    // Create autism_sessions table
    $sql = "CREATE TABLE IF NOT EXISTS autism_sessions (
        id INT(11) NOT NULL AUTO_INCREMENT,
        client_id INT(11) NOT NULL,
        staff_id INT(11),
        service_type_id INT(11),
        session_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        duration_hours DECIMAL(5,2),
        session_type VARCHAR(50),
        location VARCHAR(100),
        goals_addressed TEXT,
        interventions TEXT,
        client_response TEXT,
        notes TEXT,
        status ENUM('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
        billing_status ENUM('unbilled','billed','paid','denied') DEFAULT 'unbilled',
        created_by INT(11),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_client (client_id),
        KEY idx_staff (staff_id),
        KEY idx_service (service_type_id),
        KEY idx_date (session_date),
        KEY idx_status (status),
        KEY idx_billing (billing_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ Created autism_sessions table\n\n";
    
    // Check all autism tables
    echo "Checking all autism tables:\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'autism_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "✓ $table\n";
    }
    
    echo "\nDatabase fix completed successfully!\n";
    echo "</pre>";
    
    echo '<p><a href="clients.php">Go to Clients Page</a></p>';
    
} catch (Exception $e) {
    echo "<pre>";
    echo "Database Error: " . $e->getMessage() . "\n";
    echo "</pre>";
}
?>