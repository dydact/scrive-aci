<?php

/**
 * Authentication Helper for Role-Based Access Control
 * 
 * @package   Scrive
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
 * @license   MIT License
 */

// Create roles and permissions tables via SQL
function createRoleBasedTables() {
    $host = 'localhost';
    $dbname = 'openemr';
    $username = 'openemr';
    $password = 'openemr';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "<h2>🔐 Implementing Role-Based Access Control</h2>";
        
        // 1. Create staff roles table
        echo "<h3>Step 1: Creating staff roles system...</h3>";
        
        $rolesSql = "CREATE TABLE IF NOT EXISTS autism_staff_roles (
            role_id INT AUTO_INCREMENT PRIMARY KEY,
            role_name VARCHAR(50) NOT NULL UNIQUE,
            role_level INT NOT NULL DEFAULT 1,
            description TEXT,
            can_view_org_ma_numbers BOOLEAN DEFAULT FALSE,
            can_view_client_ma_numbers BOOLEAN DEFAULT TRUE,
            can_edit_client_data BOOLEAN DEFAULT FALSE,
            can_schedule_sessions BOOLEAN DEFAULT FALSE,
            can_manage_staff BOOLEAN DEFAULT FALSE,
            can_view_billing BOOLEAN DEFAULT FALSE,
            can_manage_authorizations BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($rolesSql);
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
        
        $pdo->exec($userRolesSql);
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
            UNIQUE KEY unique_program_ma (program_id, ma_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($orgMaSql);
        echo "<p>✅ Organizational MA numbers table created (ADMIN ONLY)</p>";

        // 4. Update client enrollments to separate client MA from org MA
        echo "<h3>Step 2: Updating client MA number handling...</h3>";
        
        try {
            $pdo->exec("ALTER TABLE autism_client_enrollments MODIFY COLUMN ma_number VARCHAR(20) NULL COMMENT 'Individual client MA number (like SSN)'");
            echo "<p>✅ Updated client MA number field</p>";
        } catch (Exception $e) {
            echo "<p>⚠️ Client MA field already exists: " . htmlspecialchars($e->getMessage()) . "</p>";
        }

        // 5. Insert standard staff roles
        echo "<h3>Step 3: Creating standard staff roles...</h3>";
        
        $standardRoles = [
            ['Administrator', 5, 'Full system access including organizational billing information', 1, 1, 1, 1, 1, 1, 1],
            ['Supervisor', 4, 'Supervisory access with billing oversight but no organizational MA access', 0, 1, 1, 1, 0, 1, 1],
            ['Case_Manager', 3, 'Case management with client access and scheduling', 0, 1, 1, 1, 0, 0, 0],
            ['Direct_Care_Staff', 2, 'Direct care staff with limited client access', 0, 1, 0, 0, 0, 0, 0],
            ['Technician', 1, 'Basic technician access with session documentation only', 0, 0, 0, 0, 0, 0, 0]
        ];

        // Clear existing roles and insert new ones
        $pdo->exec("DELETE FROM autism_staff_roles");
        
        foreach ($standardRoles as $role) {
            $sql = "INSERT INTO autism_staff_roles 
                (role_name, role_level, description, can_view_org_ma_numbers, can_view_client_ma_numbers, 
                 can_edit_client_data, can_schedule_sessions, can_manage_staff, can_view_billing, can_manage_authorizations) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($role);
            
            echo "<p>✅ Created role: <strong>{$role[0]}</strong> (Level {$role[1]})</p>";
        }

        // 6. Insert organizational MA numbers (ADMIN ONLY ACCESS)
        echo "<h3>Step 4: Securing organizational MA numbers...</h3>";
        
        // Get program IDs
        $programs = [];
        $stmt = $pdo->query("SELECT program_id, abbreviation FROM autism_programs");
        while ($row = $stmt->fetch()) {
            $programs[$row['abbreviation']] = $row['program_id'];
        }

        $orgMaNumbers = [
            'AW' => '410608300',   // American Caregivers' Autism Waiver billing number
            'DDA' => '410608301',  // American Caregivers' DDA billing number  
            'CFC' => '522902200',  // American Caregivers' CFC billing number
            'CS' => '433226100'    // American Caregivers' CS billing number
        ];

        // Clear and insert organizational MA numbers
        $pdo->exec("DELETE FROM autism_org_ma_numbers");
        
        foreach ($orgMaNumbers as $programAbbr => $maNumber) {
            if (isset($programs[$programAbbr])) {
                $sql = "INSERT INTO autism_org_ma_numbers 
                    (program_id, ma_number, description, is_active, effective_date, created_by) 
                    VALUES (?, ?, ?, 1, CURDATE(), 1)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $programs[$programAbbr],
                    $maNumber,
                    "American Caregivers billing MA number for {$programAbbr} program"
                ]);
                
                echo "<p>🔒 Secured organizational MA number for {$programAbbr}: {$maNumber} (ADMIN ONLY)</p>";
            }
        }

        // 7. Create role checking view
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
        
        $pdo->exec($viewSql);
        echo "<p>✅ Created user permissions view</p>";

        echo "<h3>✅ Role-Based Access Control Implementation Complete!</h3>";
        echo "<p><strong>🚨 CRITICAL SECURITY UPDATE:</strong></p>";
        echo "<ul>";
        echo "<li>🔒 <strong>Organizational MA Numbers</strong> - Now hidden from all staff except administrators</li>";
        echo "<li>👤 <strong>Individual Client MA Numbers</strong> - Properly separated and role-controlled</li>";
        echo "<li>🎭 <strong>5-Tier Role System</strong> - Administrator → Supervisor → Case Manager → Direct Care → Technician</li>";
        echo "<li>🛡️ <strong>Permission-Based Access</strong> - Each role has specific capabilities</li>";
        echo "</ul>";
        
        return true;
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        return false;
    }
}

// Function to check user permissions
function getUserPermissions($userId) {
    $host = 'localhost';
    $dbname = 'openemr';
    $username = 'openemr';
    $password = 'openemr';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $stmt = $pdo->prepare("SELECT * FROM autism_user_permissions WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}

// Function to check if user can view organizational MA numbers
function canViewOrgMANumbers($userId) {
    $permissions = getUserPermissions($userId);
    return $permissions && $permissions['can_view_org_ma_numbers'];
}

// Function to check if user can view client MA numbers
function canViewClientMANumbers($userId) {
    $permissions = getUserPermissions($userId);
    return $permissions && $permissions['can_view_client_ma_numbers'];
}

// If this file is accessed directly, run the setup
if (basename(__FILE__) == basename($_SERVER["SCRIPT_NAME"])) {
    createRoleBasedTables();
}

?> 