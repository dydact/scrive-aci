<?php
// Setup script for claim management tables
require_once '../config_sqlite.php';

echo "<h2>Setting up Claim Management System</h2>";

try {
    $conn = getConnection();
    
    // Read and execute SQL file
    $sql_file = '../../sql/claim_management_tables.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Split by semicolons but ignore ones inside quotes
    $queries = preg_split('/;(?=([^"]*"[^"]*")*[^"]*$)/', $sql_content);
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        try {
            if ($conn->query($query)) {
                $success_count++;
                echo "<div style='color: green;'>✓ Executed: " . substr($query, 0, 60) . "...</div>";
            }
        } catch (Exception $e) {
            $error_count++;
            echo "<div style='color: red;'>✗ Error: " . $e->getMessage() . "</div>";
            echo "<div style='color: gray; font-size: 0.9em;'>Query: " . substr($query, 0, 100) . "...</div>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Summary</h3>";
    echo "<p>Successfully executed: $success_count queries</p>";
    echo "<p>Errors: $error_count</p>";
    
    // Verify tables exist
    echo "<h3>Verifying Tables</h3>";
    $tables_to_check = [
        'claim_activity_log',
        'organization_settings',
        'claim_batches',
        'authorization_usage_log',
        'remittance_advice',
        'remittance_details'
    ];
    
    foreach ($tables_to_check as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result && $result->num_rows > 0) {
            echo "<div style='color: green;'>✓ Table '$table' exists</div>";
        } else {
            echo "<div style='color: red;'>✗ Table '$table' not found</div>";
        }
    }
    
    echo "<hr>";
    echo "<p><a href='claim_management.php'>Go to Claim Management</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Setup failed: " . $e->getMessage() . "</div>";
}
?>