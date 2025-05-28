<?php
require_once 'auth.php';
initScriveAuth();

echo "<h2>Table Structure Check</h2>";

try {
    // Check structure of autism_client_enrollments
    echo "<h3>Structure of autism_client_enrollments table:</h3>";
    $structure = sqlStatement("DESCRIBE autism_client_enrollments");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = sqlFetchArray($structure)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check what other autism tables exist
    echo "<h3>All autism tables:</h3>";
    $autism_tables = sqlStatement("SHOW TABLES LIKE 'autism_%'");
    echo "<ul>";
    while ($row = sqlFetchArray($autism_tables)) {
        $table_name = array_values($row)[0];
        echo "<li>" . htmlspecialchars($table_name) . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?> 