-- Supervisor Approval System Tables
-- Creates tables for tracking various approval workflows

-- 1. Add supervisor-related columns to existing tables if they don't exist
ALTER TABLE `autism_session_notes` 
ADD COLUMN IF NOT EXISTS `supervisor_comments` TEXT AFTER `additional_notes`,
ADD COLUMN IF NOT EXISTS `duration_minutes` INT(11) GENERATED ALWAYS AS (TIME_TO_SEC(TIMEDIFF(end_time, start_time)) / 60) STORED,
ADD COLUMN IF NOT EXISTS `created_by` INT(11) AFTER `staff_id`,
ADD KEY IF NOT EXISTS `idx_created_at` (`created_at`),
ADD CONSTRAINT IF NOT EXISTS `fk_session_created_by` FOREIGN KEY (`created_by`) 
    REFERENCES `autism_users` (`id`) ON DELETE SET NULL;

-- 2. Add columns to time clock for approval workflow
ALTER TABLE `autism_time_clock` 
ADD COLUMN IF NOT EXISTS `status` ENUM('active','pending','approved','rejected') DEFAULT 'active' AFTER `total_hours`,
ADD COLUMN IF NOT EXISTS `approved_by` INT(11) AFTER `status`,
ADD COLUMN IF NOT EXISTS `approved_at` TIMESTAMP NULL AFTER `approved_by`,
ADD COLUMN IF NOT EXISTS `supervisor_notes` TEXT AFTER `notes`,
ADD COLUMN IF NOT EXISTS `manual_override` BOOLEAN DEFAULT FALSE AFTER `is_billable`,
ADD CONSTRAINT IF NOT EXISTS `fk_time_clock_approved_by` FOREIGN KEY (`approved_by`) 
    REFERENCES `autism_users` (`id`) ON DELETE SET NULL;

