<?php
require_once dirname(__DIR__) . '/config_sqlite.php';

// Check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /autism_waiver_app/simple_login.php');
    exit;
}

// Date range filter
$dateFrom = $_GET['date_from'] ?? date('Y-m-01', strtotime('-6 months'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Get comprehensive analytics data
$analytics = [];

try {
    // Denial rate trends
    $stmt = $db->prepare("
        SELECT 
            strftime('%Y-%m', denial_date) as month,
            COUNT(*) as total_denials,
            SUM(amount) as total_amount,
            COUNT(DISTINCT denial_code) as unique_codes,
            AVG(amount) as avg_amount
        FROM claim_denials 
        WHERE denial_date BETWEEN ? AND ?
        GROUP BY strftime('%Y-%m', denial_date)
        ORDER BY month ASC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $analytics['trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Denial by reason analysis
    $stmt = $db->prepare("
        SELECT 
            denial_code,
            denial_reason,
            COUNT(*) as count,
            SUM(amount) as total_amount,
            ROUND(AVG(amount), 2) as avg_amount,
            COUNT(CASE WHEN status = 'resolved' AND resolution_type = 'paid' THEN 1 END) as successful_appeals,
            ROUND(COUNT(CASE WHEN status = 'resolved' AND resolution_type = 'paid' THEN 1 END) * 100.0 / 
                  COUNT(CASE WHEN status IN ('resolved', 'appealed') THEN 1 END), 1) as appeal_success_rate,
            COUNT(CASE WHEN status = 'resolved' AND resolution_type = 'denied' THEN 1 END) as unsuccessful_appeals,
            AVG(CASE WHEN status = 'resolved' 
                THEN JULIANDAY(resolution_date) - JULIANDAY(denial_date) 
                END) as avg_resolution_days
        FROM claim_denials 
        WHERE denial_date BETWEEN ? AND ?
        GROUP BY denial_code
        ORDER BY count DESC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $analytics['by_reason'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Provider analysis
    $stmt = $db->prepare("
        SELECT 
            cd.provider_id,
            p.provider_name,
            COUNT(*) as denial_count,
            SUM(cd.amount) as total_denied_amount,
            ROUND(AVG(cd.amount), 2) as avg_denial_amount,
            COUNT(DISTINCT cd.denial_code) as unique_denial_reasons,
            COUNT(CASE WHEN cd.status = 'resolved' AND cd.resolution_type = 'paid' THEN 1 END) as recoveries
        FROM claim_denials cd
        LEFT JOIN providers p ON cd.provider_id = p.id
        WHERE cd.denial_date BETWEEN ? AND ?
        GROUP BY cd.provider_id
        HAVING denial_count > 0
        ORDER BY denial_count DESC
        LIMIT 20
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $analytics['by_provider'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Service type analysis
    $stmt = $db->prepare("
        SELECT 
            cd.service_type,
            COUNT(*) as denial_count,
            SUM(cd.amount) as total_amount,
            ROUND(AVG(cd.amount), 2) as avg_amount,
            COUNT(CASE WHEN cd.status = 'resolved' AND cd.resolution_type = 'paid' THEN 1 END) as recoveries,
            ROUND(SUM(CASE WHEN cd.status = 'resolved' AND cd.resolution_type = 'paid' 
                      THEN cd.amount ELSE 0 END), 2) as recovered_amount
        FROM claim_denials cd
        WHERE cd.denial_date BETWEEN ? AND ?
        GROUP BY cd.service_type
        ORDER BY denial_count DESC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $analytics['by_service'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Aging analysis
    $stmt = $db->prepare("
        SELECT 
            CASE 
                WHEN JULIANDAY('now') - JULIANDAY(denial_date) <= 30 THEN '0-30 days'
                WHEN JULIANDAY('now') - JULIANDAY(denial_date) <= 60 THEN '31-60 days'
                WHEN JULIANDAY('now') - JULIANDAY(denial_date) <= 90 THEN '61-90 days'
                ELSE 'Over 90 days'
            END as age_bucket,
            COUNT(*) as count,
            SUM(amount) as total_amount,
            ROUND(AVG(amount), 2) as avg_amount
        FROM claim_denials 
        WHERE denial_date BETWEEN ? AND ? AND status != 'resolved'
        GROUP BY age_bucket
        ORDER BY 
            CASE age_bucket
                WHEN '0-30 days' THEN 1
                WHEN '31-60 days' THEN 2
                WHEN '61-90 days' THEN 3
                ELSE 4
            END
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $analytics['aging'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Staff performance
    $stmt = $db->prepare("
        SELECT 
            u.full_name,
            COUNT(cd.id) as denials_assigned,
            COUNT(CASE WHEN cd.status = 'resolved' THEN 1 END) as denials_resolved,
            COUNT(CASE WHEN cd.status = 'resolved' AND cd.resolution_type = 'paid' THEN 1 END) as successful_recoveries,
            SUM(CASE WHEN cd.status = 'resolved' AND cd.resolution_type = 'paid' 
                THEN cd.amount ELSE 0 END) as amount_recovered,
            ROUND(AVG(CASE WHEN cd.status = 'resolved' 
                      THEN JULIANDAY(cd.resolution_date) - JULIANDAY(cd.assigned_date) 
                      END), 1) as avg_resolution_days,
            ROUND(COUNT(CASE WHEN cd.status = 'resolved' AND cd.resolution_type = 'paid' THEN 1 END) * 100.0 / 
                  NULLIF(COUNT(CASE WHEN cd.status = 'resolved' THEN 1 END), 0), 1) as success_rate
        FROM users u
        LEFT JOIN claim_denials cd ON u.id = cd.assigned_to 
            AND cd.assigned_date BETWEEN ? AND ?
        WHERE u.role IN ('admin', 'billing_specialist')
        GROUP BY u.id
        ORDER BY amount_recovered DESC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $analytics['staff_performance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Preventable denials analysis
    $preventableCodes = ['M03', 'M07', 'M08', 'M10', 'M11', 'M13', 'M15', 'M19']; // Duplicate, coding errors, etc.
    $stmt = $db->prepare("
        SELECT 
            denial_code,
            denial_reason,
            COUNT(*) as count,
            SUM(amount) as total_amount,
            ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM claim_denials WHERE denial_date BETWEEN ? AND ?), 1) as percentage
        FROM claim_denials 
        WHERE denial_code IN ('" . implode("','", $preventableCodes) . "')
            AND denial_date BETWEEN ? AND ?
        GROUP BY denial_code
        ORDER BY count DESC
    ");
    $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
    $analytics['preventable'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recovery metrics
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_denials,
            SUM(amount) as total_denied_amount,
            COUNT(CASE WHEN status = 'resolved' AND resolution_type = 'paid' THEN 1 END) as successful_appeals,
            SUM(CASE WHEN status = 'resolved' AND resolution_type = 'paid' THEN amount ELSE 0 END) as recovered_amount,
            COUNT(CASE WHEN status = 'resolved' AND resolution_type = 'denied' THEN 1 END) as failed_appeals,
            COUNT(CASE WHEN status = 'pending' OR status = 'in_progress' THEN 1 END) as pending_work,
            COUNT(CASE WHEN appeal_status = 'submitted' THEN 1 END) as pending_appeals
        FROM claim_denials 
        WHERE denial_date BETWEEN ? AND ?
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $analytics['recovery'] = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching analytics: " . $e->getMessage());
}

// Calculate key performance indicators
$kpis = [];
if (!empty($analytics['recovery'])) {
    $recovery = $analytics['recovery'];
    $kpis['denial_rate'] = 0; // Would need total claims submitted to calculate
    $kpis['appeal_success_rate'] = $recovery['successful_appeals'] > 0 
        ? round(($recovery['successful_appeals'] / ($recovery['successful_appeals'] + $recovery['failed_appeals'])) * 100, 1)
        : 0;
    $kpis['recovery_rate'] = $recovery['total_denied_amount'] > 0 
        ? round(($recovery['recovered_amount'] / $recovery['total_denied_amount']) * 100, 1)
        : 0;
    $kpis['pending_work_percentage'] = $recovery['total_denials'] > 0 
        ? round(($recovery['pending_work'] / $recovery['total_denials']) * 100, 1)
        : 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Denial Analytics - ACI Autism Waiver</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-header {
            background: linear-gradient(135deg, #0066cc 0%, #004499 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .kpi-card {
            border-left: 4px solid #0066cc;
            transition: transform 0.2s;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .chart-container {
            height: 400px;
            margin: 1rem 0;
        }
        .trend-up { color: #dc3545; }
        .trend-down { color: #28a745; }
        .trend-neutral { color: #6c757d; }
        .filter-section {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .analytics-table {
            font-size: 0.9rem;
        }
        .analytics-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/autism_waiver_app/billing/denial_management.php">
                <i class="bi bi-arrow-left"></i> Back to Denial Management
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/autism_waiver_app/logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="analytics-header">
            <h1 class="mb-3"><i class="bi bi-graph-up"></i> Denial Analytics Dashboard</h1>
            <p class="lead mb-0">Comprehensive analysis of denial patterns and recovery performance</p>
        </div>

        <!-- Date Filter -->
        <div class="filter-section">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $dateFrom; ?>">
                </div>
                <div class="col-md-3">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $dateTo; ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-success" onclick="exportAnalytics()">
                        <i class="bi bi-download"></i> Export Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Key Performance Indicators -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card kpi-card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Appeal Success Rate</h6>
                        <h2 class="mb-0 <?php echo ($kpis['appeal_success_rate'] ?? 0) >= 75 ? 'text-success' : (($kpis['appeal_success_rate'] ?? 0) >= 50 ? 'text-warning' : 'text-danger'); ?>">
                            <?php echo $kpis['appeal_success_rate'] ?? 0; ?>%
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kpi-card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Recovery Rate</h6>
                        <h2 class="mb-0 <?php echo ($kpis['recovery_rate'] ?? 0) >= 60 ? 'text-success' : (($kpis['recovery_rate'] ?? 0) >= 40 ? 'text-warning' : 'text-danger'); ?>">
                            <?php echo $kpis['recovery_rate'] ?? 0; ?>%
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kpi-card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Pending Work</h6>
                        <h2 class="mb-0 <?php echo ($kpis['pending_work_percentage'] ?? 0) <= 20 ? 'text-success' : (($kpis['pending_work_percentage'] ?? 0) <= 40 ? 'text-warning' : 'text-danger'); ?>">
                            <?php echo $kpis['pending_work_percentage'] ?? 0; ?>%
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kpi-card">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Recovered</h6>
                        <h2 class="mb-0 text-success">
                            $<?php echo number_format($analytics['recovery']['recovered_amount'] ?? 0, 0); ?>
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Denial Trends Over Time</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Top Denial Reasons</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="reasonChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Analysis Tables -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Denial by Reason Analysis</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table analytics-table table-sm">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Count</th>
                                        <th>Amount</th>
                                        <th>Success %</th>
                                        <th>Avg Days</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics['by_reason'] ?? [] as $reason): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($reason['denial_code']); ?></span></td>
                                        <td><?php echo $reason['count']; ?></td>
                                        <td>$<?php echo number_format($reason['total_amount'], 0); ?></td>
                                        <td>
                                            <span class="<?php echo $reason['appeal_success_rate'] >= 70 ? 'text-success' : ($reason['appeal_success_rate'] >= 50 ? 'text-warning' : 'text-danger'); ?>">
                                                <?php echo $reason['appeal_success_rate'] ?? 0; ?>%
                                            </span>
                                        </td>
                                        <td><?php echo number_format($reason['avg_resolution_days'] ?? 0, 0); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Service Type Analysis</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table analytics-table table-sm">
                                <thead>
                                    <tr>
                                        <th>Service Type</th>
                                        <th>Denials</th>
                                        <th>Amount</th>
                                        <th>Recovered</th>
                                        <th>Recovery %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics['by_service'] ?? [] as $service): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($service['service_type']); ?></td>
                                        <td><?php echo $service['denial_count']; ?></td>
                                        <td>$<?php echo number_format($service['total_amount'], 0); ?></td>
                                        <td>$<?php echo number_format($service['recovered_amount'], 0); ?></td>
                                        <td>
                                            <?php 
                                            $recoveryPct = $service['total_amount'] > 0 
                                                ? round(($service['recovered_amount'] / $service['total_amount']) * 100, 1)
                                                : 0;
                                            ?>
                                            <span class="<?php echo $recoveryPct >= 60 ? 'text-success' : ($recoveryPct >= 40 ? 'text-warning' : 'text-danger'); ?>">
                                                <?php echo $recoveryPct; ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preventable Denials Analysis -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Preventable Denials</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table analytics-table table-sm">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Reason</th>
                                        <th>Count</th>
                                        <th>Amount</th>
                                        <th>% of Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics['preventable'] ?? [] as $preventable): ?>
                                    <tr>
                                        <td><span class="badge bg-warning text-dark"><?php echo htmlspecialchars($preventable['denial_code']); ?></span></td>
                                        <td><?php echo htmlspecialchars($preventable['denial_reason']); ?></td>
                                        <td><?php echo $preventable['count']; ?></td>
                                        <td>$<?php echo number_format($preventable['total_amount'], 0); ?></td>
                                        <td><?php echo $preventable['percentage']; ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php 
                        $totalPreventable = array_sum(array_column($analytics['preventable'] ?? [], 'count'));
                        $totalPreventableAmount = array_sum(array_column($analytics['preventable'] ?? [], 'total_amount'));
                        ?>
                        <div class="alert alert-warning mt-3">
                            <strong>Total Preventable Denials:</strong> <?php echo $totalPreventable; ?> claims worth $<?php echo number_format($totalPreventableAmount, 2); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Staff Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table analytics-table table-sm">
                                <thead>
                                    <tr>
                                        <th>Staff</th>
                                        <th>Assigned</th>
                                        <th>Resolved</th>
                                        <th>Recovered</th>
                                        <th>Success %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($analytics['staff_performance'] ?? [] as $staff): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($staff['full_name']); ?></td>
                                        <td><?php echo $staff['denials_assigned']; ?></td>
                                        <td><?php echo $staff['denials_resolved']; ?></td>
                                        <td>$<?php echo number_format($staff['amount_recovered'], 0); ?></td>
                                        <td>
                                            <span class="<?php echo ($staff['success_rate'] ?? 0) >= 70 ? 'text-success' : (($staff['success_rate'] ?? 0) >= 50 ? 'text-warning' : 'text-danger'); ?>">
                                                <?php echo $staff['success_rate'] ?? 0; ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Trend Chart
        const trendData = <?php echo json_encode($analytics['trends'] ?? []); ?>;
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendData.map(d => d.month),
                datasets: [{
                    label: 'Denial Count',
                    data: trendData.map(d => d.total_denials),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Denied Amount ($)',
                    data: trendData.map(d => d.total_amount),
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: { display: true, text: 'Count' }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: { display: true, text: 'Amount ($)' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });

        // Reason Chart
        const reasonData = <?php echo json_encode(array_slice($analytics['by_reason'] ?? [], 0, 10)); ?>;
        const reasonCtx = document.getElementById('reasonChart').getContext('2d');
        new Chart(reasonCtx, {
            type: 'doughnut',
            data: {
                labels: reasonData.map(d => d.denial_code),
                datasets: [{
                    data: reasonData.map(d => d.count),
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                        '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
                    ]
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

        // Export function
        function exportAnalytics() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'pdf');
            window.open('denial_analytics.php?' + params.toString());
        }
    </script>
</body>
</html>