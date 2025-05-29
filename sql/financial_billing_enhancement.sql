-- Financial and Billing Enhancement System
-- EDI 837/835 integration for Maryland Medicaid

-- 1. Insurance Information Management
CREATE TABLE IF NOT EXISTS `autism_insurance_info` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `insurance_type` ENUM('primary','secondary','tertiary') DEFAULT 'primary',
    `payer_name` VARCHAR(255) NOT NULL,
    `payer_id` VARCHAR(50) COMMENT 'EDI Payer ID',
    `policy_number` VARCHAR(50) NOT NULL,
    `group_number` VARCHAR(50),
    `subscriber_name` VARCHAR(255),
    `subscriber_dob` DATE,
    `subscriber_relationship` VARCHAR(50),
    `effective_date` DATE NOT NULL,
    `termination_date` DATE,
    `copay_amount` DECIMAL(10,2) DEFAULT 0.00,
    `deductible_amount` DECIMAL(10,2) DEFAULT 0.00,
    `deductible_met` DECIMAL(10,2) DEFAULT 0.00,
    `is_active` BOOLEAN DEFAULT TRUE,
    `verified_date` DATE,
    `verified_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_client_type` (`client_id`, `insurance_type`),
    KEY `idx_active` (`is_active`),
    CONSTRAINT `fk_insurance_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_insurance_verified_by` FOREIGN KEY (`verified_by`) 
        REFERENCES `autism_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Prior Authorization Tracking
CREATE TABLE IF NOT EXISTS `autism_prior_authorizations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `client_id` INT(11) NOT NULL,
    `insurance_id` INT(11) NOT NULL,
    `auth_number` VARCHAR(50) NOT NULL,
    `service_type_id` INT(11) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `authorized_units` INT NOT NULL,
    `units_used` INT DEFAULT 0,
    `units_remaining` INT GENERATED ALWAYS AS (authorized_units - units_used) STORED,
    `frequency` VARCHAR(100) COMMENT 'e.g., 5x per week',
    `status` ENUM('pending','approved','denied','expired','exhausted') DEFAULT 'pending',
    `request_date` DATE,
    `approval_date` DATE,
    `denial_reason` TEXT,
    `notes` TEXT,
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_auth_number` (`auth_number`),
    KEY `idx_client_dates` (`client_id`, `start_date`, `end_date`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_auth_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_auth_insurance` FOREIGN KEY (`insurance_id`) 
        REFERENCES `autism_insurance_info` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_auth_service` FOREIGN KEY (`service_type_id`) 
        REFERENCES `autism_service_types` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. EDI Transaction Tracking
CREATE TABLE IF NOT EXISTS `autism_edi_transactions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `transaction_type` ENUM('837','835','277','271','270','278','999') NOT NULL,
    `transaction_set_id` VARCHAR(50) NOT NULL,
    `batch_id` VARCHAR(50),
    `direction` ENUM('outbound','inbound') NOT NULL,
    `status` ENUM('pending','transmitted','accepted','rejected','processing','completed') DEFAULT 'pending',
    `file_name` VARCHAR(255),
    `file_content` LONGTEXT COMMENT 'EDI file content',
    `total_claims` INT DEFAULT 0,
    `total_amount` DECIMAL(12,2) DEFAULT 0.00,
    `sender_id` VARCHAR(50),
    `receiver_id` VARCHAR(50),
    `transmission_date` TIMESTAMP NULL,
    `acknowledgment_date` TIMESTAMP NULL,
    `error_count` INT DEFAULT 0,
    `error_details` JSON,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_transaction_set` (`transaction_set_id`),
    KEY `idx_batch` (`batch_id`),
    KEY `idx_type_status` (`transaction_type`, `status`),
    KEY `idx_date` (`transmission_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Claims Management (Enhanced)
