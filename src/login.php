<?php
session_start();
require_once 'config.php';
require_once 'openemr_integration.php';

$error = null;
$success = null;

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    $success = "You have been logged out successfully.";
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
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
        
        // Use OpenEMR-compatible authentication
        $user = authenticateOpenEMRUser($username, $password);
        
        if (!$user) {
            throw new Exception("Invalid username or password.");
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['access_level'] = $user['access_level'];
        $_SESSION['login_time'] = time();
        
        // Set additional fields if available
        if (isset($user['staff_id'])) {
            $_SESSION['staff_id'] = $user['staff_id'];
        }
        if (isset($user['job_title'])) {
            $_SESSION['job_title'] = $user['job_title'];
        }
        if (isset($user['role_name'])) {
            $_SESSION['role_name'] = $user['role_name'];
        } else {
            $_SESSION['role_name'] = $user['title'] ?? 'User';
        }
        
        header('Location: dashboard.php');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - American Caregivers Inc</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --aci-blue: #1e40af;
            --aci-red: #dc2626;
            --aci-green: #16a34a;
            --medical-blue: #3b82f6;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            padding: 3rem;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        .logo {
            margin-bottom: 2rem;
        }
        
        .logo-text {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .logo-text .a { color: var(--aci-blue); }
        .logo-text .c { color: var(--aci-red); }
        .logo-text .i { color: var(--aci-green); }
        
        .company-name {
            color: var(--aci-blue);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .tagline {
            color: #64748b;
            font-size: 1rem;
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--aci-blue);
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }
        
        .password-container {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 1.2rem;
        }
        
        .login-btn {
            width: 100%;
            background: var(--aci-blue);
            color: white;
            padding: 0.875rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1rem;
        }
        
        .login-btn:hover {
            background: #1e3a8a;
            transform: translateY(-1px);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        .alert-error {
            background: #fef2f2;
            color: var(--aci-red);
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #f0fdf4;
            color: var(--aci-green);
            border: 1px solid #bbf7d0;
        }
        
        .back-link {
            color: var(--aci-blue);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s;
        }
        
        .back-link:hover {
            color: #1e3a8a;
            text-decoration: underline;
        }
        
        .forgot-password {
            margin-top: 1rem;
        }
        
        .forgot-password a {
            color: var(--aci-blue);
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .openemr-note {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            color: #0c4a6e;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
    </style>
    <script src="/public/assets/js/interactive-help.js"></script>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-text">
                <span class="a">A</span><span class="c">C</span><span class="i">I</span>
            </div>
            <div class="company-name">American Caregivers, Inc.</div>
            <div class="tagline">Setting the Standard of Quality Care</div>
        </div>
        
        <div class="openemr-note" style="display: none;">
            <!-- Note: System is integrated with OpenEMR but this is hidden from users -->
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()">👁</button>
                </div>
            </div>
            
            <button type="submit" class="login-btn" id="login-button">Login</button>
        </form>
        
        <div class="forgot-password">
            <a href="password_reset.php">Forgot your password?</a>
        </div>
        
        <div style="margin-top: 1rem; text-align: center;">
            <a href="/help_center.php" class="back-link" style="margin-right: 1rem;">📚 Help Center</a>
            <a href="/index.php" class="back-link">← Back to Website</a>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleButton.textContent = '👁';
            }
        }
    </script>
</body>
</html> 