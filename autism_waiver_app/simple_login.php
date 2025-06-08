<?php
session_start();
require_once dirname(__FILE__) . '/../src/config.php';
require_once dirname(__FILE__) . '/../src/openemr_integration.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /src/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Use database authentication
        $user = authenticateOpenEMRUser($username, $password);
        
        if ($user) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['access_level'] = $user['access_level'];
            $_SESSION['role_name'] = $user['role'] ?? $user['title'] ?? 'User';
            $_SESSION['login_time'] = time();
            
            // Redirect to dashboard
            header('Location: /src/dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
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
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e293b;
        }
        
        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
            margin: 1rem;
        }
        
        .login-header {
            background: #059669;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 1.75rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #059669;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background: #047857;
            transform: translateY(-1px);
        }
        
        .error {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid #fecaca;
        }
        
        .credentials-help {
            margin-top: 2rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .credentials-help h3 {
            font-size: 0.875rem;
            color: #059669;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .credentials-help ul {
            list-style: none;
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        .credentials-help li {
            margin-bottom: 0.25rem;
        }
        
        .company-info {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>American Caregivers Inc</h1>
            <p>Autism Waiver Management System</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           required 
                           autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required>
                </div>
                
                <button type="submit" class="btn">Sign In</button>
            </form>
            
            <div class="credentials-help">
                <h3>Login Information:</h3>
                <ul>
                    <li>Use your assigned username or email and password</li>
                    <li>Contact your supervisor if you need login assistance</li>
                </ul>
            </div>
            
            <div class="company-info">
                <strong>American Caregivers Incorporated</strong><br>
                HIPAA Compliant • Maryland Autism Waiver Services
            </div>
        </div>
    </div>
</body>
</html>