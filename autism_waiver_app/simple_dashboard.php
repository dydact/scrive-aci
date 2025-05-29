<?php
require_once 'simple_auth_helper.php';
requireLogin();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Autism Waiver Management System</title>
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
            line-height: 1.6;
        }
        
        .header {
            background: white;
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #059669;
            font-size: 1.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-badge {
            background: #f0fdf4;
            color: #059669;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .logout-btn {
            background: #dc2626;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .logout-btn:hover {
            background: #b91c1c;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #059669;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #059669;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .module-card {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .module-card:hover {
            transform: translateY(-2px);
        }
        
        .module-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .module-icon {
            font-size: 2rem;
        }
        
        .module-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
        }
        
        .module-description {
            color: #6b7280;
            margin-bottom: 1.5rem;
        }
        
        .module-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #059669;
            color: white;
        }
        
        .btn-primary:hover {
            background: #047857;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .access-denied {
            background: #fef3c7;
            color: #92400e;
            padding: 1rem;
            border-radius: 6px;
            text-align: center;
            margin-top: 1rem;
        }
        
        .quick-actions {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .quick-actions h2 {
            margin-bottom: 1rem;
            color: #1f2937;
        }
        
        .quick-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🧠 Autism Waiver Management System</h1>
            <div class="user-info">
                <div class="user-badge">
                    <?php echo htmlspecialchars(getUserFullName()); ?> 
                    (<?php echo getAccessLevelName(); ?>)
                </div>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Quick Actions for Admin/Supervisor -->
        <?php if (hasAccessLevel(4)): ?>
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="quick-buttons">
                <a href="iiss_session_note.php" class="btn btn-primary">📝 New Session Note</a>
                <a href="treatment_plan_manager.php" class="btn btn-primary">📋 Treatment Plans</a>
                <a href="schedule_manager.php" class="btn btn-primary">📅 Schedule Manager</a>
                <?php if (hasAccessLevel(5)): ?>
                <a href="#" class="btn btn-secondary">⚙️ System Settings</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">3</div>
                <div class="stat-label">Active Clients</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">4</div>
                <div class="stat-label">Staff Members</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">0</div>
                <div class="stat-label">Today's Sessions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">$0</div>
                <div class="stat-label">Monthly Revenue</div>
            </div>
        </div>
        
        <!-- Module Cards -->
        <div class="modules-grid">
            <!-- Clinical Documentation -->
            <div class="module-card">
                <div class="module-header">
                    <div class="module-icon">📝</div>
                    <div class="module-title">Clinical Documentation</div>
                </div>
                <div class="module-description">
                    Document IISS sessions, manage treatment plans, and track client progress
                </div>
                <div class="module-actions">
                    <?php if (hasAccessLevel(2)): ?>
                        <a href="iiss_session_note.php" class="btn btn-primary">Create Session Note</a>
                    <?php endif; ?>
                    <?php if (hasAccessLevel(3)): ?>
                        <a href="treatment_plan_manager.php" class="btn btn-primary">Treatment Plans</a>
                    <?php endif; ?>
                    <?php if (!hasAccessLevel(2)): ?>
                        <div class="access-denied">Requires DSP access or higher</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Scheduling -->
            <div class="module-card">
                <div class="module-header">
                    <div class="module-icon">📅</div>
                    <div class="module-title">Scheduling & Calendar</div>
                </div>
                <div class="module-description">
                    Manage appointments, view schedules, and coordinate resources
                </div>
                <div class="module-actions">
                    <?php if (hasAccessLevel(2)): ?>
                        <a href="schedule_manager.php" class="btn btn-primary">View Schedule</a>
                    <?php endif; ?>
                    <?php if (hasAccessLevel(4)): ?>
                        <a href="#" class="btn btn-secondary">Manage Staff</a>
                    <?php endif; ?>
                    <?php if (!hasAccessLevel(2)): ?>
                        <div class="access-denied">Requires DSP access or higher</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Client Management -->
            <div class="module-card">
                <div class="module-header">
                    <div class="module-icon">👥</div>
                    <div class="module-title">Client Management</div>
                </div>
                <div class="module-description">
                    View client information, enrollment details, and service history
                </div>
                <div class="module-actions">
                    <?php if (hasAccessLevel(2)): ?>
                        <a href="#" class="btn btn-primary">View Clients</a>
                    <?php endif; ?>
                    <?php if (hasAccessLevel(3)): ?>
                        <a href="#" class="btn btn-secondary">Add Client</a>
                    <?php endif; ?>
                    <?php if (!hasAccessLevel(2)): ?>
                        <div class="access-denied">Requires DSP access or higher</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Financial & Billing -->
            <div class="module-card">
                <div class="module-header">
                    <div class="module-icon">💰</div>
                    <div class="module-title">Financial & Billing</div>
                </div>
                <div class="module-description">
                    Manage billing, claims, insurance information, and financial reports
                </div>
                <div class="module-actions">
                    <?php if (hasAccessLevel(4)): ?>
                        <a href="#" class="btn btn-primary">Billing Dashboard</a>
                        <a href="#" class="btn btn-secondary">View Claims</a>
                    <?php endif; ?>
                    <?php if (hasAccessLevel(5)): ?>
                        <a href="#" class="btn btn-secondary">Financial Reports</a>
                    <?php endif; ?>
                    <?php if (!hasAccessLevel(4)): ?>
                        <div class="access-denied">Requires Supervisor access or higher</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Reports & Analytics -->
            <div class="module-card">
                <div class="module-header">
                    <div class="module-icon">📊</div>
                    <div class="module-title">Reports & Analytics</div>
                </div>
                <div class="module-description">
                    Generate reports, view analytics, and track key performance metrics
                </div>
                <div class="module-actions">
                    <?php if (hasAccessLevel(3)): ?>
                        <a href="#" class="btn btn-primary">Client Reports</a>
                    <?php endif; ?>
                    <?php if (hasAccessLevel(4)): ?>
                        <a href="#" class="btn btn-secondary">Staff Reports</a>
                    <?php endif; ?>
                    <?php if (hasAccessLevel(5)): ?>
                        <a href="#" class="btn btn-secondary">System Reports</a>
                    <?php endif; ?>
                    <?php if (!hasAccessLevel(3)): ?>
                        <div class="access-denied">Requires Case Manager access or higher</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- System Administration -->
            <?php if (hasAccessLevel(5)): ?>
            <div class="module-card">
                <div class="module-header">
                    <div class="module-icon">⚙️</div>
                    <div class="module-title">System Administration</div>
                </div>
                <div class="module-description">
                    Manage users, system settings, integrations, and security
                </div>
                <div class="module-actions">
                    <a href="#" class="btn btn-primary">User Management</a>
                    <a href="#" class="btn btn-secondary">System Settings</a>
                    <a href="#" class="btn btn-secondary">Database Tools</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    // Handle logout
    if (isset($_GET['logout'])) {
        logout();
    }
    ?>
</body>
</html>