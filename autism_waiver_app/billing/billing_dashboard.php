<?php
session_start();
require_once '../auth_helper.php';
require_once '../config_sqlite.php';

// Ensure user is logged in and has appropriate role
ensure_logged_in();
$user_role = $_SESSION['role'] ?? '';

// Check if user has billing access
if (!in_array($user_role, ['Administrator', 'Billing Manager', 'Financial Analyst', 'Executive'])) {
    header('Location: ../simple_login.php');
    exit();
}

// Get date range parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // Default to first day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Default to last day of current month
$comparison_start = date('Y-m-01', strtotime('-1 month', strtotime($start_date)));
$comparison_end = date('Y-m-t', strtotime('-1 month', strtotime($start_date)));

// Initialize metrics
$metrics = [
    'total_revenue' => 0,
    'collection_rate' => 0,
    'denial_rate' => 0,
    'avg_days_to_payment' => 0,
    'claims_submitted' => 0,
    'claims_paid' => 0,
    'claims_denied' => 0,
    'claims_pending' => 0,
    'ar_current' => 0,
    'ar_30_days' => 0,
    'ar_60_days' => 0,
    'ar_90_days' => 0,
    'ar_over_90' => 0
];

// Fetch KPI data
try {
    // Total Revenue
    $stmt = $pdo->prepare("
        SELECT SUM(amount_paid) as total_revenue 
        FROM billing_claims 
        WHERE payment_date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $metrics['total_revenue'] = $stmt->fetchColumn() ?: 0;

    // Claims Metrics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_claims,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_claims,
            SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denied_claims,
            SUM(CASE WHEN status IN ('submitted', 'pending') THEN 1 ELSE 0 END) as pending_claims
        FROM billing_claims 
        WHERE date_submitted BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $claim_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $metrics['claims_submitted'] = $claim_data['total_claims'];
    $metrics['claims_paid'] = $claim_data['paid_claims'];
    $metrics['claims_denied'] = $claim_data['denied_claims'];
    $metrics['claims_pending'] = $claim_data['pending_claims'];
    
    // Calculate rates
    if ($metrics['claims_submitted'] > 0) {
        $metrics['collection_rate'] = round(($metrics['claims_paid'] / $metrics['claims_submitted']) * 100, 1);
        $metrics['denial_rate'] = round(($metrics['claims_denied'] / $metrics['claims_submitted']) * 100, 1);
    }

    // Average Days to Payment
    $stmt = $pdo->prepare("
        SELECT AVG(JULIANDAY(payment_date) - JULIANDAY(date_submitted)) as avg_days
        FROM billing_claims 
        WHERE status = 'paid' 
        AND payment_date BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $metrics['avg_days_to_payment'] = round($stmt->fetchColumn() ?: 0);

    // A/R Aging
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN JULIANDAY(?) - JULIANDAY(date_submitted) <= 30 THEN amount_billed ELSE 0 END) as ar_current,
            SUM(CASE WHEN JULIANDAY(?) - JULIANDAY(date_submitted) BETWEEN 31 AND 60 THEN amount_billed ELSE 0 END) as ar_30,
            SUM(CASE WHEN JULIANDAY(?) - JULIANDAY(date_submitted) BETWEEN 61 AND 90 THEN amount_billed ELSE 0 END) as ar_60,
            SUM(CASE WHEN JULIANDAY(?) - JULIANDAY(date_submitted) BETWEEN 91 AND 120 THEN amount_billed ELSE 0 END) as ar_90,
            SUM(CASE WHEN JULIANDAY(?) - JULIANDAY(date_submitted) > 120 THEN amount_billed ELSE 0 END) as ar_over_90
        FROM billing_claims 
        WHERE status IN ('submitted', 'pending')
    ");
    $stmt->execute([$today, $today, $today, $today, $today]);
    $ar_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $metrics['ar_current'] = $ar_data['ar_current'] ?: 0;
    $metrics['ar_30_days'] = $ar_data['ar_30'] ?: 0;
    $metrics['ar_60_days'] = $ar_data['ar_60'] ?: 0;
    $metrics['ar_90_days'] = $ar_data['ar_90'] ?: 0;
    $metrics['ar_over_90'] = $ar_data['ar_over_90'] ?: 0;

    // Payer Performance
    $stmt = $pdo->prepare("
        SELECT 
            p.name as payer_name,
            COUNT(bc.id) as claim_count,
            SUM(bc.amount_paid) as total_paid,
            AVG(JULIANDAY(bc.payment_date) - JULIANDAY(bc.date_submitted)) as avg_payment_days,
            SUM(CASE WHEN bc.status = 'denied' THEN 1 ELSE 0 END) as denials
        FROM billing_claims bc
        JOIN payers p ON bc.payer_id = p.id
        WHERE bc.date_submitted BETWEEN ? AND ?
        GROUP BY p.id
        ORDER BY total_paid DESC
        LIMIT 10
    ");
    $stmt->execute([$start_date, $end_date]);
    $payer_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recent Activity
    $stmt = $pdo->prepare("
        SELECT 
            bc.*,
            c.name as client_name,
            p.name as payer_name
        FROM billing_claims bc
        JOIN clients c ON bc.client_id = c.id
        JOIN payers p ON bc.payer_id = p.id
        ORDER BY bc.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Alerts and Notifications
    $alerts = [];
    
    // Timely filing alerts (claims approaching 90 days)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM billing_claims 
        WHERE status IN ('submitted', 'pending') 
        AND JULIANDAY(?) - JULIANDAY(date_of_service) BETWEEN 75 AND 90
    ");
    $stmt->execute([$today]);
    $timely_filing_count = $stmt->fetchColumn();
    if ($timely_filing_count > 0) {
        $alerts[] = ['type' => 'warning', 'message' => "$timely_filing_count claims approaching timely filing deadline"];
    }

    // Authorization expiration alerts
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM authorizations 
        WHERE end_date BETWEEN ? AND DATE(?, '+30 days')
        AND status = 'active'
    ");
    $stmt->execute([$today, $today]);
    $auth_expiring_count = $stmt->fetchColumn();
    if ($auth_expiring_count > 0) {
        $alerts[] = ['type' => 'info', 'message' => "$auth_expiring_count authorizations expiring within 30 days"];
    }

    // High denial rate alert
    if ($metrics['denial_rate'] > 10) {
        $alerts[] = ['type' => 'danger', 'message' => "Denial rate (" . $metrics['denial_rate'] . "%) exceeds threshold"];
    }

    // Monthly Trends (last 6 months)
    $trends = [];
    for ($i = 5; $i >= 0; $i--) {
        $trend_start = date('Y-m-01', strtotime("-$i months"));
        $trend_end = date('Y-m-t', strtotime("-$i months"));
        
        $stmt = $pdo->prepare("
            SELECT 
                SUM(amount_paid) as revenue,
                COUNT(*) as claims,
                SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denials
            FROM billing_claims 
            WHERE date_submitted BETWEEN ? AND ?
        ");
        $stmt->execute([$trend_start, $trend_end]);
        $trend_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $trends[] = [
            'month' => date('M Y', strtotime($trend_start)),
            'revenue' => $trend_data['revenue'] ?: 0,
            'claims' => $trend_data['claims'] ?: 0,
            'denial_rate' => $trend_data['claims'] > 0 ? round(($trend_data['denials'] / $trend_data['claims']) * 100, 1) : 0
        ];
    }

    // Outstanding Tasks
    $tasks = [];
    
    // Claims requiring follow-up
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM billing_claims 
        WHERE status = 'pending' 
        AND JULIANDAY(?) - JULIANDAY(date_submitted) > 30
    ");
    $stmt->execute([$today]);
    $followup_count = $stmt->fetchColumn();
    if ($followup_count > 0) {
        $tasks[] = ['title' => 'Claims requiring follow-up', 'count' => $followup_count, 'link' => 'claims_management.php?filter=pending'];
    }

    // Denied claims to appeal
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM billing_claims 
        WHERE status = 'denied' 
        AND appeal_status IS NULL
    ");
    $stmt->execute();
    $appeal_count = $stmt->fetchColumn();
    if ($appeal_count > 0) {
        $tasks[] = ['title' => 'Denied claims to review', 'count' => $appeal_count, 'link' => 'denial_management.php'];
    }

    // Missing documentation
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM billing_claims 
        WHERE status = 'draft' 
        AND documentation_complete = 0
    ");
    $stmt->execute();
    $missing_docs = $stmt->fetchColumn();
    if ($missing_docs > 0) {
        $tasks[] = ['title' => 'Claims with missing documentation', 'count' => $missing_docs, 'link' => 'claims_management.php?filter=incomplete'];
    }

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
}

// Calculate total A/R
$total_ar = $metrics['ar_current'] + $metrics['ar_30_days'] + $metrics['ar_60_days'] + $metrics['ar_90_days'] + $metrics['ar_over_90'];

// Goals vs Actual (example goals - should be configurable)
$goals = [
    'revenue' => 500000,
    'collection_rate' => 95,
    'denial_rate' => 5,
    'days_to_payment' => 21
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Dashboard - ACI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
            border: 1px solid #e9ecef;
        }
        
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0.5rem 0;
            color: #2c3e50;
        }
        
        .metric-label {
            color: #6c757d;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .metric-change {
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .metric-change.positive {
            color: #28a745;
        }
        
        .metric-change.negative {
            color: #dc3545;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }
        
        .alert-custom {
            border-radius: 8px;
            border-left: 4px solid;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
        }
        
        .alert-custom.warning {
            background-color: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        .alert-custom.danger {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .alert-custom.info {
            background-color: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        
        .quick-action-btn {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            text-decoration: none;
            color: #495057;
            transition: all 0.2s;
            display: block;
            height: 100%;
        }
        
        .quick-action-btn:hover {
            border-color: #667eea;
            color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(102, 126, 234, 0.1);
        }
        
        .quick-action-btn i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            transition: background-color 0.2s;
        }
        
        .activity-item:hover {
            background-color: #f8f9fa;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .task-item {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .task-count {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.875rem;
        }
        
        .payer-row {
            transition: background-color 0.2s;
        }
        
        .payer-row:hover {
            background-color: #f8f9fa;
        }
        
        .progress-bar-custom {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.6s ease;
        }
        
        .date-selector {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .export-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .export-btn:hover {
            background: #218838;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        @media (max-width: 768px) {
            .metric-value {
                font-size: 2rem;
            }
            
            .dashboard-header {
                padding: 1rem 0;
            }
        }
    </style>
</head>
<body>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="mb-0">Billing Operations Center</h1>
                    <p class="mb-0 opacity-75">Real-time insights and performance metrics</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <button class="export-btn" onclick="exportDashboard()">
                        <i class="fas fa-download me-2"></i>Export Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Date Range Selector -->
        <div class="date-selector mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Update Dashboard
                    </button>
                </div>
            </form>
        </div>

        <!-- Alerts Section -->
        <?php if (!empty($alerts)): ?>
        <div class="mb-4">
            <?php foreach ($alerts as $alert): ?>
            <div class="alert-custom <?php echo $alert['type']; ?>">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($alert['message']); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Key Performance Indicators -->
        <h2 class="section-title">Key Performance Indicators</h2>
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="metric-card">
                    <div class="metric-label">Total Revenue</div>
                    <div class="metric-value">$<?php echo number_format($metrics['total_revenue'], 0); ?></div>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i> 12.5% vs last period
                    </div>
                    <div class="mt-3">
                        <div class="progress-bar-custom">
                            <div class="progress-fill bg-success" style="width: <?php echo min(($metrics['total_revenue'] / $goals['revenue']) * 100, 100); ?>%"></div>
                        </div>
                        <small class="text-muted">Goal: $<?php echo number_format($goals['revenue'], 0); ?></small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="metric-card">
                    <div class="metric-label">Collection Rate</div>
                    <div class="metric-value"><?php echo $metrics['collection_rate']; ?>%</div>
                    <div class="metric-change <?php echo $metrics['collection_rate'] >= $goals['collection_rate'] ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-<?php echo $metrics['collection_rate'] >= $goals['collection_rate'] ? 'check' : 'times'; ?>"></i> 
                        Goal: <?php echo $goals['collection_rate']; ?>%
                    </div>
                    <div class="mt-3">
                        <small class="text-muted"><?php echo $metrics['claims_paid']; ?> of <?php echo $metrics['claims_submitted']; ?> claims</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="metric-card">
                    <div class="metric-label">Denial Rate</div>
                    <div class="metric-value"><?php echo $metrics['denial_rate']; ?>%</div>
                    <div class="metric-change <?php echo $metrics['denial_rate'] <= $goals['denial_rate'] ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-<?php echo $metrics['denial_rate'] <= $goals['denial_rate'] ? 'check' : 'times'; ?>"></i> 
                        Goal: ≤<?php echo $goals['denial_rate']; ?>%
                    </div>
                    <div class="mt-3">
                        <small class="text-muted"><?php echo $metrics['claims_denied']; ?> denied claims</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="metric-card">
                    <div class="metric-label">Avg Days to Payment</div>
                    <div class="metric-value"><?php echo $metrics['avg_days_to_payment']; ?></div>
                    <div class="metric-change <?php echo $metrics['avg_days_to_payment'] <= $goals['days_to_payment'] ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-<?php echo $metrics['avg_days_to_payment'] <= $goals['days_to_payment'] ? 'check' : 'times'; ?>"></i> 
                        Goal: ≤<?php echo $goals['days_to_payment']; ?> days
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">Payment velocity</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2 class="section-title">Quick Actions</h2>
        <div class="row mb-4">
            <div class="col-6 col-md-2 mb-3">
                <a href="claims_submission.php" class="quick-action-btn">
                    <i class="fas fa-file-invoice"></i>
                    <div>Submit Claim</div>
                </a>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <a href="payment_posting.php" class="quick-action-btn">
                    <i class="fas fa-dollar-sign"></i>
                    <div>Post Payment</div>
                </a>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <a href="denial_management.php" class="quick-action-btn">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>Manage Denials</div>
                </a>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <a href="reports.php" class="quick-action-btn">
                    <i class="fas fa-chart-bar"></i>
                    <div>Run Reports</div>
                </a>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <a href="authorization_tracking.php" class="quick-action-btn">
                    <i class="fas fa-key"></i>
                    <div>Authorizations</div>
                </a>
            </div>
            <div class="col-6 col-md-2 mb-3">
                <a href="edi_processing.php" class="quick-action-btn">
                    <i class="fas fa-exchange-alt"></i>
                    <div>EDI Center</div>
                </a>
            </div>
        </div>

        <div class="row">
            <!-- A/R Aging Chart -->
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h3 class="h5 mb-3">Accounts Receivable Aging</h3>
                    <canvas id="arAgingChart"></canvas>
                    <div class="mt-3">
                        <strong>Total A/R: $<?php echo number_format($total_ar, 2); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Monthly Revenue Trend -->
            <div class="col-md-6 mb-4">
                <div class="chart-container">
                    <h3 class="h5 mb-3">Revenue Trend (6 Months)</h3>
                    <canvas id="revenueTrendChart"></canvas>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Outstanding Tasks -->
            <div class="col-md-4 mb-4">
                <div class="chart-container">
                    <h3 class="h5 mb-3">Outstanding Tasks</h3>
                    <?php if (empty($tasks)): ?>
                        <p class="text-muted text-center py-3">No outstanding tasks</p>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                        <div class="task-item">
                            <div>
                                <a href="<?php echo $task['link']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </a>
                            </div>
                            <div class="task-count"><?php echo $task['count']; ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cash Flow Projection -->
            <div class="col-md-4 mb-4">
                <div class="chart-container">
                    <h3 class="h5 mb-3">Cash Flow Projection</h3>
                    <canvas id="cashFlowChart"></canvas>
                </div>
            </div>

            <!-- Claims Status Distribution -->
            <div class="col-md-4 mb-4">
                <div class="chart-container">
                    <h3 class="h5 mb-3">Claims Status</h3>
                    <canvas id="claimsStatusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Payer Performance -->
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="chart-container">
                    <h3 class="h5 mb-3">Top Payer Performance</h3>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Payer</th>
                                    <th>Claims</th>
                                    <th>Revenue</th>
                                    <th>Avg Payment Days</th>
                                    <th>Denial Rate</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payer_performance as $payer): ?>
                                <tr class="payer-row">
                                    <td><?php echo htmlspecialchars($payer['payer_name']); ?></td>
                                    <td><?php echo $payer['claim_count']; ?></td>
                                    <td>$<?php echo number_format($payer['total_paid'], 2); ?></td>
                                    <td><?php echo round($payer['avg_payment_days']); ?> days</td>
                                    <td>
                                        <?php 
                                        $denial_rate = $payer['claim_count'] > 0 ? round(($payer['denials'] / $payer['claim_count']) * 100, 1) : 0;
                                        echo $denial_rate . '%';
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $performance_score = 100 - ($denial_rate * 2) - max(0, ($payer['avg_payment_days'] - 21));
                                        $performance_class = $performance_score >= 80 ? 'success' : ($performance_score >= 60 ? 'warning' : 'danger');
                                        ?>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar bg-<?php echo $performance_class; ?>" 
                                                 style="width: <?php echo max(0, min(100, $performance_score)); ?>%">
                                                <?php echo round($performance_score); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="chart-container">
                    <h3 class="h5 mb-3">Recent Activity</h3>
                    <div class="activity-list">
                        <?php foreach ($recent_activity as $activity): ?>
                        <div class="activity-item">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <strong><?php echo htmlspecialchars($activity['client_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">Claim #<?php echo $activity['claim_number']; ?></small>
                                </div>
                                <div class="col-md-2">
                                    <?php echo htmlspecialchars($activity['payer_name']); ?>
                                </div>
                                <div class="col-md-2">
                                    $<?php echo number_format($activity['amount_billed'], 2); ?>
                                </div>
                                <div class="col-md-2">
                                    <span class="badge bg-<?php 
                                        echo $activity['status'] == 'paid' ? 'success' : 
                                            ($activity['status'] == 'denied' ? 'danger' : 
                                            ($activity['status'] == 'pending' ? 'warning' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst($activity['status']); ?>
                                    </span>
                                </div>
                                <div class="col-md-3 text-end">
                                    <small class="text-muted">
                                        <?php echo date('m/d/Y h:i A', strtotime($activity['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // A/R Aging Chart
        const arAgingCtx = document.getElementById('arAgingChart').getContext('2d');
        new Chart(arAgingCtx, {
            type: 'doughnut',
            data: {
                labels: ['Current', '31-60 Days', '61-90 Days', '91-120 Days', 'Over 120 Days'],
                datasets: [{
                    data: [
                        <?php echo $metrics['ar_current']; ?>,
                        <?php echo $metrics['ar_30_days']; ?>,
                        <?php echo $metrics['ar_60_days']; ?>,
                        <?php echo $metrics['ar_90_days']; ?>,
                        <?php echo $metrics['ar_over_90']; ?>
                    ],
                    backgroundColor: ['#28a745', '#ffc107', '#fd7e14', '#dc3545', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Revenue Trend Chart
        const revenueTrendCtx = document.getElementById('revenueTrendChart').getContext('2d');
        new Chart(revenueTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($trends, 'month')); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($trends, 'revenue')); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Cash Flow Projection Chart
        const cashFlowCtx = document.getElementById('cashFlowChart').getContext('2d');
        new Chart(cashFlowCtx, {
            type: 'bar',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Expected',
                    data: [85000, 92000, 78000, 95000],
                    backgroundColor: 'rgba(40, 167, 69, 0.8)'
                }, {
                    label: 'Projected',
                    data: [78000, 85000, 82000, 88000],
                    backgroundColor: 'rgba(102, 126, 234, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + (value/1000) + 'K';
                            }
                        }
                    }
                }
            }
        });

        // Claims Status Chart
        const claimsStatusCtx = document.getElementById('claimsStatusChart').getContext('2d');
        new Chart(claimsStatusCtx, {
            type: 'pie',
            data: {
                labels: ['Paid', 'Pending', 'Denied'],
                datasets: [{
                    data: [
                        <?php echo $metrics['claims_paid']; ?>,
                        <?php echo $metrics['claims_pending']; ?>,
                        <?php echo $metrics['claims_denied']; ?>
                    ],
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Export Dashboard Function
        function exportDashboard() {
            // In a real implementation, this would generate a PDF or Excel report
            alert('Generating executive report...\nThis would export all dashboard data to PDF/Excel format.');
        }

        // Auto-refresh dashboard every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>