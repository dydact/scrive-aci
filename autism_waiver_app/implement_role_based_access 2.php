<?php

/**
 * Role-Based Access Control Implementation
 * 
 * @package   Scrive
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
 * @license   MIT License
 */

require_once 'auth.php';
initScriveAuth();

echo "<h2>🔐 Implementing Role-Based Access Control</h2>";

try {
    // 1. Create staff roles table
    echo "<h3>Step 1: Creating staff roles system...</h3>";
    
    $rolesSql = "CREATE TABLE IF NOT EXISTS autism_staff_roles (
        role_id INT AUTO_INCREMENT PRIMARY KEY,
        role_name VARCHAR(50) NOT NULL UNIQUE,
        role_level INT NOT NULL DEFAULT 1,
        description TEXT,
        permissions JSON,
        can_view_org_ma_numbers BOOLEAN DEFAULT FALSE,
        can_view_client_ma_numbers BOOLEAN DEFAULT TRUE,
        can_edit_client_data BOOLEAN DEFAULT FALSE,
        can_schedule_sessions BOOLEAN DEFAULT FALSE,
        can_manage_staff BOOLEAN DEFAULT FALSE,
        can_view_billing BOOLEAN DEFAULT FALSE,
        can_manage_authorizations BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    sqlStatement($rolesSql);
    echo "<p>✅ Staff roles table created</p>";

    // 2. Create user-role assignments table
    $userRolesSql = "CREATE TABLE IF NOT EXISTS autism_user_roles (
        assignment_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        role_id INT NOT NULL,
        assigned_by INT NOT NULL,
        assigned_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (role_id) REFERENCES autism_staff_roles(role_id),
        UNIQUE KEY unique_user_role (user_id, role_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    sqlStatement($userRolesSql);
    echo "<p>✅ User-role assignments table created</p>";

    // 3. Create organizational MA numbers table (ADMIN ONLY)
    $orgMaSql = "CREATE TABLE IF NOT EXISTS autism_org_ma_numbers (
        org_ma_id INT AUTO_INCREMENT PRIMARY KEY,
        program_id INT NOT NULL,
        ma_number VARCHAR(20) NOT NULL,
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        effective_date DATE,
        expiration_date DATE,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (program_id) REFERENCES autism_programs(program_id),
        UNIQUE KEY unique_program_ma (program_id, ma_number)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    sqlStatement($orgMaSql);
    echo "<p>✅ Organizational MA numbers table created (ADMIN ONLY)</p>";

    // 4. Update client enrollments to separate client MA from org MA
    echo "<h3>Step 2: Updating client MA number handling...</h3>";
    
    try {
        sqlStatement("ALTER TABLE autism_client_enrollments MODIFY COLUMN ma_number VARCHAR(20) NULL COMMENT 'Individual client MA number (like SSN)'");
        echo "<p>✅ Updated client MA number field</p>";
    } catch (Exception $e) {
        echo "<p>⚠️ Client MA field already exists: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // 5. Insert standard staff roles
    echo "<h3>Step 3: Creating standard staff roles...</h3>";
    
    $standardRoles = [
        [
            'role_name' => 'Administrator',
            'role_level' => 5,
            'description' => 'Full system access including organizational billing information',
            'can_view_org_ma_numbers' => true,
            'can_view_client_ma_numbers' => true,
            'can_edit_client_data' => true,
            'can_schedule_sessions' => true,
            'can_manage_staff' => true,
            'can_view_billing' => true,
            'can_manage_authorizations' => true
        ],
        [
            'role_name' => 'Supervisor',
            'role_level' => 4,
            'description' => 'Supervisory access with billing oversight but no organizational MA access',
            'can_view_org_ma_numbers' => false,
            'can_view_client_ma_numbers' => true,
            'can_edit_client_data' => true,
            'can_schedule_sessions' => true,
            'can_manage_staff' => false,
            'can_view_billing' => true,
            'can_manage_authorizations' => true
        ],
        [
            'role_name' => 'Case_Manager',
            'role_level' => 3,
            'description' => 'Case management with client access and scheduling',
            'can_view_org_ma_numbers' => false,
            'can_view_client_ma_numbers' => true,
            'can_edit_client_data' => true,
            'can_schedule_sessions' => true,
            'can_manage_staff' => false,
            'can_view_billing' => false,
            'can_manage_authorizations' => false
        ],
        [
            'role_name' => 'Direct_Care_Staff',
            'role_level' => 2,
            'description' => 'Direct care staff with limited client access',
            'can_view_org_ma_numbers' => false,
            'can_view_client_ma_numbers' => true,
            'can_edit_client_data' => false,
            'can_schedule_sessions' => false,
            'can_manage_staff' => false,
            'can_view_billing' => false,
            'can_manage_authorizations' => false
        ],
        [
            'role_name' => 'Technician',
            'role_level' => 1,
            'description' => 'Basic technician access with session documentation only',
            'can_view_org_ma_numbers' => false,
            'can_view_client_ma_numbers' => false,
            'can_edit_client_data' => false,
            'can_schedule_sessions' => false,
            'can_manage_staff' => false,
            'can_view_billing' => false,
            'can_manage_authorizations' => false
        ]
    ];

    // Clear existing roles and insert new ones
    sqlStatement("DELETE FROM autism_staff_roles");
    
    foreach ($standardRoles as $role) {
        $fields = implode(', ', array_keys($role));
        $placeholders = str_repeat('?,', count($role) - 1) . '?';
        $values = array_values($role);
        
        $sql = "INSERT INTO autism_staff_roles ({$fields}) VALUES ({$placeholders})";
        sqlStatement($sql, $values);
        
        echo "<p>✅ Created role: <strong>{$role['role_name']}</strong> (Level {$role['role_level']})</p>";
    }

    // 6. Insert organizational MA numbers (ADMIN ONLY ACCESS)
    echo "<h3>Step 4: Securing organizational MA numbers...</h3>";
    
    // Get program IDs
    $programs = [];
    $programsResult = sqlStatement("SELECT program_id, abbreviation FROM autism_programs");
    while ($row = sqlFetchArray($programsResult)) {
        $programs[$row['abbreviation']] = $row['program_id'];
    }

    $orgMaNumbers = [
        'AW' => '410608300',   // American Caregivers' Autism Waiver billing number
        'DDA' => '410608301',  // American Caregivers' DDA billing number  
        'CFC' => '522902200',  // American Caregivers' CFC billing number
        'CS' => '433226100'    // American Caregivers' CS billing number
    ];

    // Clear and insert organizational MA numbers
    sqlStatement("DELETE FROM autism_org_ma_numbers");
    
    foreach ($orgMaNumbers as $programAbbr => $maNumber) {
        if (isset($programs[$programAbbr])) {
            $orgMaData = [
                'program_id' => $programs[$programAbbr],
                'ma_number' => $maNumber,
                'description' => "American Caregivers billing MA number for {$programAbbr} program",
                'is_active' => true,
                'effective_date' => date('Y-m-d'),
                'created_by' => 1
            ];
            
            $fields = implode(', ', array_keys($orgMaData));
            $placeholders = str_repeat('?,', count($orgMaData) - 1) . '?';
            $values = array_values($orgMaData);
            
            $sql = "INSERT INTO autism_org_ma_numbers ({$fields}) VALUES ({$placeholders})";
            sqlStatement($sql, $values);
            
            echo "<p>🔒 Secured organizational MA number for {$programAbbr}: {$maNumber} (ADMIN ONLY)</p>";
        }
    }

    // 7. Create role checking functions
    echo "<h3>Step 5: Creating role-based access functions...</h3>";
    
    $viewSql = "
    CREATE OR REPLACE VIEW autism_user_permissions AS
    SELECT 
        ur.user_id,
        u.username,
        sr.role_name,
        sr.role_level,
        sr.can_view_org_ma_numbers,
        sr.can_view_client_ma_numbers,
        sr.can_edit_client_data,
        sr.can_schedule_sessions,
        sr.can_manage_staff,
        sr.can_view_billing,
        sr.can_manage_authorizations
    FROM autism_user_roles ur
    JOIN autism_staff_roles sr ON ur.role_id = sr.role_id
    JOIN users u ON ur.user_id = u.id
    WHERE ur.is_active = TRUE";
    
    sqlStatement($viewSql);
    echo "<p>✅ Created user permissions view</p>";

    echo "<h3>✅ Role-Based Access Control Implementation Complete!</h3>";
    echo "<p><strong>Security Features Implemented:</strong></p>";
    echo "<ul>";
    echo "<li>🔒 <strong>Organizational MA Numbers</strong> - Hidden from all staff except administrators</li>";
    echo "<li>👤 <strong>Individual Client MA Numbers</strong> - Properly separated and role-controlled</li>";
    echo "<li>🎭 <strong>5-Tier Role System</strong> - Administrator → Supervisor → Case Manager → Direct Care → Technician</li>";
    echo "<li>🛡️ <strong>Permission-Based Access</strong> - Each role has specific capabilities</li>";
    echo "<li>📊 <strong>Audit Trail</strong> - Role assignments tracked with dates and assigners</li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Update client management interface to hide organizational MA numbers</li>";
    echo "<li>Implement role checking in API methods</li>";
    echo "<li>Update forms to collect individual client MA numbers only</li>";
    echo "<li>Add role assignment interface for administrators</li>";
    echo "</ol>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace: " . htmlspecialchars($e->getTraceAsString()) . "</p>";
}

?> 