-- 3. Create schedule changes tracking table
CREATE TABLE IF NOT EXISTS `autism_schedule_changes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `schedule_id` INT(11) NOT NULL,
    `requested_by` INT(11) NOT NULL,
    `change_type` ENUM('reschedule','cancel','add','swap') NOT NULL,
    `original_date` DATE,
    `original_start_time` TIME,
    `original_end_time` TIME,
    `new_date` DATE,
    `new_start_time` TIME,
    `new_end_time` TIME,
    `swap_with_staff_id` INT(11),
    `reason` TEXT NOT NULL,
    `change_date` DATE NOT NULL,
    `status` ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending',
    `approved_by` INT(11),
    `approved_at` TIMESTAMP NULL,
    `approval_notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_schedule` (`schedule_id`),
    KEY `idx_requested_by` (`requested_by`),
    KEY `idx_status` (`status`),
    KEY `idx_change_date` (`change_date`),
    CONSTRAINT `fk_schedule_change_schedule` FOREIGN KEY (`schedule_id`) 
        REFERENCES `autism_schedules` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_schedule_change_requested` FOREIGN KEY (`requested_by`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_schedule_change_approved` FOREIGN KEY (`approved_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_schedule_change_swap` FOREIGN KEY (`swap_with_staff_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Create time off requests table
CREATE TABLE IF NOT EXISTS `autism_time_off_requests` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `request_type` ENUM('vacation','sick','personal','bereavement','jury_duty','fmla','other') NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `total_days` DECIMAL(5,2) GENERATED ALWAYS AS (DATEDIFF(end_date, start_date) + 1) STORED,
    `reason` TEXT,
    `coverage_arranged` BOOLEAN DEFAULT FALSE,
    `coverage_notes` TEXT,
    `status` ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending',
    `approved_by` INT(11),
    `approved_at` TIMESTAMP NULL,
    `approval_comments` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_staff` (`staff_id`),
    KEY `idx_dates` (`start_date`, `end_date`),
    KEY `idx_status` (`status`),
    KEY `idx_created` (`created_at`),
    CONSTRAINT `fk_time_off_staff` FOREIGN KEY (`staff_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_time_off_approved` FOREIGN KEY (`approved_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create audit log table for tracking all approvals
CREATE TABLE IF NOT EXISTS `autism_audit_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `record_type` VARCHAR(50) NOT NULL,
    `record_id` INT(11) NOT NULL,
    `old_value` JSON,
    `new_value` JSON,
    `details` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_action` (`action`),
    KEY `idx_record` (`record_type`, `record_id`),
    KEY `idx_created` (`created_at`),
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) 
        REFERENCES `autism_users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create approval rules configuration table
CREATE TABLE IF NOT EXISTS `autism_approval_rules` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `rule_name` VARCHAR(100) NOT NULL,
    `rule_type` ENUM('session_late','time_discrepancy','schedule_change','time_off','billing_adjustment') NOT NULL,
    `threshold_hours` INT(11) COMMENT 'Hours threshold for late submissions',
    `threshold_amount` DECIMAL(10,2) COMMENT 'Dollar threshold for billing',
    `threshold_percentage` DECIMAL(5,2) COMMENT 'Percentage threshold for discrepancies',
    `auto_approve` BOOLEAN DEFAULT FALSE,
    `require_comment` BOOLEAN DEFAULT TRUE,
    `notification_enabled` BOOLEAN DEFAULT TRUE,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_rule_type` (`rule_type`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Insert default approval rules
INSERT INTO `autism_approval_rules` (`rule_name`, `rule_type`, `threshold_hours`, `require_comment`) VALUES
('Late Session Notes', 'session_late', 48, TRUE),
('Time Clock Discrepancies', 'time_discrepancy', NULL, TRUE),
('Schedule Changes', 'schedule_change', NULL, FALSE),
('Time Off Requests', 'time_off', NULL, FALSE),
('Billing Adjustments', 'billing_adjustment', NULL, TRUE)
ON DUPLICATE KEY UPDATE `id`=`id`;

-- 8. Create notifications table for approval requests
CREATE TABLE IF NOT EXISTS `autism_approval_notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `recipient_id` INT(11) NOT NULL,
    `sender_id` INT(11),
    `notification_type` ENUM('approval_required','approved','rejected','reminder') NOT NULL,
    `record_type` VARCHAR(50) NOT NULL,
    `record_id` INT(11) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT,
    `is_read` BOOLEAN DEFAULT FALSE,
    `read_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_recipient` (`recipient_id`, `is_read`),
    KEY `idx_record` (`record_type`, `record_id`),
    KEY `idx_created` (`created_at`),
    CONSTRAINT `fk_notification_recipient` FOREIGN KEY (`recipient_id`) 
        REFERENCES `autism_users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notification_sender` FOREIGN KEY (`sender_id`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Create view for approval dashboard statistics
CREATE OR REPLACE VIEW `v_approval_stats` AS
SELECT 
    u.id as supervisor_id,
    CONCAT(u.first_name, ' ', u.last_name) as supervisor_name,
    -- Session notes stats
    (SELECT COUNT(*) FROM autism_session_notes sn 
     WHERE sn.status IN ('completed', 'pending_approval')
     AND sn.created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)) as late_sessions,
    (SELECT COUNT(*) FROM autism_session_notes sn 
     WHERE sn.status IN ('completed', 'pending_approval')) as pending_sessions,
    -- Time clock stats
    (SELECT COUNT(*) FROM autism_time_clock tc 
     WHERE tc.status = 'pending' 
     AND (tc.manual_override = 1 OR 
          ABS(TIME_TO_SEC(TIMEDIFF(tc.clock_out, tc.clock_in)) - (tc.total_hours * 3600)) > 300)) as time_discrepancies,
    -- Schedule change stats
    (SELECT COUNT(*) FROM autism_schedule_changes sc 
     WHERE sc.status = 'pending') as pending_schedule_changes,
    -- Time off stats
    (SELECT COUNT(*) FROM autism_time_off_requests tor 
     WHERE tor.status = 'pending') as pending_time_off,
    -- Billing stats
    (SELECT COUNT(*) FROM autism_billing_entries be 
     WHERE be.status IN ('pending', 'disputed')) as pending_billing
FROM autism_users u
WHERE u.access_level >= 4;

-- 10. Create stored procedure for bulk approvals
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS sp_bulk_approve_items(
    IN p_supervisor_id INT,
    IN p_item_type VARCHAR(50),
    IN p_item_ids TEXT,
    IN p_comments TEXT
)
BEGIN
    DECLARE v_id INT;
    DECLARE v_pos INT DEFAULT 1;
    DECLARE v_len INT;
    
    -- Start transaction
    START TRANSACTION;
    
    -- Process each ID in the comma-separated list
    WHILE v_pos > 0 DO
        SET v_pos = LOCATE(',', p_item_ids);
        IF v_pos > 0 THEN
            SET v_id = SUBSTRING(p_item_ids, 1, v_pos - 1);
            SET p_item_ids = SUBSTRING(p_item_ids, v_pos + 1);
        ELSE
            SET v_id = p_item_ids;
        END IF;
        
        -- Approve based on type
        CASE p_item_type
            WHEN 'session' THEN
                UPDATE autism_session_notes 
                SET status = 'approved', 
                    approved_by = p_supervisor_id, 
                    approved_at = NOW(),
                    supervisor_comments = CONCAT(IFNULL(supervisor_comments, ''), 
                                                IF(p_comments != '', CONCAT('\n[Approved] ', p_comments), ''))
                WHERE id = v_id AND status IN ('completed', 'pending_approval');
                
            WHEN 'time' THEN
                UPDATE autism_time_clock 
                SET status = 'approved',
                    approved_by = p_supervisor_id,
                    approved_at = NOW(),
                    supervisor_notes = CONCAT(IFNULL(supervisor_notes, ''), 
                                            IF(p_comments != '', CONCAT('\n[Approved] ', p_comments), ''))
                WHERE id = v_id AND status = 'pending';
                
            WHEN 'billing' THEN
                UPDATE autism_billing_entries 
                SET status = 'approved',
                    approved_by = p_supervisor_id,
                    approved_at = NOW(),
                    notes = CONCAT(IFNULL(notes, ''), 
                                  IF(p_comments != '', CONCAT('\n[Approved] ', p_comments), ''))
                WHERE entry_id = v_id AND status IN ('pending', 'disputed');
        END CASE;
        
        -- Log the approval
        INSERT INTO autism_audit_log (user_id, action, record_type, record_id, details)
        VALUES (p_supervisor_id, 'bulk_approve', p_item_type, v_id, 
                JSON_OBJECT('comments', p_comments));
        
        IF v_pos = 0 THEN
            LEAVE;
        END IF;
    END WHILE;
    
    COMMIT;
END//

DELIMITER ;

-- Grant permissions for supervisor role
-- GRANT SELECT, UPDATE ON autism_session_notes TO 'iris_user'@'localhost';
-- GRANT SELECT, UPDATE ON autism_time_clock TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_schedule_changes TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_time_off_requests TO 'iris_user'@'localhost';
-- GRANT SELECT, UPDATE ON autism_billing_entries TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT ON autism_audit_log TO 'iris_user'@'localhost';
-- GRANT SELECT ON v_approval_stats TO 'iris_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE sp_bulk_approve_items TO 'iris_user'@'localhost';