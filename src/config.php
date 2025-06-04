<?php
// MariaDB Database configuration for OpenEMR integration

// Database Configuration - Use environment variables in Docker or fallback to defaults
define('DB_TYPE', 'mysql');
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'openemr');
define('DB_USER', getenv('DB_USER') ?: 'openemr');
define('DB_PASS', getenv('DB_PASS') ?: 'openemr');
define('DB_CHARSET', 'utf8mb4');

// OpenEMR Integration Paths
define('OPENEMR_BASE_PATH', getenv('OPENEMR_BASE_PATH') ?: '/var/www/localhost/htdocs/openemr');
define('OPENEMR_SITE', getenv('SITE') ?: 'americancaregivers');

// Application settings
define('APP_NAME', 'American Caregivers Inc - Autism Waiver Management');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'production');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('PASSWORD_MIN_LENGTH', 8);

// Medicaid billing settings - American Caregivers Inc
define('MEDICAID_PROVIDER_ID', '1013104314');
define('ORGANIZATION_NPI', '1013104314');
define('ORGANIZATION_TAX_ID', '52-2305229');
define('TAXONOMY_CODE', '251C00000X'); // Home Infusion Therapy Services

// Email settings - Using webhost mail server (to be configured)
define('SMTP_HOST', 'localhost'); // Will be updated with webhost SMTP
define('SMTP_PORT', 587);
define('SMTP_USERNAME', ''); // To be configured
define('SMTP_PASSWORD', ''); // To be configured
define('FROM_EMAIL', 'noreply@acgcares.com');
define('CONTACT_EMAIL', 'contact@acgcares.com');
define('MAIL_ENABLED', false); // Set to true when mail server is configured

// File paths
define('UPLOADS_DIR', __DIR__ . '/../uploads');
define('LOGS_DIR', __DIR__ . '/../logs');

// Error reporting based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', LOGS_DIR . '/error.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOGS_DIR . '/error.log');
}

/**
 * Get database connection using MariaDB/MySQL
 */
function getDatabase() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=utf8mb4',
                DB_HOST,
                DB_NAME
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,  // Enable buffered queries
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }
    
    return $pdo;
}

/**
 * Get OpenEMR database connection (same as main for this setup)
 */
function getOpenEMRDatabase() {
    return getDatabase();
}
?> 