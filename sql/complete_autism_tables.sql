-- Complete Autism Waiver Application Database Schema
-- Run this to create all required tables

-- 1. Users table
CREATE TABLE IF NOT EXISTS autism_users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100),
    access_level INT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_username (username),
    KEY idx_access_level (access_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Clients table
CREATE TABLE IF NOT EXISTS autism_clients (
    id INT(11) NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE,
    ma_number VARCHAR(20),
    waiver_type_id INT(11),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    emergency_contact VARCHAR(100),
    emergency_phone VARCHAR(20),
    status ENUM('active','inactive','discharged') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_name (last_name, first_name),
    KEY idx_ma_number (ma_number),
    KEY idx_status (status),
    KEY idx_waiver_type (waiver_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Staff members table
CREATE TABLE IF NOT EXISTS autism_staff_members (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11),
    employee_id VARCHAR(50),
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    role VARCHAR(50),
    hire_date DATE,
    status ENUM('active','inactive','on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_id (user_id),
    KEY idx_employee_id (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Waiver types
CREATE TABLE IF NOT EXISTS autism_waiver_types (
    id INT(11) NOT NULL AUTO_INCREMENT,
    waiver_code VARCHAR(20) NOT NULL,
    waiver_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY idx_waiver_code (waiver_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Service types
CREATE TABLE IF NOT EXISTS autism_service_types (
    id INT(11) NOT NULL AUTO_INCREMENT,
    service_code VARCHAR(20) NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    rate DECIMAL(10,2),
    unit_type ENUM('hour','unit','day','session') DEFAULT 'hour',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY idx_service_code (service_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Sessions table (THIS IS THE MISSING ONE)
CREATE TABLE IF NOT EXISTS autism_sessions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    client_id INT(11) NOT NULL,
    staff_id INT(11),
    service_type_id INT(11),
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration_hours DECIMAL(5,2),
    session_type VARCHAR(50),
    location VARCHAR(100),
    goals_addressed TEXT,
    interventions TEXT,
    client_response TEXT,
    notes TEXT,
    status ENUM('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
    billing_status ENUM('unbilled','billed','paid','denied') DEFAULT 'unbilled',
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_client (client_id),
    KEY idx_staff (staff_id),
    KEY idx_service (service_type_id),
    KEY idx_date (session_date),
    KEY idx_status (status),
    KEY idx_billing (billing_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Schedules table
CREATE TABLE IF NOT EXISTS autism_schedules (
    id INT(11) NOT NULL AUTO_INCREMENT,
    staff_id INT(11),
    client_id INT(11) NOT NULL,
    service_type_id INT(11),
    scheduled_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    notes TEXT,
    status ENUM('scheduled','confirmed','completed','cancelled','no_show') DEFAULT 'scheduled',
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_date_time (scheduled_date, start_time),
    KEY idx_client (client_id),
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Client authorizations
CREATE TABLE IF NOT EXISTS autism_client_authorizations (
    id INT(11) NOT NULL AUTO_INCREMENT,
    client_id INT(11) NOT NULL,
    waiver_type_id INT(11) NOT NULL,
    service_type_id INT(11) NOT NULL,
    fiscal_year INT(4) NOT NULL,
    fiscal_year_start DATE NOT NULL,
    fiscal_year_end DATE NOT NULL,
    weekly_hours DECIMAL(5,2),
    yearly_hours DECIMAL(7,2),
    used_hours DECIMAL(7,2) DEFAULT 0,
    remaining_hours DECIMAL(7,2),
    authorization_number VARCHAR(50),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active','expired','suspended','terminated') DEFAULT 'active',
    notes TEXT,
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_client (client_id),
    KEY idx_waiver (waiver_type_id),
    KEY idx_service (service_type_id),
    KEY idx_fiscal_year (fiscal_year),
    KEY idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Claims table
CREATE TABLE IF NOT EXISTS autism_claims (
    id INT(11) NOT NULL AUTO_INCREMENT,
    claim_number VARCHAR(50) UNIQUE,
    client_id INT(11) NOT NULL,
    service_date_from DATE NOT NULL,
    service_date_to DATE NOT NULL,
    total_amount DECIMAL(10,2),
    status ENUM('draft','generated','submitted','paid','denied') DEFAULT 'draft',
    payment_amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_client (client_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Organization settings
CREATE TABLE IF NOT EXISTS autism_organization_settings (
    id INT(11) NOT NULL AUTO_INCREMENT,
    organization_name VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    city VARCHAR(100),
    state VARCHAR(2),
    zip VARCHAR(10),
    phone VARCHAR(20),
    email VARCHAR(255),
    tax_id VARCHAR(20),
    npi VARCHAR(20),
    medicaid_provider_id VARCHAR(50),
    website VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default data
INSERT IGNORE INTO autism_users (username, password_hash, email, full_name, access_level) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
'admin@americancaregivers.com', 'System Administrator', 5);

INSERT IGNORE INTO autism_waiver_types (waiver_code, waiver_name, description) VALUES 
('AW', 'Autism Waiver', 'Maryland Autism Waiver Program for children and adults with Autism Spectrum Disorder'),
('CFC', 'Community First Choice', 'Community-based services and supports for individuals with disabilities'),
('CP', 'Community Pathways', 'Supports for individuals with developmental disabilities in community settings'),
('FCP', 'Family Supports', 'Family-centered supports for individuals with developmental disabilities'),
('TBI', 'Brain Injury Waiver', 'Services for individuals with traumatic brain injuries');

INSERT IGNORE INTO autism_service_types (service_code, service_name, description, rate, unit_type) VALUES 
('IISS', 'Individual Intensive Support Services', 'One-on-one support services for individuals with autism', 35.00, 'hour'),
('TI', 'Therapeutic Integration', 'Community integration and therapeutic support', 40.00, 'hour'),
('RESPITE', 'Respite Care', 'Temporary relief care for primary caregivers', 30.00, 'hour'),
('FAMILY', 'Family Consultation', 'Support and consultation for family members', 50.00, 'hour');

INSERT IGNORE INTO autism_staff_members (user_id, employee_id, full_name, email, phone, role, hire_date, status) VALUES 
(1, 'EMP001', 'System Administrator', 'admin@americancaregivers.com', '(240) 555-0001', 'Administrator', '2020-01-01', 'active');

INSERT IGNORE INTO autism_clients (first_name, last_name, ma_number) VALUES 
('John', 'Doe', 'MD123456789'),
('Jane', 'Smith', 'MD987654321');

-- Grant permissions (if needed)
-- GRANT ALL PRIVILEGES ON openemr.autism_* TO 'openemr'@'%';