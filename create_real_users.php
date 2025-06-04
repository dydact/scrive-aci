<?php
// Script to create real ACG user accounts
require_once '/var/www/localhost/htdocs/src/config.php';

try {
    $pdo = getDatabase();
    
    // First, let's modify the access_level to support level 6 for supreme admin
    $pdo->exec("ALTER TABLE autism_users MODIFY COLUMN access_level INT DEFAULT 1");
    
    // Delete test users
    $pdo->exec("DELETE FROM autism_users WHERE email LIKE '%@aci.com'");
    
    // User data with their specific roles
    $users = [
        [
            'username' => 'frank',
            'email' => 'frank@acgcares.com',
            'password' => 'Supreme2024!',
            'access_level' => 6, // Supreme Admin
            'full_name' => 'Frank (Supreme Admin)',
            'role' => 'Supreme Administrator'
        ],
        [
            'username' => 'mary.emah',
            'email' => 'mary.emah@acgcares.com',
            'password' => 'CEO2024!',
            'access_level' => 5, // Admin
            'full_name' => 'Mary Emah',
            'role' => 'Chief Executive Officer'
        ],
        [
            'username' => 'drukpeh',
            'email' => 'drukpeh@duck.com',
            'password' => 'Executive2024!',
            'access_level' => 5, // Admin
            'full_name' => 'Dr. Ukpeh',
            'role' => 'Executive'
        ],
        [
            'username' => 'amanda.georgi',
            'email' => 'amanda.georgi@acgcares.com',
            'password' => 'HR2024!',
            'access_level' => 4, // Supervisor
            'full_name' => 'Amanda Georgi',
            'role' => 'Human Resources Officer'
        ],
        [
            'username' => 'edwin.recto',
            'email' => 'edwin.recto@acgcares.com',
            'password' => 'Clinical2024!',
            'access_level' => 4, // Supervisor
            'full_name' => 'Edwin Recto',
            'role' => 'Site Supervisor / Clinical Lead'
        ],
        [
            'username' => 'pam.pastor',
            'email' => 'pam.pastor@acgcares.com',
            'password' => 'Billing2024!',
            'access_level' => 4, // Supervisor (Billing Admin)
            'full_name' => 'Pam Pastor',
            'role' => 'Billing Administrator'
        ],
        [
            'username' => 'yanika.crosse',
            'email' => 'yanika.crosse@acgcares.com',
            'password' => 'Billing2024!',
            'access_level' => 4, // Supervisor (Billing Admin)
            'full_name' => 'Yanika Crosse',
            'role' => 'Billing Administrator'
        ],
        [
            'username' => 'alvin.ukpeh',
            'email' => 'alvin.ukpeh@acgcares.com',
            'password' => 'SysAdmin2024!',
            'access_level' => 5, // Admin (but less than Frank)
            'full_name' => 'Alvin Ukpeh',
            'role' => 'System Administrator'
        ]
    ];
    
    // Create user permissions table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_user_permissions (
            user_id INT,
            permission VARCHAR(100),
            granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES autism_users(id) ON DELETE CASCADE,
            PRIMARY KEY (user_id, permission)
        )
    ");
    
    // Add role column to users table if it doesn't exist
    $pdo->exec("ALTER TABLE autism_users ADD COLUMN IF NOT EXISTS role VARCHAR(100) DEFAULT NULL");
    
    // Insert users
    $stmt = $pdo->prepare("
        INSERT INTO autism_users (username, email, password_hash, access_level, full_name, role, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    foreach ($users as $user) {
        $passwordHash = password_hash($user['password'], PASSWORD_DEFAULT);
        $stmt->execute([
            $user['username'],
            $user['email'],
            $passwordHash,
            $user['access_level'],
            $user['full_name'],
            $user['role']
        ]);
        echo "Created user: {$user['email']} with password: {$user['password']}\n";
    }
    
    // Grant specific permissions
    $permissions = [
        'frank@acgcares.com' => ['supreme_admin', 'all_access', 'system_config', 'database_management'],
        'mary.emah@acgcares.com' => ['executive_dashboard', 'financial_overview', 'reports_all', 'staff_overview'],
        'drukpeh@duck.com' => ['executive_dashboard', 'financial_overview', 'reports_all', 'staff_overview'],
        'amanda.georgi@acgcares.com' => ['hr_management', 'employee_records', 'payroll_access', 'staff_management'],
        'edwin.recto@acgcares.com' => ['clinical_oversight', 'waiver_administration', 'note_approval', 'treatment_plans'],
        'pam.pastor@acgcares.com' => ['billing_management', 'hours_entry', 'claims_processing', 'payment_posting'],
        'yanika.crosse@acgcares.com' => ['billing_management', 'hours_entry', 'claims_processing', 'payment_posting'],
        'alvin.ukpeh@acgcares.com' => ['system_admin', 'user_management', 'technical_support', 'reports_all']
    ];
    
    $permStmt = $pdo->prepare("
        INSERT INTO autism_user_permissions (user_id, permission) 
        SELECT id, ? FROM autism_users WHERE email = ?
    ");
    
    foreach ($permissions as $email => $perms) {
        foreach ($perms as $perm) {
            $permStmt->execute([$perm, $email]);
        }
    }
    
    echo "\nAll users created successfully!\n";
    echo "\nAccess Levels:\n";
    echo "Level 6: Supreme Admin (Frank only)\n";
    echo "Level 5: Admin (CEO, Executive, System Admin)\n";
    echo "Level 4: Supervisor (HR, Clinical, Billing)\n";
    echo "Level 3: Case Manager\n";
    echo "Level 2: Direct Support Professional\n";
    echo "Level 1: Technician\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>