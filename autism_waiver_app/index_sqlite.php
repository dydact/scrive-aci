<?php
session_start();
require_once 'config_sqlite.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = getDatabase();
    
    // Get dashboard statistics
    $stats = [];
    
    // Total clients
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_clients");
    $stats['total_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active staff
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_staff_members WHERE is_active = 1");
    $stats['active_staff'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Session notes today
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM autism_session_notes WHERE session_date = date('now')");
    $stmt->execute();
    $stats['sessions_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Recent session notes
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
    $recent_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get clients assigned to current user (if not admin)
    $my_clients = [];
    if ($_SESSION['access_level'] < 5) {
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
        $my_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
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
        }
        
        .action-btn.secondary {
            background: #6366f1;
        }
        
        .action-btn.secondary:hover {
            background: #4f46e5;
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
                (<?php echo htmlspecialchars($_SESSION['role_name']); ?>)
            </div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="quick-actions">
            <a href="clients.php" class="action-btn">👥 View Clients</a>
            <a href="new_session.php" class="action-btn">📝 New Session Note</a>
            <a href="billing_integration.php" class="action-btn secondary">💰 Billing</a>
            <a href="mobile_employee_portal.php" class="action-btn secondary">📱 Mobile Portal</a>
        </div>
        
        <div class="stats-grid">
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
                <h3><?php echo count($my_clients); ?></h3>
                <p>My Assigned Clients</p>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="card">
                <h2>📋 Recent Session Notes</h2>
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
                <h2>👥 My Assigned Clients</h2>
                <?php if (empty($my_clients)): ?>
                    <p style="color: #64748b; text-align: center; padding: 2rem;">
                        <?php echo $_SESSION['access_level'] >= 5 ? 'All clients accessible (Administrator)' : 'No clients assigned'; ?>
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
            <h3 style="color: #0c4a6e; margin-bottom: 0.5rem;">🧪 Testing Environment</h3>
            <p style="color: #075985; font-size: 0.875rem;">
                This is a complete testing environment with SQLite database. 
                All features are functional including billing integration, session notes, and role-based access control.
            </p>
        </div>
    </div>
</body>
</html> 