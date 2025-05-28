<?php

/**
 * Comprehensive Database Setup for Autism Waiver Application
 * 
 * @package   Autism Waiver App
 * @author    American Caregivers Inc
 * @copyright Copyright (c) 2025 American Caregivers Inc
 * @license   MIT License
 */

// Set ignoreAuth to bypass site checks during setup process
$ignoreAuth = true;

// Include OpenEMR database configuration
require_once __DIR__ . '/../interface/globals.php';

// Include Scrive authentication
require_once 'auth.php';

// Initialize Scrive authentication 
initScriveAuth();

$messages = [];
$error = false;

// Handle setup action
if ($_POST['action'] === 'setup' && $_POST['confirm'] === 'yes') {
    try {
        // Complete SQL for creating comprehensive autism waiver system
        $sql_statements = [
            // Core treatment planning tables (updated terminology)
            "CREATE TABLE IF NOT EXISTS `autism_plan` (
                `plan_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `client_id` BIGINT UNSIGNED NOT NULL,
                `plan_type` ENUM('initial','review','discharge') NOT NULL,
                `service_types` VARCHAR(255) DEFAULT NULL,
                `date_start` DATE NOT NULL,
                `date_end` DATE DEFAULT NULL,
                `created_by` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                `strengths` TEXT DEFAULT NULL,
                `materials` TEXT DEFAULT NULL,
                `evaluator` VARCHAR(255) DEFAULT NULL,
                `next_review_date` DATE DEFAULT NULL,
                `status` ENUM('active','closed','pending') NOT NULL DEFAULT 'active',
                `guardian_signature` TEXT DEFAULT NULL,
                `guardian_signature_date` DATETIME DEFAULT NULL,
                PRIMARY KEY (`plan_id`),
                KEY `idx_autism_plan_client` (`client_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `autism_goal` (
                `goal_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `plan_id` INT UNSIGNED NOT NULL,
                `domain` VARCHAR(100) DEFAULT NULL,
                `goal_description` TEXT NOT NULL,
                `baseline` TEXT DEFAULT NULL,
                `target_criterion` TEXT DEFAULT NULL,
                `sequence` INT UNSIGNED DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`goal_id`),
                KEY `idx_autism_goal_plan` (`plan_id`),
                CONSTRAINT `fk_autism_goal_plan` FOREIGN KEY (`plan_id`) REFERENCES `autism_plan` (`plan_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `autism_objective` (
                `obj_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `goal_id` INT UNSIGNED NOT NULL,
                `objective_text` TEXT NOT NULL,
                `target_criterion` TEXT DEFAULT NULL,
                `implementation` TEXT DEFAULT NULL,
                `progress` TEXT DEFAULT NULL,
                `achieved_flag` TINYINT(1) NOT NULL DEFAULT 0,
                `measurement_type` ENUM('percentage','frequency','duration','quality') DEFAULT 'percentage',
                `baseline_value` DECIMAL(5,2) DEFAULT NULL,
                `target_value` DECIMAL(5,2) DEFAULT NULL,
                `current_value` DECIMAL(5,2) DEFAULT NULL,
                PRIMARY KEY (`obj_id`),
                KEY `idx_autism_obj_goal` (`goal_id`),
                CONSTRAINT `fk_autism_obj_goal` FOREIGN KEY (`goal_id`) REFERENCES `autism_goal` (`goal_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Programs table (parent level with MA numbers)
            "CREATE TABLE IF NOT EXISTS `autism_programs` (
                `program_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `abbreviation` VARCHAR(10) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `ma_number` VARCHAR(20) NOT NULL,
                `is_active` TINYINT(1) DEFAULT 1,
                `created_by` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`program_id`),
                UNIQUE KEY `unique_name` (`name`),
                UNIQUE KEY `unique_abbreviation` (`abbreviation`),
                KEY `idx_ma_number` (`ma_number`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Service types management (updated for program hierarchy and unit billing)
            "CREATE TABLE IF NOT EXISTS `autism_service_types` (
                `service_type_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `program_id` INT UNSIGNED NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `abbreviation` VARCHAR(10) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `billing_code` VARCHAR(20) NOT NULL,
                `default_rate_per_unit` DECIMAL(8,2) DEFAULT NULL,
                `max_daily_units` INT DEFAULT NULL,
                `requires_authorization` TINYINT(1) DEFAULT 1,
                `unit_increment_minutes` TINYINT DEFAULT 15,
                `minimum_billable_minutes` TINYINT DEFAULT 5,
                `rounding_threshold_minutes` TINYINT DEFAULT 8,
                `is_active` TINYINT(1) DEFAULT 1,
                `created_by` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`service_type_id`),
                UNIQUE KEY `unique_program_name` (`program_id`, `name`),
                UNIQUE KEY `unique_program_code` (`program_id`, `billing_code`),
                CONSTRAINT `fk_service_program` FOREIGN KEY (`program_id`) REFERENCES `autism_programs` (`program_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Client program enrollments
            "CREATE TABLE IF NOT EXISTS `autism_client_enrollments` (
                `enrollment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `client_id` BIGINT UNSIGNED NOT NULL,
                `program_id` INT UNSIGNED NOT NULL,
                `enrollment_date` DATE NOT NULL,
                `end_date` DATE DEFAULT NULL,
                `status` ENUM('active','inactive','terminated','pending') DEFAULT 'active',
                `ma_eligible` TINYINT(1) DEFAULT 1,
                `notes` TEXT DEFAULT NULL,
                `created_by` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`enrollment_id`),
                KEY `idx_client_program` (`client_id`, `program_id`),
                CONSTRAINT `fk_enrollment_program` FOREIGN KEY (`program_id`) REFERENCES `autism_programs` (`program_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Employee management
            "CREATE TABLE IF NOT EXISTS `autism_employees` (
                `employee_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `openemr_user_id` INT UNSIGNED DEFAULT NULL,
                `employee_number` VARCHAR(20) UNIQUE DEFAULT NULL,
                `first_name` VARCHAR(100) NOT NULL,
                `last_name` VARCHAR(100) NOT NULL,
                `email` VARCHAR(255) DEFAULT NULL,
                `phone` VARCHAR(20) DEFAULT NULL,
                `role` ENUM('admin','supervisor','lead_technician','technician','billing','support') NOT NULL,
                `employment_status` ENUM('active','inactive','terminated','on_leave') DEFAULT 'active',
                `hire_date` DATE DEFAULT NULL,
                `hourly_rate` DECIMAL(8,2) DEFAULT NULL,
                `overtime_rate` DECIMAL(8,2) DEFAULT NULL,
                `certifications` TEXT DEFAULT NULL,
                `specializations` TEXT DEFAULT NULL,
                `max_clients` INT DEFAULT NULL,
                `travel_time_rate` DECIMAL(8,2) DEFAULT NULL,
                `emergency_contact` VARCHAR(255) DEFAULT NULL,
                `notes` TEXT DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`employee_id`),
                KEY `idx_openemr_user` (`openemr_user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Client assignments (which employees work with which clients)
            "CREATE TABLE IF NOT EXISTS `autism_client_assignments` (
                `assignment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `client_id` BIGINT UNSIGNED NOT NULL,
                `employee_id` INT UNSIGNED NOT NULL,
                `assignment_type` ENUM('primary','secondary','backup','supervisor') NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE DEFAULT NULL,
                `status` ENUM('active','inactive','pending') DEFAULT 'active',
                `notes` TEXT DEFAULT NULL,
                `created_by` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`assignment_id`),
                KEY `idx_client_employee` (`client_id`, `employee_id`),
                KEY `idx_employee` (`employee_id`),
                CONSTRAINT `fk_assignment_employee` FOREIGN KEY (`employee_id`) REFERENCES `autism_employees` (`employee_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Scheduling system
            "CREATE TABLE IF NOT EXISTS `autism_schedules` (
                `schedule_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `client_id` BIGINT UNSIGNED NOT NULL,
                `employee_id` INT UNSIGNED NOT NULL,
                `service_type_id` INT UNSIGNED NOT NULL,
                `scheduled_date` DATE NOT NULL,
                `start_time` TIME NOT NULL,
                `end_time` TIME NOT NULL,
                `duration_minutes` SMALLINT UNSIGNED NOT NULL,
                `status` ENUM('scheduled','confirmed','in_progress','completed','cancelled','no_show','rescheduled') DEFAULT 'scheduled',
                `location` VARCHAR(255) DEFAULT NULL,
                `location_type` ENUM('home','community','clinic','virtual') DEFAULT 'home',
                `transportation_provided` TINYINT(1) DEFAULT 0,
                `notes` TEXT DEFAULT NULL,
                `confirmation_sent` DATETIME DEFAULT NULL,
                `confirmed_by` VARCHAR(100) DEFAULT NULL,
                `cancelled_reason` VARCHAR(255) DEFAULT NULL,
                `created_by` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`schedule_id`),
                KEY `idx_client_date` (`client_id`, `scheduled_date`),
                KEY `idx_employee_date` (`employee_id`, `scheduled_date`),
                KEY `idx_service_type` (`service_type_id`),
                CONSTRAINT `fk_schedule_employee` FOREIGN KEY (`employee_id`) REFERENCES `autism_employees` (`employee_id`),
                CONSTRAINT `fk_schedule_service_type` FOREIGN KEY (`service_type_id`) REFERENCES `autism_service_types` (`service_type_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Updated session tracking for unit billing
            "CREATE TABLE IF NOT EXISTS `autism_session` (
                `session_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `schedule_id` INT UNSIGNED DEFAULT NULL,
                `client_id` BIGINT UNSIGNED NOT NULL,
                `employee_id` INT UNSIGNED NOT NULL,
                `service_type_id` INT UNSIGNED NOT NULL,
                `date` DATE NOT NULL,
                `time_start` TIME NOT NULL,
                `time_end` TIME NOT NULL,
                `duration_minutes` SMALLINT UNSIGNED NOT NULL,
                `billable_units` SMALLINT UNSIGNED NOT NULL,
                `clock_in_time` DATETIME DEFAULT NULL,
                `clock_out_time` DATETIME DEFAULT NULL,
                `actual_duration_minutes` SMALLINT UNSIGNED DEFAULT NULL,
                `location` VARCHAR(255) DEFAULT NULL,
                `narrative_note` TEXT DEFAULT NULL,
                `participation_level` ENUM('high','medium','low') DEFAULT NULL,
                `goal_achievement` ENUM('exceeded','met','partial','not_met') DEFAULT NULL,
                `interventions_used` TEXT DEFAULT NULL,
                `recommendations` TEXT DEFAULT NULL,
                `incidents` TEXT DEFAULT NULL,
                `parent_contact` TEXT DEFAULT NULL,
                `signature_required` TINYINT(1) DEFAULT 0,
                `signature_data` TEXT DEFAULT NULL,
                `signature_date` DATETIME DEFAULT NULL,
                `billing_status` ENUM('pending','approved','billed','paid','disputed') DEFAULT 'pending',
                `created_ts` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`session_id`),
                KEY `idx_client_date` (`client_id`, `date`),
                KEY `idx_employee_date` (`employee_id`, `date`),
                KEY `idx_schedule` (`schedule_id`),
                KEY `idx_billing_status` (`billing_status`),
                CONSTRAINT `fk_session_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `autism_schedules` (`schedule_id`),
                CONSTRAINT `fk_session_employee` FOREIGN KEY (`employee_id`) REFERENCES `autism_employees` (`employee_id`),
                CONSTRAINT `fk_session_service_type` FOREIGN KEY (`service_type_id`) REFERENCES `autism_service_types` (`service_type_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `autism_session_activity` (
                `activity_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `session_id` INT UNSIGNED NOT NULL,
                `objective_id` INT UNSIGNED DEFAULT NULL,
                `time_recorded` TIME DEFAULT NULL,
                `activity_desc` TEXT DEFAULT NULL,
                `outcome` ENUM('success','partial','failure','n/a') DEFAULT NULL,
                `measurement_value` DECIMAL(5,2) DEFAULT NULL,
                `activity_note` TEXT DEFAULT NULL,
                `photo_attachments` TEXT DEFAULT NULL,
                PRIMARY KEY (`activity_id`),
                KEY `idx_session` (`session_id`),
                KEY `idx_objective` (`objective_id`),
                CONSTRAINT `fk_activity_session` FOREIGN KEY (`session_id`) REFERENCES `autism_session` (`session_id`) ON DELETE CASCADE,
                CONSTRAINT `fk_activity_objective` FOREIGN KEY (`objective_id`) REFERENCES `autism_objective` (`obj_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `autism_session_incident` (
                `incident_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `session_id` INT UNSIGNED NOT NULL,
                `incident_time` TIME DEFAULT NULL,
                `incident_type` VARCHAR(50) NOT NULL,
                `severity` ENUM('low','medium','high','critical') DEFAULT 'low',
                `description` TEXT DEFAULT NULL,
                `action_taken` TEXT DEFAULT NULL,
                `follow_up_required` TINYINT(1) DEFAULT 0,
                `reported_to` VARCHAR(255) DEFAULT NULL,
                `report_date` DATETIME DEFAULT NULL,
                PRIMARY KEY (`incident_id`),
                KEY `idx_session` (`session_id`),
                KEY `idx_severity` (`severity`),
                CONSTRAINT `fk_incident_session` FOREIGN KEY (`session_id`) REFERENCES `autism_session` (`session_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Updated billing rates for unit-based billing
            "CREATE TABLE IF NOT EXISTS `autism_billing_rates` (
                `rate_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `service_type_id` INT UNSIGNED NOT NULL,
                `employee_role` ENUM('admin','supervisor','lead_technician','technician','billing','support') DEFAULT NULL,
                `rate_type` ENUM('standard','overtime','holiday','weekend','evening','travel') DEFAULT 'standard',
                `rate_per_unit` DECIMAL(8,2) NOT NULL,
                `effective_date` DATE NOT NULL,
                `end_date` DATE DEFAULT NULL,
                `is_active` TINYINT(1) DEFAULT 1,
                `notes` TEXT DEFAULT NULL,
                `created_by` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`rate_id`),
                KEY `idx_service_type` (`service_type_id`),
                KEY `idx_effective_date` (`effective_date`),
                CONSTRAINT `fk_rate_service_type` FOREIGN KEY (`service_type_id`) REFERENCES `autism_service_types` (`service_type_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Updated billing entries for unit-based billing
            "CREATE TABLE IF NOT EXISTS `autism_billing_entries` (
                `entry_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `session_id` INT UNSIGNED DEFAULT NULL,
                `client_id` BIGINT UNSIGNED NOT NULL,
                `employee_id` INT UNSIGNED NOT NULL,
                `service_type_id` INT UNSIGNED NOT NULL,
                `billing_date` DATE NOT NULL,
                `start_time` TIME NOT NULL,
                `end_time` TIME NOT NULL,
                `total_minutes` SMALLINT UNSIGNED NOT NULL,
                `billable_units` SMALLINT UNSIGNED NOT NULL,
                `travel_units` SMALLINT UNSIGNED DEFAULT 0,
                `rate_per_unit` DECIMAL(8,2) NOT NULL,
                `total_amount` DECIMAL(8,2) NOT NULL,
                `billing_code` VARCHAR(20) DEFAULT NULL,
                `ma_number` VARCHAR(20) DEFAULT NULL,
                `authorization_number` VARCHAR(50) DEFAULT NULL,
                `status` ENUM('draft','pending','approved','billed','paid','disputed','void') DEFAULT 'draft',
                `invoice_number` VARCHAR(50) DEFAULT NULL,
                `payment_date` DATE DEFAULT NULL,
                `notes` TEXT DEFAULT NULL,
                `approved_by` INT UNSIGNED DEFAULT NULL,
                `approved_at` DATETIME DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`entry_id`),
                KEY `idx_session` (`session_id`),
                KEY `idx_client_date` (`client_id`, `billing_date`),
                KEY `idx_employee_date` (`employee_id`, `billing_date`),
                KEY `idx_status` (`status`),
                KEY `idx_invoice` (`invoice_number`),
                KEY `idx_ma_number` (`ma_number`),
                CONSTRAINT `fk_billing_session` FOREIGN KEY (`session_id`) REFERENCES `autism_session` (`session_id`),
                CONSTRAINT `fk_billing_employee` FOREIGN KEY (`employee_id`) REFERENCES `autism_employees` (`employee_id`),
                CONSTRAINT `fk_billing_service_type` FOREIGN KEY (`service_type_id`) REFERENCES `autism_service_types` (`service_type_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Updated authorizations for unit-based tracking
            "CREATE TABLE IF NOT EXISTS `autism_client_authorizations` (
                `auth_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `client_id` BIGINT UNSIGNED NOT NULL,
                `service_type_id` INT UNSIGNED NOT NULL,
                `authorization_number` VARCHAR(50) NOT NULL,
                `approved_units` INT NOT NULL,
                `used_units` INT DEFAULT 0,
                `remaining_units` INT NOT NULL,
                `start_date` DATE NOT NULL,
                `end_date` DATE NOT NULL,
                `annual_pcp_date` DATE DEFAULT NULL,
                `financial_redetermination_date` DATE DEFAULT NULL,
                `status` ENUM('active','expired','exhausted','suspended') DEFAULT 'active',
                `notes` TEXT DEFAULT NULL,
                `created_by` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`auth_id`),
                KEY `idx_client` (`client_id`),
                KEY `idx_service_type` (`service_type_id`),
                KEY `idx_auth_number` (`authorization_number`),
                KEY `idx_dates` (`start_date`, `end_date`),
                CONSTRAINT `fk_auth_service_type` FOREIGN KEY (`service_type_id`) REFERENCES `autism_service_types` (`service_type_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Custom document templates
            "CREATE TABLE IF NOT EXISTS `autism_document_templates` (
                `template_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `template_type` ENUM('progress_note','treatment_plan','assessment','report','form','letter') NOT NULL,
                `category` VARCHAR(100) DEFAULT NULL,
                `json_structure` LONGTEXT NOT NULL,
                `css_styling` TEXT DEFAULT NULL,
                `is_active` TINYINT(1) DEFAULT 1,
                `is_required_signature` TINYINT(1) DEFAULT 0,
                `version` INT DEFAULT 1,
                `created_by` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`template_id`),
                KEY `idx_type` (`template_type`),
                KEY `idx_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `autism_custom_documents` (
                `document_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `template_id` INT UNSIGNED NOT NULL,
                `client_id` BIGINT UNSIGNED NOT NULL,
                `session_id` INT UNSIGNED DEFAULT NULL,
                `document_name` VARCHAR(255) NOT NULL,
                `document_data` LONGTEXT NOT NULL,
                `pdf_path` VARCHAR(500) DEFAULT NULL,
                `status` ENUM('draft','completed','signed','archived') DEFAULT 'draft',
                `signature_data` TEXT DEFAULT NULL,
                `signature_date` DATETIME DEFAULT NULL,
                `signed_by` VARCHAR(255) DEFAULT NULL,
                `created_by` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`document_id`),
                KEY `idx_template` (`template_id`),
                KEY `idx_client` (`client_id`),
                KEY `idx_session` (`session_id`),
                KEY `idx_status` (`status`),
                CONSTRAINT `fk_doc_template` FOREIGN KEY (`template_id`) REFERENCES `autism_document_templates` (`template_id`),
                CONSTRAINT `fk_doc_session` FOREIGN KEY (`session_id`) REFERENCES `autism_session` (`session_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // System settings and configuration
            "CREATE TABLE IF NOT EXISTS `autism_system_settings` (
                `setting_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `setting_key` VARCHAR(100) NOT NULL UNIQUE,
                `setting_value` TEXT DEFAULT NULL,
                `setting_type` ENUM('string','integer','decimal','boolean','json') DEFAULT 'string',
                `description` TEXT DEFAULT NULL,
                `is_system` TINYINT(1) DEFAULT 0,
                `updated_by` INT UNSIGNED DEFAULT NULL,
                `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`setting_id`),
                UNIQUE KEY `unique_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            // Audit log for compliance
            "CREATE TABLE IF NOT EXISTS `autism_audit_log` (
                `log_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `table_name` VARCHAR(100) NOT NULL,
                `record_id` INT UNSIGNED NOT NULL,
                `action` ENUM('INSERT','UPDATE','DELETE','VIEW') NOT NULL,
                `old_values` LONGTEXT DEFAULT NULL,
                `new_values` LONGTEXT DEFAULT NULL,
                `user_id` INT UNSIGNED NOT NULL,
                `ip_address` VARCHAR(45) DEFAULT NULL,
                `user_agent` TEXT DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`log_id`),
                KEY `idx_table_record` (`table_name`, `record_id`),
                KEY `idx_user` (`user_id`),
                KEY `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];

        // Execute all SQL statements
        $tableCount = 0;
        foreach ($sql_statements as $sql) {
            $result = sqlStatement($sql);
            if (!$result) {
                throw new Exception("Failed to execute SQL statement");
            }
            $tableCount++;
        }

        // Insert default programs with MA numbers
        $defaultPrograms = [
            ['Autism Waiver', 'AW', 'Autism Waiver Program for individuals with autism spectrum disorders', '410608300'],
            ['Developmental Disabilities Association', 'DDA', 'Services for individuals with developmental disabilities', 'TBA'],
            ['Community First Choice', 'CFC', 'Community-based services and supports', '522902200'],
            ['Community Options', 'CO', 'Community living options and supports', '522902200'],
            ['Community Personal Assistance Services', 'CPAS', 'Personal assistance in community settings', '522902200'],
            ['Community Supports Waiver', 'CS', 'Community-based waiver services', '433226100']
        ];

        $programIds = [];
        foreach ($defaultPrograms as $program) {
            $checkSql = "SELECT program_id FROM autism_programs WHERE abbreviation = ?";
            $exists = sqlQuery($checkSql, [$program[1]]);
            
            if (!$exists || empty($exists['program_id'])) {
                $insertSql = "INSERT INTO autism_programs 
                    (name, abbreviation, description, ma_number, created_by) 
                    VALUES (?, ?, ?, ?, ?)";
                $programId = sqlInsert($insertSql, [...$program, $_SESSION['authUserID']]);
            } else {
                $programId = $exists['program_id'];
            }
            $programIds[$program[1]] = $programId;
        }

        // Insert default service types linked to programs with unit-based rates
        $defaultServiceTypes = [
            // Autism Waiver Services
            ['AW', 'Intensive Individual Support Services', 'IISS', 'Intensive individual support services for autism waiver participants', 'W9306', 11.25, 32, 1, 15, 5, 8],
            ['AW', 'Therapeutic Integration', 'TI', 'Therapeutic integration services in community settings', 'W9308', 12.50, 24, 1, 15, 5, 8],
            ['AW', 'Intensive Therapeutic Integration', 'ITI', 'Intensive therapeutic integration services', 'W93', 15.00, 16, 1, 15, 5, 8],
            ['AW', 'Family Consultation', 'FC', 'Family consultation and training services', 'W9315', 15.00, 16, 1, 15, 5, 8],
            ['AW', 'Respite Care', 'RESP', 'Respite care services for families', 'W9314', 10.00, 48, 1, 15, 5, 8],
            
            // CFC Services
            ['CFC', 'Personal Assistance Agency', 'PA', 'Personal assistance services through agency model', 'W5519', 8.50, 64, 1, 15, 5, 8],
            
            // CO Services  
            ['CO', 'Personal Assistance Agency', 'PA', 'Personal assistance services through agency model', 'W5519', 8.50, 64, 1, 15, 5, 8],
            
            // CPAS Services
            ['CPAS', 'Personal Assistance Agency', 'PA', 'Personal assistance services in community settings', 'W5527', 8.50, 64, 1, 15, 5, 8]
        ];

        foreach ($defaultServiceTypes as $serviceType) {
            $programAbbr = $serviceType[0];
            $programId = $programIds[$programAbbr] ?? null;
            
            if ($programId) {
                $checkSql = "SELECT COUNT(*) as count FROM autism_service_types WHERE program_id = ? AND billing_code = ?";
                $exists = sqlQuery($checkSql, [$programId, $serviceType[4]]);
                
                if (!$exists || $exists['count'] == 0) {
                    $insertSql = "INSERT INTO autism_service_types 
                        (program_id, name, abbreviation, description, billing_code, default_rate_per_unit, 
                         max_daily_units, requires_authorization, unit_increment_minutes, minimum_billable_minutes, 
                         rounding_threshold_minutes, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    sqlStatement($insertSql, [
                        $programId,
                        $serviceType[1], // name
                        $serviceType[2], // abbreviation
                        $serviceType[3], // description
                        $serviceType[4], // billing_code
                        $serviceType[5], // default_rate_per_unit
                        $serviceType[6], // max_daily_units
                        $serviceType[7], // requires_authorization
                        $serviceType[8], // unit_increment_minutes
                        $serviceType[9], // minimum_billable_minutes
                        $serviceType[10], // rounding_threshold_minutes
                        $_SESSION['authUserID']
                    ]);
                }
            }
        }

        // Insert default system settings
        $defaultSettings = [
            ['company_name', 'American Caregivers Incorporated', 'string', 'Company name for reports and documents'],
            ['company_address', '123 Main St, Baltimore, MD 21201', 'string', 'Company address'],
            ['company_phone', '(410) 555-0123', 'string', 'Company phone number'],
            ['default_session_duration', '60', 'integer', 'Default session duration in minutes'],
            ['billing_unit_minutes', '15', 'integer', 'Billing increment in minutes (15-minute units)'],
            ['rounding_threshold_minutes', '8', 'integer', 'Minutes threshold for rounding up to next unit'],
            ['minimum_billable_minutes', '5', 'integer', 'Minimum billable time in minutes'],
            ['require_electronic_signature', '1', 'boolean', 'Require electronic signatures on documents'],
            ['billing_cutoff_day', '25', 'integer', 'Day of month for billing cutoff'],
            ['max_daily_units_warning', '32', 'integer', 'Units to trigger daily limit warning'],
            ['auto_clock_out_hours', '12', 'integer', 'Auto clock out after X hours'],
            ['travel_time_enabled', '1', 'boolean', 'Enable travel time tracking'],
            ['medicaid_provider_id', '', 'string', 'Medicaid provider identification number'],
            ['primary_ma_number', '410608300', 'string', 'Primary MA number for Autism Waiver services']
        ];

        foreach ($defaultSettings as $setting) {
            $checkSql = "SELECT COUNT(*) as count FROM autism_system_settings WHERE setting_key = ?";
            $exists = sqlQuery($checkSql, [$setting[0]]);
            
            if (!$exists || $exists['count'] == 0) {
                $insertSql = "INSERT INTO autism_system_settings 
                    (setting_key, setting_value, setting_type, description) 
                    VALUES (?, ?, ?, ?)";
                sqlStatement($insertSql, $setting);
            }
        }

        $messages[] = ['type' => 'success', 'text' => "Comprehensive database setup completed successfully!"];
        $messages[] = ['type' => 'info', 'text' => "{$tableCount} tables created with unit-based billing autism waiver management system"];
        $messages[] = ['type' => 'info', 'text' => 'Default programs, service types, and system settings have been configured'];
        
    } catch (Exception $e) {
        $error = true;
        $messages[] = ['type' => 'danger', 'text' => 'Error setting up database: ' . $e->getMessage()];
    }
}

// Check current database status
$tablesExist = false;
$tableCount = 0;
try {
    $tables = [
        'autism_programs', 'autism_plan', 'autism_goal', 'autism_objective', 'autism_session', 
        'autism_session_activity', 'autism_session_incident', 'autism_service_types',
        'autism_employees', 'autism_client_assignments', 'autism_client_enrollments', 'autism_schedules',
        'autism_billing_rates', 'autism_billing_entries', 'autism_client_authorizations',
        'autism_document_templates', 'autism_custom_documents', 'autism_system_settings',
        'autism_audit_log'
    ];
    
    foreach ($tables as $table) {
        $result = sqlQuery("SHOW TABLES LIKE '$table'");
        if ($result) {
            $tableCount++;
        }
    }
    $tablesExist = $tableCount === count($tables);
} catch (Exception $e) {
    $messages[] = ['type' => 'warning', 'text' => 'Could not check database status: ' . $e->getMessage()];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprehensive Database Setup - Scrive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .setup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-brain me-2"></i>
                Scrive
            </a>
            <a href="index.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i>
                Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header setup-header">
                        <h4 class="mb-0">
                            <i class="fas fa-database me-2"></i>
                            Comprehensive Database Setup
                        </h4>
                        <p class="mb-0 mt-2">Complete autism waiver management system with scheduling, billing, and document management</p>
                    </div>
                    <div class="card-body">
                        <!-- Messages -->
                        <?php foreach ($messages as $message): ?>
                            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message['text']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endforeach; ?>

                        <!-- Current Status -->
                        <div class="mb-4">
                            <h5>Current Database Status</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <?php if ($tablesExist): ?>
                                            <span class="badge bg-success me-2">
                                                <i class="fas fa-check"></i>
                                                Complete System Installed
                                            </span>
                                            <span class="text-muted"><?php echo $tableCount; ?> of 19 tables installed</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning me-2">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                System Not Complete
                                            </span>
                                            <span class="text-muted"><?php echo $tableCount; ?> of 19 tables found</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!$tablesExist): ?>
                            <!-- Setup Information -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0">Core Features</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled mb-0">
                                                <li><i class="fas fa-check text-success me-2"></i>Client Management (not "patients")</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Employee & Assignment Tracking</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Service Type Configuration</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Modern Scheduling System</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Enhanced Progress Notes</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Treatment Plan Management</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-success text-white">
                                            <h6 class="mb-0">Advanced Features</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled mb-0">
                                                <li><i class="fas fa-check text-success me-2"></i>Comprehensive Billing System</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Time Tracking & Payroll</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Authorization Management</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Custom Document Builder</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Audit Logging</li>
                                                <li><i class="fas fa-check text-success me-2"></i>System Configuration</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Database Tables List -->
                            <div class="mb-4">
                                <h6>Database Tables to be Created (19 tables):</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_programs</code> - Program definitions with MA numbers</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_plan</code> - Treatment plans</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_goal</code> - Treatment goals</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_objective</code> - Goal objectives</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_session</code> - Session records</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_session_activity</code> - Activity logs</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_session_incident</code> - Incident reports</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_service_types</code> - Service configuration</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_employees</code> - Staff management</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_client_assignments</code> - Client-staff assignments</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_client_enrollments</code> - Program enrollments</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_schedules</code> - Appointment scheduling</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_billing_rates</code> - Unit rate management</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_billing_entries</code> - Unit billing tracking</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_client_authorizations</code> - Unit authorization tracking</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_document_templates</code> - Custom document templates</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_custom_documents</code> - Generated documents</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_system_settings</code> - System configuration</li>
                                            <li><i class="fas fa-table text-primary me-2"></i><code>autism_audit_log</code> - Compliance audit trail</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Setup Form -->
                            <div class="border rounded p-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                                <h5 class="text-primary">
                                    <i class="fas fa-rocket me-2"></i>
                                    Initialize Complete Autism Waiver System
                                </h5>
                                <p class="mb-3">
                                    This will create a comprehensive management system with all advanced features for autism waiver services.
                                    <strong>Note:</strong> All references use "client" terminology as required for autism waiver services.
                                </p>

                                <form method="post" onsubmit="return confirm('Are you sure you want to create the complete database system? This will install 19 tables and configure the entire autism waiver management system.');">
                                    <input type="hidden" name="action" value="setup">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="confirm" value="yes" id="confirmSetup" required>
                                        <label class="form-check-label" for="confirmSetup">
                                            I understand this will create the complete autism waiver management system with 19 database tables, service types, and system settings
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-play me-2"></i>
                                        Install Complete System
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <!-- Already Installed -->
                            <div class="alert alert-success" role="alert">
                                <h5 class="alert-heading">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Complete System Ready!
                                </h5>
                                <p>
                                    The comprehensive autism waiver management system is installed and ready to use.
                                    You can now manage clients, employees, scheduling, billing, and custom documents.
                                </p>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Quick Start:</h6>
                                        <ol>
                                            <li>Configure service types</li>
                                            <li>Add employees</li>
                                            <li>Assign clients to employees</li>
                                            <li>Start scheduling and documenting</li>
                                        </ol>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Next Steps:</h6>
                                        <a href="service_types.php" class="btn btn-outline-primary btn-sm me-2 mb-2">
                                            <i class="fas fa-cogs me-1"></i>Service Types
                                        </a>
                                        <a href="employees.php" class="btn btn-outline-success btn-sm me-2 mb-2">
                                            <i class="fas fa-users me-1"></i>Employees
                                        </a>
                                        <a href="schedule_dashboard.php" class="btn btn-outline-info btn-sm me-2 mb-2">
                                            <i class="fas fa-calendar me-1"></i>Scheduling
                                        </a>
                                        <a href="billing_dashboard.php" class="btn btn-outline-warning btn-sm me-2 mb-2">
                                            <i class="fas fa-dollar-sign me-1"></i>Billing
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center">
                                <a href="index.php" class="btn btn-success btn-lg">
                                    <i class="fas fa-arrow-right me-2"></i>
                                    Go to Dashboard
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 