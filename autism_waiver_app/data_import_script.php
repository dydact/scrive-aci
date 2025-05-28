<?php
/**
 * DATA IMPORT SCRIPT - American Caregivers Inc
 * 
 * This script helps import real employee and client data
 * into the production Scrive system for aci.dydact.io
 * 
 * Usage: Upload CSV files or provide data arrays to populate
 * the production database with real ACI employee and client information
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
 * Import staff members from CSV or array
 */
function importStaffMembers($staffData) {
    $pdo = getDbConnection();
    
    $sql = "INSERT INTO autism_staff_members 
            (employee_id, first_name, last_name, email, phone, hire_date, job_title, department, hourly_rate) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $imported = 0;
    
    foreach ($staffData as $staff) {
        try {
            $stmt->execute([
                $staff['employee_id'],
                $staff['first_name'],
                $staff['last_name'],
                $staff['email'],
                $staff['phone'] ?? null,
                $staff['hire_date'],
                $staff['job_title'] ?? 'Direct Care Staff',
                $staff['department'] ?? 'Clinical Services',
                $staff['hourly_rate'] ?? 15.00
            ]);
            $imported++;
        } catch (PDOException $e) {
            echo "Error importing staff {$staff['first_name']} {$staff['last_name']}: " . $e->getMessage() . "\n";
        }
    }
    
    return $imported;
}

/**
 * Import clients from CSV or array
 */
function importClients($clientData) {
    $pdo = getDbConnection();
    
    $sql = "INSERT INTO autism_clients 
            (client_ma_number, first_name, last_name, date_of_birth, gender, address_line1, 
             city, state, zip_code, phone, emergency_contact_name, emergency_contact_phone, 
             emergency_contact_relationship, school_name, primary_diagnosis) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $imported = 0;
    
    foreach ($clientData as $client) {
        try {
            $stmt->execute([
                $client['client_ma_number'],
                $client['first_name'],
                $client['last_name'],
                $client['date_of_birth'],
                $client['gender'] ?? 'Other',
                $client['address_line1'] ?? null,
                $client['city'] ?? null,
                $client['state'] ?? 'MD',
                $client['zip_code'] ?? null,
                $client['phone'] ?? null,
                $client['emergency_contact_name'] ?? null,
                $client['emergency_contact_phone'] ?? null,
                $client['emergency_contact_relationship'] ?? null,
                $client['school_name'] ?? null,
                $client['primary_diagnosis'] ?? 'Autism Spectrum Disorder'
            ]);
            $imported++;
        } catch (PDOException $e) {
            echo "Error importing client {$client['first_name']} {$client['last_name']}: " . $e->getMessage() . "\n";
        }
    }
    
    return $imported;
}

/**
 * Assign staff roles
 */
