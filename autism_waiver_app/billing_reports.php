<?php
require_once '../src/init.php';
requireAuth(3); // Case Manager+ access

$error = null;
$success = null;
$currentUser = getCurrentUser();

// Get report parameters
$reportType = $_GET['report'] ?? 'revenue_summary';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$payerId = $_GET['payer_id'] ?? '';
$serviceTypeId = $_GET['service_type_id'] ?? '';
$clientId = $_GET['client_id'] ?? '';
$exportFormat = $_GET['export'] ?? '';

try {
    $pdo = getDatabase();
    
    // Get report data based on type
    $reportData = [];
    $reportTitle = '';
    $chartData = [];
    
    switch($reportType) {
        case 'revenue_summary':
            $reportTitle = 'Revenue Summary Report';
            $query = "
                SELECT 
                    DATE_FORMAT(c.created_at, '%Y-%m') as period,
                    COUNT(DISTINCT c.id) as claim_count,
                    COUNT(DISTINCT c.client_id) as unique_clients,
                    SUM(c.total_amount) as billed_amount,
                    SUM(CASE WHEN c.status = 'paid' THEN c.payment_amount ELSE 0 END) as collected_amount,
                    SUM(CASE WHEN c.status IN ('draft','generated','submitted') THEN c.total_amount ELSE 0 END) as pending_amount,
                    AVG(CASE WHEN c.status = 'paid' THEN DATEDIFF(c.updated_at, c.created_at) ELSE NULL END) as avg_days_to_payment
                FROM autism_claims c
                WHERE c.created_at BETWEEN ? AND ?
                " . ($clientId ? "AND c.client_id = ?" : "") . "
                GROUP BY DATE_FORMAT(c.created_at, '%Y-%m')
                ORDER BY period DESC
            ";
            $params = [$startDate, $endDate];
            if ($clientId) $params[] = $clientId;
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'aging_report':
            $reportTitle = 'Accounts Receivable Aging Report';
            $query = "
                SELECT 
                    cl.id,
                    cl.first_name,
                    cl.last_name,
                    cl.ma_number,
                    c.claim_number,
                    c.service_date_from,
                    c.service_date_to,
                    c.total_amount,
                    c.status,
                    c.created_at,
                    DATEDIFF(CURDATE(), c.created_at) as age_days,
                    CASE 
                        WHEN DATEDIFF(CURDATE(), c.created_at) <= 30 THEN '0-30 days'
                        WHEN DATEDIFF(CURDATE(), c.created_at) <= 60 THEN '31-60 days'
                        WHEN DATEDIFF(CURDATE(), c.created_at) <= 90 THEN '61-90 days'
                        WHEN DATEDIFF(CURDATE(), c.created_at) <= 120 THEN '91-120 days'
                        ELSE 'Over 120 days'
                    END as aging_bucket
                FROM autism_claims c
                LEFT JOIN autism_clients cl ON c.client_id = cl.id
                WHERE c.status IN ('generated', 'submitted')
                  AND c.created_at BETWEEN ? AND ?
                " . ($clientId ? "AND c.client_id = ?" : "") . "
                ORDER BY age_days DESC
            ";
            $params = [$startDate, $endDate];
            if ($clientId) $params[] = $clientId;
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get aging summary
            $agingSummary = $pdo->prepare("
                SELECT 
                    CASE 
                        WHEN DATEDIFF(CURDATE(), created_at) <= 30 THEN '0-30 days'
                        WHEN DATEDIFF(CURDATE(), created_at) <= 60 THEN '31-60 days'
                        WHEN DATEDIFF(CURDATE(), created_at) <= 90 THEN '61-90 days'
                        WHEN DATEDIFF(CURDATE(), created_at) <= 120 THEN '91-120 days'
                        ELSE 'Over 120 days'
                    END as aging_bucket,
                    COUNT(*) as claim_count,
                    SUM(total_amount) as total_amount
                FROM autism_claims
                WHERE status IN ('generated', 'submitted')
                GROUP BY aging_bucket
                ORDER BY 
                    CASE aging_bucket
                        WHEN '0-30 days' THEN 1
                        WHEN '31-60 days' THEN 2
                        WHEN '61-90 days' THEN 3
                        WHEN '91-120 days' THEN 4
                        ELSE 5
                    END
            ");
            $agingSummary->execute();
            $chartData['aging'] = $agingSummary->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'denial_analysis':
            $reportTitle = 'Claim Denial Analysis';
            $query = "
                SELECT 
                    c.claim_number,
                    cl.first_name,
                    cl.last_name,
                    cl.ma_number,
                    c.service_date_from,
                    c.service_date_to,
                    c.total_amount,
                    c.status,
                    c.created_at,
                    c.updated_at,
                    cd.denial_code,
                    cd.denial_reason,
                    cd.denial_date,
                    cd.appeal_filed,
                    cd.appeal_outcome
                FROM autism_claims c
                LEFT JOIN autism_clients cl ON c.client_id = cl.id
                LEFT JOIN autism_claim_denials cd ON c.id = cd.claim_id
                WHERE c.status = 'denied'
                  AND c.created_at BETWEEN ? AND ?
                " . ($clientId ? "AND c.client_id = ?" : "") . "
                ORDER BY c.created_at DESC
            ";
            $params = [$startDate, $endDate];
            if ($clientId) $params[] = $clientId;
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get denial reasons summary
            $denialSummary = $pdo->prepare("
                SELECT 
                    cd.denial_code,
                    cd.denial_reason,
                    COUNT(*) as denial_count,
                    SUM(c.total_amount) as total_denied_amount
                FROM autism_claim_denials cd
                JOIN autism_claims c ON cd.claim_id = c.id
                WHERE c.created_at BETWEEN ? AND ?
                GROUP BY cd.denial_code, cd.denial_reason
                ORDER BY denial_count DESC
            ");
            $denialSummary->execute([$startDate, $endDate]);
            $chartData['denials'] = $denialSummary->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'collection_rates':
            $reportTitle = 'Collection Rates & Trends';
            $query = "
                SELECT 
                    DATE_FORMAT(c.created_at, '%Y-%m') as period,
                    COUNT(*) as total_claims,
                    SUM(c.total_amount) as billed_amount,
                    SUM(CASE WHEN c.status = 'paid' THEN c.payment_amount ELSE 0 END) as collected_amount,
                    SUM(CASE WHEN c.status = 'paid' THEN 1 ELSE 0 END) as paid_claims,
                    SUM(CASE WHEN c.status = 'denied' THEN 1 ELSE 0 END) as denied_claims,
                    SUM(CASE WHEN c.status IN ('generated','submitted') THEN 1 ELSE 0 END) as pending_claims,
                    ROUND(SUM(CASE WHEN c.status = 'paid' THEN c.payment_amount ELSE 0 END) / SUM(c.total_amount) * 100, 2) as collection_rate
                FROM autism_claims c
                WHERE c.created_at BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(c.created_at, '%Y-%m')
                ORDER BY period DESC
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'outstanding_balances':
            $reportTitle = 'Outstanding Balances by Client';
            $query = "
                SELECT 
                    cl.id as client_id,
                    cl.first_name,
                    cl.last_name,
                    cl.ma_number,
                    cl.phone,
                    cl.email,
                    COUNT(c.id) as unpaid_claims,
                    SUM(c.total_amount) as total_outstanding,
                    MIN(c.created_at) as oldest_claim_date,
                    MAX(c.created_at) as newest_claim_date,
                    AVG(DATEDIFF(CURDATE(), c.created_at)) as avg_age_days
                FROM autism_clients cl
                JOIN autism_claims c ON cl.id = c.client_id
                WHERE c.status IN ('generated', 'submitted')
                  AND c.created_at BETWEEN ? AND ?
                GROUP BY cl.id
                HAVING total_outstanding > 0
                ORDER BY total_outstanding DESC
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'payer_mix':
            $reportTitle = 'Payer Mix Analysis';
            // Since we're focused on Maryland Medicaid, simulate payer mix
            $query = "
                SELECT 
                    'Maryland Medicaid' as payer_name,
                    COUNT(*) as claim_count,
                    SUM(c.total_amount) as billed_amount,
                    SUM(CASE WHEN c.status = 'paid' THEN c.payment_amount ELSE 0 END) as collected_amount,
                    AVG(CASE WHEN c.status = 'paid' THEN c.payment_amount / c.total_amount * 100 ELSE NULL END) as avg_payment_rate,
                    AVG(CASE WHEN c.status = 'paid' THEN DATEDIFF(c.updated_at, c.created_at) ELSE NULL END) as avg_days_to_payment
                FROM autism_claims c
                WHERE c.created_at BETWEEN ? AND ?
                GROUP BY payer_name
                
                UNION ALL
                
                SELECT 
                    'Self-Pay' as payer_name,
                    0 as claim_count,
                    0 as billed_amount,
                    0 as collected_amount,
                    0 as avg_payment_rate,
                    0 as avg_days_to_payment
                FROM dual
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'service_profitability':
            $reportTitle = 'Service Profitability Analysis';
            $query = "
                SELECT 
                    st.name as service_name,
                    st.service_code,
                    COUNT(DISTINCT sn.id) as session_count,
                    COUNT(DISTINCT sn.client_id) as unique_clients,
                    SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600) as total_hours,
                    AVG(br.rate_per_unit) as avg_rate,
                    SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600 * br.rate_per_unit) as estimated_revenue,
                    COUNT(DISTINCT sn.provider_id) as providers_used
                FROM autism_session_notes sn
                JOIN autism_service_types st ON sn.service_type_id = st.id
                LEFT JOIN autism_billing_rates br ON st.id = br.service_type_id AND br.is_active = 1
                WHERE sn.session_date BETWEEN ? AND ?
                  AND sn.status = 'approved'
                GROUP BY st.id
                ORDER BY estimated_revenue DESC
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'ma_billing_summary':
            $reportTitle = 'Maryland Medicaid Billing Summary';
            $query = "
                SELECT 
                    cl.ma_number,
                    cl.first_name,
                    cl.last_name,
                    COUNT(c.id) as claim_count,
                    SUM(c.total_amount) as total_billed,
                    SUM(CASE WHEN c.status = 'paid' THEN c.payment_amount ELSE 0 END) as total_paid,
                    SUM(CASE WHEN c.status IN ('generated','submitted') THEN c.total_amount ELSE 0 END) as pending_amount,
                    AVG(CASE WHEN c.status = 'paid' THEN c.payment_amount / c.total_amount * 100 ELSE NULL END) as avg_payment_rate
                FROM autism_clients cl
                JOIN autism_claims c ON cl.id = c.client_id
                WHERE c.created_at BETWEEN ? AND ?
                  AND cl.ma_number IS NOT NULL
                " . ($clientId ? "AND cl.id = ?" : "") . "
                GROUP BY cl.id
                ORDER BY total_billed DESC
            ";
            $params = [$startDate, $endDate];
            if ($clientId) $params[] = $clientId;
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'authorization_analysis':
            $reportTitle = 'Authorization vs Billed Analysis';
            $query = "
                SELECT 
                    cl.first_name,
                    cl.last_name,
                    cl.ma_number,
                    pa.authorization_number,
                    st.name as service_type,
                    pa.start_date,
                    pa.end_date,
                    pa.authorized_units,
                    pa.used_units,
                    (pa.authorized_units - pa.used_units) as remaining_units,
                    ROUND((pa.used_units / pa.authorized_units) * 100, 2) as utilization_rate,
                    pa.status as auth_status
                FROM autism_prior_authorizations pa
                JOIN autism_clients cl ON pa.client_id = cl.id
                JOIN autism_service_types st ON pa.service_type_id = st.id
                WHERE pa.start_date <= ? AND pa.end_date >= ?
                " . ($clientId ? "AND cl.id = ?" : "") . "
                ORDER BY utilization_rate DESC
            ";
            $params = [$endDate, $startDate];
            if ($clientId) $params[] = $clientId;
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'timely_filing':
            $reportTitle = 'Timely Filing Compliance Report';
            $query = "
                SELECT 
                    c.claim_number,
                    cl.first_name,
                    cl.last_name,
                    cl.ma_number,
                    c.service_date_to,
                    c.created_at as claim_created,
                    DATEDIFF(c.created_at, c.service_date_to) as days_to_file,
                    CASE 
                        WHEN DATEDIFF(c.created_at, c.service_date_to) <= 365 THEN 'Compliant'
                        ELSE 'Non-Compliant'
                    END as filing_status,
                    c.status as claim_status,
                    c.total_amount
                FROM autism_claims c
                JOIN autism_clients cl ON c.client_id = cl.id
                WHERE c.created_at BETWEEN ? AND ?
                ORDER BY days_to_file DESC
            ";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$startDate, $endDate]);
            $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
    }
    
    // Get filter data
    $clients = $pdo->query("SELECT id, first_name, last_name FROM autism_clients WHERE status = 'active' ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);
    $serviceTypes = $pdo->query("SELECT id, name FROM autism_service_types ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle export
    if ($exportFormat === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . str_replace(' ', '_', $reportTitle) . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Write headers
        if (!empty($reportData)) {
            fputcsv($output, array_keys($reportData[0]));
        }
        
        // Write data
        foreach ($reportData as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
    $reportData = [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Reports - Scrive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .navbar-brand {
            font-weight: 600;
        }
        .reports-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .report-nav {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
        }
        .report-nav .nav-link {
            color: #495057;
            border-radius: 5px;
            padding: 0.5rem 1rem;
            margin: 0.2rem;
        }
        .report-nav .nav-link:hover {
            background: #e9ecef;
        }
        .report-nav .nav-link.active {
            background: #667eea;
            color: white;
        }
        .stat-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .table-export {
            position: sticky;
            top: 0;
            background: white;
            z-index: 10;
            padding: 1rem 0;
        }
        .aging-0-30 { color: #28a745; }
        .aging-31-60 { color: #ffc107; }
        .aging-61-90 { color: #fd7e14; }
        .aging-91-120 { color: #dc3545; }
        .aging-over-120 { color: #721c24; font-weight: bold; }
        
        @media print {
            .no-print {
                display: none !important;
            }
            .reports-header {
                background: none !important;
                color: black !important;
                border-bottom: 2px solid black;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark no-print">
        <div class="container">
            <a class="navbar-brand" href="<?= UrlManager::url('dashboard') ?>">
                <i class="fas fa-brain me-2"></i>
                ACI Billing Reports
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>
                    <?= htmlspecialchars($currentUser['full_name']) ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="<?= UrlManager::url('billing_dashboard') ?>">
                    <i class="fas fa-chart-line me-1"></i>
                    Billing Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="reports-header py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="mb-1">
                        <i class="fas fa-file-alt me-2"></i>
                        <?= htmlspecialchars($reportTitle) ?>
                    </h2>
                    <p class="mb-0">Comprehensive billing and financial analysis</p>
                </div>
                <div class="col-auto no-print">
                    <button class="btn btn-light me-2" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>
                        Print
                    </button>
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-light">
                        <i class="fas fa-file-csv me-2"></i>
                        Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-4">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Report Navigation -->
            <div class="col-lg-3 mb-4 no-print">
                <div class="report-nav">
                    <h6 class="text-muted mb-3">REPORT TYPES</h6>
                    <nav class="nav flex-column">
                        <a class="nav-link <?= $reportType === 'revenue_summary' ? 'active' : '' ?>" 
                           href="?report=revenue_summary">
                            <i class="fas fa-chart-line me-2"></i>Revenue Summary
                        </a>
                        <a class="nav-link <?= $reportType === 'aging_report' ? 'active' : '' ?>" 
                           href="?report=aging_report">
                            <i class="fas fa-calendar-alt me-2"></i>Aging Report
                        </a>
                        <a class="nav-link <?= $reportType === 'denial_analysis' ? 'active' : '' ?>" 
                           href="?report=denial_analysis">
                            <i class="fas fa-times-circle me-2"></i>Denial Analysis
                        </a>
                        <a class="nav-link <?= $reportType === 'collection_rates' ? 'active' : '' ?>" 
                           href="?report=collection_rates">
                            <i class="fas fa-percentage me-2"></i>Collection Rates
                        </a>
                        <a class="nav-link <?= $reportType === 'outstanding_balances' ? 'active' : '' ?>" 
                           href="?report=outstanding_balances">
                            <i class="fas fa-money-bill me-2"></i>Outstanding Balances
                        </a>
                        <a class="nav-link <?= $reportType === 'payer_mix' ? 'active' : '' ?>" 
                           href="?report=payer_mix">
                            <i class="fas fa-building me-2"></i>Payer Mix
                        </a>
                        <a class="nav-link <?= $reportType === 'service_profitability' ? 'active' : '' ?>" 
                           href="?report=service_profitability">
                            <i class="fas fa-coins me-2"></i>Service Profitability
                        </a>
                        
                        <h6 class="text-muted mb-2 mt-4">MARYLAND MEDICAID</h6>
                        <a class="nav-link <?= $reportType === 'ma_billing_summary' ? 'active' : '' ?>" 
                           href="?report=ma_billing_summary">
                            <i class="fas fa-id-card me-2"></i>MA Billing Summary
                        </a>
                        <a class="nav-link <?= $reportType === 'authorization_analysis' ? 'active' : '' ?>" 
                           href="?report=authorization_analysis">
                            <i class="fas fa-key me-2"></i>Authorization Analysis
                        </a>
                        <a class="nav-link <?= $reportType === 'timely_filing' ? 'active' : '' ?>" 
                           href="?report=timely_filing">
                            <i class="fas fa-clock me-2"></i>Timely Filing
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Report Content -->
            <div class="col-lg-9">
                <!-- Filters -->
                <div class="card mb-4 no-print">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="report" value="<?= htmlspecialchars($reportType) ?>">
                            
                            <div class="col-md-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" 
                                       value="<?= htmlspecialchars($startDate) ?>">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" class="form-control" name="end_date" 
                                       value="<?= htmlspecialchars($endDate) ?>">
                            </div>
                            
                            <?php if (in_array($reportType, ['revenue_summary', 'aging_report', 'outstanding_balances', 'ma_billing_summary', 'authorization_analysis'])): ?>
                            <div class="col-md-4">
                                <label class="form-label">Client (Optional)</label>
                                <select class="form-select" name="client_id">
                                    <option value="">All Clients</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?= $client['id'] ?>" <?= $clientId == $client['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($client['last_name'] . ', ' . $client['first_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Apply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Report Display -->
                <?php if ($reportType === 'aging_report' && !empty($chartData['aging'])): ?>
                    <!-- Aging Summary Cards -->
                    <div class="row mb-4">
                        <?php foreach ($chartData['aging'] as $bucket): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <h6 class="text-muted"><?= htmlspecialchars($bucket['aging_bucket']) ?></h6>
                                    <div class="stat-value <?= 'aging-' . str_replace([' days', '-'], ['', '-'], $bucket['aging_bucket']) ?>">
                                        $<?= number_format($bucket['total_amount'], 2) ?>
                                    </div>
                                    <small class="text-muted"><?= $bucket['claim_count'] ?> claims</small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($reportType === 'denial_analysis' && !empty($chartData['denials'])): ?>
                    <!-- Denial Reasons Chart -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Top Denial Reasons</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="denialChart" height="100"></canvas>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($reportType === 'collection_rates' && !empty($reportData)): ?>
                    <!-- Collection Rate Trend Chart -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Collection Rate Trend</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="collectionChart" height="100"></canvas>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Data Table -->
                <div class="card">
                    <div class="card-body p-0">
                        <?php if (empty($reportData)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-chart-bar" style="font-size: 3rem;"></i>
                                <p class="mt-3">No data available for the selected criteria</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <?php foreach (array_keys($reportData[0]) as $column): ?>
                                                <th><?= ucwords(str_replace('_', ' ', $column)) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $key => $value): ?>
                                                    <td>
                                                        <?php if (strpos($key, 'amount') !== false || strpos($key, 'revenue') !== false || strpos($key, 'rate') !== false): ?>
                                                            <?php if (strpos($key, 'rate') !== false): ?>
                                                                <?= number_format($value, 2) ?>%
                                                            <?php else: ?>
                                                                $<?= number_format($value, 2) ?>
                                                            <?php endif; ?>
                                                        <?php elseif (strpos($key, 'date') !== false && $value): ?>
                                                            <?= date('m/d/Y', strtotime($value)) ?>
                                                        <?php elseif ($key === 'aging_bucket'): ?>
                                                            <span class="<?= 'aging-' . str_replace([' days', '-'], ['', '-'], $value) ?>">
                                                                <?= htmlspecialchars($value) ?>
                                                            </span>
                                                        <?php elseif ($key === 'status'): ?>
                                                            <span class="badge bg-<?= match($value) {
                                                                'active' => 'success',
                                                                'paid' => 'success',
                                                                'denied' => 'danger',
                                                                'submitted' => 'info',
                                                                'generated' => 'warning',
                                                                default => 'secondary'
                                                            } ?>">
                                                                <?= ucfirst($value) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($value ?? '') ?>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Denial Analysis Chart
        <?php if ($reportType === 'denial_analysis' && !empty($chartData['denials'])): ?>
        const denialCtx = document.getElementById('denialChart').getContext('2d');
        new Chart(denialCtx, {
            type: 'bar',
            data: {
                labels: [<?= '"' . implode('","', array_column($chartData['denials'], 'denial_reason')) . '"' ?>],
                datasets: [{
                    label: 'Denial Count',
                    data: [<?= implode(',', array_column($chartData['denials'], 'denial_count')) ?>],
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>

        // Collection Rate Chart
        <?php if ($reportType === 'collection_rates' && !empty($reportData)): ?>
        const collectionCtx = document.getElementById('collectionChart').getContext('2d');
        new Chart(collectionCtx, {
            type: 'line',
            data: {
                labels: [<?= '"' . implode('","', array_column($reportData, 'period')) . '"' ?>],
                datasets: [{
                    label: 'Collection Rate %',
                    data: [<?= implode(',', array_column($reportData, 'collection_rate')) ?>],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        // Quick date range selectors
        function setDateRange(days) {
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(endDate.getDate() - days);
            
            document.querySelector('[name="start_date"]').value = startDate.toISOString().split('T')[0];
            document.querySelector('[name="end_date"]').value = endDate.toISOString().split('T')[0];
        }

        // Add quick date buttons
        document.addEventListener('DOMContentLoaded', function() {
            const filterCard = document.querySelector('.card-body form');
            if (filterCard) {
                const quickDates = document.createElement('div');
                quickDates.className = 'col-12 mb-3';
                quickDates.innerHTML = `
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary" onclick="setDateRange(30)">Last 30 Days</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setDateRange(90)">Last 90 Days</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setDateRange(180)">Last 6 Months</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="setDateRange(365)">Last Year</button>
                    </div>
                `;
                filterCard.insertBefore(quickDates, filterCard.firstChild);
            }
        });
    </script>
</body>
</html>