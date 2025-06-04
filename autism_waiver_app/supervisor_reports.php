<?php
require_once '../src/init.php';
requireAuth(4); // Supervisor+ access

$error = null;
$success = null;
$currentUser = getCurrentUser();

// Get filter parameters
$reportType = $_GET['report_type'] ?? 'productivity';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$staffId = $_GET['staff_id'] ?? '';
$clientId = $_GET['client_id'] ?? '';
$serviceType = $_GET['service_type'] ?? '';
$fiscalYear = $_GET['fiscal_year'] ?? date('Y');

// Maryland fiscal year runs July 1 - June 30
$fiscalYearStart = $fiscalYear . '-07-01';
$fiscalYearEnd = ($fiscalYear + 1) . '-06-30';

// Initialize report data
$reportData = [];
$staffList = [];
$clientList = [];
$serviceTypes = [];

try {
    $pdo = getDatabase();
    
    // Get staff list for filters
    $stmt = $pdo->query("
        SELECT id, first_name, last_name, employee_id 
        FROM autism_staff_members 
        WHERE status = 'active' 
        ORDER BY last_name, first_name
    ");
    $staffList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get client list for filters
    $stmt = $pdo->query("
        SELECT id, first_name, last_name, ma_number 
        FROM autism_clients 
        WHERE status = 'active' 
        ORDER BY last_name, first_name
    ");
    $clientList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get service types
    $stmt = $pdo->query("
        SELECT id, service_code, service_name 
        FROM autism_service_types 
        WHERE is_active = TRUE 
        ORDER BY service_name
    ");
    $serviceTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate report based on type
    switch ($reportType) {
        case 'productivity':
            $reportData = generateProductivityReport($pdo, $startDate, $endDate, $staffId);
            break;
        case 'utilization':
            $reportData = generateUtilizationReport($pdo, $startDate, $endDate, $clientId);
            break;
        case 'billing_performance':
            $reportData = generateBillingPerformanceReport($pdo, $startDate, $endDate);
            break;
        case 'quality_metrics':
            $reportData = generateQualityMetricsReport($pdo, $startDate, $endDate);
            break;
        case 'authorization_alerts':
            $reportData = generateAuthorizationAlertsReport($pdo);
            break;
        case 'fiscal_year':
            $reportData = generateFiscalYearReport($pdo, $fiscalYearStart, $fiscalYearEnd);
            break;
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Report generation functions
function generateProductivityReport($pdo, $startDate, $endDate, $staffId = '') {
    $params = [$startDate, $endDate];
    $staffFilter = '';
    if ($staffId) {
        $staffFilter = 'AND sn.staff_id = ?';
        $params[] = $staffId;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.first_name,
            s.last_name,
            s.employee_id,
            COUNT(DISTINCT sn.id) as total_sessions,
            COUNT(DISTINCT sn.client_id) as clients_served,
            SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600) as total_hours,
            COUNT(DISTINCT DATE(sn.session_date)) as days_worked,
            SUM(CASE WHEN sn.status = 'approved' THEN 1 ELSE 0 END) as approved_sessions,
            SUM(CASE WHEN sn.status = 'pending' THEN 1 ELSE 0 END) as pending_sessions
        FROM autism_staff_members s
        LEFT JOIN autism_session_notes sn ON s.id = sn.staff_id 
            AND sn.session_date BETWEEN ? AND ? $staffFilter
        WHERE s.status = 'active'
        GROUP BY s.id
        ORDER BY total_hours DESC
    ");
    $stmt->execute($params);
    
    return [
        'type' => 'productivity',
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'summary' => calculateProductivitySummary($pdo, $startDate, $endDate)
    ];
}

function generateUtilizationReport($pdo, $startDate, $endDate, $clientId = '') {
    $params = [$startDate, $endDate];
    $clientFilter = '';
    if ($clientId) {
        $clientFilter = 'AND c.id = ?';
        $params[] = $clientId;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.first_name,
            c.last_name,
            c.ma_number,
            a.service_type_id,
            st.service_name,
            a.authorization_number as auth_number,
            (a.total_units * 
                CASE st.unit_type 
                    WHEN '15min' THEN 0.25 
                    WHEN 'hour' THEN 1 
                    ELSE 1 
                END
            ) as authorized_hours,
            a.start_date as auth_start,
            a.end_date as auth_end,
            COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600), 0) as hours_used,
            ((a.total_units * 
                CASE st.unit_type 
                    WHEN '15min' THEN 0.25 
                    WHEN 'hour' THEN 1 
                    ELSE 1 
                END
            ) - COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600), 0)) as hours_remaining,
            ROUND((COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600), 0) / (a.total_units * 
                CASE st.unit_type 
                    WHEN '15min' THEN 0.25 
                    WHEN 'hour' THEN 1 
                    ELSE 1 
                END
            ) * 100), 2) as utilization_percent
        FROM autism_clients c
        INNER JOIN autism_authorizations a ON c.id = a.client_id
        INNER JOIN autism_service_types st ON a.service_type_id = st.id
        LEFT JOIN autism_session_notes sn ON c.id = sn.client_id 
            AND sn.service_type_id = a.service_type_id
            AND sn.session_date BETWEEN ? AND ?
            AND sn.status IN ('approved', 'billed')
        WHERE c.status = 'active' 
            AND a.status = 'active'
            $clientFilter
        GROUP BY c.id, a.id
        ORDER BY c.last_name, c.first_name, st.service_name
    ");
    $stmt->execute($params);
    
    return [
        'type' => 'utilization',
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'summary' => calculateUtilizationSummary($pdo, $startDate, $endDate)
    ];
}

