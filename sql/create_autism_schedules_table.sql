-- Create simple autism_schedules table for schedule manager
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
    KEY `idx_staff` (`staff_id`),
    CONSTRAINT `fk_schedule_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_schedule_staff` FOREIGN KEY (`staff_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_schedule_service` FOREIGN KEY (`service_type_id`) 
        REFERENCES `autism_service_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;