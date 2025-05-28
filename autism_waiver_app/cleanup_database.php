<?php
require_once 'auth.php';
initScriveAuth();

echo "<h2>Database Cleanup</h2>";

try {
    // Remove test clients
    $result = sqlStatement("DELETE FROM patient_data WHERE fname IN ('Test', 'Debug') OR pid = 0");
    echo "<p>✅ Cleaned up test patient records</p>";
    
    // Remove any test enrollments
    $result = sqlStatement("DELETE FROM autism_client_enrollments WHERE client_id NOT IN (SELECT pid FROM patient_data)");
    echo "<p>✅ Cleaned up orphaned enrollments</p>";
    
    // Check current counts
    $client_count = sqlQuery("SELECT COUNT(*) as count FROM patient_data");
    $enrollment_count = sqlQuery("SELECT COUNT(*) as count FROM autism_client_enrollments");
    
    echo "<h3>Current Database Status:</h3>";
    echo "<p>Total Clients: " . $client_count['count'] . "</p>";
    echo "<p>Total Enrollments: " . $enrollment_count['count'] . "</p>";
    
    echo "<p style='color: green; font-weight: bold;'>✅ Database cleaned and ready for client creation!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?> 