<?php
session_start();
require_once 'config.php';
require_once 'openemr_integration.php';
require_once 'UrlManager.php';

// Strip .php extension if present
UrlManager::stripPhpExtension();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    UrlManager::redirect('login');
}

try {
    $pdo = getDatabase();
    
    // Get dashboard statistics based on role
    $stats = [];
    
    // Role-based statistics
    if ($_SESSION['access_level'] >= 5) {
        // Administrator - see everything
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_clients");
        $stats['total_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_staff_members WHERE is_active = 1");
        $stats['active_staff'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_session_notes WHERE session_date = CURDATE()");
        $stats['sessions_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_billing_claims WHERE status = 'generated'");
        $stats['pending_claims'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
    } elseif ($_SESSION['access_level'] >= 3) {
        // Case Manager/Supervisor - see assigned clients and team stats
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT c.id) as count 
            FROM autism_clients c 
            JOIN autism_staff_assignments sa ON c.id = sa.client_id 
            WHERE sa.staff_id = ? AND sa.is_active = 1
        ");
        $stmt->execute([$_SESSION['staff_id'] ?? $_SESSION['user_id']]);
        $stats['my_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM autism_session_notes sn 
            JOIN autism_staff_assignments sa ON sn.client_id = sa.client_id 
            WHERE sa.staff_id = ? AND sn.session_date = CURDATE()
        ");
        $stmt->execute([$_SESSION['staff_id'] ?? $_SESSION['user_id']]);
        $stats['my_sessions_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM autism_session_notes sn 
            WHERE sn.staff_id = ? AND sn.session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$_SESSION['staff_id'] ?? $_SESSION['user_id']]);
        $stats['my_sessions_week'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_treatment_plans WHERE status = 'active'");
        $stats['active_plans'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Add pending approvals count for supervisors
        if ($_SESSION['access_level'] >= 4) {
            $stmt = $pdo->query("
                SELECT COUNT(*) as count FROM autism_session_notes 
                WHERE status IN ('completed', 'pending_approval') 
                AND created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)
            ");
            $stats['late_approvals'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $stmt = $pdo->query("
                SELECT COUNT(*) as count FROM autism_session_notes 
                WHERE status IN ('completed', 'pending_approval')
            ");
            $stats['pending_approvals'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        }
        
    } else {
        // Direct Care Staff/Technician - see only their work
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT c.id) as count 
            FROM autism_clients c 
            JOIN autism_staff_assignments sa ON c.id = sa.client_id 
            WHERE sa.staff_id = ? AND sa.is_active = 1
        ");
        $stmt->execute([$_SESSION['staff_id'] ?? $_SESSION['user_id']]);
        $stats['my_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM autism_session_notes 
            WHERE staff_id = ? AND session_date = CURDATE()
        ");
        $stmt->execute([$_SESSION['staff_id'] ?? $_SESSION['user_id']]);
        $stats['my_sessions_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM autism_session_notes 
            WHERE staff_id = ? AND session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $stmt->execute([$_SESSION['staff_id'] ?? $_SESSION['user_id']]);
        $stats['my_sessions_week'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM autism_session_notes 
            WHERE staff_id = ? AND session_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$_SESSION['staff_id'] ?? $_SESSION['user_id']]);
        $stats['my_sessions_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    // Get recent session notes based on role (simplified for now to avoid table structure issues)
    $recent_sessions = [];
    
    // Get clients assigned to current user (simplified for now)
    $my_clients = [];
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    error_log($error_message);
    // Initialize empty stats to prevent errors
    $stats = ['total_clients' => 0, 'active_staff' => 0, 'sessions_today' => 0, 'pending_claims' => 0];
    $recent_sessions = [];
    $my_clients = [];
}

// Define role-based actions - URLs adjusted to be root-relative
$role_actions = [];
switch ($_SESSION['access_level']) {
    case 5: // Administrator
        $role_actions = [
            ['url' => '/clients', 'label' => '👥 Client Management', 'class' => 'action-btn'],
            ['url' => '/billing', 'label' => '💰 Billing System', 'class' => 'action-btn'],
            ['url' => '/reports/billing', 'label' => '📊 Billing Reports', 'class' => 'action-btn'],
            ['url' => '/supervisor_reports', 'label' => '📈 Supervisor Reports', 'class' => 'action-btn'],
            ['url' => '/supervisor_approvals', 'label' => '✅ Approvals', 'class' => 'action-btn'],
            ['url' => '/admin', 'label' => '🔧 Admin Panel', 'class' => 'action-btn admin-panel'],
            ['url' => '/role-switcher', 'label' => '🔄 Switch Role', 'class' => 'action-btn secondary'],
            ['url' => '/staff', 'label' => '📱 Staff Portal', 'class' => 'action-btn secondary'],
            ['url' => '/schedule', 'label' => '📅 Schedules', 'class' => 'action-btn'],
            ['url' => '/reports', 'label' => '📊 All Reports', 'class' => 'action-btn']
        ];
        break;
    case 4: // Supervisor
        $role_actions = [
            ['url' => '/supervisor_reports', 'label' => '📈 Supervisor Reports', 'class' => 'action-btn btn-primary'],
            ['url' => '/clients', 'label' => '👥 Team Clients', 'class' => 'action-btn'],
            ['url' => '/supervisor_approvals', 'label' => '✅ Approvals', 'class' => 'action-btn'],
            ['url' => '/supervisor', 'label' => '📋 Supervision Portal', 'class' => 'action-btn'],
            ['url' => '/billing', 'label' => '💰 Billing Dashboard', 'class' => 'action-btn'],
            ['url' => '/reports/billing', 'label' => '📊 Billing Reports', 'class' => 'action-btn'],
            ['url' => '/staff', 'label' => '📱 Staff Portal', 'class' => 'action-btn secondary']
        ];
        break;
    case 3: // Case Manager
        $role_actions = [
            ['url' => '/clients/secure', 'label' => '👥 My Clients', 'class' => 'action-btn'],
            ['url' => '/case-manager', 'label' => '📋 Treatment Plans', 'class' => 'action-btn'],
            ['url' => '/billing', 'label' => '💰 Billing Dashboard', 'class' => 'action-btn'],
            ['url' => '/reports/billing', 'label' => '📊 Billing Reports', 'class' => 'action-btn'],
            ['url' => '/staff', 'label' => '📱 Staff Portal', 'class' => 'action-btn secondary']
        ];
        break;
    case 2: // Direct Care Staff
        $role_actions = [
            ['url' => '/staff', 'label' => '📱 Staff Portal', 'class' => 'action-btn'],
            ['url' => '/staff/notes', 'label' => '📝 Session Notes', 'class' => 'action-btn'],
            ['url' => '/staff/clients', 'label' => '👥 My Clients', 'class' => 'action-btn secondary']
        ];
        break;
    case 1: // Technician
        $role_actions = [
            ['url' => '/staff', 'label' => '📱 Staff Portal', 'class' => 'action-btn'],
            ['url' => '/staff/notes', 'label' => '📝 Session Notes', 'class' => 'action-btn secondary']
        ];
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - American Caregivers Inc</title>
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
        
        .header h1 {
            color: #059669;
            font-size: 1.5rem;
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
        
        .tagline {
            font-size: 0.875rem;
            color: #64748b;
            margin-left: 0.5rem;
            padding-left: 0.5rem;
            border-left: 1px solid #e5e7eb;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-badge {
            background: #059669;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
        }
        
        .role-badge {
            background: #6366f1;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
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
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .role-banner {
            background: linear-gradient(45deg, #6366f1, #8b5cf6);
            color: white;
            padding: 1rem 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .role-banner h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .role-banner p {
            opacity: 0.9;
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
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #059669;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .action-btn {
            background: #059669;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .action-btn:hover {
            background: #047857;
            color: white;
        }
        
        .action-btn.secondary {
            background: #6366f1;
        }
        
        .action-btn.secondary:hover {
            background: #d1d5db;
        }
        
        .action-btn.admin-panel {
            background: #dc2626;
            color: white;
            border: 2px solid #dc2626;
            font-weight: 600;
        }
        
        .action-btn.admin-panel:hover {
            background: #b91c1c;
            border-color: #b91c1c;
            transform: translateY(-2px);
        }
        
        .production-notice {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            color: #0c4a6e;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .user-info {
                flex-direction: column;
            }
        }
    </style>
    <script src="/public/assets/js/interactive-help.js"></script>
</head>
<body>
    <div class="header">
        <div class="logo-banner">
            <div class="logo-text">
                <span class="a">A</span><span class="c">C</span><span class="i">I</span>
            </div>
            <div class="company-name">American Caregivers Inc</div>
            <div class="tagline">Autism Waiver Management System</div>
        </div>
        <div class="user-info">
            <div class="user-badge">
                <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
            </div>
            <div class="role-badge">
                <?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Staff'); ?> (Level <?php echo $_SESSION['access_level']; ?>)
            </div>
            <a href="/help" class="logout-btn" style="background: #4a90e2; margin-right: 10px;" target="_blank">Help</a>
            <a href="/logout" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="role-banner">
            <h2><?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Staff'); ?> Dashboard</h2>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?>! Access your autism waiver management tools below.</p>
        </div>
        
        <div class="production-notice">
            <h3 style="color: #0c4a6e; margin-bottom: 0.5rem;">🏥 Production Environment - System Active</h3>
            <p style="color: #0369a1; margin-bottom: 0;">
                This system uses MariaDB for production data storage. All changes are permanent.
            </p>
        </div>
        
        <div class="quick-actions">
            <?php foreach ($role_actions as $action): ?>
                <a href="<?php echo $action['url']; ?>" class="<?php echo $action['class']; ?>">
                    <?php echo $action['label']; ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="stats-grid">
            <?php if ($_SESSION['access_level'] >= 5): ?>
                <div class="stat-card">
                    <h3><?php echo $stats['total_clients'] ?? 0; ?></h3>
                    <p>Total Clients</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['active_staff'] ?? 0; ?></h3>
                    <p>Active Staff Members</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['sessions_today'] ?? 0; ?></h3>
                    <p>Session Notes Today</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['pending_claims'] ?? 0; ?></h3>
                    <p>Pending Claims</p>
                </div>
            <?php else: ?>
                <div class="stat-card">
                    <h3><?php echo $stats['my_clients'] ?? 0; ?></h3>
                    <p>My Assigned Clients</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['my_sessions_today'] ?? 0; ?></h3>
                    <p>My Sessions Today</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['my_sessions_week'] ?? 0; ?></h3>
                    <p>My Sessions This Week</p>
                </div>
                <?php if ($_SESSION['access_level'] >= 3): ?>
                    <div class="stat-card">
                        <h3><?php echo $stats['active_plans'] ?? 0; ?></h3>
                        <p>Active Treatment Plans</p>
                    </div>
                    <?php if ($_SESSION['access_level'] >= 4): ?>
                        <div class="stat-card" style="border: 2px solid #dc2626;">
                            <h3 style="color: #dc2626;"><?php echo $stats['late_approvals'] ?? 0; ?></h3>
                            <p>Late Approvals (>48hr)</p>
                        </div>
                        <div class="stat-card">
                            <h3 style="color: #059669;"><?php echo $stats['pending_approvals'] ?? 0; ?></h3>
                            <p>Pending Approvals</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="stat-card">
                        <h3><?php echo $stats['my_sessions_month'] ?? 0; ?></h3>
                        <p>My Sessions This Month</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 