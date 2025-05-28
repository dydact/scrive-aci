-- ================================================================
-- AMERICAN CAREGIVERS INC - PRODUCTION DATABASE SETUP
-- Scrive Autism Waiver Management System
-- 
-- Usage: Import real employee and client data for production deployment
-- Target: aci.dydact.io production server
-- ================================================================

-- Drop existing tables in correct order (foreign key dependencies)
DROP TABLE IF EXISTS autism_goal_progress;
DROP TABLE IF EXISTS autism_treatment_goals;
DROP TABLE IF EXISTS autism_treatment_plans;
DROP TABLE IF EXISTS autism_session_notes;
DROP TABLE IF EXISTS autism_client_services;
DROP TABLE IF EXISTS autism_staff_assignments;
DROP TABLE IF EXISTS autism_client_enrollments;
DROP TABLE IF EXISTS autism_user_roles;
DROP TABLE IF EXISTS autism_staff_roles;
DROP TABLE IF EXISTS autism_security_log;
DROP TABLE IF EXISTS autism_org_ma_numbers;
DROP TABLE IF EXISTS autism_service_types;
DROP TABLE IF EXISTS autism_programs;
DROP TABLE IF EXISTS autism_staff_members;
DROP TABLE IF EXISTS autism_clients;

-- ================================================================
-- CORE CONFIGURATION TABLES
-- ================================================================

