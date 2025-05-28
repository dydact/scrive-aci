<?php

/**
 * Scrive Authentication Helper
 * 
 * @package   Scrive
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
 * @license   MIT License
 */

// Configure session settings before starting session
if (session_status() == PHP_SESSION_NONE) {
    // Set session save path to /tmp if not set
    if (empty(session_save_path())) {
        session_save_path('/tmp');
    }
    
    // Set session cookie parameters for better security
    session_set_cookie_params([
        'lifetime' => 28000, // 24+ hours  
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

// Set ignoreAuth to bypass site checks during authentication process
$ignoreAuth = true;

// Include OpenEMR database configuration (backend only)
require_once __DIR__ . '/../interface/globals.php';

/**
 * Check if user is authenticated with Scrive
 */
function requireScriveAuth() {
    // Make sure session is started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['scrive_user_id']) || empty($_SESSION['scrive_user_id'])) {
        // Not logged in - redirect to Scrive login
        header('Location: login.php');
        exit;
    }
    
    // Optional: Check session timeout (24 hours)
    if (isset($_SESSION['scrive_login_time']) && 
        (time() - $_SESSION['scrive_login_time']) > (24 * 60 * 60)) {
        
        // Session expired
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
}

/**
 * Get current Scrive user information
 */
function getCurrentScriveUser() {
    // Make sure session is started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['scrive_user_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['scrive_user_id'],
        'username' => $_SESSION['scrive_username'] ?? '',
        'fname' => $_SESSION['scrive_user_fname'] ?? '',
        'lname' => $_SESSION['scrive_user_lname'] ?? '',
        'email' => $_SESSION['scrive_user_email'] ?? '',
        'facility_id' => $_SESSION['scrive_facility_id'] ?? '',
        'facility_name' => $_SESSION['scrive_facility_name'] ?? '',
        'authorized' => $_SESSION['scrive_authorized'] ?? 0,
        'full_name' => ($_SESSION['scrive_user_fname'] ?? '') . ' ' . ($_SESSION['scrive_user_lname'] ?? ''),
        'login_time' => $_SESSION['scrive_login_time'] ?? 0
    ];
}

/**
 * Check if current user is admin/authorized
 */
function isScriveAdmin() {
    return isset($_SESSION['scrive_authorized']) && $_SESSION['scrive_authorized'];
}

/**
 * Logout current user
 */
function scriveLogout() {
    // Clear all session variables
    session_destroy();
    
    // Redirect to login with logout message
    header('Location: login.php?action=logout');
    exit;
}

/**
 * Initialize Scrive authentication check
 * Call this at the top of every protected page
 */
function initScriveAuth() {
    // Start session if not already started
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Require authentication
    requireScriveAuth();
    
    // Set compatibility variables for OpenEMR functions
    if (!isset($_SESSION['authUser'])) {
        $_SESSION['authUser'] = $_SESSION['scrive_username'] ?? '';
        $_SESSION['authUserID'] = $_SESSION['scrive_user_id'] ?? '';
        $_SESSION['authGroup'] = $_SESSION['scrive_facility_id'] ?? '';
        $_SESSION['site_id'] = 'default'; // Required by OpenEMR globals.php
    }
}

?> 