function assignStaffRoles($roleAssignments) {
    $pdo = getDbConnection();
    
    // Get staff and role mappings
    $staffMap = [];
    $staffStmt = $pdo->query("SELECT staff_id, email, first_name, last_name FROM autism_staff_members");
    while ($row = $staffStmt->fetch(PDO::FETCH_ASSOC)) {
        $staffMap[$row['email']] = $row;
    }
    
    $roleMap = [];
    $roleStmt = $pdo->query("SELECT role_id, role_name FROM autism_staff_roles");
    while ($row = $roleStmt->fetch(PDO::FETCH_ASSOC)) {
        $roleMap[$row['role_name']] = $row['role_id'];
    }
    
    $sql = "INSERT INTO autism_user_roles (staff_id, role_id, assigned_by) VALUES (?, ?, 1)";
    $stmt = $pdo->prepare($sql);
    $assigned = 0;
    
    foreach ($roleAssignments as $assignment) {
        if (isset($staffMap[$assignment['email']]) && isset($roleMap[$assignment['role_name']])) {
            try {
                $stmt->execute([
                    $staffMap[$assignment['email']]['staff_id'],
                    $roleMap[$assignment['role_name']]
                ]);
                $assigned++;
            } catch (PDOException $e) {
                echo "Error assigning role {$assignment['role_name']} to {$assignment['email']}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    return $assigned;
}

/**
 * Import client enrollments
 */
function importClientEnrollments($enrollmentData) {
    $pdo = getDbConnection();
    
    // Get client and program mappings
    $clientMap = [];
    $clientStmt = $pdo->query("SELECT client_id, client_ma_number FROM autism_clients");
    while ($row = $clientStmt->fetch(PDO::FETCH_ASSOC)) {
        $clientMap[$row['client_ma_number']] = $row['client_id'];
    }
    
    $programMap = [];
    $programStmt = $pdo->query("SELECT program_id, abbreviation FROM autism_programs");
    while ($row = $programStmt->fetch(PDO::FETCH_ASSOC)) {
        $programMap[$row['abbreviation']] = $row['program_id'];
    }
    
    $sql = "INSERT INTO autism_client_enrollments 
            (client_id, program_id, enrollment_date, authorization_start, authorization_end, 
             authorized_weekly_units, case_manager_id, county_jurisdiction) 
            VALUES (?, ?, ?, ?, ?, ?, 1, ?)";
    
    $stmt = $pdo->prepare($sql);
    $enrolled = 0;
    
    foreach ($enrollmentData as $enrollment) {
        if (isset($clientMap[$enrollment['client_ma_number']]) && isset($programMap[$enrollment['program']])) {
            try {
                $stmt->execute([
                    $clientMap[$enrollment['client_ma_number']],
                    $programMap[$enrollment['program']],
                    $enrollment['enrollment_date'],
                    $enrollment['authorization_start'],
                    $enrollment['authorization_end'],
                    $enrollment['authorized_weekly_units'],
                    $enrollment['county_jurisdiction'] ?? 'Baltimore County'
                ]);
                $enrolled++;
            } catch (PDOException $e) {
                echo "Error enrolling client {$enrollment['client_ma_number']}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    return $enrolled;
}

/**
 * Create initial treatment plans
 */
function createInitialTreatmentPlans($planData) {
    $pdo = getDbConnection();
    
    // Get client mapping
    $clientMap = [];
    $clientStmt = $pdo->query("SELECT client_id, client_ma_number, first_name, last_name FROM autism_clients");
    while ($row = $clientStmt->fetch(PDO::FETCH_ASSOC)) {
        $clientMap[$row['client_ma_number']] = $row;
    }
    
    $planSql = "INSERT INTO autism_treatment_plans 
                (client_id, plan_name, created_date, start_date, created_by, plan_overview) 
                VALUES (?, ?, CURDATE(), CURDATE(), 1, ?)";
    
    $goalSql = "INSERT INTO autism_treatment_goals 
                (plan_id, goal_category, goal_title, goal_description, target_criteria, priority, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, 1)";
    
    $planStmt = $pdo->prepare($planSql);
    $goalStmt = $pdo->prepare($goalSql);
    $created = 0;
    
    foreach ($planData as $plan) {
        if (isset($clientMap[$plan['client_ma_number']])) {
            try {
                $pdo->beginTransaction();
                
                // Create treatment plan
                $planStmt->execute([
                    $clientMap[$plan['client_ma_number']]['client_id'],
                    $plan['plan_name'],
                    $plan['plan_overview'] ?? 'Comprehensive autism intervention plan'
                ]);
                
                $planId = $pdo->lastInsertId();
                
                // Create goals
                foreach ($plan['goals'] as $goal) {
                    $goalStmt->execute([
                        $planId,
                        $goal['category'],
                        $goal['title'],
                        $goal['description'],
                        $goal['target_criteria'] ?? null,
                        $goal['priority'] ?? 'medium'
                    ]);
                }
                
                $pdo->commit();
                $created++;
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo "Error creating treatment plan for {$plan['client_ma_number']}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    return $created;
}

/**
 * Generate default passwords for staff
 */
function generateStaffPasswords() {
    $pdo = getDbConnection();
    
    $stmt = $pdo->query("SELECT staff_id, first_name, last_name, email FROM autism_staff_members WHERE is_active = TRUE");
    $passwords = [];
    
    while ($staff = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Generate a default password: FirstnameLastname2024!
        $defaultPassword = ucfirst(strtolower($staff['first_name'])) . ucfirst(strtolower($staff['last_name'])) . '2024!';
        
        $passwords[] = [
            'staff_id' => $staff['staff_id'],
            'email' => $staff['email'],
            'name' => $staff['first_name'] . ' ' . $staff['last_name'],
            'default_password' => $defaultPassword,
            'hashed_password' => password_hash($defaultPassword, PASSWORD_DEFAULT)
        ];
    }
    
    return $passwords;
}

/**
 * Main import function
 */
function runDataImport() {
    echo "<h1>American Caregivers Inc - Data Import Script</h1>\n";
    echo "<p>Ready to import real employee and client data for production deployment to aci.dydact.io</p>\n";
    
    // Example data structure - replace with real data
    echo "<h2>📊 Import Status</h2>\n";
    
    // Check if tables exist
    $pdo = getDbConnection();
    $tables = $pdo->query("SHOW TABLES LIKE 'autism_%'")->fetchAll();
    
    if (empty($tables)) {
        echo "<p style='color: red;'>❌ Database tables not found. Please run production_setup.sql first.</p>\n";
        return;
    }
    
    echo "<p style='color: green;'>✅ Database tables ready for import</p>\n";
    
    // Display data import template
    echo "<h2>📝 Data Import Templates</h2>\n";
    echo "<p>Please provide data in the following formats:</p>\n";
    
    // Staff data template
    echo "<h3>👥 Staff Members CSV Format:</h3>\n";
    echo "<pre>employee_id,first_name,last_name,email,phone,hire_date,job_title,department,hourly_rate\n";
    echo "ACI001,John,Smith,jsmith@americancaregivers.com,410-555-0101,2023-01-15,Direct Care Staff,Clinical Services,18.50\n";
    echo "ACI002,Jane,Johnson,jjohnson@americancaregivers.com,410-555-0102,2023-02-20,Case Manager,Clinical Services,25.00</pre>\n";
    
    // Client data template
    echo "<h3>👶 Clients CSV Format:</h3>\n";
    echo "<pre>client_ma_number,first_name,last_name,date_of_birth,gender,address_line1,city,state,zip_code,phone,emergency_contact_name,emergency_contact_phone,emergency_contact_relationship,school_name\n";
    echo "555-11-1234,Emma,Rodriguez,2015-03-15,F,123 Main St,Baltimore,MD,21201,410-555-1001,Maria Rodriguez,410-555-1002,Mother,Elementary School #1</pre>\n";
    
    // Role assignments template
    echo "<h3>🎭 Role Assignments CSV Format:</h3>\n";
    echo "<pre>email,role_name\n";
    echo "jsmith@americancaregivers.com,Direct Care Staff\n";
    echo "jjohnson@americancaregivers.com,Case Manager</pre>\n";
    
    // Enrollment template
    echo "<h3>📋 Client Enrollments CSV Format:</h3>\n";
    echo "<pre>client_ma_number,program,enrollment_date,authorization_start,authorization_end,authorized_weekly_units,county_jurisdiction\n";
    echo "555-11-1234,AW,2024-01-01,2024-01-01,2024-12-31,20,Baltimore County</pre>\n";
    
    echo "<hr>\n";
    echo "<h2>🚀 Next Steps</h2>\n";
    echo "<ol>\n";
    echo "<li>Prepare your real data files in the formats above</li>\n";
    echo "<li>Update this script with your actual data</li>\n";
    echo "<li>Run the import process</li>\n";
    echo "<li>Generate initial passwords for staff</li>\n";
    echo "<li>Deploy to aci.dydact.io</li>\n";
    echo "</ol>\n";
    
    // Generate sample passwords for existing demo users
    echo "<h2>🔐 Sample Password Generation</h2>\n";
    try {
        $passwords = generateStaffPasswords();
        if (!empty($passwords)) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
            echo "<tr><th>Name</th><th>Email</th><th>Default Password</th></tr>\n";
            foreach ($passwords as $pwd) {
                echo "<tr><td>{$pwd['name']}</td><td>{$pwd['email']}</td><td>{$pwd['default_password']}</td></tr>\n";
            }
            echo "</table>\n";
            echo "<p><strong>Note:</strong> Staff should change these passwords on first login.</p>\n";
        }
    } catch (Exception $e) {
        echo "<p>No staff members found to generate passwords for.</p>\n";
    }
}

// Run the import interface
if (php_sapi_name() === 'cli') {
    runDataImport();
} else {
    echo "<!DOCTYPE html><html><head><title>ACI Data Import</title></head><body>";
    runDataImport();
    echo "</body></html>";
}

?> 