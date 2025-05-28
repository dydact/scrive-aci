<?php
/**
 * COMPLETE TESTING ENVIRONMENT SETUP
 * American Caregivers Inc - Autism Waiver Management System
 * 
 * This script sets up the complete testing environment including:
 * - Database schema creation
 * - Sample data import
 * - Billing integration setup
 * - Security configuration
 * - Test user accounts
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🚀 Setting up American Caregivers Inc Testing Environment...\n\n";

// Database configuration
$db_config = [
    'host' => 'localhost',
    'dbname' => 'autism_waiver',
    'username' => 'root',
    'password' => ''
];

try {
    // Connect to MySQL
    $pdo = new PDO("mysql:host={$db_config['host']}", $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to MySQL server\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$db_config['dbname']}");
    $pdo->exec("USE {$db_config['dbname']}");
    
    echo "✅ Database '{$db_config['dbname']}' created/selected\n";
    
    // Execute main schema
    echo "📊 Creating main database schema...\n";
    $schema_sql = file_get_contents('production_setup.sql');
    if ($schema_sql) {
        $pdo->exec($schema_sql);
        echo "✅ Main schema created successfully\n";
    }
    
    // Execute billing tables
    echo "💰 Creating billing integration tables...\n";
    $billing_sql = file_get_contents('billing_tables.sql');
    if ($billing_sql) {
        $pdo->exec($billing_sql);
        echo "✅ Billing tables created successfully\n";
    }
    
    // Import sample data
    echo "👥 Importing sample employee and client data...\n";
    
    // Sample programs
    $pdo->exec("
        INSERT IGNORE INTO autism_programs (id, program_name, program_code, description) VALUES
        (1, 'Autism Waiver', 'AW', 'Maryland Autism Waiver Program'),
        (2, 'DDA Services', 'DDA', 'Developmental Disabilities Administration'),
        (3, 'Community First Choice', 'CFC', 'Community First Choice Program'),
        (4, 'Community Services', 'CS', 'General Community Services')
    ");
    
    // Sample service types
    $pdo->exec("
        INSERT IGNORE INTO autism_service_types (id, service_type, billing_code, hourly_rate, description) VALUES
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
        INSERT IGNORE INTO autism_staff_roles (id, role_name, access_level, description) VALUES
        (1, 'Administrator', 5, 'Full system access including organizational billing'),
        (2, 'Supervisor', 4, 'Staff oversight and billing reports (no org MA)'),
        (3, 'Case Manager', 3, 'Treatment planning and client coordination'),
        (4, 'Direct Care Staff', 2, 'Session notes and client interaction'),
        (5, 'Technician', 1, 'Basic session documentation only')
    ");
    
    // Sample staff members (key personnel)
    $pdo->exec("
        INSERT IGNORE INTO autism_staff_members (id, first_name, last_name, email, phone, job_title, hourly_rate, hire_date, is_active) VALUES
        (1, 'Mary', 'Emah', 'mary.emah@acgcares.com', '301-408-0100', 'CEO', 0.00, '2015-01-01', 1),
        (2, 'Amanda', 'Georgie', 'amanda.georgie@acgcares.com', '301-408-0101', 'Executive Staff', 35.00, '2015-06-01', 1),
        (3, 'Joyce', 'Aboagye', 'joyce.aboagye@acgcares.com', '301-408-0102', 'DSP', 18.50, '2020-03-15', 1),
        (4, 'Oluwadamilare', 'Abidakun', 'oluwadamilare.abidakun@acgcares.com', '301-408-0103', 'Autism Technician', 20.00, '2021-01-10', 1),
        (5, 'Sumayya', 'Abdul Khadar', 'sumayya.khadar@acgcares.com', '301-408-0104', 'DSP', 18.50, '2021-05-20', 1)
    ");
    
    // Sample clients (from Plan of Service report)
    $pdo->exec("
        INSERT IGNORE INTO autism_clients (id, first_name, last_name, date_of_birth, ma_number, address, city, state, zip_code, phone, emergency_contact_name, emergency_contact_phone, school_name, case_coordinator, md_county) VALUES
        (1, 'Jahan', 'Begum', '2010-03-15', '123456789', '123 Main St', 'Silver Spring', 'MD', '20904', '301-555-0101', 'Parent Guardian', '301-555-0102', 'Local Elementary', 'Case Manager 1', 'Montgomery'),
        (2, 'Jamil', 'Crosse', '2012-07-22', '987654321', '456 Oak Ave', 'Columbia', 'MD', '21044', '301-555-0201', 'Parent Guardian', '301-555-0202', 'Columbia Middle', 'Case Manager 2', 'Howard'),
        (3, 'Stefan', 'Fernandes', '2008-11-08', '456789123', '789 Pine Rd', 'Baltimore', 'MD', '21201', '410-555-0301', 'Parent Guardian', '410-555-0302', 'Baltimore High', 'Case Manager 3', 'Baltimore City'),
        (4, 'Tsadkan', 'Gebremedhin', '2014-02-14', '789123456', '321 Elm St', 'Rockville', 'MD', '20850', '301-555-0401', 'Parent Guardian', '301-555-0402', 'Rockville Elementary', 'Case Manager 4', 'Montgomery'),
        (5, 'Almaz', 'Gebreyohanes', '2011-09-30', '321654987', '654 Maple Dr', 'Annapolis', 'MD', '21401', '410-555-0501', 'Parent Guardian', '410-555-0502', 'Annapolis Middle', 'Case Manager 5', 'Anne Arundel')
    ");
    
    // User accounts for testing
    $pdo->exec("
        INSERT IGNORE INTO autism_users (id, username, password_hash, staff_id, is_active, created_at) VALUES
        (1, 'admin', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 1, 1, NOW()),
        (2, 'supervisor', '" . password_hash('super123', PASSWORD_DEFAULT) . "', 2, 1, NOW()),
        (3, 'casemanager', '" . password_hash('case123', PASSWORD_DEFAULT) . "', 3, 1, NOW()),
        (4, 'staff', '" . password_hash('staff123', PASSWORD_DEFAULT) . "', 4, 1, NOW()),
        (5, 'tech', '" . password_hash('tech123', PASSWORD_DEFAULT) . "', 5, 1, NOW())
    ");
    
    // User role assignments
    $pdo->exec("
        INSERT IGNORE INTO autism_user_roles (user_id, role_id, assigned_date) VALUES
        (1, 1, NOW()), -- Admin
        (2, 2, NOW()), -- Supervisor
        (3, 3, NOW()), -- Case Manager
        (4, 4, NOW()), -- Direct Care Staff
        (5, 5, NOW())  -- Technician
    ");
    
    // Client enrollments
    $pdo->exec("
        INSERT IGNORE INTO autism_client_enrollments (client_id, program_id, enrollment_date, fiscal_year, status) VALUES
        (1, 1, '2025-01-01', '2025', 'active'), -- Jahan Begum - AW
        (2, 1, '2025-01-01', '2025', 'active'), -- Jamil Crosse - AW
        (3, 1, '2025-01-01', '2025', 'active'), -- Stefan Fernandes - AW
        (4, 2, '2025-01-01', '2025', 'active'), -- Tsadkan Gebremedhin - DDA
        (5, 3, '2025-01-01', '2025', 'active')  -- Almaz Gebreyohanes - CFC
    ");
    
    // Client services
    $pdo->exec("
        INSERT IGNORE INTO autism_client_services (client_id, service_type_id, weekly_units, start_date, end_date, status) VALUES
        (1, 1, 20, '2025-01-01', '2025-12-31', 'active'), -- Jahan - IISS 20hrs/week
        (2, 2, 15, '2025-01-01', '2025-12-31', 'active'), -- Jamil - TI 15hrs/week
        (3, 1, 20, '2025-01-01', '2025-12-31', 'active'), -- Stefan - IISS 20hrs/week
        (4, 3, 8, '2025-01-01', '2025-12-31', 'active'),  -- Tsadkan - Respite 8hrs/week
        (5, 4, 2, '2025-01-01', '2025-12-31', 'active')   -- Almaz - FC 2hrs/week
    ");
    
    // Staff assignments
    $pdo->exec("
        INSERT IGNORE INTO autism_staff_assignments (client_id, staff_id, role_type, start_date, is_active) VALUES
        (1, 3, 'primary', '2025-01-01', 1),    -- Joyce -> Jahan
        (2, 4, 'primary', '2025-01-01', 1),    -- Oluwadamilare -> Jamil
        (3, 5, 'primary', '2025-01-01', 1),    -- Sumayya -> Stefan
        (4, 3, 'primary', '2025-01-01', 1),    -- Joyce -> Tsadkan
        (5, 4, 'primary', '2025-01-01', 1),    -- Oluwadamilare -> Almaz
        (1, 2, 'supervisor', '2025-01-01', 1), -- Amanda supervises all
        (2, 2, 'supervisor', '2025-01-01', 1),
        (3, 2, 'supervisor', '2025-01-01', 1),
        (4, 2, 'supervisor', '2025-01-01', 1),
        (5, 2, 'supervisor', '2025-01-01', 1)
    ");
    
    // Sample treatment plans
    $pdo->exec("
        INSERT IGNORE INTO autism_treatment_plans (client_id, plan_name, start_date, end_date, created_by, status) VALUES
        (1, 'Jahan Begum Treatment Plan 2025', '2025-01-01', '2025-12-31', 3, 'active'),
        (2, 'Jamil Crosse Treatment Plan 2025', '2025-01-01', '2025-12-31', 3, 'active'),
        (3, 'Stefan Fernandes Treatment Plan 2025', '2025-01-01', '2025-12-31', 3, 'active'),
        (4, 'Tsadkan Gebremedhin Treatment Plan 2025', '2025-01-01', '2025-12-31', 3, 'active'),
        (5, 'Almaz Gebreyohanes Treatment Plan 2025', '2025-01-01', '2025-12-31', 3, 'active')
    ");
    
    // Sample treatment goals
    $pdo->exec("
        INSERT IGNORE INTO autism_treatment_goals (treatment_plan_id, goal_description, target_date, priority, status) VALUES
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
        INSERT IGNORE INTO autism_session_notes (client_id, staff_id, session_date, duration_minutes, service_type_id, session_notes, treatment_goals_addressed, progress_rating) VALUES
        (1, 3, '$today', 60, 1, 'Worked on communication skills using visual aids and verbal prompts. Client showed good engagement and made progress with requesting preferred items.', 'Improve communication skills through verbal and non-verbal methods', 4),
        (2, 4, '$today', 90, 2, 'Therapeutic integration session focused on social skills development. Client participated in group activities and showed improved peer interaction.', 'Enhance social interaction skills with peers', 3),
        (3, 5, '$yesterday', 120, 1, 'Individual support session covering job readiness skills. Practiced interview techniques and workplace social skills.', 'Develop job readiness skills for future employment', 4),
        (4, 3, '$yesterday', 60, 3, 'Respite care session providing family relief. Client engaged in recreational activities and maintained appropriate behavior.', 'Increase participation in family and community activities', 3),
        (5, 4, '$today', 30, 4, 'Family consultation session discussing self-advocacy strategies. Provided parents with tools and techniques for supporting independence.', 'Develop self-advocacy skills and independence', 4)
    ");
    
    // Create config file
    echo "⚙️ Creating configuration file...\n";
    $config_content = "<?php
// Database configuration
define('DB_HOST', '{$db_config['host']}');
define('DB_NAME', '{$db_config['dbname']}');
define('DB_USER', '{$db_config['username']}');
define('DB_PASS', '{$db_config['password']}');

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
?>";
    
    file_put_contents('config.php', $config_content);
    echo "✅ Configuration file created\n";
    
    // Create logs directory
    if (!is_dir('logs')) {
        mkdir('logs', 0755, true);
        echo "✅ Logs directory created\n";
    }
    
    // Create uploads directory
    if (!is_dir('uploads')) {
        mkdir('uploads', 0755, true);
        echo "✅ Uploads directory created\n";
    }
    
    // Create .htaccess for security
    $htaccess_content = "# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection \"1; mode=block\"
    Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains\"
</IfModule>

# Prevent access to sensitive files
<Files ~ \"\\.(sql|log|md|txt|csv)$\">
    Order allow,deny
    Deny from all
</Files>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Cache static files
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css \"access plus 1 month\"
    ExpiresByType application/javascript \"access plus 1 month\"
    ExpiresByType image/png \"access plus 1 month\"
    ExpiresByType image/jpg \"access plus 1 month\"
    ExpiresByType image/jpeg \"access plus 1 month\"
</IfModule>";
    
    file_put_contents('.htaccess', $htaccess_content);
    echo "✅ Security .htaccess file created\n";
    
    echo "\n🎉 Testing environment setup complete!\n\n";
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
    echo "3. Test different portals:\n";
    echo "   - Main Dashboard: http://localhost:8080/index.php\n";
    echo "   - Mobile Portal: http://localhost:8080/mobile_employee_portal.php\n";
    echo "   - Case Manager: http://localhost:8080/case_manager_portal.php\n";
    echo "   - Billing System: http://localhost:8080/billing_integration.php\n";
    echo "   - Website: http://localhost:8080/aci_homepage.html\n";
    echo "   - Application Form: http://localhost:8080/application_form.php\n\n";
    
    echo "📊 SAMPLE DATA INCLUDED:\n";
    echo "========================\n";
    echo "- 5 Staff members (Mary Emah, Amanda Georgie, Joyce Aboagye, etc.)\n";
    echo "- 5 Clients (Jahan Begum, Jamil Crosse, Stefan Fernandes, etc.)\n";
    echo "- Treatment plans with goals\n";
    echo "- Sample session notes\n";
    echo "- Billing integration setup\n";
    echo "- Security roles and permissions\n\n";
    
    echo "💰 MEDICAID BILLING FEATURES:\n";
    echo "=============================\n";
    echo "- Real-time eligibility verification\n";
    echo "- Claim generation and submission\n";
    echo "- Encounter data export\n";
    echo "- Prior authorization tracking\n";
    echo "- Payment and denial management\n";
    echo "- Complete audit trails\n\n";
    
    echo "🔐 SECURITY FEATURES:\n";
    echo "====================\n";
    echo "- 5-tier role-based access control\n";
    echo "- MA number access restrictions\n";
    echo "- Complete audit logging\n";
    echo "- Session management\n";
    echo "- HIPAA compliance\n\n";
    
    echo "✅ System is ready for comprehensive testing!\n";
    echo "📞 For support: contact@acgcares.com\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration and try again.\n";
}
?> 