-- Apply remaining tables and fixes for American Caregivers Inc

-- Add missing column to autism_session_notes if not exists
ALTER TABLE autism_session_notes 
ADD COLUMN IF NOT EXISTS created_by INT(11) AFTER additional_notes;

-- Create time clock table if not exists
CREATE TABLE IF NOT EXISTS `autism_time_clock` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `employee_id` INT(11) NOT NULL,
    `clock_in` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `clock_out` TIMESTAMP NULL,
    `client_id` INT(11),
    `session_note_id` INT(11),
    `break_minutes` INT DEFAULT 0,
    `total_hours` DECIMAL(5,2),
    `status` ENUM('clocked_in','clocked_out','approved','rejected') DEFAULT 'clocked_in',
    `notes` TEXT,
    `approved_by` INT(11),
    `approved_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    KEY `idx_employee_date` (`employee_id`, `clock_in`),
    KEY `idx_status` (`status`),
    KEY `idx_clock_in` (`clock_in`),
    CONSTRAINT `fk_time_clock_employee` FOREIGN KEY (`employee_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_time_clock_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_time_clock_session` FOREIGN KEY (`session_note_id`) 
        REFERENCES `autism_session_notes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_time_clock_approved_by` FOREIGN KEY (`approved_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create IISS notes table
CREATE TABLE IF NOT EXISTS `autism_iiss_notes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `staff_id` INT(11) NOT NULL,
    `session_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `location` VARCHAR(255),
    `service_code` VARCHAR(20) DEFAULT 'IISS',
    `units` DECIMAL(5,2) NOT NULL,
    `goals_addressed` TEXT,
    `interventions` TEXT,
    `client_response` TEXT,
    `behavioral_observations` TEXT,
    `skill_development` TEXT,
    `communication_notes` TEXT,
    `social_interaction` TEXT,
    `daily_living_skills` TEXT,
    `community_participation` TEXT,
    `family_involvement` TEXT,
    `medical_concerns` TEXT,
    `recommendations` TEXT,
    `follow_up_needed` BOOLEAN DEFAULT FALSE,
    `supervisor_review_needed` BOOLEAN DEFAULT FALSE,
    `status` ENUM('draft','completed','approved','void') DEFAULT 'draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_date` (`client_id`, `session_date`),
    KEY `idx_staff_date` (`staff_id`, `session_date`),
    KEY `idx_session_date` (`session_date`),
    CONSTRAINT `fk_iiss_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_iiss_staff` FOREIGN KEY (`staff_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create billing claims tables
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

-- Create schedules table
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

-- Create payment plans table
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
    KEY `idx_next_payment` (`next_payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add constraint after table is created
ALTER TABLE autism_payment_plans
ADD CONSTRAINT fk_payment_plan_client FOREIGN KEY (`client_id`) 
    REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT;

-- Create authorizations table
CREATE TABLE IF NOT EXISTS `autism_authorizations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `authorization_number` VARCHAR(50) NOT NULL UNIQUE,
    `service_type_id` INT(11) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `total_units` DECIMAL(10,2) NOT NULL,
    `used_units` DECIMAL(10,2) DEFAULT 0.00,
    `remaining_units` DECIMAL(10,2) GENERATED ALWAYS AS (total_units - used_units) STORED,
    `status` ENUM('active','expired','exhausted','cancelled') DEFAULT 'active',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client` (`client_id`),
    KEY `idx_auth_number` (`authorization_number`),
    KEY `idx_dates` (`start_date`, `end_date`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_auth_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_auth_service_type` FOREIGN KEY (`service_type_id`) 
        REFERENCES `autism_service_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Show created tables
SELECT 'Remaining tables created successfully!' as status;

-- Show all autism tables
SHOW TABLES LIKE 'autism_%';