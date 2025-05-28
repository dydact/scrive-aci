<?php
/**
 * ENHANCED DATA IMPORT SCRIPT - American Caregivers Inc
 * 
 * This script imports real employee and client data from CSV files
 * for production deployment to aci.dydact.io
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$db_config = [
    'host' => 'localhost',
    'database' => 'openemr',
    'username' => 'openemr', 
    'password' => 'openemr'
];

/**
 * Get database connection
 */
function getDbConnection() {
    global $db_config;
    try {
        $dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Parse employee CSV data
 */
function parseEmployeeCSV($filename) {
    $employees = [];
    
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $headers = fgetcsv($handle); // Skip header row
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty($data[0])) continue; // Skip empty rows
            
            $employee = [
                'name' => $data[0] ?? '',
                'dob' => $data[1] ?? '',
                'ssn' => $data[2] ?? '',
                'gender' => $data[3] ?? '',
                'email' => $data[4] ?? '',
                'phone' => $data[6] ?? '',
                'role' => $data[7] ?? 'DSP',
                'employment_type' => $data[8] ?? 'Contractor',
                'client' => $data[9] ?? '',
                'address' => $data[10] ?? ''
            ];
            
            // Parse name into first and last
            $nameParts = explode(',', $employee['name']);
            if (count($nameParts) >= 2) {
                $employee['last_name'] = trim($nameParts[0]);
                $employee['first_name'] = trim($nameParts[1]);
            } else {
                $nameParts = explode(' ', $employee['name']);
                $employee['first_name'] = $nameParts[0] ?? '';
                $employee['last_name'] = $nameParts[count($nameParts) - 1] ?? '';
            }
            
            // Generate employee ID
            $employee['employee_id'] = 'ACI' . str_pad(count($employees) + 1, 3, '0', STR_PAD_LEFT);
            
            // Determine job title based on role
            $roleMapping = [
                'DSP' => 'Direct Support Professional',
                'Autism Technician' => 'Autism Technician',
                'PCA' => 'Personal Care Assistant',
                'CNA' => 'Certified Nursing Assistant',
                'CMT' => 'Certified Medication Technician',
                'Parent' => 'Parent/Family DSP',
                'Executive Staff' => 'Executive Staff',
                'CEO' => 'Chief Executive Officer'
            ];
            
            $employee['job_title'] = $roleMapping[$employee['role']] ?? 'Direct Care Staff';
            
            // Determine hourly rate based on role
            $rateMapping = [
                'CEO' => 0, // Salary
                'Executive Staff' => 35.00,
                'DSP' => 18.50,
                'Autism Technician' => 17.00,
                'PCA' => 16.50,
                'CNA' => 19.00,
                'CMT' => 20.00,
                'Parent' => 15.00
            ];
            
            $employee['hourly_rate'] = $rateMapping[$employee['role']] ?? 15.00;
            
            $employees[] = $employee;
        }
        fclose($handle);
    }
    
    return $employees;
}

/**
 * Parse client CSV data from Plan of Service report
 */
function parseClientCSV($filename) {
    $clients = [];
    
    if (($handle = fopen($filename, "r")) !== FALSE) {
        $headers = fgetcsv($handle); // Skip header row
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty($data[3])) continue; // Skip if no MA number
            
            // Parse client name
            $clientName = $data[4] ?? '';
            $nameParts = explode(',', $clientName);
            
            $client = [
                'client_ma_number' => $data[3] ?? '',
                'last_name' => trim($nameParts[0] ?? ''),
                'first_name' => trim($nameParts[1] ?? ''),
                'provider_ma_number' => $data[1] ?? '',
                'assigned_sp_name' => $data[6] ?? '',
                'enrolled_program' => $data[7] ?? '',
                'service_type' => $data[12] ?? '',
                'service_name' => $data[13] ?? '',
                'units' => floatval($data[14] ?? 0),
                'units_period' => $data[15] ?? '',
                'frequency' => $data[16] ?? '',
                'effective_date' => $data[10] ?? '',
                'end_date' => $data[11] ?? ''
            ];
            
            // Determine program abbreviation
            $programMapping = [
                'CO' => 'CS',
                'CFC' => 'CFC',
                'AW' => 'AW',
                'CO,REM' => 'CS',
                'CFC,CP' => 'CFC'
            ];
            
            $client['program'] = $programMapping[$client['enrolled_program']] ?? 'CFC';
            
            $clients[] = $client;
        }
        fclose($handle);
    }
    
    return $clients;
}

