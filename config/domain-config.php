<?php
/**
 * Domain Configuration for American Caregivers Inc
 * Configures the application to work with aci.dydact.io domain
 */

// Domain configuration
define('PRIMARY_DOMAIN', 'aci.dydact.io');
define('WWW_DOMAIN', 'www.aci.dydact.io');
define('STAFF_SUBDOMAIN', 'staff.aci.dydact.io');
define('ADMIN_SUBDOMAIN', 'admin.aci.dydact.io');
define('API_SUBDOMAIN', 'api.aci.dydact.io');

// SSL/HTTPS configuration
define('FORCE_SSL', false); // Disabled until SSL is set up
define('SSL_PORT', 443);

// Environment detection
$current_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_production = in_array($current_host, [
    PRIMARY_DOMAIN,
    WWW_DOMAIN,
    STAFF_SUBDOMAIN,
    ADMIN_SUBDOMAIN,
    API_SUBDOMAIN
]);

define('IS_PRODUCTION', $is_production);

// Base URLs - all use the main domain with clean paths
if ($is_production) {
    define('BASE_URL', 'http://' . PRIMARY_DOMAIN); // Use http until SSL is set up
    define('STAFF_URL', 'http://' . PRIMARY_DOMAIN . '/login');
    define('ADMIN_URL', 'http://' . PRIMARY_DOMAIN . '/admin');
    define('API_URL', 'http://' . PRIMARY_DOMAIN . '/api');
} else {
    define('BASE_URL', 'http://localhost:8080');
    define('STAFF_URL', 'http://localhost:8080/login');
    define('ADMIN_URL', 'http://localhost:8080/admin');
    define('API_URL', 'http://localhost:8080/api');
}

// Redirect functions
function redirect_to_ssl() {
    if (IS_PRODUCTION && !isset($_SERVER['HTTPS'])) {
        $redirect_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header("Location: $redirect_url", true, 301);
        exit();
    }
}

function redirect_www_to_non_www() {
    if (IS_PRODUCTION && $_SERVER['HTTP_HOST'] === WWW_DOMAIN) {
        $redirect_url = 'https://' . PRIMARY_DOMAIN . $_SERVER['REQUEST_URI'];
        header("Location: $redirect_url", true, 301);
        exit();
    }
}

// Route handling based on subdomain
function get_current_subdomain() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    
    switch ($host) {
        case STAFF_SUBDOMAIN:
            return 'staff';
        case ADMIN_SUBDOMAIN:
            return 'admin';
        case API_SUBDOMAIN:
            return 'api';
        default:
            return 'public';
    }
}

// Security headers for production
function set_security_headers() {
    if (IS_PRODUCTION) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Initialize domain configuration
if (IS_PRODUCTION) {
    redirect_to_ssl();
    redirect_www_to_non_www();
    set_security_headers();
}

// Current subdomain context
define('CURRENT_SUBDOMAIN', get_current_subdomain());
?>