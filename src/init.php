<?php
/**
 * Global Initialization File
 * Include this at the top of every PHP file to handle:
 * - Session management
 * - URL prettification
 * - Authentication checks
 * - Common includes
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include core files
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/openemr_integration.php'; 
require_once __DIR__ . '/UrlManager.php';

// Strip .php extension from URLs automatically
UrlManager::stripPhpExtension();

/**
 * Check authentication with optional minimum access level
 */
function requireAuth($minAccessLevel = 1) {
    if (!isset($_SESSION['user_id'])) {
        UrlManager::redirect('login');
    }
    
    if (isset($minAccessLevel) && $_SESSION['access_level'] < $minAccessLevel) {
        UrlManager::redirectWithError('dashboard', 'Insufficient permissions for this page.');
    }
}

/**
 * Get current user info safely
 */
function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null, // Added for consistency
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? '',
        'last_name' => $_SESSION['last_name'] ?? '',
        'full_name' => ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''),
        'email' => $_SESSION['email'] ?? '',
        'access_level' => $_SESSION['access_level'] ?? 0,
        'role_name' => $_SESSION['role_name'] ?? 'User',
        'staff_id' => $_SESSION['staff_id'] ?? null,
        'job_title' => $_SESSION['job_title'] ?? null
    ];
}

/**
 * Get safe redirect parameter from URL
 */
function getRedirectParam() {
    $redirect = $_GET['redirect'] ?? '';
    if (empty($redirect) || strpos($redirect, 'http') === 0) {
        return '/dashboard';
    }
    return $redirect;
}