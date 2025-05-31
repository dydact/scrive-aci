-- Create simple autism_schedules table without foreign key constraints for now
CREATE TABLE IF NOT EXISTS `autism_schedules` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;