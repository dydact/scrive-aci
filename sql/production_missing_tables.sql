-- Production Missing Tables for Scrive ACI
-- Only creates tables that don't already exist in the system

-- 1. Time Clock Entries (missing functionality for employee clock in/out)
-- This is different from session notes - it tracks employee work hours
CREATE TABLE IF NOT EXISTS `autism_time_clock` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `employee_id` INT(11) NOT NULL,
    `clock_in` TIMESTAMP NOT NULL,
    `clock_out` TIMESTAMP NULL DEFAULT NULL,
    `clock_in_location` VARCHAR(255) DEFAULT NULL,
    `clock_out_location` VARCHAR(255) DEFAULT NULL,
    `total_hours` DECIMAL(5,2) DEFAULT NULL,
    `status` ENUM('clocked_in', 'clocked_out', 'adjusted') DEFAULT 'clocked_in',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_employee_date` (`employee_id`, `clock_in`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_time_clock_employee` FOREIGN KEY (`employee_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Real-time Eligibility Verification Log (EVS tracking)
-- Tracks Medicaid eligibility checks for compliance
CREATE TABLE IF NOT EXISTS `autism_eligibility_verification` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `ma_number` VARCHAR(20) NOT NULL,
    `verification_date` DATE NOT NULL,
    `eligibility_status` ENUM('eligible', 'ineligible', 'pending', 'error') NOT NULL,
    `coverage_type` VARCHAR(100) DEFAULT NULL,
    `program_code` VARCHAR(10) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `mco_name` VARCHAR(100) DEFAULT NULL,
    `copay_amount` DECIMAL(10,2) DEFAULT 0.00,
    `response_code` VARCHAR(50) DEFAULT NULL,
    `response_message` TEXT,
    `verified_by` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_date` (`client_id`, `verification_date`),
    KEY `idx_ma_number` (`ma_number`),
    KEY `idx_status` (`eligibility_status`),
    CONSTRAINT `fk_eligibility_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_eligibility_verified_by` FOREIGN KEY (`verified_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Provider Configuration (for NPI, Tax ID, etc.)
-- Extends system settings for provider-specific information
CREATE TABLE IF NOT EXISTS `autism_provider_config` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `setting_type` ENUM('text', 'number', 'boolean', 'json', 'encrypted') DEFAULT 'text',
    `category` VARCHAR(50) DEFAULT 'general',
    `description` TEXT,
    `is_sensitive` BOOLEAN DEFAULT FALSE,
    `updated_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_setting_key` (`setting_key`),
    KEY `idx_category` (`category`),
    CONSTRAINT `fk_provider_config_updated_by` FOREIGN KEY (`updated_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Mobile App Session Tracking (for mobile portal usage)
CREATE TABLE IF NOT EXISTS `autism_mobile_sessions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `device_id` VARCHAR(255) DEFAULT NULL,
    `device_type` VARCHAR(50) DEFAULT NULL,
    `app_version` VARCHAR(20) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT,
    `login_time` TIMESTAMP NOT NULL,
    `logout_time` TIMESTAMP NULL DEFAULT NULL,
    `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `session_token` VARCHAR(255) DEFAULT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    PRIMARY KEY (`id`),
    KEY `idx_user_active` (`user_id`, `is_active`),
    KEY `idx_session_token` (`session_token`),
    KEY `idx_last_activity` (`last_activity`),
    CONSTRAINT `fk_mobile_session_user` FOREIGN KEY (`user_id`) 
        REFERENCES `autism_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default provider configuration values
INSERT INTO `autism_provider_config` (`setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_sensitive`) VALUES
('medicaid_provider_id', '', 'text', 'billing', 'Maryland Medicaid Provider ID', TRUE),
('provider_npi', '', 'text', 'billing', 'National Provider Identifier (NPI)', TRUE),
('provider_tax_id', '', 'encrypted', 'billing', 'Federal Tax ID Number', TRUE),
('organization_name', 'American Caregivers Inc', 'text', 'organization', 'Legal Organization Name', FALSE),
('organization_dba', 'ACI', 'text', 'organization', 'Doing Business As Name', FALSE),
('evs_endpoint', 'https://encrypt.emdhealthchoice.org/emedicaid/', 'text', 'api', 'Maryland EVS API Endpoint', FALSE),
('evs_api_key', '', 'encrypted', 'api', 'EVS API Key', TRUE),
('crisp_endpoint', 'https://portal.crisphealth.org/api', 'text', 'api', 'CRISP HIE Endpoint', FALSE),
('crisp_api_key', '', 'encrypted', 'api', 'CRISP API Key', TRUE),
('claims_endpoint', 'https://claims.maryland.gov/submit', 'text', 'api', 'Claims Submission Endpoint', FALSE),
('max_session_duration', '12', 'number', 'security', 'Maximum session duration in hours', FALSE),
('password_expiry_days', '90', 'number', 'security', 'Password expiration in days', FALSE),
('require_2fa', 'false', 'boolean', 'security', 'Require two-factor authentication', FALSE),
('billing_mode', 'test', 'text', 'billing', 'Billing mode: test or production', FALSE)
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_time_clock_date ON autism_time_clock(DATE(clock_in));
CREATE INDEX IF NOT EXISTS idx_eligibility_recent ON autism_eligibility_verification(created_at DESC);

-- Grant appropriate permissions (adjust as needed for your user)
-- GRANT SELECT, INSERT, UPDATE ON autism_time_clock TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_eligibility_verification TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_provider_config TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_mobile_sessions TO 'iris_user'@'localhost';