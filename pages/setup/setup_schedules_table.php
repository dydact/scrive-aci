<?php
// Create autism_schedules table via PHP
require_once 'src/config.php';
require_once 'src/openemr_integration.php';

try {
    $pdo = getDatabase();
    
    echo "Creating autism_schedules table...\n";
    
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
    echo "autism_schedules table created successfully!\n";
    
    // Create autism_organization_settings table if needed
    echo "Creating autism_organization_settings table...\n";
    
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
    echo "autism_organization_settings table created successfully!\n";
    
    echo "All tables created successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}