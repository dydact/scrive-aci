<?php
require_once 'auth.php';
initScriveAuth();

echo "<h2>Client Verification</h2>";

try {
    // Check if John Smith was created
    $client = sqlQuery("SELECT * FROM patient_data WHERE fname = 'John' AND lname = 'Smith'");
    
    if ($client) {
        echo "<h3>✅ Client Found in patient_data:</h3>";
        echo "<p>PID: " . $client['pid'] . "</p>";
        echo "<p>Name: " . htmlspecialchars($client['fname'] . ' ' . $client['lname']) . "</p>";
        echo "<p>DOB: " . htmlspecialchars($client['DOB']) . "</p>";
        echo "<p>Public ID: " . htmlspecialchars($client['pubpid']) . "</p>";
        
        // Check enrollment data
        $enrollment = sqlQuery("SELECT e.*, p.name as program_name, p.abbreviation, p.ma_number as program_ma 
                               FROM autism_client_enrollments e 
                               JOIN autism_programs p ON e.program_id = p.program_id 
                               WHERE e.client_id = ?", [$client['pid']]);
        
        if ($enrollment) {
            echo "<h3>✅ Autism Waiver Enrollment Found:</h3>";
            echo "<p>Program: " . htmlspecialchars($enrollment['program_name'] . ' (' . $enrollment['abbreviation'] . ')') . "</p>";
            echo "<p>Program MA#: " . htmlspecialchars($enrollment['program_ma']) . "</p>";
            echo "<p>Enrollment Date: " . htmlspecialchars($enrollment['enrollment_date']) . "</p>";
            echo "<p>Status: " . htmlspecialchars($enrollment['status']) . "</p>";
            echo "<p>Jurisdiction: " . htmlspecialchars($enrollment['jurisdiction']) . "</p>";
            echo "<p>Parent/Guardian: " . htmlspecialchars($enrollment['parent_guardian']) . "</p>";
            echo "<p>School: " . htmlspecialchars($enrollment['school']) . "</p>";
            echo "<p>Case Coordinator: " . htmlspecialchars($enrollment['case_coordinator']) . "</p>";
            echo "<p>Allowed Services: " . htmlspecialchars($enrollment['allowed_services']) . "</p>";
        } else {
            echo "<h3>❌ No Autism Waiver Enrollment Found</h3>";
        }
        
    } else {
        echo "<h3>❌ No Client Found</h3>";
    }
    
    // Show total counts
    $client_count = sqlQuery("SELECT COUNT(*) as count FROM patient_data");
    $enrollment_count = sqlQuery("SELECT COUNT(*) as count FROM autism_client_enrollments");
    
    echo "<h3>Database Counts:</h3>";
    echo "<p>Total Clients: " . $client_count['count'] . "</p>";
    echo "<p>Total Enrollments: " . $enrollment_count['count'] . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?> 