<?php
// Emergency database fix script
require_once 'src/config.php';
require_once 'src/openemr_integration.php';

try {
    echo "Connecting to database...\n";
    $pdo = getDatabase();
    
    echo "Checking existing autism tables...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'autism_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Existing autism tables:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    echo "\nCreating missing tables...\n";
    
    // Create autism_schedules table
    echo "Creating autism_schedules...\n";
    $sql = "CREATE TABLE IF NOT EXISTS `autism_schedules` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `staff_id` INT(11),
        `client_id` INT(11) NOT NULL,
        `service_type_id` INT(11),
        `scheduled_date` DATE NOT NULL,
        `start_time` TIME NOT NULL,
        `end_time` TIME NOT NULL,
        `notes` TEXT,
        `status` ENUM('scheduled','confirmed','completed','cancelled','no_show') DEFAULT 'scheduled',
        `created_by` INT(11),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_date_time` (`scheduled_date`, `start_time`),
        KEY `idx_client` (`client_id`),
        KEY `idx_staff` (`staff_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✓ autism_schedules created\n";
    
    // Create autism_organization_settings table
    echo "Creating autism_organization_settings...\n";
    $sql2 = "CREATE TABLE IF NOT EXISTS `autism_organization_settings` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `organization_name` VARCHAR(255) NOT NULL,
        `address` VARCHAR(255),
        `city` VARCHAR(100),
        `state` VARCHAR(2),
        `zip` VARCHAR(10),
        `phone` VARCHAR(20),
        `email` VARCHAR(255),
        `tax_id` VARCHAR(20),
        `npi` VARCHAR(20),
        `medicaid_provider_id` VARCHAR(50),
        `website` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql2);
    echo "✓ autism_organization_settings created\n";
    
    // Create autism_claims table if missing
    echo "Creating autism_claims...\n";
    $sql3 = "CREATE TABLE IF NOT EXISTS `autism_claims` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `claim_number` VARCHAR(50) UNIQUE,
        `client_id` INT(11) NOT NULL,
        `service_date_from` DATE NOT NULL,
        `service_date_to` DATE NOT NULL,
        `total_amount` DECIMAL(10,2),
        `status` ENUM('draft','generated','submitted','paid','denied') DEFAULT 'draft',
        `payment_amount` DECIMAL(10,2),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_client` (`client_id`),
        KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql3);
    echo "✓ autism_claims created\n";
    
    // Create autism_staff_members table
    echo "Creating autism_staff_members...\n";
    $sql4 = "CREATE TABLE IF NOT EXISTS `autism_staff_members` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(11),
        `employee_id` VARCHAR(50),
        `full_name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255),
        `phone` VARCHAR(20),
        `role` VARCHAR(50),
        `hire_date` DATE,
        `status` ENUM('active','inactive','on_leave') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_employee_id` (`employee_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql4);
    echo "✓ autism_staff_members created\n";
    
    // Insert sample data to test
    echo "Inserting sample schedule...\n";
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO autism_schedules 
        (client_id, scheduled_date, start_time, end_time, notes, status) 
        VALUES (1, CURDATE(), '10:00:00', '11:00:00', 'Test session', 'scheduled')
    ");
    $stmt->execute();
    echo "✓ Sample schedule added\n";
    
    // Insert sample staff members
    echo "Inserting sample staff members...\n";
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO autism_staff_members 
        (user_id, employee_id, full_name, email, phone, role, hire_date, status) 
        VALUES 
        (1, 'EMP001', 'System Administrator', 'admin@americancaregivers.com', '(240) 555-0001', 'Administrator', '2020-01-01', 'active'),
        (NULL, 'EMP002', 'Jane Manager', 'jane.manager@americancaregivers.com', '(240) 555-0002', 'Case Manager', '2021-03-15', 'active'),
        (NULL, 'EMP003', 'John Support', 'john.support@americancaregivers.com', '(240) 555-0003', 'DSP', '2022-06-01', 'active')
    ");
    $stmt->execute();
    echo "✓ Sample staff members added\n";
    
    // Create autism_service_types table
    echo "Creating autism_service_types...\n";
    $sql5 = "CREATE TABLE IF NOT EXISTS `autism_service_types` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `service_code` VARCHAR(20) NOT NULL,
        `service_name` VARCHAR(100) NOT NULL,
        `description` TEXT,
        `rate` DECIMAL(10,2),
        `unit_type` ENUM('hour','unit','day','session') DEFAULT 'hour',
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_service_code` (`service_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql5);
    echo "✓ autism_service_types created\n";
    
    // Insert sample service types
    echo "Inserting sample service types...\n";
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO autism_service_types 
        (service_code, service_name, description, rate, unit_type) 
        VALUES 
        ('IISS', 'Individual Intensive Support Services', 'One-on-one support services for individuals with autism', 35.00, 'hour'),
        ('TI', 'Therapeutic Integration', 'Community integration and therapeutic support', 40.00, 'hour'),
        ('RESPITE', 'Respite Care', 'Temporary relief care for primary caregivers', 30.00, 'hour'),
        ('FAMILY', 'Family Consultation', 'Support and consultation for family members', 50.00, 'hour')
    ");
    $stmt->execute();
    echo "✓ Sample service types added\n";
    
    // Check final table list
    echo "\nFinal table check:\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'autism_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "✓ $table\n";
    }
    
    echo "\nDatabase fix completed successfully!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}