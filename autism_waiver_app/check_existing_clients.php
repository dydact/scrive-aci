<?php
require_once 'auth.php';
initScriveAuth();

echo "<h2>All Existing Clients</h2>";

try {
    // Get all clients
    $clients = sqlStatement("SELECT pid, fname, lname, DOB, pubpid, regdate FROM patient_data ORDER BY pid DESC LIMIT 10");
    
    echo "<h3>Recent Clients in patient_data:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>PID</th><th>Name</th><th>DOB</th><th>Public ID</th><th>Registration Date</th></tr>";
    
    while ($client = sqlFetchArray($clients)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($client['pid']) . "</td>";
        echo "<td>" . htmlspecialchars($client['fname'] . ' ' . $client['lname']) . "</td>";
        echo "<td>" . htmlspecialchars($client['DOB']) . "</td>";
        echo "<td>" . htmlspecialchars($client['pubpid']) . "</td>";
        echo "<td>" . htmlspecialchars($client['regdate']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?> 