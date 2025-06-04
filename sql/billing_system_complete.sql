-- Comprehensive Billing System Database Schema
-- For Scrive ACI Autism Waiver Application

-- EDI transaction tables
CREATE TABLE IF NOT EXISTS autism_edi_files (
    id INT(11) NOT NULL AUTO_INCREMENT,
    file_type ENUM('837','835','997','999','277','271','270','278') NOT NULL,
    direction ENUM('inbound','outbound') NOT NULL,
    filename VARCHAR(255),
    control_number VARCHAR(20),
    content LONGTEXT,
    content_hash VARCHAR(64),
    record_count INT(11) DEFAULT 0,
    total_amount DECIMAL(12,2),
    status ENUM('pending','processing','completed','error','archived') DEFAULT 'pending',
    error_message TEXT,
    processed_at TIMESTAMP NULL,
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_file_type (file_type),
    KEY idx_status (status),
    KEY idx_control_number (control_number),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clearinghouse submissions tracking
CREATE TABLE IF NOT EXISTS autism_clearinghouse_submissions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    edi_file_id INT(11) NOT NULL,
    clearinghouse VARCHAR(100) NOT NULL,
    submission_method ENUM('sftp','api','manual') DEFAULT 'api',
    submission_id VARCHAR(100),
    batch_id VARCHAR(100),
    status ENUM('queued','submitted','accepted','rejected','processing','completed') DEFAULT 'queued',
    acknowledgment_code VARCHAR(20),
    response_997 TEXT,
    response_277 TEXT,
    error_details TEXT,
    submitted_at TIMESTAMP NULL,
    acknowledged_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    retry_count INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_edi_file (edi_file_id),
    KEY idx_submission_id (submission_id),
    KEY idx_status (status),
    KEY idx_submitted_at (submitted_at),
    FOREIGN KEY (edi_file_id) REFERENCES autism_edi_files(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced claims table with EDI tracking
ALTER TABLE autism_claims ADD COLUMN IF NOT EXISTS edi_file_id INT(11) AFTER claim_number;
ALTER TABLE autism_claims ADD COLUMN IF NOT EXISTS clearinghouse_id VARCHAR(50) AFTER edi_file_id;
ALTER TABLE autism_claims ADD COLUMN IF NOT EXISTS original_claim_id INT(11) AFTER clearinghouse_id;
ALTER TABLE autism_claims ADD COLUMN IF NOT EXISTS claim_frequency ENUM('1','7','8') DEFAULT '1' AFTER original_claim_id;
ALTER TABLE autism_claims ADD COLUMN IF NOT EXISTS patient_control_number VARCHAR(50) AFTER claim_frequency;
ALTER TABLE autism_claims ADD COLUMN IF NOT EXISTS facility_code VARCHAR(10) AFTER patient_control_number;
ALTER TABLE autism_claims ADD COLUMN IF NOT EXISTS claim_note TEXT AFTER facility_code;
ALTER TABLE autism_claims ADD COLUMN IF NOT EXISTS total_charge DECIMAL(10,2) AFTER total_amount;
ALTER TABLE autism_claims ADD COLUMN IF NOT EXISTS total_paid DECIMAL(10,2) DEFAULT 0.00 AFTER payment_amount;
ALTER TABLE autism_claims ADD COLUMN IF NOT EXISTS total_adjusted DECIMAL(10,2) DEFAULT 0.00 AFTER total_paid;
ALTER TABLE autism_claims ADD COLUMN IF NOT EXISTS patient_paid DECIMAL(10,2) DEFAULT 0.00 AFTER total_adjusted;
ALTER TABLE autism_claims ADD COLUMN IF NOT EXISTS submitted_at TIMESTAMP NULL AFTER created_at;
ALTER TABLE autism_claims ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP NULL AFTER payment_date;

-- Detailed claim lines for service level tracking
CREATE TABLE IF NOT EXISTS autism_claim_lines (
    id INT(11) NOT NULL AUTO_INCREMENT,
    claim_id INT(11) NOT NULL,
    line_number INT(3) NOT NULL,
    session_id INT(11),
    service_date DATE NOT NULL,
    place_of_service VARCHAR(2) DEFAULT '12',
    procedure_code VARCHAR(10) NOT NULL,
    modifier1 VARCHAR(2),
    modifier2 VARCHAR(2),
    modifier3 VARCHAR(2),
    modifier4 VARCHAR(2),
    diagnosis_pointer VARCHAR(4) DEFAULT '1',
    units DECIMAL(7,2) NOT NULL,
    charge_amount DECIMAL(10,2) NOT NULL,
    allowed_amount DECIMAL(10,2),
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    copay_amount DECIMAL(10,2) DEFAULT 0.00,
    coinsurance_amount DECIMAL(10,2) DEFAULT 0.00,
    deductible_amount DECIMAL(10,2) DEFAULT 0.00,
    adjustment_amount DECIMAL(10,2) DEFAULT 0.00,
    adjustment_reason VARCHAR(10),
    line_note TEXT,
    status ENUM('pending','approved','denied','partial') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_claim_id (claim_id),
    KEY idx_session_id (session_id),
    KEY idx_service_date (service_date),
    KEY idx_procedure (procedure_code),
    FOREIGN KEY (claim_id) REFERENCES autism_claims(id),
    FOREIGN KEY (session_id) REFERENCES autism_sessions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment batches for ERA/EOB processing
CREATE TABLE IF NOT EXISTS autism_payment_batches (
    id INT(11) NOT NULL AUTO_INCREMENT,
    batch_type ENUM('era','manual','patient') DEFAULT 'era',
    check_number VARCHAR(50),
    eft_number VARCHAR(50),
    payment_method ENUM('check','eft','credit_card','cash') DEFAULT 'check',
    payment_date DATE NOT NULL,
    deposit_date DATE,
    payer_id VARCHAR(50),
    payer_name VARCHAR(100) NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    applied_amount DECIMAL(12,2) DEFAULT 0.00,
    unapplied_amount DECIMAL(12,2) DEFAULT 0.00,
    era_file_id INT(11),
    bank_account VARCHAR(50),
    posted_by INT(11),
    posted_at TIMESTAMP NULL,
    status ENUM('pending','partial','posted','reconciled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_check_number (check_number),
    KEY idx_payment_date (payment_date),
    KEY idx_payer (payer_id),
    KEY idx_status (status),
    KEY idx_era_file (era_file_id),
    FOREIGN KEY (era_file_id) REFERENCES autism_edi_files(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Individual payments linked to claims
CREATE TABLE IF NOT EXISTS autism_payments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    payment_batch_id INT(11),
    claim_id INT(11) NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    patient_responsibility DECIMAL(10,2) DEFAULT 0.00,
    contractual_adjustment DECIMAL(10,2) DEFAULT 0.00,
    other_adjustment DECIMAL(10,2) DEFAULT 0.00,
    payment_date DATE NOT NULL,
    posting_date DATE,
    trace_number VARCHAR(50),
    claim_status_code VARCHAR(10),
    posted_by INT(11),
    posted_at TIMESTAMP NULL,
    void_reason TEXT,
    voided_by INT(11),
    voided_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_batch (payment_batch_id),
    KEY idx_claim (claim_id),
    KEY idx_payment_date (payment_date),
    KEY idx_trace (trace_number),
    FOREIGN KEY (payment_batch_id) REFERENCES autism_payment_batches(id),
    FOREIGN KEY (claim_id) REFERENCES autism_claims(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Claim adjustments with reason codes
CREATE TABLE IF NOT EXISTS autism_claim_adjustments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    payment_id INT(11),
    claim_line_id INT(11),
    adjustment_group ENUM('CO','OA','PI','PR') NOT NULL,
    reason_code VARCHAR(10) NOT NULL,
    adjustment_amount DECIMAL(10,2) NOT NULL,
    quantity DECIMAL(7,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payment (payment_id),
    KEY idx_claim_line (claim_line_id),
    KEY idx_reason_code (reason_code),
    FOREIGN KEY (payment_id) REFERENCES autism_payments(id),
    FOREIGN KEY (claim_line_id) REFERENCES autism_claim_lines(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enhanced denial tracking
ALTER TABLE autism_claim_denials ADD COLUMN IF NOT EXISTS claim_line_id INT(11) AFTER claim_id;
ALTER TABLE autism_claim_denials ADD COLUMN IF NOT EXISTS adjustment_reason VARCHAR(10) AFTER denial_code;
ALTER TABLE autism_claim_denials ADD COLUMN IF NOT EXISTS remark_code VARCHAR(10) AFTER adjustment_reason;
ALTER TABLE autism_claim_denials ADD COLUMN IF NOT EXISTS expected_reimbursement DECIMAL(10,2) AFTER followup_required;
ALTER TABLE autism_claim_denials ADD COLUMN IF NOT EXISTS corrected_claim_id INT(11) AFTER appeal_submitted;
ALTER TABLE autism_claim_denials ADD COLUMN IF NOT EXISTS status ENUM('open','appealing','resolved','written_off') DEFAULT 'open' AFTER resolution_notes;

-- Billing rules configuration
CREATE TABLE IF NOT EXISTS autism_billing_rules (
    id INT(11) NOT NULL AUTO_INCREMENT,
    rule_name VARCHAR(100) NOT NULL,
    rule_type ENUM('validation','authorization','modifier','bundling','timely_filing') NOT NULL,
    payer_type VARCHAR(50) DEFAULT 'Maryland Medicaid',
    service_type_id INT(11),
    procedure_code VARCHAR(10),
    rule_condition TEXT,
    rule_action TEXT,
    error_message VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    effective_date DATE,
    end_date DATE,
    priority INT(3) DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rule_type (rule_type),
    KEY idx_procedure (procedure_code),
    KEY idx_active (is_active),
    KEY idx_dates (effective_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Claim status history tracking
CREATE TABLE IF NOT EXISTS autism_claim_status_history (
    id INT(11) NOT NULL AUTO_INCREMENT,
    claim_id INT(11) NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    status_reason VARCHAR(255),
    changed_by INT(11),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    PRIMARY KEY (id),
    KEY idx_claim (claim_id),
    KEY idx_changed_at (changed_at),
    FOREIGN KEY (claim_id) REFERENCES autism_claims(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Patient statements and billing
CREATE TABLE IF NOT EXISTS autism_patient_statements (
    id INT(11) NOT NULL AUTO_INCREMENT,
    client_id INT(11) NOT NULL,
    statement_date DATE NOT NULL,
    due_date DATE,
    statement_number VARCHAR(20) UNIQUE,
    previous_balance DECIMAL(10,2) DEFAULT 0.00,
    current_charges DECIMAL(10,2) DEFAULT 0.00,
    payments_received DECIMAL(10,2) DEFAULT 0.00,
    adjustments DECIMAL(10,2) DEFAULT 0.00,
    balance_due DECIMAL(10,2) NOT NULL,
    aging_current DECIMAL(10,2) DEFAULT 0.00,
    aging_30_days DECIMAL(10,2) DEFAULT 0.00,
    aging_60_days DECIMAL(10,2) DEFAULT 0.00,
    aging_90_days DECIMAL(10,2) DEFAULT 0.00,
    aging_over_120 DECIMAL(10,2) DEFAULT 0.00,
    sent_method ENUM('mail','email','both','none') DEFAULT 'none',
    sent_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_client (client_id),
    KEY idx_statement_date (statement_date),
    KEY idx_statement_number (statement_number),
    FOREIGN KEY (client_id) REFERENCES autism_clients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eligibility verification tracking
CREATE TABLE IF NOT EXISTS autism_eligibility_checks (
    id INT(11) NOT NULL AUTO_INCREMENT,
    client_id INT(11) NOT NULL,
    payer_id VARCHAR(50),
    payer_name VARCHAR(100),
    check_date DATE NOT NULL,
    eligibility_start DATE,
    eligibility_end DATE,
    coverage_active TINYINT(1) DEFAULT 0,
    copay_amount DECIMAL(10,2),
    coinsurance_percent INT(3),
    deductible_amount DECIMAL(10,2),
    deductible_met DECIMAL(10,2),
    out_of_pocket_max DECIMAL(10,2),
    out_of_pocket_met DECIMAL(10,2),
    edi_270_file_id INT(11),
    edi_271_file_id INT(11),
    response_data JSON,
    status ENUM('pending','completed','error') DEFAULT 'pending',
    error_message TEXT,
    checked_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_client (client_id),
    KEY idx_check_date (check_date),
    KEY idx_payer (payer_id),
    FOREIGN KEY (client_id) REFERENCES autism_clients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payer configuration
CREATE TABLE IF NOT EXISTS autism_payers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    payer_name VARCHAR(100) NOT NULL,
    payer_id VARCHAR(50) UNIQUE,
    payer_type ENUM('medicaid','medicare','commercial','self_pay') DEFAULT 'medicaid',
    edi_id VARCHAR(50),
    edi_qualifier VARCHAR(2) DEFAULT 'PI',
    address_line1 VARCHAR(100),
    address_line2 VARCHAR(100),
    city VARCHAR(50),
    state VARCHAR(2),
    zip VARCHAR(10),
    phone VARCHAR(20),
    fax VARCHAR(20),
    website VARCHAR(255),
    enrollment_required TINYINT(1) DEFAULT 1,
    era_enrolled TINYINT(1) DEFAULT 0,
    eft_enrolled TINYINT(1) DEFAULT 0,
    timely_filing_days INT(3) DEFAULT 365,
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payer_id (payer_id),
    KEY idx_payer_type (payer_type),
    KEY idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default payers
INSERT IGNORE INTO autism_payers (payer_name, payer_id, payer_type, edi_id, timely_filing_days) VALUES
('Maryland Medicaid', 'MDMEDICAID', 'medicaid', '77023', 365),
('Medicare Part B', 'MEDICARE', 'medicare', '00440', 365),
('CareFirst BlueCross BlueShield', 'CAREFIRST', 'commercial', 'CF001', 180),
('United Healthcare', 'UHC', 'commercial', '87726', 180),
('Self Pay', 'SELF', 'self_pay', NULL, NULL);

-- Adjustment reason codes
CREATE TABLE IF NOT EXISTS autism_adjustment_codes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    code VARCHAR(10) NOT NULL,
    adjustment_group ENUM('CO','OA','PI','PR') NOT NULL,
    description VARCHAR(255) NOT NULL,
    patient_responsibility TINYINT(1) DEFAULT 0,
    write_off_eligible TINYINT(1) DEFAULT 0,
    appeal_eligible TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY idx_code (code),
    KEY idx_group (adjustment_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert common adjustment codes
INSERT IGNORE INTO autism_adjustment_codes (code, adjustment_group, description, patient_responsibility, write_off_eligible, appeal_eligible) VALUES
('1', 'PR', 'Deductible', 1, 0, 0),
('2', 'PR', 'Coinsurance', 1, 0, 0),
('3', 'PR', 'Copayment', 1, 0, 0),
('45', 'CO', 'Charges exceed fee schedule', 0, 1, 0),
('50', 'CO', 'Non-covered service', 0, 0, 1),
('96', 'CO', 'Non-covered charges', 0, 0, 1),
('97', 'CO', 'Payment included in allowance for another service', 0, 1, 0),
('109', 'CO', 'Claim not covered by this payer', 0, 0, 1),
('197', 'CO', 'Precertification/authorization absent', 0, 0, 1);

-- Create views for reporting
CREATE OR REPLACE VIEW autism_claim_summary AS
SELECT 
    c.id,
    c.claim_number,
    c.service_date_from,
    c.service_date_to,
    cl.first_name,
    cl.last_name,
    cl.ma_number,
    c.total_charge,
    c.total_paid,
    c.total_adjusted,
    (c.total_charge - c.total_paid - c.total_adjusted) as balance_due,
    c.status,
    c.submitted_at,
    c.paid_at,
    DATEDIFF(CURRENT_DATE, c.service_date_to) as days_old,
    p.payer_name
FROM autism_claims c
JOIN autism_clients cl ON c.client_id = cl.id
LEFT JOIN autism_payers p ON c.payer_id = p.id;

CREATE OR REPLACE VIEW autism_payment_summary AS
SELECT 
    pb.id as batch_id,
    pb.payment_date,
    pb.check_number,
    pb.payer_name,
    pb.total_amount as batch_amount,
    COUNT(DISTINCT p.id) as payment_count,
    COUNT(DISTINCT p.claim_id) as claim_count,
    SUM(p.payment_amount) as total_posted,
    SUM(p.contractual_adjustment) as total_adjustments,
    pb.status
FROM autism_payment_batches pb
LEFT JOIN autism_payments p ON pb.id = p.payment_batch_id
GROUP BY pb.id;

-- Add indexes for performance
ALTER TABLE autism_claims ADD INDEX idx_submitted_at (submitted_at);
ALTER TABLE autism_claims ADD INDEX idx_payer_id (payer_id);
ALTER TABLE autism_sessions ADD INDEX idx_billing_status (billing_status);
ALTER TABLE autism_edi_files ADD INDEX idx_content_hash (content_hash);

-- Insert default billing rules
INSERT IGNORE INTO autism_billing_rules (rule_name, rule_type, rule_condition, error_message) VALUES
('Maryland Medicaid Timely Filing', 'timely_filing', 'days_since_service > 365', 'Claim exceeds 365 day timely filing limit'),
('Authorization Required', 'authorization', 'service_requires_auth = 1 AND auth_number IS NULL', 'Service requires prior authorization'),
('Duplicate Claim Check', 'validation', 'duplicate_exists = 1', 'Duplicate claim already submitted'),
('Missing Diagnosis', 'validation', 'diagnosis_code IS NULL', 'Primary diagnosis code required'),
('Invalid Modifier', 'modifier', 'invalid_modifier = 1', 'Invalid modifier combination');