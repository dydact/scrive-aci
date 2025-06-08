#!/bin/sh
# Fixed database initialization for Scrive ACI - no hardcoded passwords
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
    
    # Create autism tables WITHOUT hardcoded password hashes
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" <<EOF
-- Create autism_clients table
CREATE TABLE IF NOT EXISTS autism_clients (
    id INT(11) NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    ma_number VARCHAR(20),
    waiver_type_id INT(11),
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

-- Create autism_users table WITHOUT any default users
CREATE TABLE IF NOT EXISTS autism_users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    full_name VARCHAR(255),
    role VARCHAR(50) DEFAULT 'User',
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
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(255),
    phone VARCHAR(20),
    role VARCHAR(50),
    hourly_rate DECIMAL(6,2) DEFAULT 20.00,
    hire_date DATE,
    status ENUM('active','inactive','on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_id (user_id),
    KEY idx_employee_id (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NO DEFAULT USERS - they will be created by the application

-- Insert sample clients
INSERT IGNORE INTO autism_clients (first_name, last_name, ma_number) VALUES 
('John', 'Doe', 'MD123456789'),
('Jane', 'Smith', 'MD987654321');

-- Insert sample staff members (without user accounts)
INSERT IGNORE INTO autism_staff_members (employee_id, full_name, first_name, last_name, email, phone, role, hire_date, status) VALUES 
('EMP002', 'Jane Manager', 'Jane', 'Manager', 'jane.manager@americancaregivers.com', '(240) 555-0002', 'Case Manager', '2021-03-15', 'active'),
('EMP003', 'John Support', 'John', 'Support', 'john.support@americancaregivers.com', '(240) 555-0003', 'DSP', '2022-06-01', 'active');

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

-- Create additional tables for sessions
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

EOF
    
    log "Autism tables created successfully!"
else
    log "Autism tables already exist ($AUTISM_TABLES tables found)"
fi

log "Database initialization complete - NO hardcoded users created!"
log "Users should be created through the application setup process"