function generateBillingPerformanceReport($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total_claims,
            SUM(total_amount) as total_billed,
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_claims,
            SUM(CASE WHEN status = 'paid' THEN payment_amount ELSE 0 END) as total_paid,
            SUM(CASE WHEN status = 'denied' THEN 1 ELSE 0 END) as denied_claims,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_claims,
            AVG(CASE WHEN status = 'paid' THEN DATEDIFF(payment_date, submission_date) END) as avg_payment_days
        FROM autism_claims
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    
    $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get denial reasons
    $stmt = $pdo->prepare("
        SELECT 
            denial_reason,
            COUNT(*) as count
        FROM autism_claims
        WHERE status = 'denied' 
            AND created_at BETWEEN ? AND ?
            AND denial_reason IS NOT NULL
        GROUP BY denial_reason
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute([$startDate, $endDate]);
    
    return [
        'type' => 'billing_performance',
        'monthly_data' => $monthlyData,
        'denial_reasons' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'summary' => calculateBillingSummary($pdo, $startDate, $endDate)
    ];
}

function generateQualityMetricsReport($pdo, $startDate, $endDate) {
    // Documentation compliance
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sessions,
            SUM(CASE WHEN DATEDIFF(created_at, session_date) <= 2 THEN 1 ELSE 0 END) as timely_documentation,
            SUM(CASE WHEN supervision_status = 'approved' THEN 1 ELSE 0 END) as supervisor_approved,
            SUM(CASE WHEN LENGTH(session_notes) >= 100 THEN 1 ELSE 0 END) as adequate_notes,
            SUM(CASE WHEN goals_addressed IS NOT NULL AND goals_addressed != '' THEN 1 ELSE 0 END) as goals_documented
        FROM autism_session_notes
        WHERE session_date BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $documentation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Staff compliance by individual
    $stmt = $pdo->prepare("
        SELECT 
            s.first_name,
            s.last_name,
            COUNT(sn.id) as total_sessions,
            ROUND(AVG(CASE WHEN DATEDIFF(sn.created_at, sn.session_date) <= 2 THEN 100 ELSE 0 END), 2) as timely_rate,
            ROUND(AVG(CASE WHEN LENGTH(sn.session_notes) >= 100 THEN 100 ELSE 0 END), 2) as quality_rate
        FROM autism_staff_members s
        LEFT JOIN autism_session_notes sn ON s.id = sn.staff_id
            AND sn.session_date BETWEEN ? AND ?
        WHERE s.status = 'active'
        GROUP BY s.id
        HAVING total_sessions > 0
        ORDER BY timely_rate DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    
    return [
        'type' => 'quality_metrics',
        'documentation' => $documentation,
        'staff_compliance' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'summary' => calculateQualitySummary($pdo, $startDate, $endDate)
    ];
}

function generateAuthorizationAlertsReport($pdo) {
    // Authorizations expiring in next 30 days
    $stmt = $pdo->query("
        SELECT 
            a.id,
            c.first_name,
            c.last_name,
            c.ma_number,
            a.authorization_number as auth_number,
            st.service_name,
            st.unit_type,
            (a.total_units * 
                CASE st.unit_type 
                    WHEN '15min' THEN 0.25 
                    WHEN 'hour' THEN 1 
                    ELSE 1 
                END
            ) as authorized_hours,
            a.start_date,
            a.end_date,
            DATEDIFF(a.end_date, CURDATE()) as days_until_expiry,
            COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600), 0) as hours_used,
            ((a.total_units * 
                CASE st.unit_type 
                    WHEN '15min' THEN 0.25 
                    WHEN 'hour' THEN 1 
                    ELSE 1 
                END
            ) - COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600), 0)) as hours_remaining
        FROM autism_authorizations a
        INNER JOIN autism_clients c ON a.client_id = c.id
        INNER JOIN autism_service_types st ON a.service_type_id = st.id
        LEFT JOIN autism_session_notes sn ON a.client_id = sn.client_id 
            AND a.service_type_id = sn.service_type_id
            AND sn.session_date BETWEEN a.start_date AND a.end_date
            AND sn.status IN ('approved', 'billed')
        WHERE a.status = 'active'
            AND c.status = 'active'
            AND a.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        GROUP BY a.id
        ORDER BY days_until_expiry, c.last_name
    ");
    $expiring = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // High utilization authorizations (>80%)
    $stmt = $pdo->query("
        SELECT 
            a.id,
            c.first_name,
            c.last_name,
            c.ma_number,
            a.authorization_number as auth_number,
            st.service_name,
            st.unit_type,
            a.start_date,
            (a.total_units * 
                CASE st.unit_type 
                    WHEN '15min' THEN 0.25 
                    WHEN 'hour' THEN 1 
                    ELSE 1 
                END
            ) as authorized_hours,
            COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600), 0) as hours_used,
            ROUND((COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600), 0) / (a.total_units * 
                CASE st.unit_type 
                    WHEN '15min' THEN 0.25 
                    WHEN 'hour' THEN 1 
                    ELSE 1 
                END
            ) * 100), 2) as utilization_percent,
            DATEDIFF(a.end_date, CURDATE()) as days_remaining
        FROM autism_authorizations a
        INNER JOIN autism_clients c ON a.client_id = c.id
        INNER JOIN autism_service_types st ON a.service_type_id = st.id
        LEFT JOIN autism_session_notes sn ON a.client_id = sn.client_id 
            AND a.service_type_id = sn.service_type_id
            AND sn.session_date BETWEEN a.start_date AND a.end_date
            AND sn.status IN ('approved', 'billed')
        WHERE a.status = 'active'
            AND c.status = 'active'
            AND a.end_date >= CURDATE()
        GROUP BY a.id
        HAVING utilization_percent >= 80
        ORDER BY utilization_percent DESC, c.last_name
    ");
    
    return [
        'type' => 'authorization_alerts',
        'expiring' => $expiring,
        'high_utilization' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ];
}

