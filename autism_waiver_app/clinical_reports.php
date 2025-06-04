<?php
session_start();
require_once 'auth.php';
require_once '../src/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: simple_login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'employee';

// Only allow access to staff, managers, case managers, and admin
if (!in_array($user_role, ['admin', 'manager', 'case_manager', 'bcba', 'rbt'])) {
    header('Location: simple_dashboard.php');
    exit();
}

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Get filter parameters
$report_type = $_GET['report_type'] ?? 'progress';
$client_id = $_GET['client_id'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$export_format = $_GET['export'] ?? '';

// Get client list for filter
$clients_query = "SELECT id, CONCAT(first_name, ' ', last_name) as full_name 
                  FROM autism_clients 
                  WHERE active = 1 
                  ORDER BY last_name, first_name";
$clients_result = $conn->query($clients_query);
$clients = [];
while ($row = $clients_result->fetch_assoc()) {
    $clients[] = $row;
}

// Function to get progress report data
function getProgressReport($conn, $client_id, $start_date, $end_date) {
    $data = [];
    
    if ($client_id) {
        // Get client info with treatment plan
        $client_query = "SELECT c.*, tp.plan_data 
                        FROM autism_clients c
                        LEFT JOIN autism_treatment_plans tp ON c.id = tp.client_id AND tp.status = 'active'
                        WHERE c.id = ?";
        $stmt = $conn->prepare($client_query);
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $data['client'] = $stmt->get_result()->fetch_assoc();
        
        // Get goal progress
        $goals_query = "SELECT 
                            g.id,
                            g.goal_text,
                            g.target_date,
                            g.status,
                            COUNT(DISTINCT sn.id) as session_count,
                            AVG(CASE WHEN sn.goal_progress IS NOT NULL 
                                THEN JSON_EXTRACT(sn.goal_progress, CONCAT('$.\"', g.id, '\"')) 
                                ELSE 0 END) as avg_progress
                        FROM autism_goals g
                        LEFT JOIN autism_session_notes sn ON sn.client_id = g.client_id 
                            AND sn.date BETWEEN ? AND ?
                        WHERE g.client_id = ? AND g.active = 1
                        GROUP BY g.id
                        ORDER BY g.created_at";
        $stmt = $conn->prepare($goals_query);
        $stmt->bind_param("ssi", $start_date, $end_date, $client_id);
        $stmt->execute();
        $data['goals'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get session summary
        $session_query = "SELECT 
                            COUNT(*) as total_sessions,
                            SUM(duration) as total_minutes,
                            AVG(duration) as avg_duration,
                            COUNT(DISTINCT DATE(date)) as days_with_service
                        FROM autism_sessions
                        WHERE client_id = ? AND date BETWEEN ? AND ? AND status = 'completed'";
        $stmt = $conn->prepare($session_query);
        $stmt->bind_param("iss", $client_id, $start_date, $end_date);
        $stmt->execute();
        $data['session_summary'] = $stmt->get_result()->fetch_assoc();
    } else {
        // Get all clients progress summary
        $summary_query = "SELECT 
                            c.id,
                            CONCAT(c.first_name, ' ', c.last_name) as client_name,
                            COUNT(DISTINCT g.id) as goal_count,
                            COUNT(DISTINCT s.id) as session_count,
                            SUM(s.duration) as total_minutes
                        FROM autism_clients c
                        LEFT JOIN autism_goals g ON c.id = g.client_id AND g.active = 1
                        LEFT JOIN autism_sessions s ON c.id = s.client_id 
                            AND s.date BETWEEN ? AND ? AND s.status = 'completed'
                        WHERE c.active = 1
                        GROUP BY c.id
                        ORDER BY c.last_name, c.first_name";
        $stmt = $conn->prepare($summary_query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $data['clients'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    return $data;
}

// Function to get service delivery summary
function getServiceDeliverySummary($conn, $client_id, $start_date, $end_date) {
    $data = [];
    
    $base_query = "SELECT 
                    DATE(s.date) as service_date,
                    s.service_type,
                    COUNT(*) as session_count,
                    SUM(s.duration) as total_minutes,
                    AVG(s.duration) as avg_duration,
                    COUNT(DISTINCT s.provider_id) as provider_count,
                    COUNT(DISTINCT s.client_id) as client_count
                FROM autism_sessions s
                WHERE s.date BETWEEN ? AND ? AND s.status = 'completed'";
    
    if ($client_id) {
        $base_query .= " AND s.client_id = ?";
    }
    
    $base_query .= " GROUP BY DATE(s.date), s.service_type
                    ORDER BY service_date DESC, s.service_type";
    
    $stmt = $conn->prepare($base_query);
    if ($client_id) {
        $stmt->bind_param("ssi", $start_date, $end_date, $client_id);
    } else {
        $stmt->bind_param("ss", $start_date, $end_date);
    }
    $stmt->execute();
    $data['daily_summary'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get service type breakdown
    $service_query = "SELECT 
                        s.service_type,
                        COUNT(*) as session_count,
                        SUM(s.duration) as total_minutes,
                        COUNT(DISTINCT s.client_id) as client_count,
                        COUNT(DISTINCT s.provider_id) as provider_count
                    FROM autism_sessions s
                    WHERE s.date BETWEEN ? AND ? AND s.status = 'completed'";
    
    if ($client_id) {
        $service_query .= " AND s.client_id = ?";
    }
    
    $service_query .= " GROUP BY s.service_type
                       ORDER BY total_minutes DESC";
    
    $stmt = $conn->prepare($service_query);
    if ($client_id) {
        $stmt->bind_param("ssi", $start_date, $end_date, $client_id);
    } else {
        $stmt->bind_param("ss", $start_date, $end_date);
    }
    $stmt->execute();
    $data['service_breakdown'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $data;
}

// Function to get compliance report
function getComplianceReport($conn, $start_date, $end_date) {
    $data = [];
    
    // Documentation timeliness
    $doc_query = "SELECT 
                    COUNT(*) as total_sessions,
                    SUM(CASE WHEN sn.id IS NOT NULL THEN 1 ELSE 0 END) as documented_sessions,
                    SUM(CASE WHEN TIMESTAMPDIFF(HOUR, s.date, sn.created_at) <= 24 THEN 1 ELSE 0 END) as timely_docs,
                    SUM(CASE WHEN TIMESTAMPDIFF(HOUR, s.date, sn.created_at) > 24 AND sn.id IS NOT NULL THEN 1 ELSE 0 END) as late_docs,
                    SUM(CASE WHEN sn.id IS NULL THEN 1 ELSE 0 END) as missing_docs
                FROM autism_sessions s
                LEFT JOIN autism_session_notes sn ON s.id = sn.session_id
                WHERE s.date BETWEEN ? AND ? AND s.status = 'completed'";
    $stmt = $conn->prepare($doc_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $data['documentation'] = $stmt->get_result()->fetch_assoc();
    
    // Supervision compliance
    $supervision_query = "SELECT 
                            p.id,
                            CONCAT(p.first_name, ' ', p.last_name) as provider_name,
                            p.credential_type,
                            COUNT(DISTINCT s.id) as session_count,
                            SUM(s.duration) as total_minutes,
                            COUNT(DISTINCT DATE_FORMAT(s.date, '%Y-%m')) as months_worked,
                            COUNT(DISTINCT sup.id) as supervision_sessions
                        FROM autism_providers p
                        INNER JOIN autism_sessions s ON p.id = s.provider_id
                            AND s.date BETWEEN ? AND ? AND s.status = 'completed'
                        LEFT JOIN autism_supervision sup ON p.id = sup.supervisee_id
                            AND sup.date BETWEEN ? AND ?
                        WHERE p.credential_type IN ('RBT', 'BT')
                        GROUP BY p.id
                        ORDER BY p.last_name, p.first_name";
    $stmt = $conn->prepare($supervision_query);
    $stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $data['supervision'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Credential verification
    $credential_query = "SELECT 
                            credential_type,
                            COUNT(*) as total_providers,
                            SUM(CASE WHEN credential_expiry > CURDATE() THEN 1 ELSE 0 END) as valid_credentials,
                            SUM(CASE WHEN credential_expiry <= CURDATE() THEN 1 ELSE 0 END) as expired_credentials,
                            SUM(CASE WHEN credential_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon
                        FROM autism_providers
                        WHERE active = 1
                        GROUP BY credential_type
                        ORDER BY credential_type";
    $data['credentials'] = $conn->query($credential_query)->fetch_all(MYSQLI_ASSOC);
    
    return $data;
}

// Function to get no-show/cancellation report
function getAttendanceReport($conn, $client_id, $start_date, $end_date) {
    $data = [];
    
    $attendance_query = "SELECT 
                            s.status,
                            COUNT(*) as count,
                            COUNT(DISTINCT s.client_id) as client_count,
                            COUNT(DISTINCT DATE(s.date)) as days_affected
                        FROM autism_sessions s
                        WHERE s.date BETWEEN ? AND ?";
    
    if ($client_id) {
        $attendance_query .= " AND s.client_id = ?";
    }
    
    $attendance_query .= " GROUP BY s.status
                         ORDER BY count DESC";
    
    $stmt = $conn->prepare($attendance_query);
    if ($client_id) {
        $stmt->bind_param("ssi", $start_date, $end_date, $client_id);
    } else {
        $stmt->bind_param("ss", $start_date, $end_date);
    }
    $stmt->execute();
    $data['status_breakdown'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get detailed no-show/cancellation list
    $detail_query = "SELECT 
                        s.date,
                        s.status,
                        s.cancellation_reason,
                        CONCAT(c.first_name, ' ', c.last_name) as client_name,
                        CONCAT(p.first_name, ' ', p.last_name) as provider_name,
                        s.service_type
                    FROM autism_sessions s
                    LEFT JOIN autism_clients c ON s.client_id = c.id
                    LEFT JOIN autism_providers p ON s.provider_id = p.id
                    WHERE s.date BETWEEN ? AND ? 
                        AND s.status IN ('no_show', 'cancelled', 'late_cancel')";
    
    if ($client_id) {
        $detail_query .= " AND s.client_id = ?";
    }
    
    $detail_query .= " ORDER BY s.date DESC
                      LIMIT 100";
    
    $stmt = $conn->prepare($detail_query);
    if ($client_id) {
        $stmt->bind_param("ssi", $start_date, $end_date, $client_id);
    } else {
        $stmt->bind_param("ss", $start_date, $end_date);
    }
    $stmt->execute();
    $data['details'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $data;
}

// Function to get outcome measurements
function getOutcomeReport($conn, $client_id, $start_date, $end_date) {
    $data = [];
    
    if ($client_id) {
        // Get assessment scores over time
        $assessment_query = "SELECT 
                                a.assessment_date,
                                a.assessment_type,
                                a.scores,
                                a.summary
                            FROM autism_assessments a
                            WHERE a.client_id = ? 
                                AND a.assessment_date BETWEEN ? AND ?
                            ORDER BY a.assessment_date DESC";
        $stmt = $conn->prepare($assessment_query);
        $stmt->bind_param("iss", $client_id, $start_date, $end_date);
        $stmt->execute();
        $data['assessments'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Get behavior data trends
        $behavior_query = "SELECT 
                            DATE(sn.date) as session_date,
                            sn.behaviors_data
                        FROM autism_session_notes sn
                        WHERE sn.client_id = ? 
                            AND sn.date BETWEEN ? AND ?
                            AND sn.behaviors_data IS NOT NULL
                        ORDER BY sn.date";
        $stmt = $conn->prepare($behavior_query);
        $stmt->bind_param("iss", $client_id, $start_date, $end_date);
        $stmt->execute();
        $data['behavior_trends'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        // Get overall outcome summary
        $outcome_query = "SELECT 
                            c.id,
                            CONCAT(c.first_name, ' ', c.last_name) as client_name,
                            COUNT(DISTINCT a.id) as assessment_count,
                            COUNT(DISTINCT g.id) as active_goals,
                            COUNT(DISTINCT CASE WHEN g.status = 'achieved' THEN g.id END) as achieved_goals
                        FROM autism_clients c
                        LEFT JOIN autism_assessments a ON c.id = a.client_id 
                            AND a.assessment_date BETWEEN ? AND ?
                        LEFT JOIN autism_goals g ON c.id = g.client_id
                        WHERE c.active = 1
                        GROUP BY c.id
                        HAVING assessment_count > 0 OR active_goals > 0
                        ORDER BY c.last_name, c.first_name";
        $stmt = $conn->prepare($outcome_query);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $data['client_outcomes'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    return $data;
}

// Get report data based on type
$report_data = [];
switch ($report_type) {
    case 'progress':
        $report_data = getProgressReport($conn, $client_id, $start_date, $end_date);
        break;
    case 'service_delivery':
        $report_data = getServiceDeliverySummary($conn, $client_id, $start_date, $end_date);
        break;
    case 'compliance':
        $report_data = getComplianceReport($conn, $start_date, $end_date);
        break;
    case 'attendance':
        $report_data = getAttendanceReport($conn, $client_id, $start_date, $end_date);
        break;
    case 'outcomes':
        $report_data = getOutcomeReport($conn, $client_id, $start_date, $end_date);
        break;
}

// Handle export
if ($export_format) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="clinical_report_' . $report_type . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Export logic based on report type
    switch ($report_type) {
        case 'progress':
            if ($client_id && isset($report_data['goals'])) {
                fputcsv($output, ['Goal', 'Target Date', 'Status', 'Sessions', 'Avg Progress']);
                foreach ($report_data['goals'] as $goal) {
                    fputcsv($output, [
                        $goal['goal_text'],
                        $goal['target_date'],
                        $goal['status'],
                        $goal['session_count'],
                        round($goal['avg_progress'], 1) . '%'
                    ]);
                }
            }
            break;
        case 'service_delivery':
            if (isset($report_data['daily_summary'])) {
                fputcsv($output, ['Date', 'Service Type', 'Sessions', 'Total Minutes', 'Avg Duration']);
                foreach ($report_data['daily_summary'] as $row) {
                    fputcsv($output, [
                        $row['service_date'],
                        $row['service_type'],
                        $row['session_count'],
                        $row['total_minutes'],
                        round($row['avg_duration'], 1)
                    ]);
                }
            }
            break;
        // Add more export formats as needed
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical Reports - Autism Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-card {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        }
        .report-header {
            background-color: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            border-radius: 0.375rem 0.375rem 0 0;
        }
        .metric-card {
            text-align: center;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        .metric-value {
            font-size: 2rem;
            font-weight: 600;
            color: #495057;
        }
        .metric-label {
            color: #6c757d;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .progress-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .progress-high { background-color: #28a745; }
        .progress-medium { background-color: #ffc107; }
        .progress-low { background-color: #dc3545; }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
        }
        .export-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .report-card {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary no-print">
        <div class="container-fluid">
            <a class="navbar-brand" href="simple_dashboard.php">Autism Services</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="simple_dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="clients.php">Clients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="clinical_reports.php">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">Clinical Reports</h1>
                
                <!-- Report Filters -->
                <div class="card mb-4 no-print">
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Report Type</label>
                                <select name="report_type" class="form-select" onchange="this.form.submit()">
                                    <option value="progress" <?= $report_type == 'progress' ? 'selected' : '' ?>>Client Progress</option>
                                    <option value="service_delivery" <?= $report_type == 'service_delivery' ? 'selected' : '' ?>>Service Delivery</option>
                                    <option value="compliance" <?= $report_type == 'compliance' ? 'selected' : '' ?>>Compliance</option>
                                    <option value="attendance" <?= $report_type == 'attendance' ? 'selected' : '' ?>>Attendance</option>
                                    <option value="outcomes" <?= $report_type == 'outcomes' ? 'selected' : '' ?>>Outcomes</option>
                                </select>
                            </div>
                            
                            <?php if ($report_type != 'compliance'): ?>
                            <div class="col-md-3">
                                <label class="form-label">Client</label>
                                <select name="client_id" class="form-select">
                                    <option value="">All Clients</option>
                                    <?php foreach ($clients as $client): ?>
                                        <option value="<?= $client['id'] ?>" <?= $client_id == $client['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($client['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-md-2">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?= $start_date ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="<?= $end_date ?>">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Export Options -->
                <div class="export-buttons no-print">
                    <button onclick="window.print()" class="btn btn-sm btn-secondary">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-sm btn-success">
                        <i class="bi bi-file-earmark-csv"></i> Export CSV
                    </a>
                </div>

                <!-- Report Content -->
                <?php if ($report_type == 'progress'): ?>
                    <?php if ($client_id && isset($report_data['client'])): ?>
                        <!-- Individual Client Progress Report -->
                        <div class="report-card">
                            <div class="report-header">
                                <h2><?= htmlspecialchars($report_data['client']['first_name'] . ' ' . $report_data['client']['last_name']) ?> - Progress Report</h2>
                                <p class="mb-0 text-muted">Period: <?= date('M d, Y', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?></p>
                            </div>
                            <div class="card-body">
                                <!-- Session Summary -->
                                <div class="row mb-4">
                                    <div class="col-md-3">
                                        <div class="metric-card">
                                            <div class="metric-value"><?= $report_data['session_summary']['total_sessions'] ?? 0 ?></div>
                                            <div class="metric-label">Total Sessions</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="metric-card">
                                            <div class="metric-value"><?= round(($report_data['session_summary']['total_minutes'] ?? 0) / 60, 1) ?></div>
                                            <div class="metric-label">Total Hours</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="metric-card">
                                            <div class="metric-value"><?= round($report_data['session_summary']['avg_duration'] ?? 0) ?></div>
                                            <div class="metric-label">Avg Minutes/Session</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="metric-card">
                                            <div class="metric-value"><?= $report_data['session_summary']['days_with_service'] ?? 0 ?></div>
                                            <div class="metric-label">Service Days</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Goals Progress -->
                                <h3>Goals Progress</h3>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Goal</th>
                                                <th>Target Date</th>
                                                <th>Status</th>
                                                <th>Sessions</th>
                                                <th>Avg Progress</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data['goals'] as $goal): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($goal['goal_text']) ?></td>
                                                    <td><?= date('M d, Y', strtotime($goal['target_date'])) ?></td>
                                                    <td>
                                                        <span class="badge bg-<?= $goal['status'] == 'achieved' ? 'success' : ($goal['status'] == 'in_progress' ? 'primary' : 'secondary') ?>">
                                                            <?= ucfirst(str_replace('_', ' ', $goal['status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= $goal['session_count'] ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar" role="progressbar" 
                                                                 style="width: <?= $goal['avg_progress'] ?>%"
                                                                 aria-valuenow="<?= $goal['avg_progress'] ?>" 
                                                                 aria-valuemin="0" aria-valuemax="100">
                                                                <?= round($goal['avg_progress'], 1) ?>%
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
                    <?php else: ?>
                        <!-- All Clients Progress Summary -->
                        <div class="report-card">
                            <div class="report-header">
                                <h2>Client Progress Summary</h2>
                                <p class="mb-0 text-muted">Period: <?= date('M d, Y', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?></p>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Client</th>
                                                <th>Active Goals</th>
                                                <th>Sessions</th>
                                                <th>Total Hours</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data['clients'] as $client): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($client['client_name']) ?></td>
                                                    <td><?= $client['goal_count'] ?></td>
                                                    <td><?= $client['session_count'] ?></td>
                                                    <td><?= round(($client['total_minutes'] ?? 0) / 60, 1) ?></td>
                                                    <td>
                                                        <a href="?report_type=progress&client_id=<?= $client['id'] ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
                                                           class="btn btn-sm btn-primary">View Details</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                <?php elseif ($report_type == 'service_delivery'): ?>
                    <!-- Service Delivery Report -->
                    <div class="report-card">
                        <div class="report-header">
                            <h2>Service Delivery Summary</h2>
                            <p class="mb-0 text-muted">Period: <?= date('M d, Y', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?></p>
                        </div>
                        <div class="card-body">
                            <!-- Service Type Breakdown -->
                            <h3>Service Type Breakdown</h3>
                            <div class="row mb-4">
                                <?php foreach ($report_data['service_breakdown'] as $service): ?>
                                    <div class="col-md-3">
                                        <div class="metric-card">
                                            <h5><?= htmlspecialchars($service['service_type']) ?></h5>
                                            <div class="metric-value"><?= round($service['total_minutes'] / 60, 1) ?></div>
                                            <div class="metric-label">Hours</div>
                                            <small class="text-muted">
                                                <?= $service['session_count'] ?> sessions<br>
                                                <?= $service['client_count'] ?> clients
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Daily Summary Chart -->
                            <h3>Daily Service Delivery</h3>
                            <div class="chart-container">
                                <canvas id="serviceChart"></canvas>
                            </div>

                            <!-- Detailed Table -->
                            <h3>Daily Details</h3>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Service Type</th>
                                            <th>Sessions</th>
                                            <th>Total Minutes</th>
                                            <th>Avg Duration</th>
                                            <th>Providers</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data['daily_summary'] as $day): ?>
                                            <tr>
                                                <td><?= date('M d, Y', strtotime($day['service_date'])) ?></td>
                                                <td><?= htmlspecialchars($day['service_type']) ?></td>
                                                <td><?= $day['session_count'] ?></td>
                                                <td><?= $day['total_minutes'] ?></td>
                                                <td><?= round($day['avg_duration'], 1) ?></td>
                                                <td><?= $day['provider_count'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($report_type == 'compliance'): ?>
                    <!-- Compliance Report -->
                    <div class="report-card">
                        <div class="report-header">
                            <h2>Compliance Report</h2>
                            <p class="mb-0 text-muted">Period: <?= date('M d, Y', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?></p>
                        </div>
                        <div class="card-body">
                            <!-- Documentation Compliance -->
                            <h3>Documentation Compliance</h3>
                            <div class="row mb-4">
                                <div class="col-md-3">
                                    <div class="metric-card">
                                        <div class="metric-value">
                                            <?= round(($report_data['documentation']['documented_sessions'] / $report_data['documentation']['total_sessions']) * 100) ?>%
                                        </div>
                                        <div class="metric-label">Documentation Rate</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="metric-card">
                                        <div class="metric-value">
                                            <?= round(($report_data['documentation']['timely_docs'] / $report_data['documentation']['total_sessions']) * 100) ?>%
                                        </div>
                                        <div class="metric-label">Timely Documentation</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="metric-card">
                                        <div class="metric-value"><?= $report_data['documentation']['late_docs'] ?></div>
                                        <div class="metric-label">Late Documentation</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="metric-card">
                                        <div class="metric-value"><?= $report_data['documentation']['missing_docs'] ?></div>
                                        <div class="metric-label">Missing Documentation</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Supervision Compliance -->
                            <h3>Supervision Compliance</h3>
                            <div class="table-responsive mb-4">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Provider</th>
                                            <th>Credential</th>
                                            <th>Sessions</th>
                                            <th>Hours</th>
                                            <th>Months</th>
                                            <th>Supervision Sessions</th>
                                            <th>Compliance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data['supervision'] as $provider): ?>
                                            <?php 
                                                $required_supervision = $provider['months_worked'] * 2; // 2 sessions per month
                                                $compliance_rate = ($required_supervision > 0) ? 
                                                    round(($provider['supervision_sessions'] / $required_supervision) * 100) : 0;
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($provider['provider_name']) ?></td>
                                                <td><?= $provider['credential_type'] ?></td>
                                                <td><?= $provider['session_count'] ?></td>
                                                <td><?= round($provider['total_minutes'] / 60, 1) ?></td>
                                                <td><?= $provider['months_worked'] ?></td>
                                                <td><?= $provider['supervision_sessions'] ?> / <?= $required_supervision ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $compliance_rate >= 100 ? 'success' : ($compliance_rate >= 75 ? 'warning' : 'danger') ?>">
                                                        <?= $compliance_rate ?>%
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Credential Status -->
                            <h3>Credential Status</h3>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Credential Type</th>
                                            <th>Total Providers</th>
                                            <th>Valid</th>
                                            <th>Expired</th>
                                            <th>Expiring Soon</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data['credentials'] as $cred): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($cred['credential_type']) ?></td>
                                                <td><?= $cred['total_providers'] ?></td>
                                                <td><span class="text-success"><?= $cred['valid_credentials'] ?></span></td>
                                                <td><span class="text-danger"><?= $cred['expired_credentials'] ?></span></td>
                                                <td><span class="text-warning"><?= $cred['expiring_soon'] ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($report_type == 'attendance'): ?>
                    <!-- Attendance Report -->
                    <div class="report-card">
                        <div class="report-header">
                            <h2>Attendance Report</h2>
                            <p class="mb-0 text-muted">Period: <?= date('M d, Y', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?></p>
                        </div>
                        <div class="card-body">
                            <!-- Status Breakdown -->
                            <h3>Session Status Breakdown</h3>
                            <div class="row mb-4">
                                <?php 
                                    $total_sessions = array_sum(array_column($report_data['status_breakdown'], 'count'));
                                    foreach ($report_data['status_breakdown'] as $status): 
                                        $percentage = round(($status['count'] / $total_sessions) * 100, 1);
                                ?>
                                    <div class="col-md-3">
                                        <div class="metric-card">
                                            <h5><?= ucfirst(str_replace('_', ' ', $status['status'])) ?></h5>
                                            <div class="metric-value"><?= $status['count'] ?></div>
                                            <div class="metric-label"><?= $percentage ?>% of sessions</div>
                                            <small class="text-muted">
                                                <?= $status['client_count'] ?> clients affected<br>
                                                <?= $status['days_affected'] ?> days
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Recent No-Shows/Cancellations -->
                            <h3>Recent No-Shows & Cancellations</h3>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Client</th>
                                            <th>Provider</th>
                                            <th>Service Type</th>
                                            <th>Status</th>
                                            <th>Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($report_data['details'] as $session): ?>
                                            <tr>
                                                <td><?= date('M d, Y', strtotime($session['date'])) ?></td>
                                                <td><?= htmlspecialchars($session['client_name']) ?></td>
                                                <td><?= htmlspecialchars($session['provider_name']) ?></td>
                                                <td><?= htmlspecialchars($session['service_type']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= $session['status'] == 'no_show' ? 'danger' : 'warning' ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $session['status'])) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($session['cancellation_reason'] ?? 'N/A') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($report_type == 'outcomes'): ?>
                    <!-- Outcomes Report -->
                    <div class="report-card">
                        <div class="report-header">
                            <h2>Outcome Measurements</h2>
                            <p class="mb-0 text-muted">Period: <?= date('M d, Y', strtotime($start_date)) ?> - <?= date('M d, Y', strtotime($end_date)) ?></p>
                        </div>
                        <div class="card-body">
                            <?php if ($client_id && isset($report_data['assessments'])): ?>
                                <!-- Individual Client Outcomes -->
                                <h3>Assessment History</h3>
                                <div class="table-responsive mb-4">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Assessment Type</th>
                                                <th>Scores</th>
                                                <th>Summary</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data['assessments'] as $assessment): ?>
                                                <tr>
                                                    <td><?= date('M d, Y', strtotime($assessment['assessment_date'])) ?></td>
                                                    <td><?= htmlspecialchars($assessment['assessment_type']) ?></td>
                                                    <td><?= htmlspecialchars($assessment['scores']) ?></td>
                                                    <td><?= htmlspecialchars($assessment['summary']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Behavior Trends Chart -->
                                <?php if (!empty($report_data['behavior_trends'])): ?>
                                    <h3>Behavior Trends</h3>
                                    <div class="chart-container">
                                        <canvas id="behaviorChart"></canvas>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- All Clients Outcome Summary -->
                                <h3>Client Outcome Summary</h3>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Client</th>
                                                <th>Assessments</th>
                                                <th>Active Goals</th>
                                                <th>Achieved Goals</th>
                                                <th>Achievement Rate</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data['client_outcomes'] as $client): ?>
                                                <?php 
                                                    $achievement_rate = ($client['active_goals'] > 0) ? 
                                                        round(($client['achieved_goals'] / $client['active_goals']) * 100) : 0;
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($client['client_name']) ?></td>
                                                    <td><?= $client['assessment_count'] ?></td>
                                                    <td><?= $client['active_goals'] ?></td>
                                                    <td><?= $client['achieved_goals'] ?></td>
                                                    <td>
                                                        <div class="progress" style="height: 20px; min-width: 100px;">
                                                            <div class="progress-bar bg-<?= $achievement_rate >= 75 ? 'success' : ($achievement_rate >= 50 ? 'warning' : 'danger') ?>" 
                                                                 role="progressbar" 
                                                                 style="width: <?= $achievement_rate ?>%"
                                                                 aria-valuenow="<?= $achievement_rate ?>" 
                                                                 aria-valuemin="0" aria-valuemax="100">
                                                                <?= $achievement_rate ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <a href="?report_type=outcomes&client_id=<?= $client['id'] ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
                                                           class="btn btn-sm btn-primary">View Details</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if ($report_type == 'service_delivery' && !empty($report_data['daily_summary'])): ?>
    <script>
        // Service Delivery Chart
        const serviceData = <?= json_encode($report_data['daily_summary']) ?>;
        const dates = [...new Set(serviceData.map(d => d.service_date))];
        const serviceTypes = [...new Set(serviceData.map(d => d.service_type))];
        
        const datasets = serviceTypes.map((type, index) => {
            const color = `hsl(${index * 360 / serviceTypes.length}, 70%, 50%)`;
            return {
                label: type,
                data: dates.map(date => {
                    const item = serviceData.find(d => d.service_date === date && d.service_type === type);
                    return item ? item.total_minutes / 60 : 0;
                }),
                backgroundColor: color,
                borderColor: color,
                borderWidth: 1
            };
        });
        
        const ctx = document.getElementById('serviceChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: dates.map(d => new Date(d).toLocaleDateString()),
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        stacked: true,
                    },
                    y: {
                        stacked: true,
                        title: {
                            display: true,
                            text: 'Hours'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Daily Service Hours by Type'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
    
    <?php if ($report_type == 'outcomes' && $client_id && !empty($report_data['behavior_trends'])): ?>
    <script>
        // Behavior Trends Chart
        const behaviorData = <?= json_encode($report_data['behavior_trends']) ?>;
        // Parse behavior data and create chart
        // This would need to be customized based on actual behavior data structure
    </script>
    <?php endif; ?>
</body>
</html>