<?php
require_once 'auth.php';
initScriveAuth();

echo "<h2>Programs Table Check</h2>";

try {
    // Check structure of autism_programs
    echo "<h3>Structure of autism_programs table:</h3>";
    $structure = sqlStatement("DESCRIBE autism_programs");
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
    
    // Check data in autism_programs
    echo "<h3>Data in autism_programs table:</h3>";
    $programs = sqlStatement("SELECT * FROM autism_programs");
    echo "<table border='1' style='border-collapse: collapse;'>";
    
    // Get column names
    $first_row = sqlFetchArray($programs);
    if ($first_row) {
        echo "<tr>";
        foreach (array_keys($first_row) as $col) {
            if (!is_numeric($col)) echo "<th>" . htmlspecialchars($col) . "</th>";
        }
        echo "</tr>";
        
        // Reset and show all data
        $programs = sqlStatement("SELECT * FROM autism_programs");
        while ($row = sqlFetchArray($programs)) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                if (!is_numeric($key)) echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td>No data found</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?> 