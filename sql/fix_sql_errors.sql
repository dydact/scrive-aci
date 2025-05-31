-- Fix SQL errors for MariaDB compatibility

-- Fix production_missing_tables.sql - Remove IF NOT EXISTS from CREATE INDEX
DROP INDEX IF EXISTS idx_time_clock_date ON autism_time_clock;
CREATE INDEX idx_time_clock_date ON autism_time_clock(clock_in);

-- Fix clinical_documentation_system.sql - MariaDB doesn't support multi-column function indexes
DROP INDEX IF EXISTS idx_iiss_month ON autism_iiss_notes;
CREATE INDEX idx_iiss_session_date ON autism_iiss_notes(session_date);

-- Add missing column to autism_session_notes
ALTER TABLE autism_session_notes 
ADD COLUMN IF NOT EXISTS created_by INT(11) AFTER additional_notes,
ADD CONSTRAINT fk_session_created_by FOREIGN KEY (created_by) 
    REFERENCES autism_users(id) ON DELETE SET NULL;

-- Drop duplicate constraint that's causing errno 121
ALTER TABLE autism_payment_plans DROP FOREIGN KEY IF EXISTS fk_plan_client;

-- Create missing billing tables
CREATE TABLE IF NOT EXISTS `autism_billing_claims` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `claim_number` VARCHAR(50) UNIQUE,
    `client_id` INT(11) NOT NULL,
    `service_date_from` DATE NOT NULL,
    `service_date_to` DATE NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('draft','generated','submitted','accepted','rejected','paid') DEFAULT 'draft',
    `submission_date` DATE,
    `payment_date` DATE,
    `payment_amount` DECIMAL(10,2),
    `rejection_reason` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client` (`client_id`),
    KEY `idx_status` (`status`),
    KEY `idx_dates` (`service_date_from`, `service_date_to`),
    CONSTRAINT `fk_claim_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `autism_billing_claim_lines` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `claim_id` INT(11) NOT NULL,
    `session_note_id` INT(11),
    `service_type_id` INT(11),
    `service_date` DATE NOT NULL,
    `units` DECIMAL(5,2) NOT NULL,
    `rate` DECIMAL(10,2) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `modifier` VARCHAR(10),
    `authorization_number` VARCHAR(50),
    PRIMARY KEY (`id`),
    KEY `idx_claim` (`claim_id`),
    KEY `idx_session` (`session_note_id`),
    CONSTRAINT `fk_line_claim` FOREIGN KEY (`claim_id`) 
        REFERENCES `autism_billing_claims` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_line_session` FOREIGN KEY (`session_note_id`) 
        REFERENCES `autism_session_notes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_line_service_type` FOREIGN KEY (`service_type_id`) 
        REFERENCES `autism_service_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `autism_schedules` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `client_id` INT(11) NOT NULL,
    `service_type_id` INT(11),
    `scheduled_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `status` ENUM('scheduled','confirmed','in_progress','completed','cancelled','no_show') DEFAULT 'scheduled',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_date` (`scheduled_date`),
    KEY `idx_staff_date` (`staff_id`, `scheduled_date`),
    KEY `idx_client_date` (`client_id`, `scheduled_date`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_schedule_staff` FOREIGN KEY (`staff_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_schedule_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_schedule_service_type` FOREIGN KEY (`service_type_id`) 
        REFERENCES `autism_service_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Show summary
SELECT 'Database fixes applied successfully!' as status;