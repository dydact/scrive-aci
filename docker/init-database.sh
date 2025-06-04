#!/bin/sh
# Automatic database initialization for Scrive ACI
set -e

DB_HOST="mysql"
DB_USER="openemr"
DB_PASS="openemr"
DB_NAME="openemr"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

# Install mysql client if not present
if ! command -v mysql >/dev/null 2>&1; then
    log "Installing MySQL client..."
    apk add --no-cache mysql-client
fi

# Wait for MySQL to be ready
log "Waiting for MySQL to be ready..."
COUNTER=0
MAX_TRIES=30
until mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" >/dev/null 2>&1; do
    COUNTER=$((COUNTER + 1))
    if [ $COUNTER -gt $MAX_TRIES ]; then
        log "MySQL not ready after $MAX_TRIES attempts"
        exit 1
    fi
    log "Waiting for MySQL... (attempt $COUNTER/$MAX_TRIES)"
    sleep 2
done

log "MySQL is ready. Checking if autism tables exist..."

# Check if autism tables exist
AUTISM_TABLES=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES LIKE 'autism_%'" --skip-column-names | wc -l)

if [ "$AUTISM_TABLES" -eq 0 ]; then
    log "Autism tables missing. Creating them..."
    
    # Create autism tables
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<EOF
-- Create autism_clients table
CREATE TABLE IF NOT EXISTS autism_clients (
    id INT(11) NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    ma_number VARCHAR(20),
    date_of_birth DATE,
    phone VARCHAR(20),
    email VARCHAR(255),
    address TEXT,
    emergency_contact VARCHAR(255),
    emergency_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ma_number (ma_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create autism_claims table
CREATE TABLE IF NOT EXISTS autism_claims (
    id INT(11) NOT NULL AUTO_INCREMENT,
    claim_number VARCHAR(50) UNIQUE,
    client_id INT(11) NOT NULL,
    service_date_from DATE NOT NULL,
    service_date_to DATE NOT NULL,
    total_amount DECIMAL(10,2),
    status ENUM('draft','generated','submitted','paid','denied') DEFAULT 'draft',
    payment_amount DECIMAL(10,2),
    payment_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_client (client_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create autism_schedules table
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

-- Create autism_organization_settings table
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

-- Create autism_users table
CREATE TABLE IF NOT EXISTS autism_users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    full_name VARCHAR(255),
    access_level INT(1) DEFAULT 1,
    status ENUM('active','inactive','pending') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_username (username),
    KEY idx_access_level (access_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create autism_staff_members table
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

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO autism_users (username, password_hash, email, full_name, access_level) 
VALUES ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@americancaregivers.com', 'System Administrator', 5);

-- Insert sample clients
INSERT IGNORE INTO autism_clients (first_name, last_name, ma_number) VALUES 
('John', 'Doe', 'MD123456789'),
('Jane', 'Smith', 'MD987654321');

-- Insert sample claims
INSERT IGNORE INTO autism_claims (claim_number, client_id, service_date_from, service_date_to, total_amount, status) VALUES 
('CLM001', 1, '2024-05-01', '2024-05-15', 1250.00, 'draft'),
('CLM002', 2, '2024-05-16', '2024-05-30', 980.50, 'paid');

-- Insert sample staff members
INSERT IGNORE INTO autism_staff_members (user_id, employee_id, full_name, email, phone, role, hire_date, status) VALUES 
(1, 'EMP001', 'System Administrator', 'admin@americancaregivers.com', '(240) 555-0001', 'Administrator', '2020-01-01', 'active'),
(NULL, 'EMP002', 'Jane Manager', 'jane.manager@americancaregivers.com', '(240) 555-0002', 'Case Manager', '2021-03-15', 'active'),
(NULL, 'EMP003', 'John Support', 'john.support@americancaregivers.com', '(240) 555-0003', 'DSP', '2022-06-01', 'active');

-- Create autism_service_types table
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

-- Insert sample service types
INSERT IGNORE INTO autism_service_types (service_code, service_name, description, rate, unit_type) VALUES 
('IISS', 'Individual Intensive Support Services', 'One-on-one support services for individuals with autism', 35.00, 'hour'),
('TI', 'Therapeutic Integration', 'Community integration and therapeutic support', 40.00, 'hour'),
('RESPITE', 'Respite Care', 'Temporary relief care for primary caregivers', 30.00, 'hour'),
('FAMILY', 'Family Consultation', 'Support and consultation for family members', 50.00, 'hour');

-- Create waiver types table
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

-- Insert waiver types
INSERT IGNORE INTO autism_waiver_types (waiver_code, waiver_name, description) VALUES 
('AW', 'Autism Waiver', 'Maryland Autism Waiver Program for children and adults with Autism Spectrum Disorder'),
('CFC', 'Community First Choice', 'Community-based services and supports for individuals with disabilities'),
('CP', 'Community Pathways', 'Supports for individuals with developmental disabilities in community settings'),
('FCP', 'Family Supports', 'Family-centered supports for individuals with developmental disabilities'),
('TBI', 'Brain Injury Waiver', 'Services for individuals with traumatic brain injuries');

-- Create client authorizations table
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

-- Add waiver_type_id to clients table if not exists
ALTER TABLE autism_clients ADD COLUMN IF NOT EXISTS waiver_type_id INT(11) AFTER ma_number;

-- Create autism_sessions table
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
EOF
    
    log "Autism tables created successfully!"
    
    # Create additional tables needed for full functionality
    log "Creating additional tables for full system functionality..."
    
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<'ADDITIONAL_TABLES'
-- Create audit log table
CREATE TABLE IF NOT EXISTS autism_audit_log (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11),
    action VARCHAR(50),
    table_name VARCHAR(50),
    record_id INT(11),
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_id (user_id),
    KEY idx_table_record (table_name, record_id),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create time clock table
CREATE TABLE IF NOT EXISTS autism_time_clock (
    id INT(11) NOT NULL AUTO_INCREMENT,
    employee_id INT(11) NOT NULL,
    clock_in TIMESTAMP NULL,
    clock_out TIMESTAMP NULL,
    total_hours DECIMAL(5,2),
    notes TEXT,
    is_billable TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_employee (employee_id),
    KEY idx_clock_in (clock_in)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create billing entries table
CREATE TABLE IF NOT EXISTS autism_billing_entries (
    id INT(11) NOT NULL AUTO_INCREMENT,
    employee_id INT(11) NOT NULL,
    client_id INT(11) NOT NULL,
    service_date DATE NOT NULL,
    billing_date DATE NOT NULL,
    billable_minutes INT(11) DEFAULT 0,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('pending','approved','billed','paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_employee (employee_id),
    KEY idx_client (client_id),
    KEY idx_dates (service_date, billing_date),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create payroll summary table
CREATE TABLE IF NOT EXISTS autism_payroll_summary (
    id INT(11) NOT NULL AUTO_INCREMENT,
    employee_id INT(11) NOT NULL,
    pay_period_start DATE NOT NULL,
    pay_period_end DATE NOT NULL,
    total_hours DECIMAL(5,2) DEFAULT 0,
    regular_hours DECIMAL(5,2) DEFAULT 0,
    overtime_hours DECIMAL(5,2) DEFAULT 0,
    gross_pay DECIMAL(10,2) DEFAULT 0,
    status ENUM('draft','approved','processed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_employee (employee_id),
    KEY idx_period (pay_period_start, pay_period_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add hourly_rate to staff_members if not exists
ALTER TABLE autism_staff_members ADD COLUMN IF NOT EXISTS hourly_rate DECIMAL(6,2) DEFAULT 20.00 AFTER role;
ALTER TABLE autism_staff_members ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) AFTER full_name;
ALTER TABLE autism_staff_members ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) AFTER first_name;

-- Create approvals table for supervisor workflow
CREATE TABLE IF NOT EXISTS autism_approvals (
    id INT(11) NOT NULL AUTO_INCREMENT,
    approval_type VARCHAR(50) NOT NULL,
    record_type VARCHAR(50) NOT NULL,
    record_id INT(11) NOT NULL,
    staff_id INT(11),
    client_id INT(11),
    requested_by INT(11),
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by INT(11),
    approved_at TIMESTAMP NULL,
    status ENUM('pending','approved','rejected','revision_requested') DEFAULT 'pending',
    notes TEXT,
    rejection_reason TEXT,
    metadata JSON,
    PRIMARY KEY (id),
    KEY idx_type_status (approval_type, status),
    KEY idx_record (record_type, record_id),
    KEY idx_staff (staff_id),
    KEY idx_dates (requested_at, approved_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create schedule changes table
CREATE TABLE IF NOT EXISTS autism_schedule_changes (
    id INT(11) NOT NULL AUTO_INCREMENT,
    schedule_id INT(11) NOT NULL,
    change_type ENUM('reschedule','cancel','add') NOT NULL,
    original_date DATE,
    original_start_time TIME,
    new_date DATE,
    new_start_time TIME,
    new_end_time TIME,
    reason TEXT,
    requested_by INT(11),
    approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_schedule (schedule_id),
    KEY idx_status (approval_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create time off requests table
CREATE TABLE IF NOT EXISTS autism_time_off_requests (
    id INT(11) NOT NULL AUTO_INCREMENT,
    staff_id INT(11) NOT NULL,
    request_type ENUM('vacation','sick','personal','other') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days DECIMAL(3,1),
    reason TEXT,
    approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    approved_by INT(11),
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_staff (staff_id),
    KEY idx_dates (start_date, end_date),
    KEY idx_status (approval_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create prior authorizations table
CREATE TABLE IF NOT EXISTS autism_prior_authorizations (
    id INT(11) NOT NULL AUTO_INCREMENT,
    client_id INT(11) NOT NULL,
    authorization_number VARCHAR(50) UNIQUE,
    payer_name VARCHAR(100),
    service_type_id INT(11),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    authorized_units INT(11),
    used_units INT(11) DEFAULT 0,
    remaining_units INT(11),
    status ENUM('active','expired','exhausted','pending') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_client (client_id),
    KEY idx_auth_number (authorization_number),
    KEY idx_dates (start_date, end_date),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create claim denials table
CREATE TABLE IF NOT EXISTS autism_claim_denials (
    id INT(11) NOT NULL AUTO_INCREMENT,
    claim_id INT(11) NOT NULL,
    denial_date DATE NOT NULL,
    denial_reason VARCHAR(255),
    denial_code VARCHAR(20),
    followup_required TINYINT(1) DEFAULT 1,
    appeal_deadline DATE,
    appeal_submitted TINYINT(1) DEFAULT 0,
    resolution_date DATE,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_claim (claim_id),
    KEY idx_denial_date (denial_date),
    KEY idx_followup (followup_required)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create billing rates table
CREATE TABLE IF NOT EXISTS autism_billing_rates (
    id INT(11) NOT NULL AUTO_INCREMENT,
    service_type_id INT(11) NOT NULL,
    payer_type VARCHAR(50) DEFAULT 'Maryland Medicaid',
    rate_type ENUM('hourly','unit','session','daily') DEFAULT 'hourly',
    rate_amount DECIMAL(10,2) NOT NULL,
    unit_minutes INT(11) DEFAULT 60,
    effective_date DATE NOT NULL,
    end_date DATE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_service (service_type_id),
    KEY idx_dates (effective_date, end_date),
    KEY idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create treatment plans table
CREATE TABLE IF NOT EXISTS autism_treatment_plans (
    id INT(11) NOT NULL AUTO_INCREMENT,
    client_id INT(11) NOT NULL,
    plan_date DATE NOT NULL,
    review_date DATE,
    status ENUM('draft','active','expired','archived') DEFAULT 'draft',
    goals TEXT,
    objectives TEXT,
    interventions TEXT,
    created_by INT(11),
    approved_by INT(11),
    approved_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_client (client_id),
    KEY idx_dates (plan_date, review_date),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create training modules table
CREATE TABLE IF NOT EXISTS autism_training_modules (
    id INT(11) NOT NULL AUTO_INCREMENT,
    category VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration_minutes INT(11) DEFAULT 30,
    is_mandatory TINYINT(1) DEFAULT 0,
    passing_score INT(11) DEFAULT 80,
    content_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_category (category),
    KEY idx_mandatory (is_mandatory)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create training progress table
CREATE TABLE IF NOT EXISTS autism_training_progress (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL,
    module_id INT(11) NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    score INT(11),
    attempts INT(11) DEFAULT 1,
    status ENUM('not_started','in_progress','completed','failed') DEFAULT 'not_started',
    PRIMARY KEY (id),
    UNIQUE KEY idx_user_module (user_id, module_id),
    KEY idx_status (status),
    KEY idx_completed (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update staff members with split names
UPDATE autism_staff_members 
SET first_name = SUBSTRING_INDEX(full_name, ' ', 1),
    last_name = SUBSTRING_INDEX(full_name, ' ', -1)
WHERE first_name IS NULL OR last_name IS NULL;

-- Insert some default training modules
INSERT IGNORE INTO autism_training_modules (category, title, description, duration_minutes, is_mandatory) VALUES
('orientation', 'New Employee Orientation', 'Introduction to ACI and company policies', 120, 1),
('compliance', 'HIPAA Privacy & Security', 'Understanding HIPAA requirements', 60, 1),
('compliance', 'Maryland Medicaid Billing', 'Billing requirements and procedures', 90, 1),
('clinical', 'Documentation Standards', 'Proper documentation procedures', 45, 1),
('clinical', 'Behavior Management Basics', 'Introduction to behavior interventions', 60, 0);

-- Insert billing rates for services
INSERT IGNORE INTO autism_billing_rates (service_type_id, rate_amount, effective_date) 
SELECT id, rate, '2024-01-01' FROM autism_service_types WHERE rate IS NOT NULL;

-- Add more sample sessions for testing
INSERT IGNORE INTO autism_sessions (client_id, staff_id, service_type_id, session_date, start_time, end_time, duration_hours, status, billing_status) VALUES
(1, 2, 1, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '09:00:00', '11:00:00', 2.0, 'completed', 'unbilled'),
(1, 2, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '14:00:00', '16:00:00', 2.0, 'completed', 'billed'),
(2, 3, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '10:00:00', '12:00:00', 2.0, 'completed', 'unbilled');

ADDITIONAL_TABLES

    log "Additional tables created successfully!"
else
    log "Autism tables already exist ($AUTISM_TABLES tables found)"
fi

log "Database initialization complete!"