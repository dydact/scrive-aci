<?php
// Script to apply training system tables to the database

try {
    // Connect to SQLite database
    $pdo = new PDO('sqlite:' . __DIR__ . '/autism_waiver.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read and execute the training tables SQL
    $sql = file_get_contents(__DIR__ . '/../sql/create_training_tables.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 60) . "...\n";
            } catch (PDOException $e) {
                echo "✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\nTraining system tables have been created successfully!\n";
    
} catch (PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}
?>