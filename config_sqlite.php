<?php
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
        $pdo = new PDO('sqlite:' . DB_FILE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        die('Database connection failed: ' . $e->getMessage());
    }
}
?>