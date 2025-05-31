-- Base Tables for Autism Waiver Management System
-- This must be run FIRST before any other schema files

-- Create autism_users table (base authentication)
CREATE TABLE IF NOT EXISTS `autism_users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `password_hash` VARCHAR(255),
    `email` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `access_level` INT(11) NOT NULL DEFAULT 1 COMMENT '1=Read Only, 2=DSP, 3=Case Manager, 4=Supervisor, 5=Admin',
    `user_type` ENUM('admin','staff','client','family') DEFAULT 'staff',
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_username` (`username`),
    KEY `idx_email` (`email`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create autism_clients table
CREATE TABLE IF NOT EXISTS `autism_clients` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `date_of_birth` DATE NOT NULL,
    `gender` ENUM('male','female','other') DEFAULT 'other',
    `ma_number` VARCHAR(20) UNIQUE COMMENT 'Medicaid Number',
    `address` VARCHAR(255),
    `city` VARCHAR(100),
    `state` VARCHAR(2) DEFAULT 'MD',
    `zip` VARCHAR(10),
    `phone` VARCHAR(20),
    `email` VARCHAR(255),
    `emergency_contact_name` VARCHAR(255),
    `emergency_contact_phone` VARCHAR(20),
    `emergency_contact_relationship` VARCHAR(100),
    `status` ENUM('active','inactive','discharged','waitlist') DEFAULT 'active',
    `enrollment_date` DATE,
    `discharge_date` DATE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ma_number` (`ma_number`),
    KEY `idx_name` (`last_name`, `first_name`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create autism_staff_members table
CREATE TABLE IF NOT EXISTS `autism_staff_members` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) UNIQUE, -- legacy field
    `user_id` INT(11),
    `employee_id` VARCHAR(50) UNIQUE,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20),
    `job_title` VARCHAR(100),
    `role` VARCHAR(100),
    `department` VARCHAR(100),
    `hire_date` DATE,
    `termination_date` DATE,
    `status` ENUM('active','inactive','terminated','on_leave') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_employee_id` (`employee_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create autism_service_types table
CREATE TABLE IF NOT EXISTS `autism_service_types` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `service_code` VARCHAR(20) NOT NULL UNIQUE,
    `service_name` VARCHAR(255) NOT NULL,
    `service_category` VARCHAR(100),
    `unit_type` ENUM('15min','hour','day','session','each') DEFAULT '15min',
    `billing_code` VARCHAR(20),
    `rate` DECIMAL(10,2) DEFAULT 0.00,
    `description` TEXT,
    `requires_authorization` BOOLEAN DEFAULT FALSE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_code` (`service_code`),
    KEY `idx_billing_code` (`billing_code`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default service types for Maryland Autism Waiver
INSERT INTO `autism_service_types` (`service_code`, `service_name`, `service_category`, `unit_type`, `billing_code`, `rate`, `requires_authorization`) VALUES
('IISS', 'Intensive Individual Support Services', 'Direct Care', '15min', 'T1019', 22.50, TRUE),
('TI', 'Therapeutic Integration', 'Behavioral', '15min', 'H2014', 25.00, TRUE),
('RC', 'Respite Care', 'Support', 'hour', 'S5150', 35.00, TRUE),
('FC', 'Family Consultation', 'Support', 'hour', 'T1027', 65.00, TRUE),
('ABA', 'Applied Behavior Analysis', 'Behavioral', 'hour', 'H0031', 75.00, TRUE),
('TCM', 'Targeted Case Management', 'Coordination', '15min', 'T1017', 18.75, FALSE)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- Create autism_session_notes table
CREATE TABLE IF NOT EXISTS `autism_session_notes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `staff_id` INT(11) NOT NULL,
    `session_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `service_type_id` INT(11),
    `session_type` VARCHAR(50),
    `goals_addressed` TEXT,
    `activities` TEXT,
    `client_response` TEXT,
    `behaviors_observed` TEXT,
    `interventions_used` TEXT,
    `progress_notes` TEXT,
    `plan_for_next_session` TEXT,
    `additional_notes` TEXT,
    `status` ENUM('draft','completed','approved','void') DEFAULT 'draft',
    `approved_by` INT(11),
    `approved_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_date` (`client_id`, `session_date`),
    KEY `idx_staff_date` (`staff_id`, `session_date`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_session_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_session_staff` FOREIGN KEY (`staff_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_session_service_type` FOREIGN KEY (`service_type_id`) 
        REFERENCES `autism_service_types` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_session_approved_by` FOREIGN KEY (`approved_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create staff roles table
CREATE TABLE IF NOT EXISTS `autism_staff_roles` (
    `role_id` INT(11) NOT NULL AUTO_INCREMENT,
    `role_name` VARCHAR(100) NOT NULL UNIQUE,
    `role_level` INT(11) NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT INTO `autism_staff_roles` (`role_name`, `role_level`, `description`) VALUES
('Administrator', 5, 'Full system access'),
('Supervisor', 4, 'Supervisory access'),
('Case Manager', 3, 'Case management access'),
('Direct Support Professional', 2, 'Direct care staff'),
('Technician', 1, 'Basic access')
ON DUPLICATE KEY UPDATE `role_id`=`role_id`;

-- Create user roles junction table
CREATE TABLE IF NOT EXISTS `autism_user_roles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `role_id` INT(11) NOT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `assigned_date` DATE,
    `assigned_by` INT(11),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_staff_role` (`staff_id`, `role_id`),
    CONSTRAINT `fk_user_role_staff` FOREIGN KEY (`staff_id`) 
        REFERENCES `autism_staff_members` (`staff_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_user_role_role` FOREIGN KEY (`role_id`) 
        REFERENCES `autism_staff_roles` (`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create staff assignments table
CREATE TABLE IF NOT EXISTS `autism_staff_assignments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `client_id` INT(11) NOT NULL,
    `assignment_type` ENUM('primary','secondary','substitute','case_manager') DEFAULT 'primary',
    `start_date` DATE NOT NULL,
    `end_date` DATE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `status` ENUM('active','inactive','pending') DEFAULT 'active',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_staff_client` (`staff_id`, `client_id`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_assignment_staff` FOREIGN KEY (`staff_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assignment_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create treatment plans table (basic version)
CREATE TABLE IF NOT EXISTS `autism_treatment_plans` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `plan_date` DATE NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` ENUM('draft','active','expired','archived') DEFAULT 'draft',
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client` (`client_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_plan_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_plan_created_by` FOREIGN KEY (`created_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grant permissions
GRANT ALL PRIVILEGES ON openemr.* TO 'openemr'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;

-- Success message
SELECT 'Base tables created successfully!' as status;