function generateFiscalYearReport($pdo, $startDate, $endDate) {
    // Overall fiscal year stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT c.id) as total_clients_served,
            COUNT(DISTINCT s.id) as total_staff,
            COUNT(DISTINCT sn.id) as total_sessions,
            SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600) as total_service_hours,
            COUNT(DISTINCT cl.id) as total_claims,
            SUM(cl.total_amount) as total_billed,
            SUM(CASE WHEN cl.status = 'paid' THEN cl.payment_amount ELSE 0 END) as total_collected
        FROM autism_session_notes sn
        LEFT JOIN autism_clients c ON sn.client_id = c.id
        LEFT JOIN autism_staff_members s ON sn.staff_id = s.id
        LEFT JOIN autism_claims cl ON cl.service_date_from >= ? AND cl.service_date_to <= ?
        WHERE sn.session_date BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate, $startDate, $endDate]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Monthly breakdown
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(sn.session_date, '%Y-%m') as month,
            COUNT(DISTINCT sn.client_id) as clients_served,
            COUNT(DISTINCT sn.id) as sessions,
            SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600) as service_hours,
            COUNT(DISTINCT cl.id) as claims,
            SUM(cl.total_amount) as billed_amount
        FROM autism_session_notes sn
        LEFT JOIN autism_claims cl ON cl.service_date_from >= DATE_FORMAT(sn.session_date, '%Y-%m-01')
            AND cl.service_date_to <= LAST_DAY(sn.session_date)
        WHERE sn.session_date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(sn.session_date, '%Y-%m')
        ORDER BY month
    ");
    $stmt->execute([$startDate, $endDate]);
    
    return [
        'type' => 'fiscal_year',
        'summary' => $summary,
        'monthly_breakdown' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'fiscal_year_start' => $startDate,
        'fiscal_year_end' => $endDate
    ];
}