-- Autism Waiver Programs (AW, DDA, CFC, CS, etc.)
CREATE TABLE autism_programs (
    program_id INT AUTO_INCREMENT PRIMARY KEY,
    program_name VARCHAR(100) NOT NULL UNIQUE,
    abbreviation VARCHAR(10) NOT NULL UNIQUE,
    description TEXT,
    organizational_ma_number VARCHAR(20) NOT NULL,
    max_weekly_units INT DEFAULT 40,
    fiscal_year_start DATE DEFAULT '2024-07-01',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_abbreviation (abbreviation),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Service Types (IISS, TI, Respite, FC)
CREATE TABLE autism_service_types (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    abbreviation VARCHAR(10) NOT NULL,
    program_id INT NOT NULL,
    default_weekly_units INT DEFAULT 10,
    hourly_rate DECIMAL(10,2) DEFAULT 15.00,
    requires_certification BOOLEAN DEFAULT FALSE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (program_id) REFERENCES autism_programs(program_id),
    INDEX idx_program (program_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- STAFF MANAGEMENT TABLES
-- ================================================================

-- Staff Members (Real Employee Data)
CREATE TABLE autism_staff_members (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    hire_date DATE NOT NULL,
    job_title VARCHAR(100),
    department VARCHAR(50),
    supervisor_id INT NULL,
    hourly_rate DECIMAL(10,2) DEFAULT 15.00,
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (supervisor_id) REFERENCES autism_staff_members(staff_id),
    INDEX idx_employee_id (employee_id),
    INDEX idx_email (email),
    INDEX idx_active (is_active),
    INDEX idx_supervisor (supervisor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff Role Definitions
CREATE TABLE autism_staff_roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_level INT NOT NULL,
    description TEXT,
    can_view_org_ma BOOLEAN DEFAULT FALSE,
    can_view_client_ma BOOLEAN DEFAULT FALSE,
    can_edit_client_data BOOLEAN DEFAULT FALSE,
    can_create_treatment_plans BOOLEAN DEFAULT FALSE,
    can_manage_staff BOOLEAN DEFAULT FALSE,
    can_view_billing BOOLEAN DEFAULT FALSE,
    can_access_security_logs BOOLEAN DEFAULT FALSE,
    
    INDEX idx_level (role_level),
    UNIQUE KEY unique_level (role_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff-Role Assignments
CREATE TABLE autism_user_roles (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_date DATE NOT NULL DEFAULT (CURDATE()),
    assigned_by INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (staff_id) REFERENCES autism_staff_members(staff_id),
    FOREIGN KEY (role_id) REFERENCES autism_staff_roles(role_id),
    FOREIGN KEY (assigned_by) REFERENCES autism_staff_members(staff_id),
    UNIQUE KEY unique_staff_role (staff_id, role_id),
    INDEX idx_staff (staff_id),
    INDEX idx_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- CLIENT MANAGEMENT TABLES
-- ================================================================

-- Clients (Real Client Data)
CREATE TABLE autism_clients (
    client_id INT AUTO_INCREMENT PRIMARY KEY,
    client_ma_number VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('M', 'F', 'Other') DEFAULT 'Other',
    address_line1 VARCHAR(100),
    address_line2 VARCHAR(100),
    city VARCHAR(50),
    state VARCHAR(2) DEFAULT 'MD',
    zip_code VARCHAR(10),
    phone VARCHAR(20),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relationship VARCHAR(50),
    school_name VARCHAR(100),
    school_district VARCHAR(100),
    primary_diagnosis TEXT,
    secondary_diagnosis TEXT,
    medication_list TEXT,
    allergies TEXT,
    special_notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_ma_number (client_ma_number),
    INDEX idx_name (last_name, first_name),
    INDEX idx_active (is_active),
    INDEX idx_dob (date_of_birth)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Client Program Enrollments
CREATE TABLE autism_client_enrollments (
    enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    program_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    authorization_start DATE NOT NULL,
    authorization_end DATE NOT NULL,
    authorized_weekly_units INT NOT NULL,
    case_manager_id INT NOT NULL,
    primary_coordinator_id INT NULL,
    county_jurisdiction VARCHAR(50) NOT NULL,
    funding_source VARCHAR(100),
    authorization_number VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES autism_clients(client_id),
    FOREIGN KEY (program_id) REFERENCES autism_programs(program_id),
    FOREIGN KEY (case_manager_id) REFERENCES autism_staff_members(staff_id),
    FOREIGN KEY (primary_coordinator_id) REFERENCES autism_staff_members(staff_id),
    INDEX idx_client (client_id),
    INDEX idx_program (program_id),
    INDEX idx_active (is_active),
    INDEX idx_case_manager (case_manager_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Client-Service Assignments
CREATE TABLE autism_client_services (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    service_id INT NOT NULL,
    weekly_allocated_units INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (enrollment_id) REFERENCES autism_client_enrollments(enrollment_id),
    FOREIGN KEY (service_id) REFERENCES autism_service_types(service_id),
    INDEX idx_enrollment (enrollment_id),
    INDEX idx_service (service_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff-Client Assignments
CREATE TABLE autism_staff_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    client_id INT NOT NULL,
    assignment_type ENUM('primary', 'secondary', 'supervisor', 'substitute') DEFAULT 'primary',
    start_date DATE NOT NULL,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (staff_id) REFERENCES autism_staff_members(staff_id),
    FOREIGN KEY (client_id) REFERENCES autism_clients(client_id),
    INDEX idx_staff (staff_id),
    INDEX idx_client (client_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- TREATMENT PLANNING TABLES
-- ================================================================

-- Treatment Plans
CREATE TABLE autism_treatment_plans (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    plan_name VARCHAR(255) NOT NULL,
    created_date DATE NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    plan_overview TEXT,
    created_by INT NOT NULL,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES autism_clients(client_id),
    FOREIGN KEY (created_by) REFERENCES autism_staff_members(staff_id),
    FOREIGN KEY (updated_by) REFERENCES autism_staff_members(staff_id),
    INDEX idx_client_id (client_id),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Treatment Goals
CREATE TABLE autism_treatment_goals (
    goal_id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    goal_category VARCHAR(100) NOT NULL,
    goal_title VARCHAR(255) NOT NULL,
    goal_description TEXT NOT NULL,
    target_criteria TEXT,
    current_progress INT DEFAULT 0,
    target_date DATE,
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
    status ENUM('active', 'achieved', 'discontinued') DEFAULT 'active',
    created_by INT NOT NULL,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (plan_id) REFERENCES autism_treatment_plans(plan_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES autism_staff_members(staff_id),
    FOREIGN KEY (updated_by) REFERENCES autism_staff_members(staff_id),
    INDEX idx_plan_id (plan_id),
    INDEX idx_category (goal_category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Goal Progress Tracking
CREATE TABLE autism_goal_progress (
    progress_id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    session_date DATE NOT NULL,
    progress_rating INT NOT NULL CHECK (progress_rating BETWEEN 1 AND 5),
    progress_notes TEXT,
    data_collected TEXT,
    staff_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (goal_id) REFERENCES autism_treatment_goals(goal_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES autism_staff_members(staff_id),
    INDEX idx_goal_id (goal_id),
    INDEX idx_session_date (session_date),
    INDEX idx_staff_id (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Session Notes
CREATE TABLE autism_session_notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    staff_id INT NOT NULL,
    service_id INT NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration_minutes INT GENERATED ALWAYS AS (TIMESTAMPDIFF(MINUTE, CONCAT(session_date, ' ', start_time), CONCAT(session_date, ' ', end_time))) STORED,
    session_notes TEXT NOT NULL,
    behavior_observations TEXT,
    interventions_used TEXT,
    client_response TEXT,
    recommendations TEXT,
    parent_communication TEXT,
    units_billed DECIMAL(5,2) NOT NULL,
    status ENUM('draft', 'submitted', 'approved', 'billed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES autism_clients(client_id),
    FOREIGN KEY (staff_id) REFERENCES autism_staff_members(staff_id),
    FOREIGN KEY (service_id) REFERENCES autism_service_types(service_id),
    INDEX idx_client_date (client_id, session_date),
    INDEX idx_staff_date (staff_id, session_date),
    INDEX idx_service (service_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- SECURITY & AUDIT TABLES
-- ================================================================

-- Organizational MA Numbers (ADMIN ACCESS ONLY)
CREATE TABLE autism_org_ma_numbers (
    org_ma_id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    ma_number VARCHAR(20) NOT NULL UNIQUE,
    provider_name VARCHAR(100) NOT NULL,
    effective_date DATE NOT NULL,
    expiration_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (program_id) REFERENCES autism_programs(program_id),
    INDEX idx_program (program_id),
    INDEX idx_ma_number (ma_number),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Security Audit Log
CREATE TABLE autism_security_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT,
    action_type VARCHAR(100) NOT NULL,
    resource_type VARCHAR(100) NOT NULL,
    resource_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    access_granted BOOLEAN NOT NULL,
    failure_reason VARCHAR(255),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (staff_id) REFERENCES autism_staff_members(staff_id),
    INDEX idx_staff_action (staff_id, action_type),
    INDEX idx_timestamp (timestamp),
    INDEX idx_access_granted (access_granted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- DEFAULT DATA INSERTION
-- ================================================================

-- Insert Default Programs
INSERT INTO autism_programs (program_name, abbreviation, description, organizational_ma_number, max_weekly_units) VALUES
('Autism Waiver', 'AW', 'Primary autism waiver program for comprehensive support services', '410608300', 40),
('Developmental Disabilities Administration', 'DDA', 'State-funded developmental disabilities support program', '410608301', 35),
('Community First Choice', 'CFC', 'Community-based support services program', '522902200', 30),
('Community Services', 'CS', 'General community support and integration services', '433226100', 25);

-- Insert Default Service Types
INSERT INTO autism_service_types (service_name, abbreviation, program_id, default_weekly_units, hourly_rate, description) VALUES
-- Autism Waiver Services
('Individual Intensive Support Services', 'IISS', 1, 20, 22.50, 'One-on-one intensive behavioral and skill development support'),
('Therapeutic Integration', 'TI', 1, 15, 20.00, 'Integration support in community and educational settings'),
('Respite Care', 'Respite', 1, 8, 18.00, 'Temporary relief care for families and caregivers'),
('Family Consultation', 'FC', 1, 2, 25.00, 'Family training and consultation services'),

-- DDA Services  
('Community Support', 'CS-DDA', 2, 12, 19.50, 'General community integration and life skills support'),
('Residential Habilitation', 'RH', 2, 25, 21.00, 'Residential living skills development'),

-- CFC Services
('Personal Care', 'PC', 3, 15, 17.50, 'Personal care and daily living assistance'),
('Companion Services', 'COMP', 3, 10, 16.00, 'Social companionship and community integration'),

-- CS Services
('Life Skills Training', 'LST', 4, 8, 20.00, 'Independent living skills development'),
('Behavioral Support', 'BS', 4, 6, 23.00, 'Behavioral intervention and support services');

-- Insert Default Staff Roles
INSERT INTO autism_staff_roles (role_name, role_level, description, can_view_org_ma, can_view_client_ma, can_edit_client_data, can_create_treatment_plans, can_manage_staff, can_view_billing, can_access_security_logs) VALUES
('Administrator', 5, 'Full system administrator with all permissions', TRUE, TRUE, TRUE, TRUE, TRUE, TRUE, TRUE),
('Supervisor', 4, 'Management oversight with billing access but no organizational MA visibility', FALSE, TRUE, TRUE, TRUE, TRUE, TRUE, FALSE),
('Case Manager', 3, 'Treatment planning and client coordination', FALSE, TRUE, TRUE, TRUE, FALSE, FALSE, FALSE),
('Direct Care Staff', 2, 'Session documentation and client interaction', FALSE, TRUE, FALSE, FALSE, FALSE, FALSE, FALSE),
('Technician', 1, 'Basic session documentation only', FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE);

-- Insert Organizational MA Numbers (SECURE - ADMIN ACCESS ONLY)
INSERT INTO autism_org_ma_numbers (program_id, ma_number, provider_name, effective_date) VALUES
(1, '410608300', 'American Caregivers Inc - Autism Waiver', '2024-01-01'),
(2, '410608301', 'American Caregivers Inc - DDA Services', '2024-01-01'),
(3, '522902200', 'American Caregivers Inc - CFC Services', '2024-01-01'),
(4, '433226100', 'American Caregivers Inc - Community Services', '2024-01-01');

-- ================================================================
-- DATA POPULATION READY
-- ================================================================

-- Note: Real employee and client data will be inserted here
-- Structure ready for:
-- 1. autism_staff_members (employee data)
-- 2. autism_user_roles (role assignments)
-- 3. autism_clients (client data)
-- 4. autism_client_enrollments (program enrollments)
-- 5. autism_treatment_plans (treatment plans)
-- 6. autism_treatment_goals (goals)
-- 7. autism_staff_assignments (staff-client assignments)

SELECT 'Production database structure created successfully. Ready for real data population.' as status; 