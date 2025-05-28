<?php

require_once 'auth.php';
require_once 'api.php';

initScriveAuth();
$currentUser = getCurrentScriveUser();
$api = new OpenEMRAPI();

echo "<h2>Debug Add Client</h2>";

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $client_data = [
            'fname' => $_POST['fname'] ?? '',
            'lname' => $_POST['lname'] ?? '',
            'DOB' => $_POST['dob'] ?? null,
            'sex' => $_POST['sex'] ?? '',
            'phone_home' => $_POST['phone_home'] ?? '',
            'email' => $_POST['email'] ?? '',
            'street' => $_POST['street'] ?? '',
            'city' => $_POST['city'] ?? '',
            'state' => $_POST['state'] ?? 'MD',
            'postal_code' => $_POST['postal_code'] ?? '',
            'providerID' => $currentUser['user_id'] ?? 1
        ];
        
        echo "<h3>Step 1: Creating basic client...</h3>";
        echo "<p>Data: " . json_encode($client_data) . "</p>";
        
        // Test basic client creation directly
        $method = new ReflectionMethod($api, 'createBasicClient');
        $method->setAccessible(true);
        
        $patientId = $method->invoke($api, $client_data);
        echo "<p style='color: green;'>✅ Basic client created with ID: {$patientId}</p>";
        
        // Check if waiver data was provided
        $waiver_data = [
            'waiver_program' => $_POST['waiver_program'] ?? '',
            'jurisdiction' => $_POST['jurisdiction'] ?? '',
            'parent_guardian' => $_POST['parent_guardian'] ?? '',
            'guardian_phone' => $_POST['guardian_phone'] ?? ''
        ];
        
        if (!empty($waiver_data['waiver_program'])) {
            echo "<h3>Step 2: Creating autism waiver enrollment...</h3>";
            echo "<p>Waiver Data: " . json_encode($waiver_data) . "</p>";
            
            // Get program_id
            $program = sqlQuery("SELECT program_id FROM autism_programs WHERE abbreviation = ?", [$waiver_data['waiver_program']]);
            echo "<p>Program lookup result: " . json_encode($program) . "</p>";
            
            if ($program) {
                $enrollmentData = [
                    'client_id' => $patientId,
                    'program_id' => $program['program_id'],
                    'enrollment_date' => date('Y-m-d'),
                    'status' => 'active',
                    'ma_eligible' => 1,
                    'jurisdiction' => $waiver_data['jurisdiction'],
                    'parent_guardian' => $waiver_data['parent_guardian'],
                    'guardian_phone' => $waiver_data['guardian_phone'],
                    'created_by' => $currentUser['user_id'] ?? 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                echo "<p>Enrollment Data: " . json_encode($enrollmentData) . "</p>";
                
                $fields = implode(', ', array_keys($enrollmentData));
                $placeholders = str_repeat('?,', count($enrollmentData) - 1) . '?';
                $values = array_values($enrollmentData);
                
                $sql = "INSERT INTO autism_client_enrollments ({$fields}) VALUES ({$placeholders})";
                echo "<p>SQL: " . htmlspecialchars($sql) . "</p>";
                
                $result = sqlStatement($sql, $values);
                echo "<p style='color: green;'>✅ Autism enrollment created!</p>";
            } else {
                echo "<p style='color: red;'>❌ Program not found for: {$waiver_data['waiver_program']}</p>";
            }
        } else {
            echo "<h3>Step 2: No waiver data provided, skipping enrollment</h3>";
        }
        
        echo "<p style='color: green; font-weight: bold;'>🎉 SUCCESS! Client creation completed.</p>";
        
    } else {
        echo "<form method='POST'>";
        echo "<p>First Name: <input type='text' name='fname' value='Debug' required></p>";
        echo "<p>Last Name: <input type='text' name='lname' value='Test' required></p>";
        echo "<p>DOB: <input type='date' name='dob' value='2010-01-01' required></p>";
        echo "<p>Waiver Program: <select name='waiver_program'><option value=''>None</option><option value='AW'>AW</option></select></p>";
        echo "<p>Jurisdiction: <input type='text' name='jurisdiction' value='Baltimore County'></p>";
        echo "<p>Parent/Guardian: <input type='text' name='parent_guardian' value='Test Parent'></p>";
        echo "<p><input type='submit' value='Create Client'></p>";
        echo "</form>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace: " . htmlspecialchars($e->getTraceAsString()) . "</p>";
}

?> 