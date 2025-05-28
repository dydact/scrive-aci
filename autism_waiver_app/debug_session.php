<?php

/**
 * Debug Session Script - Test Scrive Authentication
 */

// Start session first
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Session Debug Information</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";
echo "<p>Session Name: " . session_name() . "</p>";
echo "<p>Session Save Path: " . session_save_path() . "</p>";

echo "<h3>Session Variables:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Cookie Information:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h3>Server Information:</h3>";
echo "<p>Server Name: " . $_SERVER['SERVER_NAME'] . "</p>";
echo "<p>HTTP Host: " . $_SERVER['HTTP_HOST'] . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";

// Test setting a simple session variable
if (!isset($_SESSION['test_var'])) {
    $_SESSION['test_var'] = 'Session test successful - ' . date('Y-m-d H:i:s');
    echo "<p style='color: green;'>Test session variable set!</p>";
} else {
    echo "<p style='color: blue;'>Test session variable exists: " . $_SESSION['test_var'] . "</p>";
}

// Check for Scrive session variables
if (isset($_SESSION['scrive_user_id'])) {
    echo "<p style='color: green;'>✓ Scrive session found! User ID: " . $_SESSION['scrive_user_id'] . "</p>";
    echo "<p>User: " . ($_SESSION['scrive_username'] ?? 'Unknown') . "</p>";
    echo "<p>Login Time: " . date('Y-m-d H:i:s', $_SESSION['scrive_login_time'] ?? 0) . "</p>";
} else {
    echo "<p style='color: red;'>✗ No Scrive session found</p>";
}

echo "<hr>";
echo "<p><a href='login.php'>Go to Login</a> | <a href='index.php'>Go to Dashboard</a></p>";

?> 