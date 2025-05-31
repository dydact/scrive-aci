<?php
require_once 'init.php';
requireAuth(5); // Admin only

try {
    $pdo = getDatabase();
    
    // Handle user actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($_POST['action']) {
            case 'create_user':
                // Create new user account
                $username = trim($_POST['username']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $email = trim($_POST['email']);
                $access_level = (int)$_POST['access_level'];
                
                // Check if username exists
                $stmt = $pdo->prepare("SELECT id FROM autism_users WHERE username = ?");
                $stmt->execute([$username]);
                if ($stmt->fetch()) {
                    throw new Exception("Username already exists.");
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO autism_users 
                    (username, password, first_name, last_name, email, access_level, is_active, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
                ");
                $stmt->execute([$username, $password, $first_name, $last_name, $email, $access_level]);
                
                UrlManager::redirectWithSuccess('admin_users', 'User created successfully!');
                break;
                
            case 'update_user':
                $user_id = $_POST['user_id'];
                $stmt = $pdo->prepare("
                    UPDATE autism_users 
                    SET first_name=?, last_name=?, email=?, access_level=?
                    WHERE id=?
                ");
                $stmt->execute([
                    $_POST['first_name'],
                    $_POST['last_name'],
                    $_POST['email'],
                    $_POST['access_level'],
                    $user_id
                ]);
                
                // Update password if provided
                if (!empty($_POST['password'])) {
                    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE autism_users SET password = ? WHERE id = ?");
                    $stmt->execute([$new_password, $user_id]);
                }
                
                UrlManager::redirectWithSuccess('admin_users', 'User updated successfully!');
                break;
                
            case 'deactivate_user':
                $stmt = $pdo->prepare("UPDATE autism_users SET is_active = 0 WHERE id = ?");
                $stmt->execute([$_POST['user_id']]);
                UrlManager::redirectWithSuccess('admin_users', 'User deactivated.');
                break;
                
            case 'activate_user':
                $stmt = $pdo->prepare("UPDATE autism_users SET is_active = 1 WHERE id = ?");
                $stmt->execute([$_POST['user_id']]);
                UrlManager::redirectWithSuccess('admin_users', 'User activated.');
                break;
        }
    }
    
    // Get all users
    $stmt = $pdo->query("
        SELECT u.*, 
               CASE u.access_level
                   WHEN 1 THEN 'Technician'
                   WHEN 2 THEN 'Direct Care Staff'
                   WHEN 3 THEN 'Case Manager'
                   WHEN 4 THEN 'Supervisor'
                   WHEN 5 THEN 'Administrator'
                   ELSE 'Unknown'
               END as role_name
        FROM autism_users u
        ORDER BY u.is_active DESC, u.access_level DESC, u.last_name, u.first_name
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user being edited
    $editing_user = null;
    if (isset($_GET['edit'])) {
        $stmt = $pdo->prepare("SELECT * FROM autism_users WHERE id = ?");
        $stmt->execute([$_GET['edit']]);
        $editing_user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    error_log("Admin users error: " . $e->getMessage());
    $users = [];
}

$access_levels = [
    1 => 'Technician',
    2 => 'Direct Care Staff', 
    3 => 'Case Manager',
    4 => 'Supervisor',
    5 => 'Administrator'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - ACI Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; }
        .header { background: white; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { color: #1e40af; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .section { background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .btn-primary { background: #1e40af; color: white; }
        .btn-success { background: #059669; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-warning { background: #d97706; color: white; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f8fafc; font-weight: 600; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .role-badge { padding: 0.25rem 0.75rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .role-1 { background: #e0e7ff; color: #3730a3; }
        .role-2 { background: #ddd6fe; color: #5b21b6; }
        .role-3 { background: #fef3c7; color: #92400e; }
        .role-4 { background: #fed7d7; color: #c53030; }
        .role-5 { background: #f0fff4; color: #22543d; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; margin: 5% auto; padding: 2rem; border-radius: 12px; max-width: 600px; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>User Management</h1>
        <a href="<?= UrlManager::url('admin') ?>" style="color: #1e40af; text-decoration: none;">← Back to Admin Dashboard</a>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>
        
        <div class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2>System Users</h2>
                <button class="btn btn-primary" onclick="showAddModal()">+ Create User Account</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                            <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                            <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                            <td>
                                <span class="role-badge role-<?= $user['access_level'] ?>">
                                    <?= $user['role_name'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= $user['created_at'] ? date('m/d/Y', strtotime($user['created_at'])) : 'N/A' ?></td>
                            <td>
                                <a href="?edit=<?= $user['id'] ?>" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Edit</a>
                                <?php if ($user['is_active']): ?>
                                    <button onclick="deactivateUser(<?= $user['id'] ?>)" class="btn btn-danger" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Deactivate</button>
                                <?php else: ?>
                                    <button onclick="activateUser(<?= $user['id'] ?>)" class="btn btn-success" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">Activate</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add/Edit User Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h3><?= $editing_user ? 'Edit User' : 'Create New User' ?></h3>
            <form method="POST">
                <input type="hidden" name="action" value="<?= $editing_user ? 'update_user' : 'create_user' ?>">
                <?php if ($editing_user): ?>
                    <input type="hidden" name="user_id" value="<?= $editing_user['id'] ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <?php if (!$editing_user): ?>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" required>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" required value="<?= htmlspecialchars($editing_user['first_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" required value="<?= htmlspecialchars($editing_user['last_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($editing_user['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Access Level</label>
                        <select name="access_level" required>
                            <option value="">Select Role</option>
                            <?php foreach ($access_levels as $level => $name): ?>
                                <option value="<?= $level ?>" <?= ($editing_user['access_level'] ?? '') == $level ? 'selected' : '' ?>>
                                    <?= $name ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Password <?= $editing_user ? '(leave blank to keep current)' : '' ?></label>
                        <input type="password" name="password" <?= $editing_user ? '' : 'required' ?> minlength="6">
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="hideAddModal()">Cancel</button>
                    <button type="submit" class="btn btn-success"><?= $editing_user ? 'Update' : 'Create' ?> User</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function hideAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        function deactivateUser(id) {
            if (confirm('Are you sure you want to deactivate this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="deactivate_user">
                    <input type="hidden" name="user_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function activateUser(id) {
            if (confirm('Are you sure you want to activate this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="activate_user">
                    <input type="hidden" name="user_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        <?php if ($editing_user): ?>
            showAddModal();
        <?php endif; ?>
    </script>
</body>
</html>