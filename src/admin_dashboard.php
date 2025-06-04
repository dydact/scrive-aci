<?php
session_start();
require_once 'config.php';
require_once 'openemr_integration.php';
require_once 'UrlManager.php';

// Strip .php extension if present
UrlManager::stripPhpExtension();

// Check if user is logged in and is administrator
if (!isset($_SESSION['user_id']) || $_SESSION['access_level'] < 5) {
    UrlManager::redirect('login');
}

try {
    $pdo = getDatabase();
    
    // Get organizational statistics
    $stats = [];
    
    // Total employees
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_staff_members");
    $stats['total_employees'] = $stmt->fetch()['count'];
    
    // Active employees
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_staff_members WHERE is_active = 1");
    $stats['active_employees'] = $stmt->fetch()['count'];
    
    // Total clients
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_clients");
    $stats['total_clients'] = $stmt->fetch()['count'];
    
    // Active users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_users WHERE is_active = 1");
    $stats['active_users'] = $stmt->fetch()['count'];
    
    // This month's revenue (placeholder)
    $stats['monthly_revenue'] = '$0.00';
    
    // Pending claims
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_billing_claims WHERE status = 'pending'");
    $stats['pending_claims'] = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard - American Caregivers Inc</title>
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
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #64748b;
            margin-bottom: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #64748b;
            font-size: 0.875rem;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .admin-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .admin-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .admin-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .admin-card h3 {
            color: #1e40af;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .admin-card p {
            color: #64748b;
            margin-bottom: 1.5rem;
        }
        
        .admin-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .admin-link {
            background: #1e40af;
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .admin-link:hover {
            background: #1e3a8a;
        }
        
        .admin-link.secondary {
            background: #e5e7eb;
            color: #1e293b;
        }
        
        .admin-link.secondary:hover {
            background: #d1d5db;
        }
        
        .alert {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            color: #92400e;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
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
            <div class="company-name">Administrator Control Panel</div>
        </div>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
            <a href="<?= UrlManager::url('logout') ?>" class="admin-link secondary" style="margin: 0; padding: 0.5rem 1rem;">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <a href="<?= UrlManager::url('dashboard') ?>" class="back-link">← Back to Main Dashboard</a>
        
        <h1 class="page-title">Administrator Dashboard</h1>
        <p class="page-subtitle">Manage your organization, employees, and system settings</p>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Employees</h3>
                <div class="stat-value"><?php echo $stats['total_employees'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Active Employees</h3>
                <div class="stat-value"><?php echo $stats['active_employees'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Clients</h3>
                <div class="stat-value"><?php echo $stats['total_clients'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Active Users</h3>
                <div class="stat-value"><?php echo $stats['active_users'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <h3>Monthly Revenue</h3>
                <div class="stat-value"><?php echo $stats['monthly_revenue'] ?? '$0.00'; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Claims</h3>
                <div class="stat-value"><?php echo $stats['pending_claims'] ?? 0; ?></div>
            </div>
        </div>
        
        <div class="admin-sections">
            <div class="admin-card">
                <h3>👥 Employee Management</h3>
                <p>Manage staff members, roles, assignments, and schedules.</p>
                <div class="admin-actions">
                    <a href="<?= UrlManager::url('admin_employees') ?>" class="admin-link">View All Employees</a>
                    <a href="<?= UrlManager::url('admin_employees', ['action' => 'add']) ?>" class="admin-link secondary">Add New Employee</a>
                    <a href="<?= UrlManager::url('schedule') ?>" class="admin-link secondary">Manage Schedules</a>
                </div>
            </div>
            
            <div class="admin-card">
                <h3>🔐 User Management</h3>
                <p>Create and manage user accounts, permissions, and access levels.</p>
                <div class="admin-actions">
                    <a href="<?= UrlManager::url('admin_users') ?>" class="admin-link">Manage Users</a>
                    <a href="<?= UrlManager::url('admin_users', ['action' => 'add']) ?>" class="admin-link secondary">Create User Account</a>
                    <a href="<?= UrlManager::url('admin_users', ['tab' => 'roles']) ?>" class="admin-link secondary">Manage Roles</a>
                </div>
            </div>
            
            <div class="admin-card">
                <h3>🏢 Organization Settings</h3>
                <p>Configure organization details, billing information, and system preferences.</p>
                <div class="admin-actions">
                    <a href="<?= UrlManager::url('admin_organization') ?>" class="admin-link">Organization Profile</a>
                    <a href="<?= UrlManager::url('admin_organization', ['tab' => 'billing']) ?>" class="admin-link secondary">Billing Settings</a>
                    <a href="<?= UrlManager::url('admin_organization', ['tab' => 'system']) ?>" class="admin-link secondary">System Settings</a>
                </div>
            </div>
            
            <div class="admin-card">
                <h3>📊 Reports & Analytics</h3>
                <p>Generate comprehensive reports and analyze organizational performance.</p>
                <div class="admin-actions">
                    <a href="<?= UrlManager::url('reports') ?>" class="admin-link">View Reports</a>
                    <a href="<?= UrlManager::url('reports', ['tab' => 'audit']) ?>" class="admin-link secondary">Audit Log</a>
                    <a href="<?= UrlManager::url('reports', ['tab' => 'analytics']) ?>" class="admin-link secondary">Analytics Dashboard</a>
                </div>
            </div>
            
            <div class="admin-card">
                <h3>🔧 System Administration</h3>
                <p>Manage system configurations, integrations, and maintenance tasks.</p>
                <div class="admin-actions">
                    <a href="/autism_waiver_app/admin_database.php" class="admin-link">Database Management</a>
                    <a href="admin_backup.php" class="admin-link secondary">Backup & Restore</a>
                    <a href="admin_integrations.php" class="admin-link secondary">Integrations</a>
                </div>
            </div>
            
            <div class="admin-card">
                <h3>💰 Financial Management</h3>
                <p>Oversee billing, claims, payments, and financial reporting.</p>
                <div class="admin-actions">
                    <a href="<?= UrlManager::url('billing_dashboard') ?>" class="admin-link">Billing Dashboard</a>
                    <a href="<?= UrlManager::url('billing_claims') ?>" class="admin-link secondary">Claims Management</a>
                    <a href="<?= UrlManager::url('billing', ['tab' => 'payments']) ?>" class="admin-link secondary">Payment History</a>
                </div>
            </div>
        </div>
        
        <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="alert">
            <?php 
            echo htmlspecialchars($_SESSION['admin_message']); 
            unset($_SESSION['admin_message']);
            ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="/public/assets/js/interactive-help.js"></script>
</body>
</html> 