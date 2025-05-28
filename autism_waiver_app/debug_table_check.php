<?php

// Include Scrive authentication and API
require_once 'auth.php';

// Initialize authentication
initScriveAuth();

echo "<h2>Debug Table Existence Check</h2>";

try {
    // Test the table check query
    echo "<h3>Checking for autism_client_enrollments table:</h3>";
    $table_check = sqlQuery("SHOW TABLES LIKE 'autism_client_enrollments'");
    
    echo "<pre>";
    echo "Result type: " . gettype($table_check) . "\n";
    echo "Result value: ";
    var_dump($table_check);
    echo "\n";
    echo "Empty check: " . (empty($table_check) ? 'TRUE' : 'FALSE') . "\n";
    echo "Boolean evaluation: " . ($table_check ? 'TRUE' : 'FALSE') . "\n";
    echo "</pre>";
    
    // List all tables to see what exists
    echo "<h3>All tables in database:</h3>";
    $all_tables = sqlStatement("SHOW TABLES");
    echo "<pre>";
    while ($row = sqlFetchArray($all_tables)) {
        print_r($row);
    }
    echo "</pre>";
    
    // Test with different table check method
    echo "<h3>Alternative table check method:</h3>";
    $alt_check = sqlQuery("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'aci_EMR' AND table_name = 'autism_client_enrollments'");
    echo "<pre>";
    echo "Alternative result: ";
    var_dump($alt_check);
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?> 