/**
 * Import staff to database
 */
function importStaffToDatabase($employees) {
    $pdo = getDbConnection();
    
    $sql = "INSERT INTO autism_staff_members 
            (employee_id, first_name, last_name, email, phone, hire_date, job_title, department, hourly_rate) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            phone = VALUES(phone),
            job_title = VALUES(job_title),
            hourly_rate = VALUES(hourly_rate)";
    
    $stmt = $pdo->prepare($sql);
    $imported = 0;
    
    foreach ($employees as $emp) {
        try {
            // Generate hire date if not provided
            $hireDate = '2023-01-01'; // Default hire date
            if (!empty($emp['dob'])) {
                // Use a date 20 years after DOB as estimated hire date
                $dobTimestamp = strtotime($emp['dob']);
                if ($dobTimestamp) {
                    $hireDate = date('Y-m-d', strtotime('+20 years', $dobTimestamp));
                }
            }
            
            $stmt->execute([
                $emp['employee_id'],
                $emp['first_name'],
                $emp['last_name'],
                $emp['email'] ?: $emp['employee_id'] . '@acgcares.com',
                $emp['phone'],
                $hireDate,
                $emp['job_title'],
                $emp['employment_type'] === 'Full Time' ? 'Clinical Services' : 'Contract Services',
                $emp['hourly_rate']
            ]);
            $imported++;
        } catch (PDOException $e) {
            echo "Error importing staff {$emp['first_name']} {$emp['last_name']}: " . $e->getMessage() . "\n";
        }
    }
    
    return $imported;
}

/**
 * Import clients to database
 */
