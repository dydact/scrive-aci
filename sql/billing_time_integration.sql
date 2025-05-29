-- Billing and Time Integration Tables for Scrive ACI
-- Creates missing tables and links time tracking to billing

-- 1. Create the missing billing entries table
CREATE TABLE IF NOT EXISTS `autism_billing_entries` (
    `entry_id` INT(11) NOT NULL AUTO_INCREMENT,
    `employee_id` INT(11) NOT NULL,
    `client_id` INT(11) NOT NULL,
    `session_note_id` INT(11) DEFAULT NULL,
    `time_clock_id` INT(11) DEFAULT NULL,
    `billing_date` DATE NOT NULL,
    `service_type_id` INT(11) DEFAULT NULL,
    `billing_code` VARCHAR(20) DEFAULT NULL,
    `total_minutes` INT(11) NOT NULL DEFAULT 0,
    `billable_minutes` INT(11) NOT NULL DEFAULT 0,
    `billable_units` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `unit_rate` DECIMAL(10,2) DEFAULT NULL,
    `hourly_rate` DECIMAL(10,2) DEFAULT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `ma_number` VARCHAR(20) DEFAULT NULL,
    `program_type` VARCHAR(50) DEFAULT NULL,
    `status` ENUM('draft','pending','approved','billed','paid','disputed','rejected') DEFAULT 'draft',
    `approved_by` INT(11) DEFAULT NULL,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `billed_at` TIMESTAMP NULL DEFAULT NULL,
    `paid_at` TIMESTAMP NULL DEFAULT NULL,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`entry_id`),
    KEY `idx_employee_date` (`employee_id`, `billing_date`),
    KEY `idx_client_date` (`client_id`, `billing_date`),
    KEY `idx_status` (`status`),
    KEY `idx_billing_date` (`billing_date`),
    KEY `idx_session_note` (`session_note_id`),
    KEY `idx_time_clock` (`time_clock_id`),
    CONSTRAINT `fk_billing_employee` FOREIGN KEY (`employee_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_billing_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_billing_session_note` FOREIGN KEY (`session_note_id`) 
        REFERENCES `autism_session_notes` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_billing_time_clock` FOREIGN KEY (`time_clock_id`) 
        REFERENCES `autism_time_clock` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_billing_service_type` FOREIGN KEY (`service_type_id`) 
        REFERENCES `autism_service_types` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_billing_approved_by` FOREIGN KEY (`approved_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Create payroll aggregation table for employee hour summaries
CREATE TABLE IF NOT EXISTS `autism_payroll_summary` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `employee_id` INT(11) NOT NULL,
    `pay_period_start` DATE NOT NULL,
    `pay_period_end` DATE NOT NULL,
    `regular_hours` DECIMAL(10,2) DEFAULT 0.00,
    `overtime_hours` DECIMAL(10,2) DEFAULT 0.00,
    `billable_hours` DECIMAL(10,2) DEFAULT 0.00,
    `non_billable_hours` DECIMAL(10,2) DEFAULT 0.00,
    `total_hours` DECIMAL(10,2) DEFAULT 0.00,
    `regular_rate` DECIMAL(10,2) DEFAULT NULL,
    `overtime_rate` DECIMAL(10,2) DEFAULT NULL,
    `gross_pay` DECIMAL(10,2) DEFAULT 0.00,
    `status` ENUM('draft','pending_approval','approved','processed','paid') DEFAULT 'draft',
    `approved_by` INT(11) DEFAULT NULL,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    `processed_at` TIMESTAMP NULL DEFAULT NULL,
    `quickbooks_sync_id` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_employee_period` (`employee_id`, `pay_period_start`, `pay_period_end`),
    KEY `idx_pay_period` (`pay_period_start`, `pay_period_end`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`employee_id`) 
        REFERENCES `autism_staff_members` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_payroll_approved_by` FOREIGN KEY (`approved_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Add session linkage to time clock entries
ALTER TABLE `autism_time_clock` 
ADD COLUMN `session_note_id` INT(11) DEFAULT NULL AFTER `employee_id`,
ADD COLUMN `client_id` INT(11) DEFAULT NULL AFTER `session_note_id`,
ADD COLUMN `is_billable` BOOLEAN DEFAULT TRUE AFTER `total_hours`,
ADD COLUMN `activity_type` ENUM('direct_service','documentation','training','meeting','travel','admin','break') DEFAULT 'direct_service' AFTER `is_billable`,
ADD KEY `idx_session_note` (`session_note_id`),
ADD KEY `idx_client` (`client_id`),
ADD CONSTRAINT `fk_time_clock_session` FOREIGN KEY (`session_note_id`) 
    REFERENCES `autism_session_notes` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_time_clock_client` FOREIGN KEY (`client_id`) 
    REFERENCES `autism_clients` (`id`) ON DELETE SET NULL;

-- 4. Create view for payroll hours aggregation
CREATE OR REPLACE VIEW `v_payroll_hours` AS
SELECT 
    tc.employee_id,
    e.first_name,
    e.last_name,
    DATE(tc.clock_in) as work_date,
    YEARWEEK(tc.clock_in) as work_week,
    SUM(tc.total_hours) as total_hours,
    SUM(CASE WHEN tc.is_billable = 1 THEN tc.total_hours ELSE 0 END) as billable_hours,
    SUM(CASE WHEN tc.is_billable = 0 THEN tc.total_hours ELSE 0 END) as non_billable_hours,
    SUM(CASE WHEN tc.activity_type = 'direct_service' THEN tc.total_hours ELSE 0 END) as direct_service_hours,
    SUM(CASE WHEN tc.activity_type = 'documentation' THEN tc.total_hours ELSE 0 END) as documentation_hours,
    SUM(CASE WHEN tc.activity_type = 'admin' THEN tc.total_hours ELSE 0 END) as admin_hours,
    COUNT(DISTINCT tc.client_id) as clients_served,
    COUNT(DISTINCT tc.id) as clock_entries
FROM autism_time_clock tc
LEFT JOIN autism_staff_members e ON tc.employee_id = e.id
WHERE tc.clock_out IS NOT NULL
GROUP BY tc.employee_id, DATE(tc.clock_in);

-- 5. Create view for billing aggregation
CREATE OR REPLACE VIEW `v_billing_summary` AS
SELECT 
    be.employee_id,
    e.first_name,
    e.last_name,
    be.billing_date,
    YEARWEEK(be.billing_date) as billing_week,
    COUNT(DISTINCT be.client_id) as clients_billed,
    SUM(be.total_minutes) / 60 as total_hours,
    SUM(be.billable_minutes) / 60 as billable_hours,
    SUM(be.billable_units) as total_units,
    SUM(be.total_amount) as total_revenue,
    be.status,
    GROUP_CONCAT(DISTINCT be.billing_code) as billing_codes,
    GROUP_CONCAT(DISTINCT be.program_type) as programs
FROM autism_billing_entries be
LEFT JOIN autism_staff_members e ON be.employee_id = e.id
GROUP BY be.employee_id, be.billing_date, be.status;

-- 6. Create stored procedure to generate billing entries from session notes
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS sp_generate_billing_entries(
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    DECLARE v_count INT DEFAULT 0;
    
    -- Insert billing entries from session notes that don't have billing entries yet
    INSERT INTO autism_billing_entries (
        employee_id,
        client_id,
        session_note_id,
        billing_date,
        service_type_id,
        billing_code,
        total_minutes,
        billable_minutes,
        billable_units,
        unit_rate,
        total_amount,
        ma_number,
        program_type,
        status
    )
    SELECT 
        sn.created_by as employee_id,
        sn.client_id,
        sn.id as session_note_id,
        DATE(sn.session_date) as billing_date,
        sn.service_type_id,
        st.billing_code,
        sn.duration_minutes as total_minutes,
        sn.duration_minutes as billable_minutes,
        CASE 
            WHEN st.billing_unit = '15min' THEN CEIL(sn.duration_minutes / 15)
            WHEN st.billing_unit = '30min' THEN CEIL(sn.duration_minutes / 30)
            WHEN st.billing_unit = 'hour' THEN CEIL(sn.duration_minutes / 60)
            ELSE 1
        END as billable_units,
        st.rate as unit_rate,
        CASE 
            WHEN st.billing_unit = '15min' THEN CEIL(sn.duration_minutes / 15) * st.rate
            WHEN st.billing_unit = '30min' THEN CEIL(sn.duration_minutes / 30) * st.rate
            WHEN st.billing_unit = 'hour' THEN CEIL(sn.duration_minutes / 60) * st.rate
            ELSE st.rate
        END as total_amount,
        c.ma_number,
        p.program_code as program_type,
        'pending' as status
    FROM autism_session_notes sn
    INNER JOIN autism_clients c ON sn.client_id = c.id
    LEFT JOIN autism_service_types st ON sn.service_type_id = st.id
    LEFT JOIN autism_programs p ON c.program_id = p.id
    LEFT JOIN autism_billing_entries be ON sn.id = be.session_note_id
    WHERE DATE(sn.session_date) BETWEEN p_start_date AND p_end_date
    AND sn.status = 'completed'
    AND be.entry_id IS NULL;
    
    SELECT ROW_COUNT() INTO v_count;
    SELECT CONCAT('Generated ', v_count, ' billing entries') as result;
END//

DELIMITER ;

-- 7. Create trigger to link time clock entries with session notes
DELIMITER //

CREATE TRIGGER IF NOT EXISTS trg_link_time_clock_to_session
AFTER INSERT ON autism_session_notes
FOR EACH ROW
BEGIN
    -- Update the most recent time clock entry for this employee/client on the same date
    UPDATE autism_time_clock tc
    SET tc.session_note_id = NEW.id,
        tc.client_id = NEW.client_id
    WHERE tc.employee_id = NEW.created_by
    AND DATE(tc.clock_in) = DATE(NEW.session_date)
    AND tc.client_id IS NULL
    AND tc.clock_out IS NOT NULL
    ORDER BY tc.clock_in DESC
    LIMIT 1;
END//

DELIMITER ;

-- 8. Sample billing codes based on Maryland Medicaid
INSERT INTO autism_service_types (service_code, service_name, billing_code, billing_unit, rate, description, is_active)
VALUES
('IISS', 'Intensive Individual Support Services', 'T1019', '15min', 12.00, 'One-on-one support services', 1),
('TI', 'Therapeutic Integration', 'H2014', '15min', 15.00, 'Therapeutic behavioral services', 1),
('RESPITE', 'Respite Care', 'T1005', 'hour', 25.00, 'Temporary relief for caregivers', 1),
('CS', 'Community Support', 'H0045', '15min', 11.00, 'Support in community settings', 1),
('SC', 'Service Coordination', 'T1016', 'month', 150.00, 'Monthly service coordination', 1)
ON DUPLICATE KEY UPDATE rate = VALUES(rate);

-- Grant permissions
-- GRANT SELECT, INSERT, UPDATE ON autism_billing_entries TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_payroll_summary TO 'iris_user'@'localhost';
-- GRANT SELECT ON v_payroll_hours TO 'iris_user'@'localhost';
-- GRANT SELECT ON v_billing_summary TO 'iris_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE sp_generate_billing_entries TO 'iris_user'@'localhost';