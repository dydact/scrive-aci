-- EDI 837 Professional Claim Tables for Autism Waiver App
-- These tables support the EDI837Generator class

-- EDI Transactions table
CREATE TABLE IF NOT EXISTS edi_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_type ENUM('inbound', 'outbound') NOT NULL,
    transaction_set VARCHAR(10) NOT NULL,
    interchange_control_number VARCHAR(20) NOT NULL,
    group_control_number VARCHAR(20) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    claim_count INT DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('pending', 'transmitted', 'accepted', 'rejected', 'error') DEFAULT 'pending',
    transmission_date DATETIME NULL,
    acknowledgment_date DATETIME NULL,
    acknowledgment_code VARCHAR(10) NULL,
    error_message TEXT NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_icn (interchange_control_number),
    INDEX idx_created_at (created_at)
);

-- EDI Acknowledgments table (for 997/999 responses)
CREATE TABLE IF NOT EXISTS edi_acknowledgments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    acknowledgment_type VARCHAR(10) NOT NULL,
    interchange_control_number VARCHAR(20) NOT NULL,
    group_control_number VARCHAR(20) NOT NULL,
    transaction_control_number VARCHAR(20) NULL,
    acknowledgment_code VARCHAR(10) NOT NULL,
    acknowledgment_date DATETIME NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_content TEXT NOT NULL,
    error_segments TEXT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES edi_transactions(id),
    INDEX idx_transaction (transaction_id)
);

-- EDI Logs table
CREATE TABLE IF NOT EXISTS edi_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_level ENUM('debug', 'info', 'warning', 'error') NOT NULL,
    message TEXT NOT NULL,
    context JSON NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_log_level (log_level),
    INDEX idx_created_at (created_at)
);

-- Billing Claims table (enhanced for EDI)
CREATE TABLE IF NOT EXISTS billing_claims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    claim_number VARCHAR(50) UNIQUE NOT NULL,
    patient_id INT NOT NULL,
    provider_id INT NOT NULL,
    rendering_provider_id INT NULL,
    referring_provider_id INT NULL,
    
    -- Claim amounts
    total_charge_amount DECIMAL(10,2) NOT NULL,
    total_paid_amount DECIMAL(10,2) DEFAULT 0.00,
    total_adjustment_amount DECIMAL(10,2) DEFAULT 0.00,
    balance_due DECIMAL(10,2) DEFAULT 0.00,
    
    -- Claim dates
    statement_from_date DATE NOT NULL,
    statement_to_date DATE NOT NULL,
    admission_date DATE NULL,
    discharge_date DATE NULL,
    
    -- Claim information
    facility_code VARCHAR(10) NOT NULL,
    claim_frequency_code VARCHAR(10) DEFAULT '1',
    provider_accept_assignment CHAR(1) DEFAULT 'Y',
    assignment_of_benefits CHAR(1) DEFAULT 'A',
    release_of_information CHAR(1) DEFAULT 'Y',
    patient_signature_source CHAR(1) DEFAULT 'P',
    
    -- Prior authorization
    prior_auth_number VARCHAR(50) NULL,
    
    -- EDI specific fields
    edi_file_id INT NULL,
    edi_status ENUM('pending', 'submitted', 'accepted', 'rejected', 'paid', 'denied') DEFAULT 'pending',
    edi_submitted_at DATETIME NULL,
    edi_response_at DATETIME NULL,
    edi_response_code VARCHAR(10) NULL,
    edi_response_message TEXT NULL,
    
    -- Payer information
    payer_id VARCHAR(50) NOT NULL,
    payer_name VARCHAR(255) NOT NULL,
    
    -- Status tracking
    claim_status ENUM('draft', 'ready', 'submitted', 'processing', 'paid', 'denied', 'appealed') DEFAULT 'draft',
    submission_count INT DEFAULT 0,
    
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (patient_id) REFERENCES patients(id),
    FOREIGN KEY (provider_id) REFERENCES providers(id),
    FOREIGN KEY (rendering_provider_id) REFERENCES providers(id),
    FOREIGN KEY (referring_provider_id) REFERENCES providers(id),
    FOREIGN KEY (edi_file_id) REFERENCES edi_transactions(id),
    
    INDEX idx_claim_number (claim_number),
    INDEX idx_patient (patient_id),
    INDEX idx_provider (provider_id),
    INDEX idx_edi_status (edi_status),
    INDEX idx_claim_status (claim_status),
    INDEX idx_statement_dates (statement_from_date, statement_to_date)
);

-- Claim Diagnoses table
CREATE TABLE IF NOT EXISTS claim_diagnoses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    claim_id INT NOT NULL,
    diagnosis_code VARCHAR(10) NOT NULL,
    diagnosis_type ENUM('principal', 'admitting', 'other') DEFAULT 'other',
    diagnosis_pointer INT NOT NULL,
    present_on_admission VARCHAR(1) NULL,
    created_at DATETIME NOT NULL,
    
    FOREIGN KEY (claim_id) REFERENCES billing_claims(id) ON DELETE CASCADE,
    UNIQUE KEY unique_claim_diagnosis (claim_id, diagnosis_code),
    INDEX idx_claim (claim_id)
);

