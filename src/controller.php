<?php
// OpenEMR-compatible controller
require_once 'config.php';
require_once 'openemr_integration.php';

class Controller {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDatabase();
    }
    
    public function act($params) {
        try {
            $action = $params['action'] ?? 'default';
            
            switch ($action) {
                case 'login':
                    return $this->handleLogin($params);
                case 'logout':
                    return $this->handleLogout();
                case 'dashboard':
                    return $this->handleDashboard();
                default:
                    return $this->handleDefault();
            }
        } catch (Exception $e) {
            error_log("Controller error: " . $e->getMessage());
            return "Error: " . $e->getMessage();
        }
    }
    
    private function handleLogin($params) {
        if (!isset($params['username']) || !isset($params['password'])) {
            return "Username and password required";
        }
        
        $user = authenticateOpenEMRUser($params['username'], $params['password']);
        if ($user) {
            session_start();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            return "Login successful";
        } else {
            return "Invalid credentials";
        }
    }
    
    private function handleLogout() {
        session_start();
        session_destroy();
        return "Logout successful";
    }
    
    private function handleDashboard() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            return "Please login first";
        }
        return "Dashboard for " . $_SESSION['username'];
    }
    
    private function handleDefault() {
        return "OpenEMR Controller - Available actions: login, logout, dashboard";
    }
}

// If called directly, create controller and handle request
if (basename($_SERVER['PHP_SELF']) === 'controller.php') {
    $controller = new Controller();
    echo $controller->act($_GET);
}
?> 