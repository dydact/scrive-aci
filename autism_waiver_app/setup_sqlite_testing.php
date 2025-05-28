<?php
/**
 * SQLITE TESTING ENVIRONMENT SETUP
 * American Caregivers Inc - Autism Waiver Management System
 * 
 * This script sets up a complete testing environment using SQLite
 * No MySQL installation required - perfect for quick testing
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🚀 Setting up American Caregivers Inc Testing Environment (SQLite)...\n\n";

try {
    // Create SQLite database
    $db_file = 'autism_waiver_test.db';
    $pdo = new PDO("sqlite:$db_file");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ SQLite database created: $db_file\n";
    
    // Create tables (SQLite compatible)
    echo "📊 Creating database schema...\n";
    
    // Programs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_programs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            program_name VARCHAR(100) NOT NULL,
            program_code VARCHAR(10) NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Service types table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_service_types (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            service_type VARCHAR(100) NOT NULL,
            billing_code VARCHAR(20) NOT NULL,
            hourly_rate DECIMAL(8,2) NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Staff roles table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_staff_roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            role_name VARCHAR(50) NOT NULL,
            access_level INTEGER NOT NULL,
            description TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Staff members table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_staff_members (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) UNIQUE,
            phone VARCHAR(20),
            job_title VARCHAR(100),
            hourly_rate DECIMAL(8,2),
            hire_date DATE,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Clients table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            date_of_birth DATE NOT NULL,
            ma_number VARCHAR(20),
            address VARCHAR(255),
            city VARCHAR(100),
            state VARCHAR(2),
            zip_code VARCHAR(10),
            phone VARCHAR(20),
            emergency_contact_name VARCHAR(100),
            emergency_contact_phone VARCHAR(20),
            school_name VARCHAR(100),
            case_coordinator VARCHAR(100),
            md_county VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            staff_id INTEGER,
            is_active BOOLEAN DEFAULT 1,
            last_login DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (staff_id) REFERENCES autism_staff_members(id)
        )
    ");
    
    // User roles table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_user_roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            role_id INTEGER NOT NULL,
            assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES autism_users(id),
            FOREIGN KEY (role_id) REFERENCES autism_staff_roles(id)
        )
    ");
    
    // Client enrollments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_client_enrollments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            program_id INTEGER NOT NULL,
            enrollment_date DATE NOT NULL,
            fiscal_year VARCHAR(4),
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES autism_clients(id),
            FOREIGN KEY (program_id) REFERENCES autism_programs(id)
        )
    ");
    
    // Client services table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_client_services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            service_type_id INTEGER NOT NULL,
            weekly_units INTEGER NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES autism_clients(id),
            FOREIGN KEY (service_type_id) REFERENCES autism_service_types(id)
        )
    ");
    
    // Staff assignments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_staff_assignments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            staff_id INTEGER NOT NULL,
            role_type VARCHAR(20) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE,
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES autism_clients(id),
            FOREIGN KEY (staff_id) REFERENCES autism_staff_members(id)
        )
    ");
    
    // Treatment plans table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_treatment_plans (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            plan_name VARCHAR(200) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE,
            created_by INTEGER,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES autism_clients(id),
            FOREIGN KEY (created_by) REFERENCES autism_staff_members(id)
        )
    ");
    
    // Treatment goals table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_treatment_goals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            treatment_plan_id INTEGER NOT NULL,
            goal_description TEXT NOT NULL,
            target_date DATE,
            priority VARCHAR(10) DEFAULT 'medium',
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (treatment_plan_id) REFERENCES autism_treatment_plans(id)
        )
    ");
    
    // Session notes table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_session_notes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id INTEGER NOT NULL,
            staff_id INTEGER NOT NULL,
            session_date DATE NOT NULL,
            duration_minutes INTEGER NOT NULL,
            service_type_id INTEGER NOT NULL,
            session_notes TEXT NOT NULL,
            treatment_goals_addressed TEXT,
            progress_rating INTEGER CHECK (progress_rating BETWEEN 1 AND 5),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (client_id) REFERENCES autism_clients(id),
            FOREIGN KEY (staff_id) REFERENCES autism_staff_members(id),
            FOREIGN KEY (service_type_id) REFERENCES autism_service_types(id)
        )
    ");
    
    // Billing claims table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS autism_billing_claims (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id INTEGER,
            claim_id VARCHAR(50) UNIQUE NOT NULL,
            ma_number VARCHAR(20) NOT NULL,
            billing_code VARCHAR(20) NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            claim_data TEXT,
            status VARCHAR(20) DEFAULT 'generated',
            response_data TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES autism_session_notes(id)
        )
    ");
    
    echo "✅ Database schema created successfully\n";
    
    // Insert sample data
    echo "👥 Importing sample data...\n";
    
    // Sample programs
    $pdo->exec("
        INSERT OR IGNORE INTO autism_programs (id, program_name, program_code, description) VALUES
        (1, 'Autism Waiver', 'AW', 'Maryland Autism Waiver Program'),
        (2, 'DDA Services', 'DDA', 'Developmental Disabilities Administration'),
        (3, 'Community First Choice', 'CFC', 'Community First Choice Program'),
        (4, 'Community Services', 'CS', 'General Community Services')
    ");
    
    // Sample service types
    $pdo->exec("
        INSERT OR IGNORE INTO autism_service_types (id, service_type, billing_code, hourly_rate, description) VALUES
        (1, 'Individual Intensive Support Services (IISS)', 'W9323', 25.00, 'One-on-one support services'),
        (2, 'Therapeutic Integration (TI)', 'W9323', 25.00, 'Therapeutic integration services'),
        (3, 'Respite Care', 'W9323', 20.00, 'Short-term relief care'),
        (4, 'Family Consultation (FC)', 'W9323', 30.00, 'Family consultation and training'),
        (5, 'Personal Care Assistant (PCA)', 'T2022', 18.50, 'Personal care assistance'),
        (6, 'Companion Services', 'T2022', 18.50, 'Companion and support services'),
        (7, 'Life Skills Training', '96158', 25.00, 'Independent living skills'),
        (8, 'Behavioral Support', '96159', 25.00, 'Behavioral intervention services')
    ");
    
    // Sample staff roles
    $pdo->exec("
        INSERT OR IGNORE INTO autism_staff_roles (id, role_name, access_level, description) VALUES
        (1, 'Administrator', 5, 'Full system access including organizational billing'),
        (2, 'Supervisor', 4, 'Staff oversight and billing reports (no org MA)'),
        (3, 'Case Manager', 3, 'Treatment planning and client coordination'),
        (4, 'Direct Care Staff', 2, 'Session notes and client interaction'),
        (5, 'Technician', 1, 'Basic session documentation only')
    ");
    
    // Sample staff members
    $pdo->exec("
        INSERT OR IGNORE INTO autism_staff_members (id, first_name, last_name, email, phone, job_title, hourly_rate, hire_date, is_active) VALUES
        (1, 'Mary', 'Emah', 'mary.emah@acgcares.com', '301-408-0100', 'CEO', 0.00, '2015-01-01', 1),
        (2, 'Amanda', 'Georgie', 'amanda.georgie@acgcares.com', '301-408-0101', 'Executive Staff', 35.00, '2015-06-01', 1),
        (3, 'Joyce', 'Aboagye', 'joyce.aboagye@acgcares.com', '301-408-0102', 'DSP', 18.50, '2020-03-15', 1),
        (4, 'Oluwadamilare', 'Abidakun', 'oluwadamilare.abidakun@acgcares.com', '301-408-0103', 'Autism Technician', 20.00, '2021-01-10', 1),
        (5, 'Sumayya', 'Abdul Khadar', 'sumayya.khadar@acgcares.com', '301-408-0104', 'DSP', 18.50, '2021-05-20', 1)
    ");
    
    // Sample clients
    $pdo->exec("
        INSERT OR IGNORE INTO autism_clients (id, first_name, last_name, date_of_birth, ma_number, address, city, state, zip_code, phone, emergency_contact_name, emergency_contact_phone, school_name, case_coordinator, md_county) VALUES
        (1, 'Jahan', 'Begum', '2010-03-15', '123456789', '123 Main St', 'Silver Spring', 'MD', '20904', '301-555-0101', 'Parent Guardian', '301-555-0102', 'Local Elementary', 'Case Manager 1', 'Montgomery'),
        (2, 'Jamil', 'Crosse', '2012-07-22', '987654321', '456 Oak Ave', 'Columbia', 'MD', '21044', '301-555-0201', 'Parent Guardian', '301-555-0202', 'Columbia Middle', 'Case Manager 2', 'Howard'),
        (3, 'Stefan', 'Fernandes', '2008-11-08', '456789123', '789 Pine Rd', 'Baltimore', 'MD', '21201', '410-555-0301', 'Parent Guardian', '410-555-0302', 'Baltimore High', 'Case Manager 3', 'Baltimore City'),
        (4, 'Tsadkan', 'Gebremedhin', '2014-02-14', '789123456', '321 Elm St', 'Rockville', 'MD', '20850', '301-555-0401', 'Parent Guardian', '301-555-0402', 'Rockville Elementary', 'Case Manager 4', 'Montgomery'),
        (5, 'Almaz', 'Gebreyohanes', '2011-09-30', '321654987', '654 Maple Dr', 'Annapolis', 'MD', '21401', '410-555-0501', 'Parent Guardian', '410-555-0502', 'Annapolis Middle', 'Case Manager 5', 'Anne Arundel')
    ");
    
    // User accounts for testing
    $pdo->exec("
        INSERT OR IGNORE INTO autism_users (id, username, password_hash, staff_id, is_active) VALUES
        (1, 'admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 1, 1),
        (2, 'supervisor', '" . password_hash('super123', PASSWORD_DEFAULT) . "', 2, 1),
        (3, 'casemanager', '" . password_hash('case123', PASSWORD_DEFAULT) . "', 3, 1),
        (4, 'staff', '" . password_hash('staff123', PASSWORD_DEFAULT) . "', 4, 1),
        (5, 'tech', '" . password_hash('tech123', PASSWORD_DEFAULT) . "', 5, 1)
    ");
    
    // User role assignments
    $pdo->exec("
        INSERT OR IGNORE INTO autism_user_roles (user_id, role_id) VALUES
        (1, 1), -- Admin
        (2, 2), -- Supervisor
        (3, 3), -- Case Manager
        (4, 4), -- Direct Care Staff
        (5, 5)  -- Technician
    ");
    
    // Client enrollments
    $pdo->exec("
        INSERT OR IGNORE INTO autism_client_enrollments (client_id, program_id, enrollment_date, fiscal_year, status) VALUES
        (1, 1, '2025-01-01', '2025', 'active'),
        (2, 1, '2025-01-01', '2025', 'active'),
        (3, 1, '2025-01-01', '2025', 'active'),
        (4, 2, '2025-01-01', '2025', 'active'),
        (5, 3, '2025-01-01', '2025', 'active')
    ");
    
    // Client services
    $pdo->exec("
        INSERT OR IGNORE INTO autism_client_services (client_id, service_type_id, weekly_units, start_date, end_date, status) VALUES
        (1, 1, 20, '2025-01-01', '2025-12-31', 'active'),
        (2, 2, 15, '2025-01-01', '2025-12-31', 'active'),
        (3, 1, 20, '2025-01-01', '2025-12-31', 'active'),
        (4, 3, 8, '2025-01-01', '2025-12-31', 'active'),
        (5, 4, 2, '2025-01-01', '2025-12-31', 'active')
    ");
    
    // Staff assignments
    $pdo->exec("
        INSERT OR IGNORE INTO autism_staff_assignments (client_id, staff_id, role_type, start_date, is_active) VALUES
        (1, 3, 'primary', '2025-01-01', 1),
        (2, 4, 'primary', '2025-01-01', 1),
        (3, 5, 'primary', '2025-01-01', 1),
        (4, 3, 'primary', '2025-01-01', 1),
        (5, 4, 'primary', '2025-01-01', 1),
        (1, 2, 'supervisor', '2025-01-01', 1),
        (2, 2, 'supervisor', '2025-01-01', 1),
        (3, 2, 'supervisor', '2025-01-01', 1),
        (4, 2, 'supervisor', '2025-01-01', 1),
        (5, 2, 'supervisor', '2025-01-01', 1)
    ");
    
    // Treatment plans
    $pdo->exec("
        INSERT OR IGNORE INTO autism_treatment_plans (client_id, plan_name, start_date, end_date, created_by, status) VALUES
        (1, 'Jahan Begum Treatment Plan 2025', '2025-01-01', '2025-12-31', 3, 'active'),
        (2, 'Jamil Crosse Treatment Plan 2025', '2025-01-01', '2025-12-31', 3, 'active'),
        (3, 'Stefan Fernandes Treatment Plan 2025', '2025-01-01', '2025-12-31', 3, 'active'),
        (4, 'Tsadkan Gebremedhin Treatment Plan 2025', '2025-01-01', '2025-12-31', 3, 'active'),
        (5, 'Almaz Gebreyohanes Treatment Plan 2025', '2025-01-01', '2025-12-31', 3, 'active')
    ");
    
    // Treatment goals
    $pdo->exec("
        INSERT OR IGNORE INTO autism_treatment_goals (treatment_plan_id, goal_description, target_date, priority, status) VALUES
        (1, 'Improve communication skills through verbal and non-verbal methods', '2025-06-30', 'high', 'active'),
        (1, 'Develop independent living skills including personal hygiene', '2025-09-30', 'medium', 'active'),
        (1, 'Reduce challenging behaviors through positive reinforcement', '2025-12-31', 'high', 'active'),
        (2, 'Enhance social interaction skills with peers', '2025-06-30', 'high', 'active'),
        (2, 'Improve academic performance in reading and math', '2025-09-30', 'medium', 'active'),
        (3, 'Develop job readiness skills for future employment', '2025-12-31', 'high', 'active'),
        (3, 'Improve emotional regulation and coping strategies', '2025-06-30', 'medium', 'active'),
        (4, 'Increase participation in family and community activities', '2025-09-30', 'medium', 'active'),
        (5, 'Develop self-advocacy skills and independence', '2025-12-31', 'high', 'active')
    ");
    
    // Sample session notes
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $pdo->exec("
        INSERT OR IGNORE INTO autism_session_notes (client_id, staff_id, session_date, duration_minutes, service_type_id, session_notes, treatment_goals_addressed, progress_rating) VALUES
        (1, 3, '$today', 60, 1, 'Worked on communication skills using visual aids and verbal prompts. Client showed good engagement and made progress with requesting preferred items.', 'Improve communication skills through verbal and non-verbal methods', 4),
        (2, 4, '$today', 90, 2, 'Therapeutic integration session focused on social skills development. Client participated in group activities and showed improved peer interaction.', 'Enhance social interaction skills with peers', 3),
        (3, 5, '$yesterday', 120, 1, 'Individual support session covering job readiness skills. Practiced interview techniques and workplace social skills.', 'Develop job readiness skills for future employment', 4),
        (4, 3, '$yesterday', 60, 3, 'Respite care session providing family relief. Client engaged in recreational activities and maintained appropriate behavior.', 'Increase participation in family and community activities', 3),
        (5, 4, '$today', 30, 4, 'Family consultation session discussing self-advocacy strategies. Provided parents with tools and techniques for supporting independence.', 'Develop self-advocacy skills and independence', 4)
    ");
    
    echo "✅ Sample data imported successfully\n";
    
    // Create SQLite config file
    echo "⚙️ Creating SQLite configuration file...\n";
    $config_content = "<?php
// SQLite Database configuration
define('DB_TYPE', 'sqlite');
define('DB_FILE', 'autism_waiver_test.db');

// Application settings
define('APP_NAME', 'American Caregivers Inc - Autism Waiver Management');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'testing');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('PASSWORD_MIN_LENGTH', 8);

// Medicaid billing settings
define('MEDICAID_PROVIDER_ID', '1234567890');
define('ORGANIZATION_NPI', '1234567890');
define('TAXONOMY_CODE', '261QM0850X');

// Email settings
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@acgcares.com');
define('CONTACT_EMAIL', 'contact@acgcares.com');

// File upload settings
define('MAX_FILE_SIZE', 10485760); // 10MB
define('UPLOAD_PATH', 'uploads/');

// Logging
define('LOG_LEVEL', 'DEBUG');
define('LOG_FILE', 'logs/application.log');

// Database connection function
function getDatabase() {
    try {
        \$pdo = new PDO('sqlite:' . DB_FILE);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return \$pdo;
    } catch (PDOException \$e) {
        die('Database connection failed: ' . \$e->getMessage());
    }
}
?>";
    
    file_put_contents('config_sqlite.php', $config_content);
    echo "✅ SQLite configuration file created\n";
    
    // Create directories
    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
        echo "✅ Logs directory created\n";
    }
    
    if (!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
        echo "✅ Uploads directory created\n";
    }
    
    echo "\n🎉 SQLite Testing Environment Setup Complete!\n\n";
    echo "📋 TESTING CREDENTIALS:\n";
    echo "======================\n";
    echo "Administrator: admin / admin123\n";
    echo "Supervisor: supervisor / super123\n";
    echo "Case Manager: casemanager / case123\n";
    echo "Direct Care Staff: staff / staff123\n";
    echo "Technician: tech / tech123\n\n";
    
    echo "🚀 TO START TESTING:\n";
    echo "===================\n";
    echo "1. Start PHP development server:\n";
    echo "   php -S localhost:8080\n\n";
    echo "2. Open browser to:\n";
    echo "   http://localhost:8080\n\n";
    echo "3. Test different features:\n";
    echo "   - Login with any of the credentials above\n";
    echo "   - View client dashboard\n";
    echo "   - Add session notes\n";
    echo "   - Test billing integration\n";
    echo "   - Try mobile portal\n\n";
    
    echo "📊 DATABASE INFO:\n";
    echo "=================\n";
    echo "Database File: autism_waiver_test.db\n";
    echo "Database Type: SQLite (no MySQL required)\n";
    echo "Sample Data: 5 staff, 5 clients, treatment plans, session notes\n\n";
    
    echo "✅ Ready for testing! No additional setup required.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 