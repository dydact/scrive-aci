<?php
require_once '../src/init.php';
requireAuth(4); // Supervisor+ access

$currentUser = getCurrentUser();

// Redirect non-supervisors to appropriate portals
if ($currentUser['access_level'] < 4) {
    header('Location: ' . UrlManager::url('staff'));
    exit;
}

try {
    $pdo = getDatabase();
    
    // Get pending approvals count
    $stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN status = 'draft' OR status = 'completed' THEN 1 ELSE 0 END) as pending_notes,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_schedules
        FROM (
            SELECT status FROM autism_session_notes WHERE status IN ('draft', 'completed')
            UNION ALL
            SELECT 'pending' as status FROM autism_schedules WHERE status = 'scheduled' AND scheduled_date > CURDATE()
        ) as pending_items
    ");
    $pendingApprovals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get staff productivity metrics (last 7 days)
    $stmt = $pdo->query("
        SELECT 
            sm.id, sm.first_name, sm.last_name,
            COUNT(DISTINCT sn.id) as completed_sessions,
            COUNT(DISTINCT DATE(tc.clock_in)) as days_worked,
            COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(tc.clock_out, tc.clock_in))/3600), 0) as total_hours,
            COUNT(DISTINCT sn.client_id) as clients_served
        FROM autism_staff_members sm
        LEFT JOIN autism_session_notes sn ON sm.id = sn.staff_id 
            AND sn.session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            AND sn.status IN ('completed', 'approved')
        LEFT JOIN autism_time_clock tc ON sm.id = tc.employee_id
            AND tc.clock_in >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            AND tc.clock_out IS NOT NULL
        WHERE sm.status = 'active'
        GROUP BY sm.id
        ORDER BY total_hours DESC
        LIMIT 10
    ");
    $staffMetrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get client service hours overview (current month)
    $stmt = $pdo->query("
        SELECT 
            c.id, c.first_name, c.last_name, c.ma_number,
            COUNT(DISTINCT sn.id) as session_count,
            SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600) as total_hours,
            COUNT(DISTINCT sn.staff_id) as staff_count,
            MAX(sn.session_date) as last_service_date
        FROM autism_clients c
        LEFT JOIN autism_session_notes sn ON c.id = sn.client_id
            AND sn.session_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
            AND sn.status IN ('completed', 'approved')
        WHERE c.status = 'active'
        GROUP BY c.id
        ORDER BY total_hours DESC
        LIMIT 10
    ");
    $clientServiceHours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get billing status summary (current month)
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_claims,
            SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_claims,
            SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted_claims,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_claims,
            SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denied_claims,
            SUM(total_amount) as total_billed,
            SUM(CASE WHEN status = 'paid' THEN payment_amount ELSE 0 END) as total_collected
        FROM autism_claims
        WHERE service_date_from >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ");
    $billingStats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'total_claims' => 0,
        'draft_claims' => 0,
        'submitted_claims' => 0,
        'paid_claims' => 0,
        'denied_claims' => 0,
        'total_billed' => 0,
        'total_collected' => 0
    ];
    
    // Get recent activities requiring attention
    $stmt = $pdo->query("
        SELECT 
            'session_note' as type,
            sn.id,
            CONCAT(c.first_name, ' ', c.last_name) as client_name,
            CONCAT(s.first_name, ' ', s.last_name) as staff_name,
            sn.session_date as activity_date,
            sn.status,
            sn.created_at
        FROM autism_session_notes sn
        JOIN autism_clients c ON sn.client_id = c.id
        JOIN autism_staff_members s ON sn.staff_id = s.id
        WHERE sn.status IN ('draft', 'completed')
        AND sn.supervisor_review_needed = 1
        
        UNION ALL
        
        SELECT 
            'time_entry' as type,
            tc.id,
            CONCAT(c.first_name, ' ', c.last_name) as client_name,
            CONCAT(s.first_name, ' ', s.last_name) as staff_name,
            DATE(tc.clock_in) as activity_date,
            tc.status,
            tc.clock_in as created_at
        FROM autism_time_clock tc
        JOIN autism_staff_members s ON tc.employee_id = s.id
        LEFT JOIN autism_clients c ON tc.client_id = c.id
        WHERE tc.status = 'clocked_out'
        AND tc.approved_by IS NULL
        
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get schedule overview for next 7 days
    $stmt = $pdo->query("
        SELECT 
            DATE(scheduled_date) as schedule_date,
            COUNT(*) as total_sessions,
            COUNT(DISTINCT staff_id) as staff_count,
            COUNT(DISTINCT client_id) as client_count
        FROM autism_schedules
        WHERE scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND status IN ('scheduled', 'confirmed')
        GROUP BY DATE(scheduled_date)
        ORDER BY schedule_date
    ");
    $upcomingSchedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Supervisor portal error: " . $e->getMessage());
    // Set default values if queries fail
    $pendingApprovals = ['pending_notes' => 0, 'pending_schedules' => 0];
    $staffMetrics = [];
    $clientServiceHours = [];
    $billingStats = [
        'total_claims' => 0,
        'draft_claims' => 0,
        'submitted_claims' => 0,
        'paid_claims' => 0,
        'denied_claims' => 0,
        'total_billed' => 0,
        'total_collected' => 0
    ];
    $recentActivities = [];
    $upcomingSchedule = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Portal - ACI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e40af;
            --secondary-color: #dc2626;
            --success-color: #16a34a;
            --warning-color: #f59e0b;
            --info-color: #0ea5e9;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        
        .navbar {
            background: white !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .brand-a { color: var(--primary-color); }
        .brand-c { color: var(--secondary-color); }
        .brand-i { color: var(--success-color); }
        
        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
        }
        
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .metric-label {
            color: #64748b;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .metric-icon.primary { background: rgba(30, 64, 175, 0.1); color: var(--primary-color); }
        .metric-icon.success { background: rgba(22, 163, 74, 0.1); color: var(--success-color); }
        .metric-icon.warning { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
        .metric-icon.info { background: rgba(14, 165, 233, 0.1); color: var(--info-color); }
        .metric-icon.danger { background: rgba(220, 38, 38, 0.1); color: var(--secondary-color); }
        
        .section-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        
        .section-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin: 0;
        }
        
        .table {
            margin: 0;
        }
        
        .table th {
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .status-draft { background: #e5e7eb; color: #374151; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-approved { background: #a7f3d0; color: #047857; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-denied { background: #fee2e2; color: #991b1b; }
        .status-paid { background: #cffafe; color: #155e75; }
        
        .activity-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.2s;
        }
        
        .activity-item:hover {
            background: #f9fafb;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-type {
            display: inline-block;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            margin-right: 0.75rem;
        }
        
        .activity-type.note { background: rgba(14, 165, 233, 0.1); color: var(--info-color); }
        .activity-type.time { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
        
        .quick-action-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }
        
        .quick-action-btn.primary {
            background: var(--primary-color);
            color: white;
        }
        
        .quick-action-btn.primary:hover {
            background: #1e3a8a;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .productivity-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .productivity-fill {
            height: 100%;
            background: var(--success-color);
            transition: width 0.3s;
        }
        
        @media (max-width: 768px) {
            .metric-value { font-size: 2rem; }
            .metric-card { padding: 1rem; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= UrlManager::url('dashboard') ?>">
                <span class="brand-a">A</span><span class="brand-c">C</span><span class="brand-i">I</span>
                <span class="ms-2 text-muted fw-normal">Supervisor Portal</span>
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-tie me-1"></i>
                    <?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>
                </span>
                <a class="btn btn-outline-danger btn-sm" href="/logout">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid p-4">
        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-value"><?= $pendingApprovals['pending_notes'] ?? 0 ?></div>
                            <div class="metric-label">Pending Notes</div>
                        </div>
                        <div class="metric-icon warning">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-value"><?= count($staffMetrics) ?></div>
                            <div class="metric-label">Active Staff</div>
                        </div>
                        <div class="metric-icon primary">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-value">$<?= number_format($billingStats['total_billed'] ?? 0, 0) ?></div>
                            <div class="metric-label">Monthly Billing</div>
                        </div>
                        <div class="metric-icon success">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="metric-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="metric-value"><?= $billingStats['denied_claims'] ?? 0 ?></div>
                            <div class="metric-label">Denied Claims</div>
                        </div>
                        <div class="metric-icon danger">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Content Grid -->
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Pending Approvals -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-tasks me-2 text-warning"></i>
                            Pending Approvals
                        </h2>
                        <a href="<?= UrlManager::url('reports', ['tab' => 'approvals']) ?>" class="btn btn-sm btn-outline-primary">
                            View All
                        </a>
                    </div>
                    <div class="p-0">
                        <?php if (empty($recentActivities)): ?>
                            <div class="empty-state">
                                <i class="fas fa-check-circle"></i>
                                <p class="mb-0">No pending approvals at this time</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="activity-item d-flex align-items-center">
                                    <div class="activity-type <?= $activity['type'] === 'session_note' ? 'note' : 'time' ?>">
                                        <i class="fas <?= $activity['type'] === 'session_note' ? 'fa-file-alt' : 'fa-clock' ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($activity['client_name'] ?? 'N/A') ?>
                                            - <?= htmlspecialchars($activity['staff_name']) ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?= date('m/d/Y', strtotime($activity['activity_date'])) ?>
                                            • <?= ucfirst(str_replace('_', ' ', $activity['type'])) ?>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="status-badge status-<?= $activity['status'] ?>">
                                            <?= ucfirst($activity['status']) ?>
                                        </span>
                                    </div>
                                    <div class="ms-3">
                                        <a href="#" class="btn btn-sm btn-outline-success" 
                                           onclick="approveItem('<?= $activity['type'] ?>', <?= $activity['id'] ?>)">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-primary"
                                           onclick="viewItem('<?= $activity['type'] ?>', <?= $activity['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Staff Productivity -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-chart-line me-2 text-primary"></i>
                            Staff Productivity (Last 7 Days)
                        </h2>
                        <a href="<?= UrlManager::url('reports', ['tab' => 'staff']) ?>" class="btn btn-sm btn-outline-primary">
                            Detailed Report
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Staff Member</th>
                                    <th>Sessions</th>
                                    <th>Hours</th>
                                    <th>Clients</th>
                                    <th>Productivity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staffMetrics as $staff): ?>
                                    <?php 
                                        $maxHours = 40; // Expected hours per week
                                        $productivity = min(100, ($staff['total_hours'] / $maxHours) * 100);
                                    ?>
                                    <tr>
                                        <td class="fw-semibold">
                                            <?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?>
                                        </td>
                                        <td><?= $staff['completed_sessions'] ?></td>
                                        <td><?= number_format($staff['total_hours'], 1) ?>h</td>
                                        <td><?= $staff['clients_served'] ?></td>
                                        <td>
                                            <div class="productivity-bar">
                                                <div class="productivity-fill" style="width: <?= $productivity ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?= round($productivity) ?>%</small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Client Service Hours -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-users me-2 text-success"></i>
                            Client Service Hours (Current Month)
                        </h2>
                        <a href="<?= UrlManager::url('clients') ?>" class="btn btn-sm btn-outline-primary">
                            All Clients
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>MA Number</th>
                                    <th>Sessions</th>
                                    <th>Hours</th>
                                    <th>Last Service</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientServiceHours as $client): ?>
                                    <tr>
                                        <td class="fw-semibold">
                                            <?= htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($client['ma_number'] ?? 'N/A') ?></td>
                                        <td><?= $client['session_count'] ?></td>
                                        <td><?= number_format($client['total_hours'] ?? 0, 1) ?>h</td>
                                        <td>
                                            <?= $client['last_service_date'] ? date('m/d/Y', strtotime($client['last_service_date'])) : 'N/A' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-bolt me-2 text-warning"></i>
                            Quick Actions
                        </h2>
                    </div>
                    <div class="p-3">
                        <div class="d-grid gap-2">
                            <a href="<?= UrlManager::url('reports', ['tab' => 'approvals']) ?>" class="quick-action-btn primary">
                                <i class="fas fa-clipboard-check me-2"></i>
                                Review Pending Notes
                            </a>
                            <a href="<?= UrlManager::url('schedule_manager') ?>" class="quick-action-btn primary">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Manage Schedules
                            </a>
                            <a href="<?= UrlManager::url('reports', ['tab' => 'staff']) ?>" class="quick-action-btn primary">
                                <i class="fas fa-chart-bar me-2"></i>
                                Generate Reports
                            </a>
                            <a href="<?= UrlManager::url('admin_employees') ?>" class="quick-action-btn primary">
                                <i class="fas fa-user-cog me-2"></i>
                                Manage Staff
                            </a>
                            <a href="<?= UrlManager::url('billing_dashboard') ?>" class="quick-action-btn primary">
                                <i class="fas fa-file-invoice-dollar me-2"></i>
                                Billing Overview
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Billing Summary -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-dollar-sign me-2 text-success"></i>
                            Billing Summary
                        </h2>
                    </div>
                    <div class="p-3">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Total Claims</span>
                                <span class="fw-semibold"><?= $billingStats['total_claims'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Draft</span>
                                <span class="status-badge status-draft"><?= $billingStats['draft_claims'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Submitted</span>
                                <span class="status-badge status-pending"><?= $billingStats['submitted_claims'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Paid</span>
                                <span class="status-badge status-paid"><?= $billingStats['paid_claims'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Denied</span>
                                <span class="status-badge status-denied"><?= $billingStats['denied_claims'] ?></span>
                            </div>
                        </div>
                        <hr>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Total Billed</span>
                                <span class="fw-semibold">$<?= number_format($billingStats['total_billed'] ?? 0, 2) ?></span>
                            </div>
                        </div>
                        <div class="mb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Collected</span>
                                <span class="fw-semibold text-success">$<?= number_format($billingStats['total_collected'] ?? 0, 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Schedule -->
                <div class="section-card">
                    <div class="section-header">
                        <h2 class="section-title">
                            <i class="fas fa-calendar me-2 text-info"></i>
                            Schedule Overview
                        </h2>
                    </div>
                    <div class="p-3">
                        <?php if (empty($upcomingSchedule)): ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                <p class="mb-0">No upcoming schedule data</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($upcomingSchedule as $day): ?>
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold">
                                                <?= date('l, M j', strtotime($day['schedule_date'])) ?>
                                            </div>
                                            <div class="text-muted small">
                                                <?= $day['staff_count'] ?> staff • <?= $day['client_count'] ?> clients
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-primary rounded-pill">
                                                <?= $day['total_sessions'] ?> sessions
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function approveItem(type, id) {
            if (confirm('Are you sure you want to approve this item?')) {
                // Create form data
                const formData = new FormData();
                formData.append('action', 'approve');
                formData.append('type', type);
                formData.append('id', id);
                
                // Send approval request
                fetch('/api/supervisor-approve', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to approve: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to approve item');
                });
            }
        }
        
        function viewItem(type, id) {
            if (type === 'session_note') {
                window.location.href = '/staff/notes/edit/' + id;
            } else if (type === 'time_entry') {
                window.location.href = '/reports?tab=timesheet&entry_id=' + id;
            }
        }
        
        // Auto-refresh every 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>