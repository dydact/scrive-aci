<?php

/**
 * Scrive Login System - Standalone Authentication
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

// Set ignoreAuth to bypass site checks during login process
$ignoreAuth = true;

// Include OpenEMR database configuration (backend only)
require_once __DIR__ . '/../interface/globals.php';

$error = null;
$success = null;

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    $success = "You have been logged out successfully.";
}

// Handle session timeout
if (isset($_GET['timeout']) && $_GET['timeout'] === '1') {
    session_destroy();
    $error = "Your session has expired. Please log in again.";
}

// Check if already logged in
if (isset($_SESSION['scrive_user_id']) && !empty($_SESSION['scrive_user_id'])) {
    header('Location: index.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            throw new Exception("Please enter both username and password.");
        }
        
        // Authenticate against OpenEMR users table
        $userQuery = "SELECT u.*, f.name as facility_name 
                      FROM users u 
                      LEFT JOIN facility f ON u.facility_id = f.id 
                      WHERE u.username = ? AND u.active = 1";
        
        $user = sqlQuery($userQuery, [$username]);
        
        if (!$user) {
            throw new Exception("Invalid username or password.");
        }
        
        // Verify password (OpenEMR uses various password methods)
        $passwordValid = false;
        
        // Check for bcrypt hash (modern OpenEMR)
        if (password_verify($password, $user['password'])) {
            $passwordValid = true;
        }
        // Check for MD5 hash (legacy OpenEMR)
        elseif (hash('md5', $password) === $user['password']) {
            $passwordValid = true;
        }
        // Check for SHA1 hash (some OpenEMR versions)
        elseif (hash('sha1', $password) === $user['password']) {
            $passwordValid = true;
        }
        // Check plain text (very old installations - not recommended)
        elseif ($password === $user['password']) {
            $passwordValid = true;
        }
        
        if (!$passwordValid) {
            throw new Exception("Invalid username or password.");
        }
        
        // Check if user has necessary permissions (basic check)
        if (!$user['authorized'] && !$user['see_auth']) {
            throw new Exception("Your account does not have permission to access this system.");
        }
        
        // Set Scrive session variables
        $_SESSION['scrive_user_id'] = $user['id'];
        $_SESSION['scrive_username'] = $user['username'];
        $_SESSION['scrive_user_fname'] = $user['fname'];
        $_SESSION['scrive_user_lname'] = $user['lname'];
        $_SESSION['scrive_user_email'] = $user['email'];
        $_SESSION['scrive_facility_id'] = $user['facility_id'];
        $_SESSION['scrive_facility_name'] = $user['facility_name'];
        $_SESSION['scrive_authorized'] = $user['authorized'];
        $_SESSION['scrive_login_time'] = time();
        
        // For OpenEMR compatibility, also set these
        $_SESSION['authUser'] = $user['username'];
        $_SESSION['authUserID'] = $user['id'];
        $_SESSION['authGroup'] = $user['facility_id'];
        $_SESSION['site_id'] = 'default'; // Required by OpenEMR globals.php
        
        // Log successful login
        error_log("Scrive login successful for user: " . $username . " (Session ID: " . session_id() . ")");
        
        // Force session save and redirect to dashboard
        session_write_close();
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Scrive login failed for user: " . ($username ?? 'unknown') . " - " . $e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Scrive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            color: white;
        }
        .company-info {
            text-align: center;
            margin-top: 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        .brain-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="brain-icon">
                <i class="fas fa-brain"></i>
            </div>
            <h2 class="mb-2">Scrive</h2>
            <p class="mb-0">AI-Powered Autism Waiver ERM</p>
        </div>
        
        <div class="login-body">
            <!-- Messages -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="fas fa-user me-2"></i>Username
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="username" 
                           name="username" 
                           placeholder="Enter your username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           required 
                           autofocus>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Password
                    </label>
                    <input type="password" 
                           class="form-control" 
                           id="password" 
                           name="password" 
                           placeholder="Enter your password"
                           required>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>
                    Sign In to Scrive
                </button>
            </form>
            
            <div class="company-info">
                <div class="mb-2">
                    <strong>American Caregivers Incorporated</strong>
                </div>
                <div>
                    <i class="fas fa-shield-alt me-1"></i>
                    HIPAA Compliant • Maryland Autism Waiver Services
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Focus on username field when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Enter key handling
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('.login-form').submit();
            }
        });
    </script>
</body>
</html> 