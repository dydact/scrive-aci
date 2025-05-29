<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /src/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // HARDCODED ADMIN CREDENTIALS FOR IMMEDIATE ACCESS
    $valid_credentials = [
        'admin' => [
            'password' => 'AdminPass123!',
            'user_id' => 1,
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'access_level' => 5,
            'user_type' => 'admin'
        ],
        'dsp_test' => [
            'password' => 'TestPass123!',
            'user_id' => 2,
            'first_name' => 'Sarah',
            'last_name' => 'Johnson',
            'access_level' => 2,
            'user_type' => 'staff'
        ],
        'cm_test' => [
            'password' => 'TestPass123!',
            'user_id' => 3,
            'first_name' => 'Michael',
            'last_name' => 'Brown',
            'access_level' => 3,
            'user_type' => 'staff'
        ],
        'supervisor_test' => [
            'password' => 'TestPass123!',
            'user_id' => 4,
            'first_name' => 'Jennifer',
            'last_name' => 'Davis',
            'access_level' => 4,
            'user_type' => 'staff'
        ]
    ];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } elseif (isset($valid_credentials[$username]) && $valid_credentials[$username]['password'] === $password) {
        // Valid login - set session variables
        $user = $valid_credentials[$username];
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $username;
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['access_level'] = $user['access_level'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['staff_member_id'] = $user['user_id']; // For staff assignments
        
        // Redirect to dashboard
        header('Location: /src/dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Autism Waiver Management System</title>
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
        
        .credentials-help code {
            background: #e5e7eb;
            padding: 0.125rem 0.25rem;
            border-radius: 3px;
            font-family: monospace;
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
            <h1>🧠 Autism Waiver System</h1>
            <p>American Caregivers Inc</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
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
                <h3>Test Credentials:</h3>
                <ul>
                    <li><strong>Admin:</strong> <code>admin</code> / <code>AdminPass123!</code></li>
                    <li><strong>DSP:</strong> <code>dsp_test</code> / <code>TestPass123!</code></li>
                    <li><strong>Case Manager:</strong> <code>cm_test</code> / <code>TestPass123!</code></li>
                    <li><strong>Supervisor:</strong> <code>supervisor_test</code> / <code>TestPass123!</code></li>
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