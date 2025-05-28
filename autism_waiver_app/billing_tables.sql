-- MEDICAID BILLING INTEGRATION TABLES
-- Additional tables for billing compliance and audit trails

-- Billing claims table
CREATE TABLE IF NOT EXISTS autism_billing_claims (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT,
    claim_id VARCHAR(50) UNIQUE NOT NULL,
    ma_number VARCHAR(20) NOT NULL,
    billing_code VARCHAR(20) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    claim_data JSON,
    status ENUM('generated', 'submitted', 'accepted', 'rejected', 'paid', 'error') DEFAULT 'generated',
    response_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES autism_session_notes(id) ON DELETE SET NULL,
    INDEX idx_ma_number (ma_number),
    INDEX idx_billing_code (billing_code),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Eligibility verification log
CREATE TABLE IF NOT EXISTS autism_eligibility_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    service_date DATE NOT NULL,
    eligibility_response JSON,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES autism_clients(id) ON DELETE CASCADE,
    INDEX idx_client_service_date (client_id, service_date),
    INDEX idx_checked_at (checked_at)
);

-- Billing activity log for audit trail
CREATE TABLE IF NOT EXISTS autism_billing_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    claim_id INT,
    activity VARCHAR(50) NOT NULL,
    activity_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (claim_id) REFERENCES autism_billing_claims(id) ON DELETE CASCADE,
    INDEX idx_claim_id (claim_id),
    INDEX idx_activity (activity),
    INDEX idx_created_at (created_at)
);

-- Provider NPI and billing information
CREATE TABLE IF NOT EXISTS autism_provider_billing (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    npi VARCHAR(10) UNIQUE,
    medicaid_provider_id VARCHAR(20),
    taxonomy_code VARCHAR(20),
    license_number VARCHAR(50),
    license_type VARCHAR(50),
    license_expiry DATE,
    billing_rate DECIMAL(8,2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES autism_staff_members(id) ON DELETE CASCADE,
    INDEX idx_npi (npi),
    INDEX idx_medicaid_provider_id (medicaid_provider_id),
    INDEX idx_staff_id (staff_id)
);

-- Encounter data for CMS reporting
CREATE TABLE IF NOT EXISTS autism_encounter_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    encounter_id VARCHAR(50) UNIQUE NOT NULL,
    session_id INT,
    member_id VARCHAR(20) NOT NULL,
    provider_npi VARCHAR(10) NOT NULL,
    service_date DATE NOT NULL,
    procedure_code VARCHAR(20) NOT NULL,
    diagnosis_code VARCHAR(20) NOT NULL,
    units INT NOT NULL DEFAULT 1,
    place_of_service VARCHAR(5) DEFAULT '12',
    service_type VARCHAR(100),
    submitted_to_cms BOOLEAN DEFAULT FALSE,
    submission_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES autism_session_notes(id) ON DELETE SET NULL,
    INDEX idx_encounter_id (encounter_id),
    INDEX idx_member_id (member_id),
    INDEX idx_service_date (service_date),
    INDEX idx_procedure_code (procedure_code),
    INDEX idx_submitted_to_cms (submitted_to_cms)
);

-- Prior authorization tracking
CREATE TABLE IF NOT EXISTS autism_prior_authorizations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    service_type_id INT NOT NULL,
    authorization_number VARCHAR(50) UNIQUE,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    authorized_units INT NOT NULL,
    used_units INT DEFAULT 0,
    status ENUM('active', 'expired', 'exhausted', 'cancelled') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES autism_clients(id) ON DELETE CASCADE,
    FOREIGN KEY (service_type_id) REFERENCES autism_service_types(id) ON DELETE CASCADE,
    INDEX idx_client_id (client_id),
    INDEX idx_authorization_number (authorization_number),
    INDEX idx_status (status),
    INDEX idx_date_range (start_date, end_date)
);

-- Insurance information for clients
CREATE TABLE IF NOT EXISTS autism_client_insurance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT NOT NULL,
    insurance_type ENUM('primary', 'secondary') DEFAULT 'primary',
    ma_number VARCHAR(20),
    mco_name VARCHAR(100),
    policy_number VARCHAR(50),
    group_number VARCHAR(50),
    effective_date DATE,
    termination_date DATE,
    copay_amount DECIMAL(6,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES autism_clients(id) ON DELETE CASCADE,
    INDEX idx_client_id (client_id),
    INDEX idx_ma_number (ma_number),
    INDEX idx_insurance_type (insurance_type),
    INDEX idx_is_active (is_active)
);

-- Billing rates by service type and program
CREATE TABLE IF NOT EXISTS autism_billing_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    program_id INT NOT NULL,
    service_type_id INT NOT NULL,
    billing_code VARCHAR(20) NOT NULL,
    rate_per_unit DECIMAL(8,2) NOT NULL,
    unit_type ENUM('15min', '30min', '60min', 'session', 'day', 'month') DEFAULT '15min',
    effective_date DATE NOT NULL,
    expiry_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES autism_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (service_type_id) REFERENCES autism_service_types(id) ON DELETE CASCADE,
    INDEX idx_program_service (program_id, service_type_id),
    INDEX idx_billing_code (billing_code),
    INDEX idx_effective_date (effective_date),
    INDEX idx_is_active (is_active)
);

