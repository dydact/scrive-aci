-- Clinical Documentation System for Autism Waiver Services
-- Designed for Maryland Autism Waiver compliance

-- 1. Client Medical Information (simplified for autism waiver)
CREATE TABLE IF NOT EXISTS `autism_client_medical_info` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `diagnoses` TEXT COMMENT 'ICD-10 codes and descriptions',
    `primary_diagnosis` VARCHAR(10) DEFAULT 'F84.0' COMMENT 'Autism Spectrum Disorder',
    `medications` TEXT COMMENT 'Current medications JSON',
    `allergies` TEXT,
    `medical_conditions` TEXT,
    `physician_name` VARCHAR(255),
    `physician_phone` VARCHAR(20),
    `pharmacy_name` VARCHAR(255),
    `pharmacy_phone` VARCHAR(20),
    `emergency_contact_1` VARCHAR(255),
    `emergency_phone_1` VARCHAR(20),
    `emergency_contact_2` VARCHAR(255),
    `emergency_phone_2` VARCHAR(20),
    `updated_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_client` (`client_id`),
    CONSTRAINT `fk_medical_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_medical_updated_by` FOREIGN KEY (`updated_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Treatment Plan Goals (structured for autism waiver)
CREATE TABLE IF NOT EXISTS `autism_treatment_goals` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `plan_id` INT(11) NOT NULL,
    `goal_category` ENUM('communication','social_skills','daily_living','behavior','academic','recreation','adaptive') NOT NULL,
    `goal_text` TEXT NOT NULL,
    `objective` TEXT COMMENT 'Measurable objective',
    `baseline` TEXT COMMENT 'Current baseline level',
    `target_date` DATE,
    `target_criteria` VARCHAR(255) COMMENT 'e.g., 80% accuracy over 3 sessions',
    `measurement_method` VARCHAR(255) COMMENT 'How progress is measured',
    `frequency` VARCHAR(100) COMMENT 'How often to work on goal',
    `status` ENUM('active','achieved','discontinued','modified') DEFAULT 'active',
    `progress_level` INT DEFAULT 0 COMMENT 'Overall progress 0-100%',
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_plan` (`plan_id`),
    KEY `idx_category` (`goal_category`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_goal_plan` FOREIGN KEY (`plan_id`) 
        REFERENCES `autism_treatment_plans` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_goal_created_by` FOREIGN KEY (`created_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Clinical Note Templates
CREATE TABLE IF NOT EXISTS `autism_note_templates` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `template_name` VARCHAR(255) NOT NULL,
    `template_type` ENUM('iiss_session','behavior_incident','progress_note','supervision','assessment') NOT NULL,
    `template_content` JSON NOT NULL COMMENT 'Template structure and fields',
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_type` (`template_type`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. IISS Session Notes (Maryland Autism Waiver compliant)
CREATE TABLE IF NOT EXISTS `autism_iiss_notes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `staff_id` INT(11) NOT NULL,
    `session_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `total_minutes` INT NOT NULL,
    `location` VARCHAR(255) COMMENT 'Home, community, etc.',
    `session_type` ENUM('direct_service','make_up','crisis_intervention') DEFAULT 'direct_service',
    `goals_addressed` JSON COMMENT 'Array of goal IDs worked on',
    `activities` TEXT NOT NULL COMMENT 'Activities performed during session',
    `client_response` TEXT NOT NULL COMMENT 'How client responded',
    `progress_notes` TEXT COMMENT 'Detailed progress observations',
    `behavior_incidents` TEXT COMMENT 'Any behavioral incidents',
    `parent_communication` TEXT COMMENT 'Communication with parents/caregivers',
    `next_session_plan` TEXT COMMENT 'Plans for next session',
    `supervisor_notes` TEXT COMMENT 'Supervisor review notes',
    `supervisor_id` INT(11),
    `supervisor_reviewed_at` TIMESTAMP NULL,
    `status` ENUM('draft','submitted','approved','needs_revision') DEFAULT 'draft',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_date` (`client_id`, `session_date`),
    KEY `idx_staff_date` (`staff_id`, `session_date`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_iiss_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_iiss_staff` FOREIGN KEY (`staff_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_iiss_supervisor` FOREIGN KEY (`supervisor_id`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Goal Progress Tracking
CREATE TABLE IF NOT EXISTS `autism_goal_progress` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `goal_id` INT(11) NOT NULL,
    `session_note_id` INT(11),
    `iiss_note_id` INT(11),
    `progress_date` DATE NOT NULL,
    `progress_rating` INT NOT NULL COMMENT '1-5 scale',
    `trials_correct` INT COMMENT 'Number of correct responses',
    `trials_total` INT COMMENT 'Total number of trials',
    `percentage` DECIMAL(5,2) COMMENT 'Calculated percentage',
    `notes` TEXT,
    `recorded_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_goal_date` (`goal_id`, `progress_date`),
    KEY `idx_session_note` (`session_note_id`),
    KEY `idx_iiss_note` (`iiss_note_id`),
    CONSTRAINT `fk_progress_goal` FOREIGN KEY (`goal_id`) 
        REFERENCES `autism_treatment_goals` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_progress_session` FOREIGN KEY (`session_note_id`) 
        REFERENCES `autism_session_notes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_progress_iiss` FOREIGN KEY (`iiss_note_id`) 
        REFERENCES `autism_iiss_notes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_progress_recorded_by` FOREIGN KEY (`recorded_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Behavior Incident Reports
CREATE TABLE IF NOT EXISTS `autism_behavior_incidents` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `incident_date` DATETIME NOT NULL,
    `location` VARCHAR(255),
    `antecedent` TEXT COMMENT 'What happened before',
    `behavior` TEXT NOT NULL COMMENT 'Description of behavior',
    `consequence` TEXT COMMENT 'What happened after',
    `duration_minutes` INT,
    `severity` ENUM('mild','moderate','severe') DEFAULT 'moderate',
    `intervention_used` TEXT,
    `outcome` TEXT,
    `injuries` BOOLEAN DEFAULT FALSE,
    `injury_description` TEXT,
    `witnesses` TEXT,
    `parent_notified` BOOLEAN DEFAULT FALSE,
    `parent_notification_time` DATETIME,
    `follow_up_required` BOOLEAN DEFAULT FALSE,
    `follow_up_notes` TEXT,
    `reported_by` INT(11) NOT NULL,
    `reviewed_by` INT(11),
    `reviewed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_date` (`client_id`, `incident_date`),
    KEY `idx_severity` (`severity`),
    KEY `idx_follow_up` (`follow_up_required`),
    CONSTRAINT `fk_incident_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_incident_reported_by` FOREIGN KEY (`reported_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_incident_reviewed_by` FOREIGN KEY (`reviewed_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Assessment Tools
CREATE TABLE IF NOT EXISTS `autism_assessments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `assessment_type` VARCHAR(50) COMMENT 'CARS, ADOS, VB-MAPP, etc.',
    `assessment_date` DATE NOT NULL,
    `assessor_id` INT(11) NOT NULL,
    `assessment_data` JSON COMMENT 'Structured assessment results',
    `score` VARCHAR(50),
    `interpretation` TEXT,
    `recommendations` TEXT,
    `next_assessment_date` DATE,
    `status` ENUM('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_type` (`client_id`, `assessment_type`),
    KEY `idx_date` (`assessment_date`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_assessment_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_assessment_assessor` FOREIGN KEY (`assessor_id`) 
        REFERENCES `autism_users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Medication Administration Records (MAR)
CREATE TABLE IF NOT EXISTS `autism_medication_records` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `medication_name` VARCHAR(255) NOT NULL,
    `dosage` VARCHAR(100),
    `route` VARCHAR(50) COMMENT 'Oral, topical, etc.',
    `frequency` VARCHAR(100),
    `prescriber` VARCHAR(255),
    `start_date` DATE,
    `end_date` DATE,
    `active` BOOLEAN DEFAULT TRUE,
    `notes` TEXT,
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_active` (`client_id`, `active`),
    CONSTRAINT `fk_medication_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_medication_created_by` FOREIGN KEY (`created_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default IISS note template
INSERT INTO `autism_note_templates` (`template_name`, `template_type`, `template_content`) VALUES
('IISS Session Note', 'iiss_session', '{
    "sections": [
        {
            "name": "session_info",
            "title": "Session Information",
            "fields": [
                {"name": "date", "type": "date", "required": true},
                {"name": "start_time", "type": "time", "required": true},
                {"name": "end_time", "type": "time", "required": true},
                {"name": "location", "type": "select", "options": ["Home", "Community", "School", "Other"], "required": true}
            ]
        },
        {
            "name": "goals_worked",
            "title": "Goals Addressed",
            "fields": [
                {"name": "goals", "type": "goal_selector", "required": true},
                {"name": "goal_progress", "type": "rating_scale", "min": 1, "max": 5}
            ]
        },
        {
            "name": "session_details",
            "title": "Session Details",
            "fields": [
                {"name": "activities", "type": "textarea", "prompt": "Describe activities performed", "required": true},
                {"name": "client_response", "type": "textarea", "prompt": "How did the client respond?", "required": true},
                {"name": "progress_notes", "type": "textarea", "prompt": "Detailed observations of progress"},
                {"name": "challenges", "type": "textarea", "prompt": "Any challenges encountered?"}
            ]
        },
        {
            "name": "communication",
            "title": "Parent/Caregiver Communication",
            "fields": [
                {"name": "parent_present", "type": "checkbox", "label": "Parent/caregiver present"},
                {"name": "communication_notes", "type": "textarea", "prompt": "Communication with family"}
            ]
        },
        {
            "name": "next_steps",
            "title": "Next Session Planning",
            "fields": [
                {"name": "next_session_plan", "type": "textarea", "prompt": "Plans for next session"},
                {"name": "materials_needed", "type": "text", "prompt": "Materials to prepare"}
            ]
        }
    ]
}');

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_iiss_month ON autism_iiss_notes(YEAR(session_date), MONTH(session_date));
CREATE INDEX IF NOT EXISTS idx_goal_progress_month ON autism_goal_progress(YEAR(progress_date), MONTH(progress_date));

-- Grant permissions
-- GRANT SELECT, INSERT, UPDATE ON autism_client_medical_info TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_treatment_goals TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_note_templates TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_iiss_notes TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_goal_progress TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_behavior_incidents TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_assessments TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_medication_records TO 'iris_user'@'localhost';