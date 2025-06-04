<?php
/**
 * Production Update Script for ACI System
 * Run this script to update the production database with new users and settings
 * 
 * Usage: php update_production.php
 */

echo "=== ACI Production Update Script ===\n\n";

// Load configuration
require_once '/var/www/localhost/htdocs/src/config.php';

try {
    echo "Connecting to database...\n";
    $pdo = getDatabase();
    echo "✓ Connected successfully\n\n";
    
    // Step 1: Add role column if it doesn't exist
    echo "Step 1: Updating database schema...\n";
    try {
        $pdo->exec("ALTER TABLE autism_users ADD COLUMN role VARCHAR(100) DEFAULT NULL");
        echo "✓ Added role column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ Role column already exists\n";
        } else {
            throw $e;
        }
    }
    
    // Step 2: Create permissions table
    echo "\nStep 2: Creating permissions table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_user_permissions (
            user_id INT,
            permission VARCHAR(100),
            granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES autism_users(id) ON DELETE CASCADE,
            PRIMARY KEY (user_id, permission)
        )
    ");
    echo "✓ Permissions table ready\n";
    
    // Step 3: Delete old test users
    echo "\nStep 3: Removing old test users...\n";
    $stmt = $pdo->exec("DELETE FROM autism_users WHERE email LIKE '%@aci.com'");
    echo "✓ Removed old test users\n";
    
    // Step 4: Insert new users
    echo "\nStep 4: Creating new user accounts...\n";
    
    $users = [
        [
            'username' => 'frank',
            'email' => 'frank@acgcares.com',
            'password' => 'Supreme2024!',
            'access_level' => 6,
            'full_name' => 'Frank (Supreme Admin)',
            'role' => 'Supreme Administrator'
        ],
        [
            'username' => 'mary.emah',
            'email' => 'mary.emah@acgcares.com',
            'password' => 'CEO2024!',
            'access_level' => 5,
            'full_name' => 'Mary Emah',
            'role' => 'Chief Executive Officer'
        ],
        [
            'username' => 'drukpeh',
            'email' => 'drukpeh@duck.com',
            'password' => 'Executive2024!',
            'access_level' => 5,
            'full_name' => 'Dr. Ukpeh',
            'role' => 'Executive'
        ],
        [
            'username' => 'amanda.georgi',
            'email' => 'amanda.georgi@acgcares.com',
            'password' => 'HR2024!',
            'access_level' => 4,
            'full_name' => 'Amanda Georgi',
            'role' => 'Human Resources Officer'
        ],
        [
            'username' => 'edwin.recto',
            'email' => 'edwin.recto@acgcares.com',
            'password' => 'Clinical2024!',
            'access_level' => 4,
            'full_name' => 'Edwin Recto',
            'role' => 'Site Supervisor / Clinical Lead'
        ],
        [
            'username' => 'pam.pastor',
            'email' => 'pam.pastor@acgcares.com',
            'password' => 'Billing2024!',
            'access_level' => 4,
            'full_name' => 'Pam Pastor',
            'role' => 'Billing Administrator'
        ],
        [
            'username' => 'yanika.crosse',
            'email' => 'yanika.crosse@acgcares.com',
            'password' => 'Billing2024!',
            'access_level' => 4,
            'full_name' => 'Yanika Crosse',
            'role' => 'Billing Administrator'
        ],
        [
            'username' => 'alvin.ukpeh',
            'email' => 'alvin.ukpeh@acgcares.com',
            'password' => 'SysAdmin2024!',
            'access_level' => 5,
            'full_name' => 'Alvin Ukpeh',
            'role' => 'System Administrator'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO autism_users (username, email, password_hash, access_level, full_name, role, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            password_hash = VALUES(password_hash),
            access_level = VALUES(access_level),
            full_name = VALUES(full_name),
            role = VALUES(role)
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
        echo "✓ Created user: {$user['email']} (Password: {$user['password']})\n";
    }
    
    // Step 5: Update service types
    echo "\nStep 5: Updating Maryland Medicaid service types...\n";
    
    // Clear existing service types
    $pdo->exec("TRUNCATE TABLE autism_service_types");
    
    // Insert new service types
    $services = [
        ['W9307', 'Regular Therapeutic Integration', 'Support services for individuals with autism in community settings (80 units/week limit)', 9.28],
        ['W9308', 'Intensive Therapeutic Integration', 'Enhanced support services requiring higher staff qualifications (60 units/week limit)', 11.60],
        ['W9306', 'Intensive Individual Support Services (IISS)', 'One-on-one intensive support for individuals with complex needs (160 units/week limit)', 12.80],
        ['W9314', 'Respite Care', 'Temporary relief for primary caregivers (96 units/day, 1344 units/year limit)', 9.07],
        ['W9315', 'Family Consultation', 'Training and support for family members (24 units/day, 160 units/year limit)', 38.10]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO autism_service_types (service_code, service_name, description, rate, unit_type, is_active) 
        VALUES (?, ?, ?, ?, 'unit', 1)
    ");
    
    foreach ($services as $service) {
        $stmt->execute($service);
        echo "✓ Added service: {$service[0]} - {$service[1]} (\${$service[3]}/unit)\n";
    }
    
    // Step 6: Update configuration constants
    echo "\nStep 6: Configuration reminders...\n";
    echo "⚠️  Make sure to update /src/config.php with:\n";
    echo "   - ORGANIZATION_NPI = '1013104314'\n";
    echo "   - ORGANIZATION_TAX_ID = '52-2305229'\n";
    echo "   - TAXONOMY_CODE = '251C00000X'\n";
    
    echo "\n⚠️  Create /config/claimmd.php with Claim.MD API credentials\n";
    
    echo "\n\n=== ✅ PRODUCTION UPDATE COMPLETE! ===\n\n";
    echo "Users can now login at https://aci.dydact.io with their new credentials.\n";
    echo "Test with: frank@acgcares.com / Supreme2024!\n\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
    exit(1);
}
?>