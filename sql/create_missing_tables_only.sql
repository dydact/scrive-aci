-- Create only missing tables for American Caregivers Inc

-- Add missing column to autism_session_notes if not exists
ALTER TABLE autism_session_notes 
ADD COLUMN IF NOT EXISTS created_by INT(11) AFTER additional_notes;

-- Create missing tables only

-- 1. Billing claims (main table already exists as autism_claims, skip)

-- 2. Billing claim lines (already exists as autism_claim_lines, skip)

-- 3. Schedules table (doesn't exist)
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

-- 4. Payment plans table
CREATE TABLE IF NOT EXISTS `autism_payment_plans` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `total_amount` DECIMAL(12,2) NOT NULL,
    `down_payment` DECIMAL(12,2) DEFAULT 0.00,
    `monthly_payment` DECIMAL(12,2) NOT NULL,
    `number_of_payments` INT NOT NULL,
    `start_date` DATE NOT NULL,
    `status` ENUM('active','completed','defaulted','cancelled') DEFAULT 'active',
    `balance_remaining` DECIMAL(12,2) NOT NULL,
    `last_payment_date` DATE,
    `next_payment_date` DATE,
    `notes` TEXT,
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_status` (`client_id`, `status`),
    KEY `idx_next_payment` (`next_payment_date`),
    CONSTRAINT `fk_payment_plan_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Authorizations table (already exists as autism_prior_authorizations, skip)

-- Create aliases for consistency
CREATE OR REPLACE VIEW autism_billing_claims AS 
SELECT * FROM autism_claims;

CREATE OR REPLACE VIEW autism_billing_claim_lines AS 
SELECT * FROM autism_claim_lines;

CREATE OR REPLACE VIEW autism_authorizations AS 
SELECT * FROM autism_prior_authorizations;

-- Summary
SELECT 'Missing tables created successfully!' as status;

-- Show all tables
SELECT COUNT(*) as total_autism_tables FROM information_schema.tables 
WHERE table_schema = 'openemr' AND table_name LIKE 'autism_%';