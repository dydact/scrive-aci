<?php
session_start();
require_once 'config_sqlite.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login_sqlite.php');
    exit;
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
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_session_notes WHERE session_date = date('now')");
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
        $stmt->execute([$_SESSION['staff_id']]);
        $stats['my_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM autism_session_notes sn 
            JOIN autism_staff_assignments sa ON sn.client_id = sa.client_id 
            WHERE sa.staff_id = ? AND sn.session_date = date('now')
        ");
        $stmt->execute([$_SESSION['staff_id']]);
        $stats['my_sessions_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM autism_session_notes sn 
            WHERE sn.staff_id = ? AND sn.session_date >= date('now', '-7 days')
        ");
        $stmt->execute([$_SESSION['staff_id']]);
        $stats['my_sessions_week'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_treatment_plans WHERE status = 'active'");
        $stats['active_plans'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
    } else {
        // Direct Care Staff/Technician - see only their work
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT c.id) as count 
            FROM autism_clients c 
            JOIN autism_staff_assignments sa ON c.id = sa.client_id 
            WHERE sa.staff_id = ? AND sa.is_active = 1
        ");
        $stmt->execute([$_SESSION['staff_id']]);
        $stats['my_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM autism_session_notes 
            WHERE staff_id = ? AND session_date = date('now')
        ");
        $stmt->execute([$_SESSION['staff_id']]);
        $stats['my_sessions_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM autism_session_notes 
            WHERE staff_id = ? AND session_date >= date('now', '-7 days')
        ");
        $stmt->execute([$_SESSION['staff_id']]);
        $stats['my_sessions_week'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM autism_session_notes 
            WHERE staff_id = ? AND session_date >= date('now', '-30 days')
        ");
        $stmt->execute([$_SESSION['staff_id']]);
        $stats['my_sessions_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
    
    // Get recent session notes based on role
    if ($_SESSION['access_level'] >= 5) {
        // Administrator - see all sessions
        $stmt = $pdo->prepare("
            SELECT 
                sn.id, sn.session_date, sn.duration_minutes, sn.progress_rating,
                c.first_name || ' ' || c.last_name as client_name,
                s.first_name || ' ' || s.last_name as staff_name,
                st.service_type
            FROM autism_session_notes sn
            JOIN autism_clients c ON sn.client_id = c.id
            JOIN autism_staff_members s ON sn.staff_id = s.id
            JOIN autism_service_types st ON sn.service_type_id = st.id
            ORDER BY sn.session_date DESC, sn.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
    } elseif ($_SESSION['access_level'] >= 3) {
        // Case Manager/Supervisor - see assigned clients' sessions
        $stmt = $pdo->prepare("
            SELECT 
                sn.id, sn.session_date, sn.duration_minutes, sn.progress_rating,
                c.first_name || ' ' || c.last_name as client_name,
                s.first_name || ' ' || s.last_name as staff_name,
                st.service_type
            FROM autism_session_notes sn
            JOIN autism_clients c ON sn.client_id = c.id
            JOIN autism_staff_members s ON sn.staff_id = s.id
            JOIN autism_service_types st ON sn.service_type_id = st.id
            JOIN autism_staff_assignments sa ON c.id = sa.client_id
            WHERE sa.staff_id = ? AND sa.is_active = 1
            ORDER BY sn.session_date DESC, sn.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['staff_id']]);
    } else {
        // Direct Care Staff/Technician - see only their sessions
        $stmt = $pdo->prepare("
            SELECT 
                sn.id, sn.session_date, sn.duration_minutes, sn.progress_rating,
                c.first_name || ' ' || c.last_name as client_name,
                s.first_name || ' ' || s.last_name as staff_name,
                st.service_type
            FROM autism_session_notes sn
            JOIN autism_clients c ON sn.client_id = c.id
            JOIN autism_staff_members s ON sn.staff_id = s.id
            JOIN autism_service_types st ON sn.service_type_id = st.id
            WHERE sn.staff_id = ?
            ORDER BY sn.session_date DESC, sn.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['staff_id']]);
    }
    $recent_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get clients assigned to current user
    $my_clients = [];
    if ($_SESSION['access_level'] >= 5) {
        // Administrator - see all clients
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                c.id, c.first_name, c.last_name, c.ma_number,
                p.program_name,
                st.service_type,
                cs.weekly_units
            FROM autism_clients c
            JOIN autism_client_enrollments ce ON c.id = ce.client_id
            JOIN autism_programs p ON ce.program_id = p.id
            JOIN autism_client_services cs ON c.id = cs.client_id
            JOIN autism_service_types st ON cs.service_type_id = st.id
            WHERE cs.status = 'active'
            ORDER BY c.last_name, c.first_name
            LIMIT 10
        ");
        $stmt->execute();
    } else {
        // Other roles - see only assigned clients
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                c.id, c.first_name, c.last_name, c.ma_number,
                p.program_name,
                st.service_type,
                cs.weekly_units
            FROM autism_clients c
            JOIN autism_staff_assignments sa ON c.id = sa.client_id
            JOIN autism_client_enrollments ce ON c.id = ce.client_id
            JOIN autism_programs p ON ce.program_id = p.id
            JOIN autism_client_services cs ON c.id = cs.client_id
            JOIN autism_service_types st ON cs.service_type_id = st.id
            WHERE sa.staff_id = ? AND sa.is_active = 1 AND cs.status = 'active'
            ORDER BY c.last_name, c.first_name
        ");
        $stmt->execute([$_SESSION['staff_id']]);
    }
    $my_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Define role-based actions
$role_actions = [];
switch ($_SESSION['access_level']) {
    case 5: // Administrator
        $role_actions = [
            ['url' => 'autism_waiver_app/secure_clients.php', 'label' => '👥 All Clients', 'class' => 'action-btn'],
            ['url' => 'autism_waiver_app/billing_integration.php', 'label' => '💰 Billing System', 'class' => 'action-btn'],
            ['url' => 'autism_waiver_app/admin_role_switcher.php', 'label' => '🔧 Admin Tools', 'class' => 'action-btn secondary'],
            ['url' => 'autism_waiver_app/mobile_employee_portal.php', 'label' => '📱 Mobile Portal', 'class' => 'action-btn secondary']
        ];
        break;
    case 4: // Supervisor
        $role_actions = [
            ['url' => 'autism_waiver_app/secure_clients.php', 'label' => '👥 My Team Clients', 'class' => 'action-btn'],
            ['url' => 'autism_waiver_app/case_manager_portal.php', 'label' => '📋 Supervision', 'class' => 'action-btn'],
            ['url' => 'autism_waiver_app/billing_integration.php', 'label' => '💰 Billing Reports', 'class' => 'action-btn secondary'],
            ['url' => 'autism_waiver_app/mobile_employee_portal.php', 'label' => '📱 Mobile Portal', 'class' => 'action-btn secondary']
        ];
        break;
    case 3: // Case Manager
        $role_actions = [
            ['url' => 'autism_waiver_app/secure_clients.php', 'label' => '👥 My Clients', 'class' => 'action-btn'],
            ['url' => 'autism_waiver_app/case_manager_portal.php', 'label' => '📋 Treatment Plans', 'class' => 'action-btn'],
            ['url' => 'autism_waiver_app/mobile_employee_portal.php', 'label' => '📱 Mobile Portal', 'class' => 'action-btn secondary']
        ];
        break;
    case 2: // Direct Care Staff
        $role_actions = [
            ['url' => 'autism_waiver_app/mobile_employee_portal.php', 'label' => '📱 Mobile Portal', 'class' => 'action-btn'],
            ['url' => 'autism_waiver_app/employee_portal.php', 'label' => '📝 Session Notes', 'class' => 'action-btn'],
            ['url' => 'autism_waiver_app/secure_clients.php', 'label' => '👥 My Clients', 'class' => 'action-btn secondary']
        ];
        break;
    case 1: // Technician
        $role_actions = [
            ['url' => 'autism_waiver_app/mobile_employee_portal.php', 'label' => '📱 Mobile Portal', 'class' => 'action-btn'],
            ['url' => 'autism_waiver_app/employee_portal.php', 'label' => '📝 Session Notes', 'class' => 'action-btn secondary']
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
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            color: #1e293b;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }
        
        .session-item {
            padding: 1rem;
            border-left: 4px solid #059669;
            background: #f8fafc;
            margin-bottom: 1rem;
            border-radius: 0 8px 8px 0;
        }
        
        .session-item h4 {
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .session-meta {
            color: #64748b;
            font-size: 0.875rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .client-item {
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .client-item h4 {
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .client-meta {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .progress-fill {
            height: 100%;
            background: #059669;
            transition: width 0.3s;
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
            background: #4f46e5;
        }
        
        .restricted-notice {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            color: #92400e;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
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
</head>
<body>
    <div class="header">
        <h1>🏥 American Caregivers Inc</h1>
        <div class="user-info">
            <div class="user-badge">
                <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
            </div>
            <div class="role-badge">
                <?php echo htmlspecialchars($_SESSION['role_name']); ?> (Level <?php echo $_SESSION['access_level']; ?>)
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="role-banner">
            <h2><?php echo htmlspecialchars($_SESSION['role_name']); ?> Dashboard</h2>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! Here's your personalized view based on your role.</p>
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
                    <h3><?php echo $stats['total_clients']; ?></h3>
                    <p>Total Clients</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['active_staff']; ?></h3>
                    <p>Active Staff Members</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['sessions_today']; ?></h3>
                    <p>Session Notes Today</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['pending_claims']; ?></h3>
                    <p>Pending Claims</p>
                </div>
            <?php else: ?>
                <div class="stat-card">
                    <h3><?php echo $stats['my_clients']; ?></h3>
                    <p>My Assigned Clients</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['my_sessions_today']; ?></h3>
                    <p>My Sessions Today</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $stats['my_sessions_week']; ?></h3>
                    <p>My Sessions This Week</p>
                </div>
                <?php if ($_SESSION['access_level'] >= 3): ?>
                    <div class="stat-card">
                        <h3><?php echo $stats['active_plans']; ?></h3>
                        <p>Active Treatment Plans</p>
                    </div>
                <?php else: ?>
                    <div class="stat-card">
                        <h3><?php echo $stats['my_sessions_month']; ?></h3>
                        <p>My Sessions This Month</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div class="content-grid">
            <div class="card">
                <h2>📋 Recent Session Notes</h2>
                <?php if ($_SESSION['access_level'] < 5): ?>
                    <div class="restricted-notice">
                        ⚠️ Showing only sessions for your assigned clients
                    </div>
                <?php endif; ?>
                <?php if (empty($recent_sessions)): ?>
                    <p style="color: #64748b; text-align: center; padding: 2rem;">No session notes found</p>
                <?php else: ?>
                    <?php foreach ($recent_sessions as $session): ?>
                        <div class="session-item">
                            <h4><?php echo htmlspecialchars($session['client_name']); ?></h4>
                            <div class="session-meta">
                                <span>📅 <?php echo date('M j, Y', strtotime($session['session_date'])); ?></span>
                                <span>⏱️ <?php echo $session['duration_minutes']; ?> min</span>
                                <span>👨‍⚕️ <?php echo htmlspecialchars($session['staff_name']); ?></span>
                                <span>🎯 <?php echo htmlspecialchars($session['service_type']); ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo ($session['progress_rating'] * 20); ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>👥 <?php echo $_SESSION['access_level'] >= 5 ? 'All Clients' : 'My Assigned Clients'; ?></h2>
                <?php if (empty($my_clients)): ?>
                    <p style="color: #64748b; text-align: center; padding: 2rem;">
                        <?php echo $_SESSION['access_level'] >= 5 ? 'No clients in system' : 'No clients assigned'; ?>
                    </p>
                <?php else: ?>
                    <?php foreach ($my_clients as $client): ?>
                        <div class="client-item">
                            <h4><?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h4>
                            <div class="client-meta">
                                <div>📋 <?php echo htmlspecialchars($client['program_name']); ?></div>
                                <div>🎯 <?php echo htmlspecialchars($client['service_type']); ?></div>
                                <div>⏰ <?php echo $client['weekly_units']; ?> hours/week</div>
                                <?php if ($_SESSION['access_level'] >= 3): ?>
                                    <div>🆔 MA: <?php echo htmlspecialchars($client['ma_number']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="margin-top: 2rem; padding: 1rem; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #0ea5e9;">
            <h3 style="color: #0c4a6e; margin-bottom: 0.5rem;">🧪 Testing Environment - Role: <?php echo htmlspecialchars($_SESSION['role_name']); ?></h3>
            <p style="color: #075985; font-size: 0.875rem;">
                This dashboard shows content specific to your role (Access Level <?php echo $_SESSION['access_level']; ?>). 
                Try logging in as different users to see how the interface changes based on permissions.
            </p>
        </div>
    </div>
</body>
</html> 