-- Claim Service Lines table
CREATE TABLE IF NOT EXISTS claim_service_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    claim_id INT NOT NULL,
    line_number INT NOT NULL,
    
    -- Service information
    service_date DATE NOT NULL,
    service_to_date DATE NULL,
    procedure_code VARCHAR(10) NOT NULL,
    modifier1 VARCHAR(2) NULL,
    modifier2 VARCHAR(2) NULL,
    modifier3 VARCHAR(2) NULL,
    modifier4 VARCHAR(2) NULL,
    
    -- Service location
    place_of_service VARCHAR(2) NOT NULL,
    
    -- Quantities and amounts
    units DECIMAL(10,2) NOT NULL,
    unit_type VARCHAR(2) DEFAULT 'UN',
    charge_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    
    -- Diagnosis pointers
    diagnosis_pointer1 INT NULL,
    diagnosis_pointer2 INT NULL,
    diagnosis_pointer3 INT NULL,
    diagnosis_pointer4 INT NULL,
    
    -- Prior authorization
    prior_auth_number VARCHAR(50) NULL,
    
    -- NDC information (if applicable)
    ndc_code VARCHAR(50) NULL,
    ndc_unit VARCHAR(2) NULL,
    ndc_quantity DECIMAL(10,4) NULL,
    
    -- Status
    line_status ENUM('pending', 'paid', 'denied', 'adjusted') DEFAULT 'pending',
    denial_reason VARCHAR(10) NULL,
    
    created_at DATETIME NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (claim_id) REFERENCES billing_claims(id) ON DELETE CASCADE,
    INDEX idx_claim (claim_id),
    INDEX idx_service_date (service_date),
    INDEX idx_procedure_code (procedure_code)
);

-- Providers table (enhanced for EDI)
CREATE TABLE IF NOT EXISTS providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_type ENUM('individual', 'organization') NOT NULL,
    
    -- Name fields
    last_name VARCHAR(100) NULL,
    first_name VARCHAR(100) NULL,
    middle_name VARCHAR(100) NULL,
    organization_name VARCHAR(255) NULL,
    
    -- Identifiers
    npi VARCHAR(10) UNIQUE NOT NULL,
    tax_id VARCHAR(20) NULL,
    medicaid_provider_id VARCHAR(50) NULL,
    
    -- Address
    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(2) NOT NULL,
    zip VARCHAR(10) NOT NULL,
    
    -- Contact
    phone VARCHAR(20) NOT NULL,
    fax VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    
    -- Specialties
    taxonomy_code VARCHAR(20) NULL,
    specialty VARCHAR(255) NULL,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at DATETIME NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_npi (npi),
    INDEX idx_active (is_active)
);

-- Payers table
CREATE TABLE IF NOT EXISTS payers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payer_id VARCHAR(50) UNIQUE NOT NULL,
    payer_name VARCHAR(255) NOT NULL,
    payer_type ENUM('medicare', 'medicaid', 'commercial', 'other') NOT NULL,
    
    -- EDI information
    edi_payer_id VARCHAR(50) NOT NULL,
    edi_receiver_id VARCHAR(50) NOT NULL,
    edi_version VARCHAR(20) DEFAULT '005010',
    
    -- Contact information
    address_line1 VARCHAR(255) NULL,
    address_line2 VARCHAR(255) NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(2) NULL,
    zip VARCHAR(10) NULL,
    phone VARCHAR(20) NULL,
    
    -- Configuration
    requires_prior_auth BOOLEAN DEFAULT FALSE,
    max_units_per_day JSON NULL,
    allowed_service_codes JSON NULL,
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_payer_id (payer_id),
    INDEX idx_active (is_active)
);

-- Insert Maryland Medicaid as default payer
INSERT INTO payers (
    payer_id, 
    payer_name, 
    payer_type, 
    edi_payer_id, 
    edi_receiver_id,
    requires_prior_auth,
    allowed_service_codes,
    created_at
) VALUES (
    'MDMEDICAID',
    'Maryland Medicaid',
    'medicaid',
    'MDMEDICAID',
    'MDMEDICAID',
    TRUE,
    '["H2019", "H2014", "T1027", "H2015", "S5111"]',
    NOW()
) ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Sample data for testing
-- Insert a test provider
INSERT INTO providers (
    provider_type,
    organization_name,
    npi,
    tax_id,
    medicaid_provider_id,
    address_line1,
    city,
    state,
    zip,
    phone,
    email,
    taxonomy_code,
    specialty,
    created_at
) VALUES (
    'organization',
    'Autism Care Institute',
    '1234567890',
    '12-3456789',
    'MD123456',
    '123 Main Street',
    'Baltimore',
    'MD',
    '21201',
    '410-555-1234',
    'billing@autismcareinstitute.com',
    '261QR0405X',
    'Clinic/Center - Developmental Disabilities',
    NOW()
) ON DUPLICATE KEY UPDATE updated_at = NOW();