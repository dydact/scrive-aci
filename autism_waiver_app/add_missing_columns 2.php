<?php
require_once 'auth.php';
initScriveAuth();

echo "<h2>Adding Missing Columns to autism_client_enrollments</h2>";

try {
    // List of columns to add
    $columns_to_add = [
        "ADD COLUMN jurisdiction VARCHAR(50) NULL COMMENT 'Maryland county jurisdiction'",
        "ADD COLUMN parent_guardian VARCHAR(100) NULL COMMENT 'Parent or guardian name'", 
        "ADD COLUMN guardian_phone VARCHAR(20) NULL COMMENT 'Guardian phone number'",
        "ADD COLUMN school VARCHAR(100) NULL COMMENT 'School or educational institution'",
        "ADD COLUMN case_coordinator VARCHAR(100) NULL COMMENT 'Case coordinator name or ID'",
        "ADD COLUMN allowed_services TEXT NULL COMMENT 'Comma-separated list of allowed service types'",
        "ADD COLUMN ma_number VARCHAR(20) NULL COMMENT 'Individual MA number if different from program default'"
    ];

    foreach ($columns_to_add as $alter_statement) {
        echo "<p>Adding column: " . htmlspecialchars($alter_statement) . "</p>";
        
        try {
            $result = sqlStatement("ALTER TABLE autism_client_enrollments " . $alter_statement);
            echo "<p style='color: green;'>✓ Successfully added column</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠ Column might already exist or error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    echo "<h3>Updated Table Structure:</h3>";
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
    
    echo "<p style='color: green; font-weight: bold;'>✓ Table structure update complete!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?> 