CREATE TABLE IF NOT EXISTS `autism_claims` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `claim_number` VARCHAR(50) NOT NULL,
    `client_id` INT(11) NOT NULL,
    `insurance_id` INT(11) NOT NULL,
    `authorization_id` INT(11),
    `service_date_from` DATE NOT NULL,
    `service_date_to` DATE NOT NULL,
    `billed_amount` DECIMAL(12,2) NOT NULL,
    `allowed_amount` DECIMAL(12,2) DEFAULT 0.00,
    `paid_amount` DECIMAL(12,2) DEFAULT 0.00,
    `patient_responsibility` DECIMAL(12,2) DEFAULT 0.00,
    `status` ENUM('draft','ready','submitted','accepted','rejected','processing','paid','denied','appealed') DEFAULT 'draft',
    `submission_date` DATE,
    `edi_transaction_id` INT(11),
    `payer_claim_number` VARCHAR(50),
    `remittance_advice` TEXT,
    `denial_reason` TEXT,
    `appeal_notes` TEXT,
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_claim_number` (`claim_number`),
    KEY `idx_client` (`client_id`),
    KEY `idx_status` (`status`),
    KEY `idx_service_dates` (`service_date_from`, `service_date_to`),
    KEY `idx_submission_date` (`submission_date`),
    CONSTRAINT `fk_claim_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_claim_insurance` FOREIGN KEY (`insurance_id`) 
        REFERENCES `autism_insurance_info` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_claim_auth` FOREIGN KEY (`authorization_id`) 
        REFERENCES `autism_prior_authorizations` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_claim_edi` FOREIGN KEY (`edi_transaction_id`) 
        REFERENCES `autism_edi_transactions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Claim Line Items