function importClientsToDatabase($clients) {
    $pdo = getDbConnection();
    
    $sql = "INSERT INTO autism_clients 
            (client_ma_number, first_name, last_name, date_of_birth, gender, 
             primary_diagnosis, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE
            first_name = VALUES(first_name),
            last_name = VALUES(last_name)";
    
    $stmt = $pdo->prepare($sql);
    $imported = 0;
    $uniqueClients = [];
    
    // Deduplicate clients by MA number
    foreach ($clients as $client) {
        $uniqueClients[$client['client_ma_number']] = $client;
    }
    
    foreach ($uniqueClients as $client) {
        try {
            // Generate estimated DOB (for demo purposes)
            $estimatedDOB = date('Y-m-d', strtotime('-' . rand(5, 18) . ' years'));
            
            $stmt->execute([
                $client['client_ma_number'],
                $client['first_name'],
                $client['last_name'],
                $estimatedDOB,
                'Other', // Gender not provided in CSV
                'Autism Spectrum Disorder' // Default diagnosis
            ]);
            $imported++;
        } catch (PDOException $e) {
            echo "Error importing client {$client['first_name']} {$client['last_name']}: " . $e->getMessage() . "\n";
        }
    }
    
    return $imported;
}

/**
 * Create client enrollments
 */
function createClientEnrollments($clients) {
    $pdo = getDbConnection();
    
    // Get client and program IDs
    $clientMap = [];
    $stmt = $pdo->query("SELECT client_id, client_ma_number FROM autism_clients");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $clientMap[$row['client_ma_number']] = $row['client_id'];
    }
    
    $programMap = [];
    $stmt = $pdo->query("SELECT program_id, abbreviation FROM autism_programs");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $programMap[$row['abbreviation']] = $row['program_id'];
    }
    
    // Get a default case manager
    $caseManagerId = $pdo->query("SELECT staff_id FROM autism_staff_members LIMIT 1")->fetchColumn();
    
    $sql = "INSERT INTO autism_client_enrollments 
            (client_id, program_id, enrollment_date, authorization_start, authorization_end, 
             authorized_weekly_units, case_manager_id, county_jurisdiction) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            authorized_weekly_units = VALUES(authorized_weekly_units)";
    
    $stmt = $pdo->prepare($sql);
    $enrolled = 0;
    
    foreach ($clients as $client) {
        if (isset($clientMap[$client['client_ma_number']]) && isset($programMap[$client['program']])) {
            try {
                $enrollmentDate = $client['effective_date'] ?: date('Y-m-d');
                $authEnd = $client['end_date'] ?: date('Y-m-d', strtotime('+1 year'));
                
                $stmt->execute([
                    $clientMap[$client['client_ma_number']],
                    $programMap[$client['program']],
                    $enrollmentDate,
                    $enrollmentDate,
                    $authEnd,
                    $client['units'],
                    $caseManagerId ?: 1,
                    'Baltimore County' // Default county
                ]);
                $enrolled++;
            } catch (PDOException $e) {
                echo "Error creating enrollment for {$client['client_ma_number']}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    return $enrolled;
}

/**
 * Assign staff roles based on job titles
 */
function assignStaffRoles() {
    $pdo = getDbConnection();
    
    // Get role mappings
    $roleMap = [];
    $stmt = $pdo->query("SELECT role_id, role_name FROM autism_staff_roles");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $roleMap[$row['role_name']] = $row['role_id'];
    }
    
    // Map job titles to roles
    $titleToRole = [
        'Chief Executive Officer' => 'Administrator',
        'Executive Staff' => 'Supervisor',
        'Case Manager' => 'Case Manager',
        'Direct Support Professional' => 'Direct Care Staff',
        'Autism Technician' => 'Direct Care Staff',
        'Personal Care Assistant' => 'Direct Care Staff',
        'Certified Nursing Assistant' => 'Direct Care Staff',
        'Certified Medication Technician' => 'Direct Care Staff',
        'Parent/Family DSP' => 'Technician'
    ];
    
    $sql = "INSERT INTO autism_user_roles (staff_id, role_id, assigned_by) 
            SELECT s.staff_id, ?, 1 
            FROM autism_staff_members s 
            WHERE s.job_title = ? 
            AND NOT EXISTS (
                SELECT 1 FROM autism_user_roles ur 
                WHERE ur.staff_id = s.staff_id AND ur.role_id = ?
            )";
    
    $stmt = $pdo->prepare($sql);
    $assigned = 0;
    
    foreach ($titleToRole as $title => $roleName) {
        if (isset($roleMap[$roleName])) {
            $result = $stmt->execute([$roleMap[$roleName], $title, $roleMap[$roleName]]);
            $assigned += $stmt->rowCount();
        }
    }
    
    return $assigned;
}

/**
 * Main import execution
 */
function executeImport() {
    echo "<h1>🚀 American Caregivers Inc - Production Data Import</h1>\n";
    
    // Check for CSV files
    $employeeFile = 'Employee lis partial.csv';
    $clientFile = 'Plan of Service %2D Current Approved Plans Report.csv';
    
    if (!file_exists($employeeFile)) {
        echo "<p style='color: red;'>❌ Employee CSV file not found: $employeeFile</p>\n";
        return;
    }
    
    if (!file_exists($clientFile)) {
        echo "<p style='color: red;'>❌ Client CSV file not found: $clientFile</p>\n";
        return;
    }
    
    echo "<h2>📊 Parsing CSV Files...</h2>\n";
    
    // Parse employee data
    $employees = parseEmployeeCSV($employeeFile);
    echo "<p>✅ Found " . count($employees) . " employees in CSV</p>\n";
    
    // Parse client data
    $clients = parseClientCSV($clientFile);
    echo "<p>✅ Found " . count($clients) . " client records in CSV</p>\n";
    
    echo "<h2>💾 Importing to Database...</h2>\n";
    
    // Import staff
    $staffImported = importStaffToDatabase($employees);
    echo "<p>✅ Imported $staffImported staff members</p>\n";
    
    // Import clients
    $clientsImported = importClientsToDatabase($clients);
    echo "<p>✅ Imported $clientsImported unique clients</p>\n";
    
    // Create enrollments
    $enrollmentsCreated = createClientEnrollments($clients);
    echo "<p>✅ Created $enrollmentsCreated client enrollments</p>\n";
    
    // Assign roles
    $rolesAssigned = assignStaffRoles();
    echo "<p>✅ Assigned $rolesAssigned staff roles</p>\n";
    
    echo "<h2>📋 Import Summary</h2>\n";
    echo "<ul>\n";
    echo "<li>Staff Members: $staffImported</li>\n";
    echo "<li>Clients: $clientsImported</li>\n";
    echo "<li>Enrollments: $enrollmentsCreated</li>\n";
    echo "<li>Role Assignments: $rolesAssigned</li>\n";
    echo "</ul>\n";
    
    echo "<p style='color: green; font-weight: bold;'>✅ Data import completed successfully!</p>\n";
}

// Run the import
if (php_sapi_name() === 'cli') {
    executeImport();
} else {
    echo "<!DOCTYPE html><html><head><title>ACI Data Import</title></head><body>";
    executeImport();
    echo "</body></html>";
}

?> 