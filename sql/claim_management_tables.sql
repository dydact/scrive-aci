-- Claim Activity Log table for tracking all claim-related activities
CREATE TABLE IF NOT EXISTS claim_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    claim_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_claim_id (claim_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (claim_id) REFERENCES billing_claims(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Add missing columns to billing_claims table
ALTER TABLE billing_claims 
ADD COLUMN IF NOT EXISTS service_start_date DATE,
ADD COLUMN IF NOT EXISTS service_end_date DATE,
ADD COLUMN IF NOT EXISTS validated BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS validated_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS clearinghouse_id VARCHAR(50),
ADD COLUMN IF NOT EXISTS submission_response TEXT,
ADD COLUMN IF NOT EXISTS payment_date DATE,
ADD COLUMN IF NOT EXISTS payment_amount DECIMAL(10,2),
ADD COLUMN IF NOT EXISTS denial_reason TEXT,
ADD COLUMN IF NOT EXISTS denial_codes VARCHAR(255),
ADD COLUMN IF NOT EXISTS modified_by INT,
ADD COLUMN IF NOT EXISTS modified_at TIMESTAMP NULL,
ADD INDEX IF NOT EXISTS idx_validated (validated),
ADD INDEX IF NOT EXISTS idx_clearinghouse_id (clearinghouse_id);

-- Add organization settings table if not exists
CREATE TABLE IF NOT EXISTS organization_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    npi VARCHAR(10),
    tax_id VARCHAR(20),
    address VARCHAR(255),
    city VARCHAR(100),
    state CHAR(2),
    zip VARCHAR(10),
    phone VARCHAR(20),
    email VARCHAR(255),
    clearinghouse_username VARCHAR(100),
    clearinghouse_password VARCHAR(255),
    clearinghouse_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default organization if not exists
INSERT INTO organization_settings (id, name, npi, tax_id, address, city, state, zip, phone, email)
SELECT 1, 'American Caregivers Inc', '1234567890', '12-3456789', 
       '220 Girard Street', 'Gaithersburg', 'MD', '20877',
       '(240) 264-0044', 'info@acgcares.com'
WHERE NOT EXISTS (SELECT 1 FROM organization_settings WHERE id = 1);

-- Add taxonomy_code to staff table if not exists
ALTER TABLE staff
ADD COLUMN IF NOT EXISTS taxonomy_code VARCHAR(20) DEFAULT '225500000X' COMMENT 'Behavioral Health Provider taxonomy';

-- Create claim batches table for tracking batch submissions
CREATE TABLE IF NOT EXISTS claim_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_number VARCHAR(50) UNIQUE NOT NULL,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_claims INT DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'submitted', 'accepted', 'rejected') DEFAULT 'pending',
    clearinghouse_batch_id VARCHAR(100),
    response_file TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_batch_number (batch_number),
    INDEX idx_status (status),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Add batch_id to billing_claims
ALTER TABLE billing_claims
ADD COLUMN IF NOT EXISTS batch_id INT,
ADD FOREIGN KEY (batch_id) REFERENCES claim_batches(id) ON DELETE SET NULL;

-- Create table for tracking authorization usage history
CREATE TABLE IF NOT EXISTS authorization_usage_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    authorization_id INT NOT NULL,
    claim_id INT,
    units_used DECIMAL(10,2),
    remaining_units DECIMAL(10,2),
    usage_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_authorization_id (authorization_id),
    INDEX idx_claim_id (claim_id),
    FOREIGN KEY (authorization_id) REFERENCES client_authorizations(id) ON DELETE CASCADE,
    FOREIGN KEY (claim_id) REFERENCES billing_claims(id) ON DELETE SET NULL
);

-- Create remittance advice table for payment posting
CREATE TABLE IF NOT EXISTS remittance_advice (
    id INT AUTO_INCREMENT PRIMARY KEY,
    check_number VARCHAR(50),
    payment_date DATE,
    total_amount DECIMAL(10,2),
    status ENUM('pending', 'posted', 'reconciled') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    posted_at TIMESTAMP NULL,
    INDEX idx_check_number (check_number),
    INDEX idx_payment_date (payment_date),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Create remittance detail table
CREATE TABLE IF NOT EXISTS remittance_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remittance_id INT NOT NULL,
    claim_id INT,
    claim_number VARCHAR(50),
    paid_amount DECIMAL(10,2),
    allowed_amount DECIMAL(10,2),
    deductible DECIMAL(10,2) DEFAULT 0,
    coinsurance DECIMAL(10,2) DEFAULT 0,
    copay DECIMAL(10,2) DEFAULT 0,
    adjustment_reason_codes VARCHAR(255),
    remark_codes VARCHAR(255),
    INDEX idx_remittance_id (remittance_id),
    INDEX idx_claim_id (claim_id),
    FOREIGN KEY (remittance_id) REFERENCES remittance_advice(id) ON DELETE CASCADE,
    FOREIGN KEY (claim_id) REFERENCES billing_claims(id) ON DELETE SET NULL
);

-- Add Maryland Medicaid specific service codes if not exists
INSERT INTO service_types (service_code, description, rate, unit_type, authorization_required, max_units_per_day)
VALUES 
    ('W1727', 'Intensive Individual Support Services', 27.00, '15min', 1, 32),
    ('W1728', 'Therapeutic Integration', 27.00, '15min', 1, 32),
    ('W7061', 'Respite Care', 4.50, '15min', 0, 96),
    ('W7060', 'Family Consultation', 40.00, 'hour', 0, 8),
    ('W7069', 'Adult Life Planning', 40.00, 'hour', 0, 8),
    ('W7235', 'Environmental Accessibility Adaptations', 100.00, 'each', 1, 1)
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    rate = VALUES(rate);