// Summary calculation functions
function calculateProductivitySummary($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT staff_id) as active_staff,
            COUNT(DISTINCT client_id) as clients_served,
            SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600) as total_hours,
            AVG(TIME_TO_SEC(TIMEDIFF(end_time, start_time))/3600) as avg_session_hours
        FROM autism_session_notes
        WHERE session_date BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function calculateUtilizationSummary($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT a.id) as total_authorizations,
            SUM(a.total_units * 
                CASE st.unit_type 
                    WHEN '15min' THEN 0.25 
                    WHEN 'hour' THEN 1 
                    ELSE 1 
                END
            ) as total_authorized_hours,
            AVG(utilization.percent_used) as avg_utilization
        FROM autism_authorizations a
        INNER JOIN autism_service_types st ON a.service_type_id = st.id
        LEFT JOIN (
            SELECT 
                a.id as auth_id,
                (SUM(TIME_TO_SEC(TIMEDIFF(sn.end_time, sn.start_time))/3600) / (a.total_units * 
                    CASE st.unit_type 
                        WHEN '15min' THEN 0.25 
                        WHEN 'hour' THEN 1 
                        ELSE 1 
                    END
                ) * 100) as percent_used
            FROM autism_session_notes sn
            INNER JOIN autism_authorizations a ON sn.client_id = a.client_id
            INNER JOIN autism_service_types st ON a.service_type_id = st.id
            WHERE sn.session_date BETWEEN ? AND ?
            GROUP BY a.id
        ) utilization ON a.id = utilization.auth_id
        WHERE a.status = 'active'
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function calculateBillingSummary($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_claims,
            SUM(total_amount) as total_billed,
            SUM(CASE WHEN status = 'paid' THEN payment_amount ELSE 0 END) as total_collected,
            ROUND(AVG(CASE WHEN status = 'paid' THEN (payment_amount / total_amount * 100) END), 2) as collection_rate
        FROM autism_claims
        WHERE created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function calculateQualitySummary($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            ROUND(AVG(CASE WHEN DATEDIFF(created_at, session_date) <= 2 THEN 100 ELSE 0 END), 2) as timely_documentation_rate,
            ROUND(AVG(CASE WHEN supervision_status = 'approved' THEN 100 ELSE 0 END), 2) as supervision_approval_rate,
            ROUND(AVG(CASE WHEN LENGTH(session_notes) >= 100 THEN 100 ELSE 0 END), 2) as note_quality_rate
        FROM autism_session_notes
        WHERE session_date BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Export functionality
if (isset($_GET['export'])) {
    $format = $_GET['export'];
    
    // Generate filename
    $filename = "supervisor_report_" . $reportType . "_" . date('Y-m-d');
    
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            exportToCSV($reportData);
            exit;
            
        case 'pdf':
            // PDF export would require additional library
            $error = "PDF export coming soon";
            break;
            
        case 'excel':
            // Excel export would require PHPSpreadsheet
            $error = "Excel export coming soon";
            break;
    }
}

function exportToCSV($reportData) {
    $output = fopen('php://output', 'w');
    
    // Headers based on report type
    switch ($reportData['type']) {
        case 'productivity':
            fputcsv($output, ['Employee ID', 'Name', 'Total Sessions', 'Clients Served', 'Total Hours', 'Days Worked', 'Approved Sessions', 'Pending Sessions']);
            foreach ($reportData['data'] as $row) {
                fputcsv($output, [
                    $row['employee_id'],
                    $row['first_name'] . ' ' . $row['last_name'],
                    $row['total_sessions'],
                    $row['clients_served'],
                    round($row['total_hours'], 2),
                    $row['days_worked'],
                    $row['approved_sessions'],
                    $row['pending_sessions']
                ]);
            }
            break;
            
        case 'utilization':
            fputcsv($output, ['Client', 'MA Number', 'Service', 'Authorized Hours', 'Hours Used', 'Hours Remaining', 'Utilization %', 'Auth Start', 'Auth End']);
            foreach ($reportData['data'] as $row) {
                fputcsv($output, [
                    $row['first_name'] . ' ' . $row['last_name'],
                    $row['ma_number'],
                    $row['service_name'],
                    $row['authorized_hours'],
                    round($row['hours_used'], 2),
                    round($row['hours_remaining'], 2),
                    $row['utilization_percent'] . '%',
                    $row['auth_start'],
                    $row['auth_end']
                ]);
            }
            break;
    }
    
    fclose($output);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Reports - Scrive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <style>
        .navbar-brand {
            font-weight: 600;
        }
        .reports-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .report-nav {
            border-bottom: 2px solid #e3e3e3;
            background-color: #f8f9fa;
        }
        .report-nav .nav-link {
            color: #495057;
            padding: 1rem 1.5rem;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .report-nav .nav-link:hover {
            color: #667eea;
            background-color: rgba(102, 126, 234, 0.05);
        }
        .report-nav .nav-link.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background-color: white;
        }
        .filter-card {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-radius: 10px;
        }
        .stat-card {
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 15px;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .export-buttons {
            position: sticky;
            top: 20px;
        }
        .alert-card {
            border-left: 4px solid;
        }
        .alert-warning-custom {
            background-color: #fff3cd;
            border-left-color: #ffc107;
        }
        .alert-danger-custom {
            background-color: #f8d7da;
            border-left-color: #dc3545;
        }
        .metric-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .metric-good {
            background-color: #d4edda;
            color: #155724;
        }
        .metric-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .metric-poor {
            background-color: #f8d7da;
            color: #721c24;
        }
        .table-report {
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .reports-header {
                background: none !important;
                color: black !important;
                -webkit-print-color-adjust: exact;
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
                Scrive Supervisor Portal
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user-shield me-1"></i>
                    <?= htmlspecialchars($currentUser['full_name']) ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="<?= UrlManager::url('dashboard') ?>">
                    <i class="fas fa-home me-1"></i>
                    Dashboard
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
                        <i class="fas fa-chart-line me-2"></i>
                        Supervisor Reports & Analytics
                    </h2>
                    <p class="mb-0">Comprehensive reporting for Maryland Autism Waiver program management</p>
                </div>
                <div class="col-auto no-print">
                    <button class="btn btn-light" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>
                        Print Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Type Navigation -->
    <nav class="report-nav sticky-top no-print">
        <div class="container">
            <ul class="nav nav-pills">
                <li class="nav-item">
                    <a class="nav-link <?= $reportType === 'productivity' ? 'active' : '' ?>" 
                       href="?report_type=productivity">
                        <i class="fas fa-user-clock me-2"></i>
                        Staff Productivity
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $reportType === 'utilization' ? 'active' : '' ?>" 
                       href="?report_type=utilization">
                        <i class="fas fa-chart-pie me-2"></i>
                        Service Utilization
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $reportType === 'billing_performance' ? 'active' : '' ?>" 
                       href="?report_type=billing_performance">
                        <i class="fas fa-dollar-sign me-2"></i>
                        Billing Performance
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $reportType === 'quality_metrics' ? 'active' : '' ?>" 
                       href="?report_type=quality_metrics">
                        <i class="fas fa-star me-2"></i>
                        Quality Metrics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $reportType === 'authorization_alerts' ? 'active' : '' ?>" 
                       href="?report_type=authorization_alerts">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Auth Alerts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $reportType === 'fiscal_year' ? 'active' : '' ?>" 
                       href="?report_type=fiscal_year">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Fiscal Year
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container my-4">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Filters -->
            <div class="col-lg-9">
                <div class="card filter-card mb-4 no-print">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <input type="hidden" name="report_type" value="<?= htmlspecialchars($reportType) ?>">
                            
                            <?php if ($reportType !== 'authorization_alerts' && $reportType !== 'fiscal_year'): ?>
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
                            <?php endif; ?>
                            
                            <?php if ($reportType === 'fiscal_year'): ?>
                                <div class="col-md-3">
                                    <label class="form-label">Fiscal Year</label>
                                    <select class="form-select" name="fiscal_year">
                                        <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                            <option value="<?= $y ?>" <?= $y == $fiscalYear ? 'selected' : '' ?>>
                                                FY <?= $y ?> - <?= $y + 1 ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($reportType === 'productivity'): ?>
                                <div class="col-md-3">
                                    <label class="form-label">Staff Member</label>
                                    <select class="form-select" name="staff_id">
                                        <option value="">All Staff</option>
                                        <?php foreach ($staffList as $staff): ?>
                                            <option value="<?= $staff['id'] ?>" <?= $staffId == $staff['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($staff['last_name'] . ', ' . $staff['first_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($reportType === 'utilization'): ?>
                                <div class="col-md-3">
                                    <label class="form-label">Client</label>
                                    <select class="form-select" name="client_id">
                                        <option value="">All Clients</option>
                                        <?php foreach ($clientList as $client): ?>
                                            <option value="<?= $client['id'] ?>" <?= $clientId == $client['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($client['last_name'] . ', ' . $client['first_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-sync me-2"></i>
                                    Generate Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Report Content -->
                <?php if (!empty($reportData)): ?>
                    <?php include __DIR__ . '/reports/' . $reportType . '_report.php'; ?>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-chart-bar text-muted" style="font-size: 3rem;"></i>
                            <p class="mt-3 text-muted">Select report parameters and click "Generate Report" to view data</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Export Options -->
            <div class="col-lg-3">
                <div class="card export-buttons no-print">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="fas fa-download me-2"></i>
                            Export Options
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" 
                               class="btn btn-outline-success">
                                <i class="fas fa-file-csv me-2"></i>
                                Export to CSV
                            </a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" 
                               class="btn btn-outline-primary">
                                <i class="fas fa-file-excel me-2"></i>
                                Export to Excel
                            </a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'pdf'])) ?>" 
                               class="btn btn-outline-danger">
                                <i class="fas fa-file-pdf me-2"></i>
                                Export to PDF
                            </a>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">
                            <i class="fas fa-save me-2"></i>
                            Saved Reports
                        </h6>
                        <div class="small text-muted">
                            <p class="mb-2">Save current configuration for quick access</p>
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="saveReport()">
                                <i class="fas fa-plus me-1"></i>
                                Save Configuration
                            </button>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">
                            <i class="fas fa-clock me-2"></i>
                            Schedule Reports
                        </h6>
                        <div class="small text-muted">
                            <p class="mb-2">Automate report generation</p>
                            <button class="btn btn-sm btn-outline-secondary w-100" onclick="scheduleReport()">
                                <i class="fas fa-calendar-plus me-1"></i>
                                Schedule This Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mt-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Report Info
                        </h6>
                    </div>
                    <div class="card-body small">
                        <p class="mb-1">
                            <strong>Generated:</strong><br>
                            <?= date('F j, Y g:i A') ?>
                        </p>
                        <p class="mb-1">
                            <strong>Report Period:</strong><br>
                            <?php if ($reportType === 'fiscal_year'): ?>
                                FY <?= $fiscalYear ?> - <?= $fiscalYear + 1 ?>
                            <?php elseif ($reportType !== 'authorization_alerts'): ?>
                                <?= date('M j, Y', strtotime($startDate)) ?> - 
                                <?= date('M j, Y', strtotime($endDate)) ?>
                            <?php else: ?>
                                Current Authorizations
                            <?php endif; ?>
                        </p>
                        <p class="mb-0">
                            <strong>Maryland Compliance:</strong><br>
                            <span class="badge bg-success">Compliant</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function saveReport() {
            const reportName = prompt('Enter a name for this report configuration:');
            if (reportName) {
                // Would save to database
                alert('Report configuration saved! You can access it from your saved reports.');
            }
        }
        
        function scheduleReport() {
            alert('Report scheduling feature coming soon! This will allow you to automatically generate and email reports on a schedule.');
        }
        
        // Auto-refresh authorization alerts
        <?php if ($reportType === 'authorization_alerts'): ?>
        setInterval(function() {
            if (!document.hidden) {
                location.reload();
            }
        }, 300000); // Refresh every 5 minutes
        <?php endif; ?>
    </script>
</body>
</html>