<?php
/**
 * Simple Authentication Helper for Autism Waiver Management System
 * Provides basic authentication functions for immediate testing
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'first_name' => $_SESSION['first_name'],
        'last_name' => $_SESSION['last_name'],
        'access_level' => $_SESSION['access_level'],
        'user_type' => $_SESSION['user_type']
    ];
}

/**
 * Check if user has minimum access level
 */
function hasAccessLevel($required_level) {
    if (!isLoggedIn()) {
        return false;
    }
    
    return $_SESSION['access_level'] >= $required_level;
}

/**
 * Require login - redirect to login if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /autism_waiver_app/simple_login.php');
        exit;
    }
}

/**
 * Require specific access level
 */
function requireAccessLevel($required_level) {
    requireLogin();
    
    if (!hasAccessLevel($required_level)) {
        http_response_code(403);
        echo "Access denied. Insufficient privileges.";
        exit;
    }
}

/**
 * Get user's full name
 */
function getUserFullName() {
    if (!isLoggedIn()) {
        return 'Guest';
    }
    
    return $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
}

/**
 * Get access level name
 */
function getAccessLevelName($level = null) {
    if ($level === null) {
        $level = $_SESSION['access_level'] ?? 0;
    }
    
    $levels = [
        1 => 'Read Only',
        2 => 'DSP (Direct Support)',
        3 => 'Case Manager',
        4 => 'Supervisor',
        5 => 'Administrator'
    ];
    
    return $levels[$level] ?? 'Unknown';
}

/**
 * Logout user
 */
function logout() {
    session_destroy();
    header('Location: /autism_waiver_app/simple_login.php');
    exit;
}

/**
 * Get test clients for immediate testing
 */
function getTestClients() {
    return [
        [
            'id' => 1,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'ma_number' => 'MA987654321',
            'date_of_birth' => '2010-05-15',
            'status' => 'active'
        ],
        [
            'id' => 2,
            'first_name' => 'Emily',
            'last_name' => 'Smith',
            'ma_number' => 'MA123456789',
            'date_of_birth' => '2012-08-22',
            'status' => 'active'
        ],
        [
            'id' => 3,
            'first_name' => 'Michael',
            'last_name' => 'Johnson',
            'ma_number' => 'MA555555555',
            'date_of_birth' => '2015-01-01',
            'status' => 'active'
        ]
    ];
}

/**
 * Get test service types
 */
function getTestServiceTypes() {
    return [
        [
            'id' => 1,
            'service_name' => 'IISS',
            'service_code' => 'IISS',
            'billing_code' => 'H2019',
            'billing_rate' => 25.00,
            'is_active' => 1
        ],
        [
            'id' => 2,
            'service_name' => 'Therapeutic Integration',
            'service_code' => 'TI',
            'billing_code' => 'H2019TI',
            'billing_rate' => 20.00,
            'is_active' => 1
        ],
        [
            'id' => 3,
            'service_name' => 'Respite Care',
            'service_code' => 'RESP',
            'billing_code' => 'S5151',
            'billing_rate' => 15.00,
            'is_active' => 1
        ]
    ];
}

/**
 * Simple database connection for testing
 * Returns PDO connection or simulates one
 */
function getTestDatabase() {
    // Try to connect to real database first
    $database = getenv('MARIADB_DATABASE') ?: 'iris';
    $username = getenv('MARIADB_USER') ?: 'iris_user';
    $password = getenv('MARIADB_PASSWORD') ?: '';
    $host = getenv('MARIADB_HOST') ?: 'localhost';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        // Return null if database not available - features will use test data
        return null;
    }
}
?>