-- Scheduling and Resource Management System
-- Advanced scheduling for autism waiver services

-- 1. Recurring Schedule Templates
CREATE TABLE IF NOT EXISTS `autism_schedule_templates` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `template_name` VARCHAR(255) NOT NULL,
    `client_id` INT(11) NOT NULL,
    `staff_id` INT(11),
    `service_type_id` INT(11) NOT NULL,
    `day_of_week` ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `duration_minutes` INT GENERATED ALWAYS AS (TIMESTAMPDIFF(MINUTE, start_time, end_time)) STORED,
    `location` VARCHAR(255),
    `effective_date` DATE NOT NULL,
    `end_date` DATE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `notes` TEXT,
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_day` (`client_id`, `day_of_week`),
    KEY `idx_staff_day` (`staff_id`, `day_of_week`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_template_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_template_staff` FOREIGN KEY (`staff_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_template_service` FOREIGN KEY (`service_type_id`) 
        REFERENCES `autism_service_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Advanced Appointments (extends basic schedules table)
CREATE TABLE IF NOT EXISTS `autism_appointments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `staff_id` INT(11),
    `service_type_id` INT(11) NOT NULL,
    `appointment_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `duration_minutes` INT GENERATED ALWAYS AS (TIMESTAMPDIFF(MINUTE, start_time, end_time)) STORED,
    `location` VARCHAR(255),
    `status` ENUM('scheduled','confirmed','in_progress','completed','cancelled','no_show','rescheduled') DEFAULT 'scheduled',
    `appointment_type` ENUM('regular','make_up','evaluation','crisis','group') DEFAULT 'regular',
    `group_session_id` INT(11) COMMENT 'For group sessions',
    `recurring_template_id` INT(11) COMMENT 'Link to recurring template',
    `confirmation_sent` BOOLEAN DEFAULT FALSE,
    `reminder_sent` BOOLEAN DEFAULT FALSE,
    `cancellation_reason` TEXT,
    `cancelled_by` INT(11),
    `cancelled_at` TIMESTAMP NULL,
    `notes` TEXT,
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_date_time` (`appointment_date`, `start_time`),
    KEY `idx_client_date` (`client_id`, `appointment_date`),
    KEY `idx_staff_date` (`staff_id`, `appointment_date`),
    KEY `idx_status` (`status`),
    KEY `idx_group` (`group_session_id`),
    CONSTRAINT `fk_appt_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_appt_staff` FOREIGN KEY (`staff_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_appt_service` FOREIGN KEY (`service_type_id`) 
        REFERENCES `autism_service_types` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_appt_template` FOREIGN KEY (`recurring_template_id`) 
        REFERENCES `autism_schedule_templates` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_appt_cancelled_by` FOREIGN KEY (`cancelled_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Group Sessions Management
CREATE TABLE IF NOT EXISTS `autism_group_sessions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `session_name` VARCHAR(255) NOT NULL,
    `service_type_id` INT(11) NOT NULL,
    `max_participants` INT DEFAULT 6,
    `current_participants` INT DEFAULT 0,
    `session_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `location` VARCHAR(255),
    `lead_staff_id` INT(11) NOT NULL,
    `support_staff_ids` JSON COMMENT 'Array of additional staff IDs',
    `status` ENUM('open','full','in_progress','completed','cancelled') DEFAULT 'open',
    `notes` TEXT,
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_date_status` (`session_date`, `status`),
    KEY `idx_lead_staff` (`lead_staff_id`),
    CONSTRAINT `fk_group_service` FOREIGN KEY (`service_type_id`) 
        REFERENCES `autism_service_types` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_group_lead_staff` FOREIGN KEY (`lead_staff_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Waitlist Management
CREATE TABLE IF NOT EXISTS `autism_waitlist` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `service_type_id` INT(11) NOT NULL,
    `requested_days` JSON COMMENT 'Preferred days of week',
    `requested_times` JSON COMMENT 'Preferred time slots',
    `priority` ENUM('urgent','high','normal','low') DEFAULT 'normal',
    `waitlist_date` DATE NOT NULL DEFAULT (CURRENT_DATE),
    `estimated_start_date` DATE,
    `status` ENUM('waiting','offered','accepted','declined','expired') DEFAULT 'waiting',
    `notes` TEXT,
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_service` (`client_id`, `service_type_id`),
    KEY `idx_priority_date` (`priority`, `waitlist_date`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_waitlist_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_waitlist_service` FOREIGN KEY (`service_type_id`) 
        REFERENCES `autism_service_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Staff Shifts and Availability
CREATE TABLE IF NOT EXISTS `autism_staff_shifts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `shift_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `shift_type` ENUM('regular','overtime','on_call','training','admin') DEFAULT 'regular',
    `total_hours` DECIMAL(5,2) GENERATED ALWAYS AS (TIMESTAMPDIFF(MINUTE, start_time, end_time) / 60) STORED,
    `break_minutes` INT DEFAULT 0,
    `net_hours` DECIMAL(5,2) GENERATED ALWAYS AS ((TIMESTAMPDIFF(MINUTE, start_time, end_time) - break_minutes) / 60) STORED,
    `status` ENUM('scheduled','confirmed','in_progress','completed','cancelled') DEFAULT 'scheduled',
    `notes` TEXT,
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_staff_shift` (`staff_id`, `shift_date`, `start_time`),
    KEY `idx_date` (`shift_date`),
    KEY `idx_staff_week` (`staff_id`, `shift_date`),
    CONSTRAINT `fk_shift_staff` FOREIGN KEY (`staff_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Staff Availability Patterns
CREATE TABLE IF NOT EXISTS `autism_staff_availability` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `day_of_week` ENUM('monday','tuesday','wednesday','thursday','friday','saturday','sunday') NOT NULL,
    `available_start` TIME NOT NULL,
    `available_end` TIME NOT NULL,
    `effective_date` DATE NOT NULL,
    `end_date` DATE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `notes` TEXT,
    PRIMARY KEY (`id`),
    KEY `idx_staff_day` (`staff_id`, `day_of_week`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_availability_staff` FOREIGN KEY (`staff_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Resource/Room Booking
CREATE TABLE IF NOT EXISTS `autism_resources` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `resource_name` VARCHAR(255) NOT NULL,
    `resource_type` ENUM('room','equipment','vehicle','material') NOT NULL,
    `location` VARCHAR(255),
    `capacity` INT,
    `description` TEXT,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_type` (`resource_type`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Resource Bookings
CREATE TABLE IF NOT EXISTS `autism_resource_bookings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `resource_id` INT(11) NOT NULL,
    `appointment_id` INT(11),
    `group_session_id` INT(11),
    `booking_date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `purpose` VARCHAR(255),
    `booked_by` INT(11) NOT NULL,
    `status` ENUM('reserved','confirmed','cancelled') DEFAULT 'reserved',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_resource_date` (`resource_id`, `booking_date`),
    KEY `idx_appointment` (`appointment_id`),
    KEY `idx_group_session` (`group_session_id`),
    CONSTRAINT `fk_booking_resource` FOREIGN KEY (`resource_id`) 
        REFERENCES `autism_resources` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_booking_appointment` FOREIGN KEY (`appointment_id`) 
        REFERENCES `autism_appointments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_booking_group` FOREIGN KEY (`group_session_id`) 
        REFERENCES `autism_group_sessions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_booking_by` FOREIGN KEY (`booked_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Calendar Integration Settings
CREATE TABLE IF NOT EXISTS `autism_calendar_integrations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `integration_type` ENUM('google','outlook','ical','caldav') NOT NULL,
    `calendar_id` VARCHAR(255),
    `access_token` TEXT,
    `refresh_token` TEXT,
    `sync_enabled` BOOLEAN DEFAULT TRUE,
    `last_sync` TIMESTAMP NULL,
    `sync_direction` ENUM('one_way_to_external','one_way_from_external','two_way') DEFAULT 'one_way_to_external',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_type` (`user_id`, `integration_type`),
    CONSTRAINT `fk_calendar_user` FOREIGN KEY (`user_id`) 
        REFERENCES `autism_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create views for scheduling

-- View for staff weekly hours including overtime
CREATE OR REPLACE VIEW `v_staff_weekly_hours` AS
SELECT 
    s.staff_id,
    sm.first_name,
    sm.last_name,
    YEARWEEK(s.shift_date) as work_week,
    SUM(s.net_hours) as total_hours,
    SUM(CASE WHEN s.shift_type = 'regular' THEN s.net_hours ELSE 0 END) as regular_hours,
    SUM(CASE WHEN s.shift_type = 'overtime' THEN s.net_hours ELSE 0 END) as overtime_hours,
    GREATEST(0, SUM(s.net_hours) - 40) as calculated_overtime,
    COUNT(DISTINCT s.shift_date) as days_worked
FROM autism_staff_shifts s
JOIN autism_staff_members sm ON s.staff_id = sm.id
WHERE s.status IN ('confirmed', 'in_progress', 'completed')
GROUP BY s.staff_id, YEARWEEK(s.shift_date);

-- View for appointment utilization
CREATE OR REPLACE VIEW `v_appointment_utilization` AS
SELECT 
    DATE(a.appointment_date) as date,
    COUNT(*) as total_appointments,
    SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN a.status = 'no_show' THEN 1 ELSE 0 END) as no_shows,
    SUM(a.duration_minutes) as total_minutes_scheduled,
    SUM(CASE WHEN a.status = 'completed' THEN a.duration_minutes ELSE 0 END) as total_minutes_delivered,
    ROUND(SUM(CASE WHEN a.status = 'completed' THEN a.duration_minutes ELSE 0 END) * 100.0 / 
          NULLIF(SUM(a.duration_minutes), 0), 2) as utilization_rate
FROM autism_appointments a
GROUP BY DATE(a.appointment_date);

-- Stored procedure to generate recurring appointments
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS sp_generate_recurring_appointments(
    IN p_template_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    DECLARE v_current_date DATE;
    DECLARE v_day_of_week VARCHAR(20);
    DECLARE v_template_day VARCHAR(20);
    DECLARE done INT DEFAULT FALSE;
    
    -- Get template details
    SELECT day_of_week INTO v_template_day
    FROM autism_schedule_templates
    WHERE id = p_template_id AND is_active = TRUE;
    
    SET v_current_date = p_start_date;
    
    WHILE v_current_date <= p_end_date DO
        SET v_day_of_week = LOWER(DAYNAME(v_current_date));
        
        IF v_day_of_week = v_template_day THEN
            -- Insert appointment from template
            INSERT INTO autism_appointments (
                client_id, staff_id, service_type_id, appointment_date,
                start_time, end_time, location, status, appointment_type,
                recurring_template_id, created_by
            )
            SELECT 
                client_id, staff_id, service_type_id, v_current_date,
                start_time, end_time, location, 'scheduled', 'regular',
                id, created_by
            FROM autism_schedule_templates
            WHERE id = p_template_id
            AND NOT EXISTS (
                SELECT 1 FROM autism_appointments
                WHERE client_id = (SELECT client_id FROM autism_schedule_templates WHERE id = p_template_id)
                AND appointment_date = v_current_date
                AND start_time = (SELECT start_time FROM autism_schedule_templates WHERE id = p_template_id)
            );
        END IF;
        
        SET v_current_date = DATE_ADD(v_current_date, INTERVAL 1 DAY);
    END WHILE;
END//

DELIMITER ;

-- Insert sample resources
INSERT INTO `autism_resources` (`resource_name`, `resource_type`, `location`, `capacity`, `description`) VALUES
('Therapy Room A', 'room', 'Main Building', 1, 'Individual therapy room with sensory equipment'),
('Therapy Room B', 'room', 'Main Building', 1, 'Individual therapy room with play area'),
('Group Activity Room', 'room', 'Main Building', 8, 'Large room for group sessions'),
('Sensory Room', 'room', 'Main Building', 2, 'Specialized sensory integration room'),
('iPad Therapy Kit', 'equipment', 'Supply Room', NULL, 'iPad with communication apps'),
('Sensory Kit', 'equipment', 'Supply Room', NULL, 'Portable sensory tools and fidgets')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Grant permissions
-- GRANT SELECT, INSERT, UPDATE ON autism_schedule_templates TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_appointments TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_group_sessions TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_waitlist TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_staff_shifts TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_staff_availability TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_resources TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_resource_bookings TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_calendar_integrations TO 'iris_user'@'localhost';
-- GRANT SELECT ON v_staff_weekly_hours TO 'iris_user'@'localhost';
-- GRANT SELECT ON v_appointment_utilization TO 'iris_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE sp_generate_recurring_appointments TO 'iris_user'@'localhost';