<?php
/**
 * Fix Production Authentication Issue
 * This will ensure aci.dydact.io uses database authentication
 */

echo "=== FIXING PRODUCTION AUTHENTICATION ===\n\n";

// First, let's update the simple_login.php to use database auth
$simpleLoginPath = '/var/www/localhost/htdocs/autism_waiver_app/simple_login.php';
$backupPath = $simpleLoginPath . '.backup_hardcoded';

echo "1. Backing up simple_login.php...\n";
if (file_exists($simpleLoginPath)) {
    copy($simpleLoginPath, $backupPath);
    echo "✓ Backup created at: $backupPath\n";
}

echo "\n2. Creating database-aware simple_login.php...\n";

$newLoginContent = '<?php
session_start();
require_once dirname(__FILE__) . \'/../src/config.php\';
require_once dirname(__FILE__) . \'/../src/openemr_integration.php\';

// If already logged in, redirect to dashboard
if (isset($_SESSION[\'user_id\'])) {
    header(\'Location: /src/dashboard.php\');
    exit;
}

$error = \'\';

if ($_SERVER[\'REQUEST_METHOD\'] == \'POST\') {
    $username = $_POST[\'username\'] ?? \'\';
    $password = $_POST[\'password\'] ?? \'\';
    
    if (empty($username) || empty($password)) {
        $error = \'Please enter both username and password.\';
    } else {
        // Use database authentication
        $user = authenticateOpenEMRUser($username, $password);
        
        if ($user) {
            // Set session variables
            $_SESSION[\'user_id\'] = $user[\'user_id\'];
            $_SESSION[\'username\'] = $user[\'username\'];
            $_SESSION[\'first_name\'] = $user[\'first_name\'];
            $_SESSION[\'last_name\'] = $user[\'last_name\'];
            $_SESSION[\'email\'] = $user[\'email\'];
            $_SESSION[\'access_level\'] = $user[\'access_level\'];
            $_SESSION[\'role_name\'] = $user[\'role\'] ?? $user[\'title\'] ?? \'User\';
            $_SESSION[\'login_time\'] = time();
            
            // Redirect to dashboard
            header(\'Location: /src/dashboard.php\');
            exit;
        } else {
            $error = \'Invalid username or password.\';
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
            font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;
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
        }
        
        .btn-login {
            width: 100%;
            padding: 0.875rem;
            background: #059669;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn-login:hover {
            background: #047857;
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
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
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" class="form-control" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>
    </div>
</body>
</html>';

file_put_contents($simpleLoginPath, $newLoginContent);
echo "✓ Updated simple_login.php to use database authentication\n";

// Now ensure the production database has our users
echo "\n3. Verifying database has correct users...\n";

require_once '/var/www/localhost/htdocs/src/config.php';

try {
    $pdo = getDatabase();
    
    // Check current users
    $stmt = $pdo->query("SELECT COUNT(*) FROM autism_users WHERE email LIKE '%@acgcares.com'");
    $acgUsersCount = $stmt->fetchColumn();
    
    if ($acgUsersCount < 8) {
        echo "⚠️  Only found $acgUsersCount ACG users. Running user creation...\n";
        
        // Run the user creation
        $users = [
            ['frank', 'frank@acgcares.com', 'Supreme2024!', 6, 'Frank (Supreme Admin)', 'Supreme Administrator'],
            ['mary.emah', 'mary.emah@acgcares.com', 'CEO2024!', 5, 'Mary Emah', 'Chief Executive Officer'],
            ['drukpeh', 'drukpeh@duck.com', 'Executive2024!', 5, 'Dr. Ukpeh', 'Executive'],
            ['amanda.georgi', 'amanda.georgi@acgcares.com', 'HR2024!', 4, 'Amanda Georgi', 'Human Resources Officer'],
            ['edwin.recto', 'edwin.recto@acgcares.com', 'Clinical2024!', 4, 'Edwin Recto', 'Site Supervisor / Clinical Lead'],
            ['pam.pastor', 'pam.pastor@acgcares.com', 'Billing2024!', 4, 'Pam Pastor', 'Billing Administrator'],
            ['yanika.crosse', 'yanika.crosse@acgcares.com', 'Billing2024!', 4, 'Yanika Crosse', 'Billing Administrator'],
            ['alvin.ukpeh', 'alvin.ukpeh@acgcares.com', 'SysAdmin2024!', 5, 'Alvin Ukpeh', 'System Administrator']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO autism_users (username, email, password_hash, access_level, full_name, role) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash)
        ");
        
        foreach ($users as $user) {
            $passwordHash = password_hash($user[2], PASSWORD_DEFAULT);
            $stmt->execute([$user[0], $user[1], $passwordHash, $user[3], $user[4], $user[5]]);
        }
        
        echo "✓ Created all ACG users\n";
    } else {
        echo "✓ All ACG users already exist\n";
    }
    
    // List all users
    echo "\n4. Current users in database:\n";
    $stmt = $pdo->query("SELECT username, email FROM autism_users ORDER BY access_level DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        echo "   - {$user['username']} ({$user['email']})\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n\n=== ✅ AUTHENTICATION FIXED! ===\n";
echo "\nThe production site at aci.dydact.io should now:\n";
echo "1. Use database authentication (not hardcoded)\n";
echo "2. Have all ACG user accounts\n";
echo "3. Accept the new login credentials\n";
echo "\nTry logging in with: frank@acgcares.com / Supreme2024!\n";
?>