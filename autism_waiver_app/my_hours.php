<?php
session_start();
require_once '../src/config.php';
require_once '../src/openemr_integration.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$error = '';
$success = '';

// Get date range from request or default to current pay period
$start_date = $_GET['start'] ?? date('Y-m-01'); // First of month
$end_date = $_GET['end'] ?? date('Y-m-t'); // Last of month

try {
    $pdo = getDatabase();
    
    // Get current staff member ID
    $stmt = $pdo->prepare("SELECT id, full_name FROM autism_staff_members WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff) {
        throw new Exception("Staff member profile not found. Please contact administrator.");
    }
    
    $staff_id = $staff['id'];
    $staff_name = $staff['full_name'];
    
    // Get all sessions for the period
    $stmt = $pdo->prepare("
        SELECT s.*, 
               c.first_name, c.last_name, c.ma_number,
               st.service_name, st.service_code, st.rate
        FROM autism_sessions s
        JOIN autism_clients c ON s.client_id = c.id
        LEFT JOIN autism_service_types st ON s.service_type_id = st.id
        WHERE s.staff_id = ? 
        AND s.session_date BETWEEN ? AND ?
        AND s.status IN ('completed', 'billed', 'paid')
        ORDER BY s.session_date DESC, s.start_time DESC
    ");
    $stmt->execute([$staff_id, $start_date, $end_date]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $total_hours = 0;
    $total_sessions = 0;
    $billable_hours = 0;
    $unbilled_hours = 0;
    $estimated_pay = 0;
    
    $hours_by_service = [];
    $hours_by_client = [];
    $daily_hours = [];
    
    foreach ($sessions as &$session) {
        // Calculate session hours
        $hours = floatval($session['duration_hours'] ?? 0);
        if ($hours == 0 && $session['start_time'] && $session['end_time']) {
            $start = new DateTime($session['session_date'] . ' ' . $session['start_time']);
            $end = new DateTime($session['session_date'] . ' ' . $session['end_time']);
            $duration = $start->diff($end);
            $hours = $duration->h + ($duration->i / 60);
            $session['duration_hours'] = $hours;
        }
        
        $total_hours += $hours;
        $total_sessions++;
        
        // Track billable vs unbilled
        if ($session['billing_status'] == 'unbilled') {
            $unbilled_hours += $hours;
        } else {
            $billable_hours += $hours;
        }
        
        // Estimate pay (using service rate or default)
        $rate = floatval($session['rate'] ?? 20); // Default $20/hour if no rate
        $estimated_pay += ($hours * $rate);
        
        // Group by service type
        $service_key = $session['service_name'] ?? 'Other';
        if (!isset($hours_by_service[$service_key])) {
            $hours_by_service[$service_key] = 0;
        }
        $hours_by_service[$service_key] += $hours;
        
        // Group by client
        $client_key = $session['first_name'] . ' ' . $session['last_name'];
        if (!isset($hours_by_client[$client_key])) {
            $hours_by_client[$client_key] = 0;
        }
        $hours_by_client[$client_key] += $hours;
        
        // Group by date
        $date_key = $session['session_date'];
        if (!isset($daily_hours[$date_key])) {
            $daily_hours[$date_key] = 0;
        }
        $daily_hours[$date_key] += $hours;
    }
    
    // Sort summaries
    arsort($hours_by_service);
    arsort($hours_by_client);
    krsort($daily_hours);
    
    // Get previous period for comparison
    $prev_start = date('Y-m-d', strtotime($start_date . ' -1 month'));
    $prev_end = date('Y-m-d', strtotime($end_date . ' -1 month'));
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count, SUM(duration_hours) as hours
        FROM autism_sessions
        WHERE staff_id = ? 
        AND session_date BETWEEN ? AND ?
        AND status IN ('completed', 'billed', 'paid')
    ");
    $stmt->execute([$staff_id, $prev_start, $prev_end]);
    $prev_period = $stmt->fetch(PDO::FETCH_ASSOC);
    $prev_hours = floatval($prev_period['hours'] ?? 0);
    
    // Calculate change percentage
    $hours_change = 0;
    if ($prev_hours > 0) {
        $hours_change = (($total_hours - $prev_hours) / $prev_hours) * 100;
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Export functionality
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="hours_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Client', 'Service', 'Start Time', 'End Time', 'Duration (hrs)', 'Status', 'Billing Status']);
    
    foreach ($sessions as $session) {
        fputcsv($output, [
            $session['session_date'],
            $session['first_name'] . ' ' . $session['last_name'],
            $session['service_name'] ?? 'N/A',
            $session['start_time'],
            $session['end_time'],
            number_format($session['duration_hours'], 2),
            $session['status'],
            $session['billing_status']
        ]);
    }
    
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Hours - ACI</title>
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
            margin-bottom: 2rem;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .header h1 {
            font-size: 1.5rem;
            color: #1e40af;
        }
        
        .nav-links {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            color: #64748b;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .nav-links a:hover,
        .nav-links a.active {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .date-filter {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filter-form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #475569;
        }
        
        .filter-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-success {
            background: #16a34a;
            color: white;
        }
        
        .btn-success:hover {
            background: #15803d;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e40af;
        }
        
        .stat-change {
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .stat-change.positive {
            color: #16a34a;
        }
        
        .stat-change.negative {
            color: #dc2626;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .sessions-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .sessions-table th {
            text-align: left;
            padding: 0.75rem;
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            font-size: 0.875rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .sessions-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .sessions-table tr:hover {
            background: #f8fafc;
        }
        
        .client-name {
            font-weight: 500;
            color: #1e293b;
        }
        
        .service-code {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: #dbeafe;
            color: #2563eb;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-completed {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-billed {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .status-paid {
            background: #d9f99d;
            color: #65a30d;
        }
        
        .summary-list {
            list-style: none;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-label {
            color: #64748b;
        }
        
        .summary-value {
            font-weight: 600;
            color: #1e293b;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 640px) {
            .sessions-table {
                font-size: 0.875rem;
            }
            
            .sessions-table th,
            .sessions-table td {
                padding: 0.5rem;
            }
            
            .hide-mobile {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1>My Hours</h1>
                <div class="nav-links">
                    <a href="/staff/dashboard">Dashboard</a>
                    <a href="/staff/schedule">Schedule</a>
                    <a href="/staff/clock">Time Clock</a>
                    <a href="/staff/notes">Session Notes</a>
                    <a href="/staff/hours" class="active">My Hours</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <!-- Date Filter -->
        <div class="date-filter">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="start">Start Date</label>
                    <input type="date" id="start" name="start" value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="filter-group">
                    <label for="end">End Date</label>
                    <input type="date" id="end" name="end" value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <button type="submit" class="btn btn-primary">Update Report</button>
                <a href="?start=<?= $start_date ?>&end=<?= $end_date ?>&export=csv" class="btn btn-success export-btn">
                    <span>📊</span> Export CSV
                </a>
            </form>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Hours</div>
                <div class="stat-value"><?= number_format($total_hours, 1) ?></div>
                <?php if ($hours_change != 0): ?>
                    <div class="stat-change <?= $hours_change > 0 ? 'positive' : 'negative' ?>">
                        <?= $hours_change > 0 ? '↑' : '↓' ?> <?= abs(number_format($hours_change, 1)) ?>% from last period
                    </div>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Sessions</div>
                <div class="stat-value"><?= $total_sessions ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Billable Hours</div>
                <div class="stat-value"><?= number_format($billable_hours, 1) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Unbilled Hours</div>
                <div class="stat-value"><?= number_format($unbilled_hours, 1) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Estimated Pay</div>
                <div class="stat-value">$<?= number_format($estimated_pay, 2) ?></div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="content-grid">
            <!-- Sessions List -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Session Details</h2>
                    <span><?= count($sessions) ?> sessions</span>
                </div>
                <div class="card-body">
                    <?php if (empty($sessions)): ?>
                        <p style="text-align: center; color: #64748b; padding: 2rem;">No sessions found for this period.</p>
                    <?php else: ?>
                        <table class="sessions-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Client</th>
                                    <th class="hide-mobile">Service</th>
                                    <th>Time</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessions as $session): ?>
                                    <tr>
                                        <td><?= date('m/d', strtotime($session['session_date'])) ?></td>
                                        <td>
                                            <div class="client-name">
                                                <?= htmlspecialchars($session['first_name'] . ' ' . $session['last_name']) ?>
                                            </div>
                                            <div class="hide-mobile" style="font-size: 0.75rem; color: #64748b;">
                                                <?= htmlspecialchars($session['ma_number'] ?? '') ?>
                                            </div>
                                        </td>
                                        <td class="hide-mobile">
                                            <?php if ($session['service_code']): ?>
                                                <span class="service-code"><?= htmlspecialchars($session['service_code']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= date('g:i A', strtotime($session['start_time'])) ?>
                                        </td>
                                        <td><?= number_format($session['duration_hours'], 1) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $session['billing_status'] ?>">
                                                <?= ucfirst($session['billing_status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Summaries -->
            <div>
                <!-- Hours by Service -->
                <div class="card" style="margin-bottom: 1rem;">
                    <div class="card-header">
                        <h3 class="card-title">Hours by Service</h3>
                    </div>
                    <div class="card-body">
                        <ul class="summary-list">
                            <?php foreach ($hours_by_service as $service => $hours): ?>
                                <li class="summary-item">
                                    <span class="summary-label"><?= htmlspecialchars($service) ?></span>
                                    <span class="summary-value"><?= number_format($hours, 1) ?> hrs</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <!-- Top Clients -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Top Clients</h3>
                    </div>
                    <div class="card-body">
                        <ul class="summary-list">
                            <?php 
                            $top_clients = array_slice($hours_by_client, 0, 5);
                            foreach ($top_clients as $client => $hours): 
                            ?>
                                <li class="summary-item">
                                    <span class="summary-label"><?= htmlspecialchars($client) ?></span>
                                    <span class="summary-value"><?= number_format($hours, 1) ?> hrs</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>