-- Medicaid denial tracking
CREATE TABLE IF NOT EXISTS autism_claim_denials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    claim_id INT NOT NULL,
    denial_code VARCHAR(20),
    denial_reason TEXT,
    denial_date DATE,
    appeal_filed BOOLEAN DEFAULT FALSE,
    appeal_date DATE,
    appeal_outcome ENUM('pending', 'approved', 'denied') NULL,
    corrected_claim_id INT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (claim_id) REFERENCES autism_billing_claims(id) ON DELETE CASCADE,
    FOREIGN KEY (corrected_claim_id) REFERENCES autism_billing_claims(id) ON DELETE SET NULL,
    INDEX idx_claim_id (claim_id),
    INDEX idx_denial_code (denial_code),
    INDEX idx_denial_date (denial_date),
    INDEX idx_appeal_filed (appeal_filed)
);

-- Payment tracking
CREATE TABLE IF NOT EXISTS autism_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    claim_id INT NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    check_number VARCHAR(50),
    remittance_advice_number VARCHAR(50),
    adjustment_amount DECIMAL(10,2) DEFAULT 0.00,
    adjustment_reason VARCHAR(255),
    payment_method ENUM('check', 'eft', 'ach') DEFAULT 'eft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (claim_id) REFERENCES autism_billing_claims(id) ON DELETE CASCADE,
    INDEX idx_claim_id (claim_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_check_number (check_number)
);

-- Insert sample billing rates for autism waiver services
INSERT INTO autism_billing_rates (program_id, service_type_id, billing_code, rate_per_unit, unit_type, effective_date) VALUES
(1, 1, 'W9323', 150.00, 'session', '2025-01-01'), -- AW - IISS
(1, 2, 'W9323', 150.00, 'session', '2025-01-01'), -- AW - TI
(1, 3, 'W9323', 150.00, 'session', '2025-01-01'), -- AW - Respite
(1, 4, 'W9323', 150.00, 'session', '2025-01-01'), -- AW - FC
(2, 5, 'T2022', 150.00, 'session', '2025-01-01'), -- DDA - PCA
(3, 6, 'T2022', 150.00, 'session', '2025-01-01'), -- CFC - Companion
(4, 7, '96158', 36.26, '30min', '2025-01-01'), -- CS - Life Skills
(4, 8, '96159', 18.12, '15min', '2025-01-01'); -- CS - Behavioral Support

-- Insert sample provider billing information
INSERT INTO autism_provider_billing (staff_id, npi, medicaid_provider_id, taxonomy_code, license_number, license_type, billing_rate) VALUES
(1, '1234567890', 'MD123456', '261QM0850X', 'LIC001', 'LCSW', 75.00), -- Mary Emah
(2, '2345678901', 'MD234567', '261QM0850X', 'LIC002', 'BCBA', 85.00), -- Amanda Georgie
(3, '3456789012', 'MD345678', '372500000X', 'LIC003', 'DSP', 25.00), -- Joyce Aboagye
(4, '4567890123', 'MD456789', '372500000X', 'LIC004', 'DSP', 25.00), -- Oluwadamilare Abidakun
(5, '5678901234', 'MD567890', '372500000X', 'LIC005', 'DSP', 25.00); -- Sumayya Abdul Khadar

-- Insert sample prior authorizations
INSERT INTO autism_prior_authorizations (client_id, service_type_id, authorization_number, start_date, end_date, authorized_units) VALUES
(1, 1, 'AUTH2025001', '2025-01-01', '2025-12-31', 1040), -- Jahan Begum - IISS (20 hrs/week * 52 weeks)
(2, 2, 'AUTH2025002', '2025-01-01', '2025-12-31', 780), -- Jamil Crosse - TI (15 hrs/week * 52 weeks)
(3, 1, 'AUTH2025003', '2025-01-01', '2025-12-31', 1040), -- Stefan Fernandes - IISS
(4, 3, 'AUTH2025004', '2025-01-01', '2025-12-31', 416), -- Tsadkan Gebremedhin - Respite (8 hrs/week * 52 weeks)
(5, 4, 'AUTH2025005', '2025-01-01', '2025-12-31', 104); -- Almaz Gebreyohanes - FC (2 hrs/week * 52 weeks)

-- Insert sample client insurance information
INSERT INTO autism_client_insurance (client_id, insurance_type, ma_number, mco_name, effective_date) VALUES
(1, 'primary', '123456789', 'Maryland HealthChoice', '2025-01-01'), -- Jahan Begum
(2, 'primary', '987654321', 'Maryland HealthChoice', '2025-01-01'), -- Jamil Crosse
(3, 'primary', '456789123', 'Maryland HealthChoice', '2025-01-01'), -- Stefan Fernandes
(4, 'primary', '789123456', 'Maryland HealthChoice', '2025-01-01'), -- Tsadkan Gebremedhin
(5, 'primary', '321654987', 'Maryland HealthChoice', '2025-01-01'), -- Almaz Gebreyohanes
(6, 'primary', '654987321', 'Maryland HealthChoice', '2025-01-01'), -- Richard Goines
(7, 'primary', '147258369', 'Maryland HealthChoice', '2025-01-01'), -- Yohana Mengistu
(8, 'primary', '258369147', 'Maryland HealthChoice', '2025-01-01'), -- Glenda Pruitt
(9, 'primary', '369147258', 'Maryland HealthChoice', '2025-01-01'), -- Rosalinda Tongos
(10, 'primary', '741852963', 'Maryland HealthChoice', '2025-01-01'); -- Ashleigh Williams 