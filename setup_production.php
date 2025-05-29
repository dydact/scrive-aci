<?php
/**
 * Scrive ACI Production Setup Script
 * This script sets up the initial superadmin user and imports existing data
 * 
 * Run this once after deployment: php setup_production.php
 */

require_once 'src/config.php';

echo "=== Scrive ACI Production Setup ===\n\n";

try {
    $pdo = getDatabase();
    
    echo "1. Creating database schema...\n";
    
    // Import the production setup SQL
    $sqlFile = dirname(__FILE__) . '/autism_waiver_app/production_setup.sql';
    if (!file_exists($sqlFile)) {
        die("ERROR: SQL file not found at: $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by delimiter to handle multiple statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && stripos($statement, 'SELECT') !== 0) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Log but continue - tables might already exist
                error_log("SQL Warning: " . $e->getMessage());
            }
        }
    }
    
    echo "   ✓ Database schema created\n\n";
    
    // Now check if setup already completed
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM autism_staff_members WHERE email = 'admin@acgcares.com'");
        $result = $stmt->fetch();
        $stmt->closeCursor(); // Close cursor to avoid buffering issues
        
        if ($result['cnt'] > 0) {
            die("Setup already completed. Superadmin user exists.\n");
        }
    } catch (PDOException $e) {
        // Table might not exist yet, continue
    }
    
    echo "2. Creating superadmin user...\n";
    
    // Create the superadmin staff member
    $stmt = $pdo->prepare("
        INSERT INTO autism_staff_members 
        (employee_id, first_name, last_name, email, phone, hire_date, job_title, department, hourly_rate, is_active) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        'ADMIN001',
        'System',
        'Administrator',
        'admin@acgcares.com',
        '301-408-0100',
        date('Y-m-d'),
        'System Administrator',
        'Administration',
        0.00,  // No hourly rate for admin
        1
    ]);
    
    $adminId = $pdo->lastInsertId();
    
    // Create user authentication record
    $password = bin2hex(random_bytes(8)); // Generate random password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Create users table for authentication
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            staff_id INT NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (staff_id) REFERENCES autism_staff_members(staff_id),
            INDEX idx_username (username)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $stmt = $pdo->prepare("
        INSERT INTO autism_users (username, password_hash, staff_id, is_active) 
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([
        'admin',
        $hashedPassword,
        $adminId,
        1
    ]);
    
    // Assign administrator role
    $stmt = $pdo->prepare("
        INSERT INTO autism_user_roles (staff_id, role_id, assigned_by) 
        VALUES (?, 1, ?)  -- role_id 1 is Administrator
    ");
    
    $stmt->execute([$adminId, $adminId]);
    
    echo "   ✓ Superadmin user created\n\n";
    
    echo "3. Setting up additional staff members...\n";
    
    // Add some key staff members
    $staff = [
        ['EMP001', 'Mary', 'Emah', 'memah@acgcares.com', '301-408-0101', 'Chief Executive Officer', 'Executive', 0.00],
        ['EMP002', 'Amanda', 'Georgie', 'ageorgie@acgcares.com', '301-408-0102', 'Executive Staff', 'Executive', 0.00],
        ['EMP003', 'John', 'Smith', 'jsmith@acgcares.com', '301-408-0103', 'Clinical Supervisor', 'Clinical', 35.00],
        ['EMP004', 'Sarah', 'Johnson', 'sjohnson@acgcares.com', '301-408-0104', 'Case Manager', 'Clinical', 28.00],
        ['EMP005', 'Michael', 'Davis', 'mdavis@acgcares.com', '301-408-0105', 'Direct Care Professional', 'Direct Care', 18.00]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO autism_staff_members 
        (employee_id, first_name, last_name, email, phone, hire_date, job_title, department, hourly_rate, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($staff as $s) {
        try {
            $stmt->execute([
                $s[0], $s[1], $s[2], $s[3], $s[4],
                date('Y-m-d', strtotime('-' . rand(30, 365) . ' days')),
                $s[5], $s[6], $s[7], 1
            ]);
        } catch (PDOException $e) {
            // Skip if already exists
            error_log("Staff member already exists: " . $s[3]);
        }
    }
    
    echo "   ✓ Staff members created\n\n";
    
    echo "4. Creating sample clients...\n";
    
    // Add sample clients (anonymized)
    $clients = [
        ['MA001234567', 'John', 'Doe', '2010-05-15', 'M', '123 Main St', 'Silver Spring', 'MD', '20901'],
        ['MA001234568', 'Jane', 'Smith', '2008-08-22', 'F', '456 Oak Ave', 'Columbia', 'MD', '21044'],
        ['MA001234569', 'Michael', 'Johnson', '2012-03-10', 'M', '789 Pine St', 'Bethesda', 'MD', '20814'],
        ['MA001234570', 'Emily', 'Williams', '2009-11-30', 'F', '321 Elm St', 'Rockville', 'MD', '20850'],
        ['MA001234571', 'David', 'Brown', '2011-07-18', 'M', '654 Maple Dr', 'Germantown', 'MD', '20874']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO autism_clients 
        (client_ma_number, first_name, last_name, date_of_birth, gender, address_line1, city, state, zip_code, 
         emergency_contact_name, emergency_contact_phone, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($clients as $c) {
        try {
            $stmt->execute([
                $c[0], $c[1], $c[2], $c[3], $c[4], $c[5], $c[6], $c[7], $c[8],
                'Emergency Contact', '301-555-0100', 1
            ]);
        } catch (PDOException $e) {
            // Skip if already exists
            error_log("Client already exists: " . $c[0]);
        }
    }
    
    echo "   ✓ Sample clients created\n\n";
    
    echo "=== SETUP COMPLETED SUCCESSFULLY ===\n\n";
    echo "IMPORTANT - Save these credentials:\n";
    echo "================================\n";
    echo "URL: https://aci.dydact.io/src/login.php\n";
    echo "Username: admin\n";
    echo "Password: $password\n";
    echo "================================\n\n";
    echo "Please change the password after first login!\n\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?> 