<?php
/**
 * Staff Portal Router
 * Routes staff to appropriate portal based on their role
 */

session_start();
require_once '../src/config.php';
require_once '../src/openemr_integration.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Route based on access level
switch ($_SESSION['access_level']) {
    case 1:
    case 2:
        // DSP/Technician - Staff Data Entry Portal
        header('Location: /staff/dashboard');
        break;
        
    case 3:
        // Case Manager - Enhanced Portal
        header('Location: /case-manager');
        break;
        
    case 4:
        // Supervisor - Management Portal
        header('Location: /supervisor');
        break;
        
    case 5:
        // Administrator - Full Access
        header('Location: /admin');
        break;
        
    default:
        // Unknown role - back to main dashboard
        header('Location: /dashboard');
        break;
}
exit;