CREATE TABLE IF NOT EXISTS `autism_claim_lines` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `claim_id` INT(11) NOT NULL,
    `line_number` INT NOT NULL,
    `billing_entry_id` INT(11),
    `service_date` DATE NOT NULL,
    `billing_code` VARCHAR(20) NOT NULL,
    `modifier_1` VARCHAR(2),
    `modifier_2` VARCHAR(2),
    `units` DECIMAL(10,2) NOT NULL,
    `unit_rate` DECIMAL(10,2) NOT NULL,
    `line_amount` DECIMAL(10,2) NOT NULL,
    `allowed_amount` DECIMAL(10,2) DEFAULT 0.00,
    `paid_amount` DECIMAL(10,2) DEFAULT 0.00,
    `adjustment_amount` DECIMAL(10,2) DEFAULT 0.00,
    `adjustment_reason` VARCHAR(50),
    `status` ENUM('included','paid','denied','adjusted') DEFAULT 'included',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_claim_line` (`claim_id`, `line_number`),
    KEY `idx_billing_entry` (`billing_entry_id`),
    CONSTRAINT `fk_line_claim` FOREIGN KEY (`claim_id`) 
        REFERENCES `autism_claims` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_line_billing_entry` FOREIGN KEY (`billing_entry_id`) 
        REFERENCES `autism_billing_entries` (`entry_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Payment Processing
CREATE TABLE IF NOT EXISTS `autism_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `payment_type` ENUM('insurance','client','credit_card','cash','check','ach') NOT NULL,
    `claim_id` INT(11),
    `client_id` INT(11),
    `payment_date` DATE NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `payment_method` VARCHAR(50),
    `reference_number` VARCHAR(100),
    `processor_transaction_id` VARCHAR(100),
    `status` ENUM('pending','processing','completed','failed','refunded','voided') DEFAULT 'pending',
    `notes` TEXT,
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_payment_date` (`payment_date`),
    KEY `idx_claim` (`claim_id`),
    KEY `idx_client` (`client_id`),
    KEY `idx_status` (`status`),
    CONSTRAINT `fk_payment_claim` FOREIGN KEY (`claim_id`) 
        REFERENCES `autism_claims` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_payment_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Payment Plans
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
    CONSTRAINT `fk_plan_client` FOREIGN KEY (`client_id`) 
        REFERENCES `autism_clients` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Financial Reports Configuration
CREATE TABLE IF NOT EXISTS `autism_financial_reports` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `report_name` VARCHAR(255) NOT NULL,
    `report_type` ENUM('aging','revenue_cycle','collection','utilization','denial','custom') NOT NULL,
    `report_query` TEXT NOT NULL,
    `parameters` JSON,
    `schedule` ENUM('daily','weekly','monthly','quarterly','on_demand') DEFAULT 'on_demand',
    `recipients` JSON COMMENT 'Email addresses for scheduled reports',
    `last_run` TIMESTAMP NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_by` INT(11),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_type` (`report_type`),
    KEY `idx_schedule` (`schedule`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create views for financial reporting

-- Aging Report View
CREATE OR REPLACE VIEW `v_aging_report` AS
SELECT 
    c.id as claim_id,
    c.claim_number,
    cl.first_name,
    cl.last_name,
    cl.ma_number,
    c.service_date_from,
    c.submission_date,
    DATEDIFF(CURDATE(), c.submission_date) as days_outstanding,
    CASE 
        WHEN DATEDIFF(CURDATE(), c.submission_date) <= 30 THEN '0-30'
        WHEN DATEDIFF(CURDATE(), c.submission_date) <= 60 THEN '31-60'
        WHEN DATEDIFF(CURDATE(), c.submission_date) <= 90 THEN '61-90'
        WHEN DATEDIFF(CURDATE(), c.submission_date) <= 120 THEN '91-120'
        ELSE 'Over 120'
    END as aging_bucket,
    c.billed_amount,
    c.paid_amount,
    c.billed_amount - c.paid_amount as outstanding_amount,
    c.status,
    i.payer_name
FROM autism_claims c
JOIN autism_clients cl ON c.client_id = cl.id
JOIN autism_insurance_info i ON c.insurance_id = i.id
WHERE c.status NOT IN ('paid', 'denied', 'draft');

-- Revenue Cycle Dashboard View
CREATE OR REPLACE VIEW `v_revenue_cycle_dashboard` AS
SELECT 
    DATE_FORMAT(c.submission_date, '%Y-%m') as month,
    COUNT(DISTINCT c.id) as total_claims,
    COUNT(DISTINCT c.client_id) as unique_clients,
    SUM(c.billed_amount) as total_billed,
    SUM(c.allowed_amount) as total_allowed,
    SUM(c.paid_amount) as total_paid,
    SUM(CASE WHEN c.status = 'denied' THEN c.billed_amount ELSE 0 END) as total_denied,
    ROUND(AVG(DATEDIFF(IFNULL(p.payment_date, CURDATE()), c.submission_date)), 1) as avg_days_to_payment,
    ROUND(SUM(c.paid_amount) * 100.0 / NULLIF(SUM(c.billed_amount), 0), 2) as collection_rate
FROM autism_claims c
LEFT JOIN autism_payments p ON c.id = p.claim_id AND p.payment_type = 'insurance'
WHERE c.submission_date IS NOT NULL
GROUP BY DATE_FORMAT(c.submission_date, '%Y-%m');

-- Stored procedures for EDI processing

DELIMITER //

-- Generate 837 Professional Claim
CREATE PROCEDURE IF NOT EXISTS sp_generate_837_claim(
    IN p_claim_id INT
)
BEGIN
    DECLARE v_edi_content TEXT;
    DECLARE v_transaction_id VARCHAR(50);
    
    -- Generate unique transaction ID
    SET v_transaction_id = CONCAT('837_', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), '_', p_claim_id);
    
    -- Build EDI 837 content (simplified structure)
    SET v_edi_content = CONCAT(
        'ISA*00*          *00*          *ZZ*', 
        (SELECT value FROM autism_provider_config WHERE setting_key = 'medicaid_provider_id'),
        '*ZZ*MDMEDICAID*',
        DATE_FORMAT(NOW(), '%y%m%d*%H%i'),
        '*^*00501*000000001*0*P*:~',
        'GS*HC*',
        (SELECT value FROM autism_provider_config WHERE setting_key = 'medicaid_provider_id'),
        '*MDMEDICAID*',
        DATE_FORMAT(NOW(), '%Y%m%d*%H%i%s'),
        '*1*X*005010X222A1~',
        'ST*837*0001*005010X222A1~'
    );
    
    -- Add claim details (this would be much more complex in reality)
    -- ... Additional EDI segments would be built here ...
    
    -- Insert EDI transaction record
    INSERT INTO autism_edi_transactions (
        transaction_type, transaction_set_id, direction, status,
        file_content, total_claims, total_amount
    )
    SELECT 
        '837', v_transaction_id, 'outbound', 'pending',
        v_edi_content, 1, c.billed_amount
    FROM autism_claims c
    WHERE c.id = p_claim_id;
    
    -- Update claim with EDI transaction
    UPDATE autism_claims 
    SET edi_transaction_id = LAST_INSERT_ID(),
        status = 'ready'
    WHERE id = p_claim_id;
    
    SELECT 'EDI 837 transaction created' as result, v_transaction_id as transaction_id;
END//

-- Process 835 Remittance Advice
CREATE PROCEDURE IF NOT EXISTS sp_process_835_remittance(
    IN p_edi_content TEXT
)
BEGIN
    DECLARE v_claim_number VARCHAR(50);
    DECLARE v_paid_amount DECIMAL(12,2);
    DECLARE v_allowed_amount DECIMAL(12,2);
    
    -- Parse EDI 835 content (simplified - would need full EDI parser)
    -- This is a placeholder for actual EDI parsing logic
    
    -- Update claim based on remittance
    UPDATE autism_claims c
    SET c.paid_amount = v_paid_amount,
        c.allowed_amount = v_allowed_amount,
        c.status = 'paid',
        c.updated_at = NOW()
    WHERE c.claim_number = v_claim_number;
    
    -- Create payment record
    INSERT INTO autism_payments (
        payment_type, claim_id, payment_date, amount, 
        payment_method, reference_number, status
    )
    SELECT 
        'insurance', c.id, CURDATE(), v_paid_amount,
        'EDI_835', p_edi_content, 'completed'
    FROM autism_claims c
    WHERE c.claim_number = v_claim_number;
    
    SELECT 'Remittance processed' as result;
END//

DELIMITER ;

-- Insert default financial reports
INSERT INTO `autism_financial_reports` (`report_name`, `report_type`, `report_query`, `parameters`) VALUES
('30-Day Aging Report', 'aging', 'SELECT * FROM v_aging_report WHERE days_outstanding <= 30', '{}'),
('Monthly Revenue Summary', 'revenue_cycle', 'SELECT * FROM v_revenue_cycle_dashboard WHERE month = ?', '{"month": "current"}'),
('Denial Analysis', 'denial', 'SELECT denial_reason, COUNT(*) as count, SUM(billed_amount) as amount FROM autism_claims WHERE status = "denied" GROUP BY denial_reason', '{}')
ON DUPLICATE KEY UPDATE report_query = VALUES(report_query);

-- Grant permissions
-- GRANT SELECT, INSERT, UPDATE ON autism_insurance_info TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_prior_authorizations TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_edi_transactions TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_claims TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_claim_lines TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_payments TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_payment_plans TO 'iris_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE ON autism_financial_reports TO 'iris_user'@'localhost';
-- GRANT SELECT ON v_aging_report TO 'iris_user'@'localhost';
-- GRANT SELECT ON v_revenue_cycle_dashboard TO 'iris_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE sp_generate_837_claim TO 'iris_user'@'localhost';
-- GRANT EXECUTE ON PROCEDURE sp_process_835_remittance TO 'iris_user'@'localhost';