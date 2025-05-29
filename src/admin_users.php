<?php
session_start();
require_once 'config.php';
require_once 'openemr_integration.php';

// Check if user is logged in and is administrator
if (!isset($_SESSION['user_id']) || $_SESSION['access_level'] < 5) {
    header('Location: login.php');
    exit;
}

$pdo = getDatabase();
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Define access levels
$access_levels = [
    1 => 'Basic User',
    2 => 'Employee',
    3 => 'Supervisor',
    4 => 'Manager',
    5 => 'Administrator'
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        try {
            $user_id = $_POST['user_id'] ?? null;
            $username = $_POST['username'];
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $email = $_POST['email'];
            $access_level = (int)$_POST['access_level'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($action === 'add') {
                // Check if username already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM autism_users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("A user with this username already exists.");
                }
                
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM autism_users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("A user with this email already exists.");
                }
                
                // Generate random password
                $password = bin2hex(random_bytes(8));
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $pdo->prepare("
                    INSERT INTO autism_users 
                    (username, password_hash, first_name, last_name, email, access_level, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$username, $password_hash, $first_name, $last_name, $email, $access_level, $is_active]);
                
                $message = "User created successfully! Temporary password: $password (Please save this password and share it with the user)";
                $action = 'list';
            } else {
                // Update existing user
                $stmt = $pdo->prepare("
                    UPDATE autism_users 
                    SET username = ?, first_name = ?, last_name = ?, email = ?, 
                        access_level = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([$username, $first_name, $last_name, $email, $access_level, $is_active, $user_id]);
                
                // Handle password reset if requested
                if (isset($_POST['reset_password'])) {
                    $password = bin2hex(random_bytes(8));
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("UPDATE autism_users SET password_hash = ? WHERE id = ?");
                    $stmt->execute([$password_hash, $user_id]);
                    
                    $message = "User updated and password reset! New password: $password";
                } else {
                    $message = "User updated successfully!";
                }
                
                $action = 'list';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($action === 'delete' && isset($_POST['user_id'])) {
        try {
            // Don't allow deleting your own account
            if ($_POST['user_id'] == $_SESSION['user_id']) {
                throw new Exception("You cannot delete your own account.");
            }
            
            $stmt = $pdo->prepare("DELETE FROM autism_users WHERE id = ?");
            $stmt->execute([$_POST['user_id']]);
            $message = "User deleted successfully!";
        } catch (Exception $e) {
            $error = "Error deleting user: " . $e->getMessage();
        }
        $action = 'list';
    }
}

// Get user data for editing
$user = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM autism_users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $user = $stmt->fetch();
    if (!$user) {
        $error = "User not found.";
        $action = 'list';
    }
}

// Get all users for listing
$users = [];
if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT u.*, 
               (SELECT COUNT(*) FROM autism_billing_claims WHERE submitted_by_user_id = u.id) as claim_count,
               (SELECT MAX(created_at) FROM autism_users WHERE id = u.id) as last_login
        FROM autism_users u
        ORDER BY u.is_active DESC, u.access_level DESC, u.last_name, u.first_name
    ");
    $users = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - American Caregivers Inc</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        
        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-banner {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-text {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .logo-text .a { color: #1e40af; }
        .logo-text .c { color: #dc2626; }
        .logo-text .i { color: #16a34a; }
        
        .company-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            color: #1e293b;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #1e40af;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e3a8a;
        }
        
        .btn-secondary {
            background: #e5e7eb;
            color: #1e293b;
        }
        
        .btn-secondary:hover {
            background: #d1d5db;
        }
        
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b91c1c;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .alert-success {
            background: #d1fae5;
            border: 1px solid #34d399;
            color: #065f46;
        }
        
        .alert-error {
            background: #fee2e2;
            border: 1px solid #f87171;
            color: #991b1b;
        }
        
        .user-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .user-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .user-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #64748b;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .user-table td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .user-table tr:hover {
            background: #f8fafc;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .access-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            background: #e0e7ff;
            color: #3730a3;
        }
        
        .access-badge.admin {
            background: #fef3c7;
            color: #92400e;
        }
        
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }
        
        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1e40af;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .action-links {
            display: flex;
            gap: 1rem;
        }
        
        .action-link {
            color: #1e40af;
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }
        
        .back-link {
            color: #1e40af;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .password-note {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            color: #92400e;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-banner">
            <div class="logo-text">
                <span class="a">A</span><span class="c">C</span><span class="i">I</span>
            </div>
            <div class="company-name">User Management</div>
        </div>
    </div>
    
    <div class="container">
        <a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($action === 'list'): ?>
        <div class="page-header">
            <h1 class="page-title">System Users</h1>
            <div class="action-buttons">
                <a href="?action=add" class="btn btn-primary">+ Create New User</a>
            </div>
        </div>
        
        <div class="user-table">
            <?php if (count($users) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Access Level</th>
                        <th>Status</th>
                        <th>Claims</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $usr): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($usr['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($usr['first_name'] . ' ' . $usr['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($usr['email']); ?></td>
                        <td>
                            <span class="access-badge <?php echo $usr['access_level'] >= 5 ? 'admin' : ''; ?>">
                                <?php echo $access_levels[$usr['access_level']] ?? 'Unknown'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge <?php echo $usr['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $usr['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td><?php echo $usr['claim_count'] ?? 0; ?></td>
                        <td><?php echo date('M d, Y', strtotime($usr['created_at'])); ?></td>
                        <td>
                            <div class="action-links">
                                <a href="?action=edit&id=<?php echo $usr['id']; ?>" class="action-link">Edit</a>
                                <?php if ($usr['id'] != $_SESSION['user_id']): ?>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $usr['id']; ?>">
                                    <button type="submit" class="action-link" style="background:none;border:none;color:#dc2626;cursor:pointer;">Delete</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <p>No users found.</p>
                <a href="?action=add" class="btn btn-primary" style="margin-top: 1rem;">Create First User</a>
            </div>
            <?php endif; ?>
        </div>
        
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <div class="page-header">
            <h1 class="page-title"><?php echo $action === 'add' ? 'Create New User' : 'Edit User'; ?></h1>
        </div>
        
        <div class="form-container">
            <form method="post">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" 
                               value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" 
                               required <?php echo $action === 'edit' ? 'readonly' : ''; ?>>
                        <?php if ($action === 'edit'): ?>
                        <small style="color: #64748b;">Username cannot be changed</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="access_level">Access Level *</label>
                        <select id="access_level" name="access_level" required>
                            <?php foreach ($access_levels as $level => $label): ?>
                            <option value="<?php echo $level; ?>" 
                                    <?php echo ($user['access_level'] ?? 1) == $level ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" 
                                   <?php echo ($user['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="is_active">Active User</label>
                        </div>
                    </div>
                    
                    <?php if ($action === 'edit'): ?>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="reset_password" name="reset_password">
                            <label for="reset_password">Reset Password</label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $action === 'add' ? 'Create User' : 'Update User'; ?>
                    </button>
                    <a href="admin_users.php" class="btn btn-secondary">Cancel</a>
                </div>
                
                <?php if ($action === 'add'): ?>
                <div class="password-note">
                    <strong>Note:</strong> A temporary password will be generated when the user is created. 
                    Please make sure to save and share this password with the user.
                </div>
                <?php endif; ?>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="/public/assets/js/interactive-help.js"></script>
</body>
</html> 