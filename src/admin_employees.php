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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        try {
            $employee_id = $_POST['employee_id'] ?? null;
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $role = $_POST['role'];
            $hire_date = $_POST['hire_date'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $hourly_rate = $_POST['hourly_rate'] ?? null;
            $address = $_POST['address'] ?? '';
            $city = $_POST['city'] ?? '';
            $state = $_POST['state'] ?? '';
            $zip = $_POST['zip'] ?? '';
            
            if ($action === 'add') {
                // Check if email already exists
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM autism_staff_members WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("An employee with this email already exists.");
                }
                
                // Insert new employee
                $stmt = $pdo->prepare("
                    INSERT INTO autism_staff_members 
                    (first_name, last_name, email, phone, role, hire_date, is_active, hourly_rate, address, city, state, zip)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$first_name, $last_name, $email, $phone, $role, $hire_date, $is_active, $hourly_rate, $address, $city, $state, $zip]);
                
                $message = "Employee added successfully!";
                $action = 'list';
            } else {
                // Update existing employee
                $stmt = $pdo->prepare("
                    UPDATE autism_staff_members 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, role = ?, 
                        hire_date = ?, is_active = ?, hourly_rate = ?, address = ?, 
                        city = ?, state = ?, zip = ?
                    WHERE id = ?
                ");
                $stmt->execute([$first_name, $last_name, $email, $phone, $role, $hire_date, 
                               $is_active, $hourly_rate, $address, $city, $state, $zip, $employee_id]);
                
                $message = "Employee updated successfully!";
                $action = 'list';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($action === 'delete' && isset($_POST['employee_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM autism_staff_members WHERE id = ?");
            $stmt->execute([$_POST['employee_id']]);
            $message = "Employee deleted successfully!";
        } catch (Exception $e) {
            $error = "Error deleting employee: " . $e->getMessage();
        }
        $action = 'list';
    }
}

// Get employee data for editing
$employee = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM autism_staff_members WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $employee = $stmt->fetch();
    if (!$employee) {
        $error = "Employee not found.";
        $action = 'list';
    }
}

// Get all employees for listing
$employees = [];
if ($action === 'list') {
    $stmt = $pdo->query("
        SELECT * FROM autism_staff_members 
        ORDER BY is_active DESC, last_name, first_name
    ");
    $employees = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management - American Caregivers Inc</title>
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
        
        .employee-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .employee-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .employee-table th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #64748b;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .employee-table td {
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .employee-table tr:hover {
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
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-banner">
            <div class="logo-text">
                <span class="a">A</span><span class="c">C</span><span class="i">I</span>
            </div>
            <div class="company-name">Employee Management</div>
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
            <h1 class="page-title">Employees</h1>
            <div class="action-buttons">
                <a href="?action=add" class="btn btn-primary">+ Add New Employee</a>
            </div>
        </div>
        
        <div class="employee-table">
            <?php if (count($employees) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Hire Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($emp['email']); ?></td>
                        <td><?php echo htmlspecialchars($emp['phone']); ?></td>
                        <td><?php echo htmlspecialchars($emp['role']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($emp['hire_date'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $emp['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $emp['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-links">
                                <a href="?action=edit&id=<?php echo $emp['id']; ?>" class="action-link">Edit</a>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                                    <button type="submit" class="action-link" style="background:none;border:none;color:#dc2626;cursor:pointer;">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <p>No employees found.</p>
                <a href="?action=add" class="btn btn-primary" style="margin-top: 1rem;">Add First Employee</a>
            </div>
            <?php endif; ?>
        </div>
        
        <?php elseif ($action === 'add' || $action === 'edit'): ?>
        <div class="page-header">
            <h1 class="page-title"><?php echo $action === 'add' ? 'Add New Employee' : 'Edit Employee'; ?></h1>
        </div>
        
        <div class="form-container">
            <form method="post">
                <?php if ($action === 'edit'): ?>
                <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                <?php endif; ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($employee['first_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($employee['last_name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone *</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="Direct Care Worker" <?php echo ($employee['role'] ?? '') === 'Direct Care Worker' ? 'selected' : ''; ?>>Direct Care Worker</option>
                            <option value="Registered Nurse" <?php echo ($employee['role'] ?? '') === 'Registered Nurse' ? 'selected' : ''; ?>>Registered Nurse</option>
                            <option value="LPN" <?php echo ($employee['role'] ?? '') === 'LPN' ? 'selected' : ''; ?>>LPN</option>
                            <option value="Behavior Analyst" <?php echo ($employee['role'] ?? '') === 'Behavior Analyst' ? 'selected' : ''; ?>>Behavior Analyst</option>
                            <option value="Case Manager" <?php echo ($employee['role'] ?? '') === 'Case Manager' ? 'selected' : ''; ?>>Case Manager</option>
                            <option value="Administrator" <?php echo ($employee['role'] ?? '') === 'Administrator' ? 'selected' : ''; ?>>Administrator</option>
                            <option value="Other" <?php echo ($employee['role'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="hire_date">Hire Date *</label>
                        <input type="date" id="hire_date" name="hire_date" 
                               value="<?php echo htmlspecialchars($employee['hire_date'] ?? date('Y-m-d')); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="hourly_rate">Hourly Rate</label>
                        <input type="number" id="hourly_rate" name="hourly_rate" step="0.01" 
                               value="<?php echo htmlspecialchars($employee['hourly_rate'] ?? ''); ?>" 
                               placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" 
                               value="<?php echo htmlspecialchars($employee['address'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" 
                               value="<?php echo htmlspecialchars($employee['city'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="state">State</label>
                        <select id="state" name="state">
                            <option value="">Select State</option>
                            <option value="NY" <?php echo ($employee['state'] ?? '') === 'NY' ? 'selected' : ''; ?>>New York</option>
                            <option value="NJ" <?php echo ($employee['state'] ?? '') === 'NJ' ? 'selected' : ''; ?>>New Jersey</option>
                            <option value="CT" <?php echo ($employee['state'] ?? '') === 'CT' ? 'selected' : ''; ?>>Connecticut</option>
                            <option value="PA" <?php echo ($employee['state'] ?? '') === 'PA' ? 'selected' : ''; ?>>Pennsylvania</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="zip">ZIP Code</label>
                        <input type="text" id="zip" name="zip" 
                               value="<?php echo htmlspecialchars($employee['zip'] ?? ''); ?>" 
                               pattern="[0-9]{5}" maxlength="5">
                    </div>
                    
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" 
                                   <?php echo ($employee['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <label for="is_active">Active Employee</label>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $action === 'add' ? 'Add Employee' : 'Update Employee'; ?>
                    </button>
                    <a href="admin_employees.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="/public/assets/js/interactive-help.js"></script>
</body>
</html> 