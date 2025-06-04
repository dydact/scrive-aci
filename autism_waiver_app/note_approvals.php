<?php
/**
 * Note Approvals - Case Manager Portal
 * 
 * @package   Scrive ACI
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
 * @license   MIT License
 */

session_start();

// Basic authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'case_manager') {
    header('Location: simple_login.php');
    exit;
}

// Database connection
require_once('../src/database/ClientRepository.php');
$pdo = new PDO('mysql:host=localhost;dbname=autism_waiver', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get filter parameters
$filter_client = $_GET['client'] ?? '';
$filter_provider = $_GET['provider'] ?? '';
$filter_date_start = $_GET['date_start'] ?? date('Y-m-d', strtotime('-30 days'));
$filter_date_end = $_GET['date_end'] ?? date('Y-m-d');
$filter_status = $_GET['status'] ?? 'pending';

// Handle approval actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $note_ids = $_POST['note_ids'] ?? [];
    $electronic_signature = $_POST['electronic_signature'] ?? '';
    
    if ($action && !empty($note_ids) && $electronic_signature === $_SESSION['user_name']) {
        try {
            $pdo->beginTransaction();
            
            foreach ($note_ids as $note_id) {
                if ($action === 'approve') {
                    $stmt = $pdo->prepare("
                        UPDATE autism_session 
                        SET approval_status = 'approved',
                            approval_date = NOW(),
                            approved_by = ?,
                            approval_signature = ?
                        WHERE session_id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id'], $electronic_signature, $note_id]);
                    
                } elseif ($action === 'request_revision') {
                    $revision_reason = $_POST['revision_reason'][$note_id] ?? 'Revision needed';
                    $stmt = $pdo->prepare("
                        UPDATE autism_session 
                        SET approval_status = 'revision_requested',
                            revision_requested_date = NOW(),
                            revision_requested_by = ?,
                            revision_reason = ?
                        WHERE session_id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id'], $revision_reason, $note_id]);
                    
                } elseif ($action === 'reject') {
                    $rejection_reason = $_POST['rejection_reason'][$note_id] ?? 'Rejected';
                    $stmt = $pdo->prepare("
                        UPDATE autism_session 
                        SET approval_status = 'rejected',
                            rejection_date = NOW(),
                            rejected_by = ?,
                            rejection_reason = ?
                        WHERE session_id = ?
                    ");
                    $stmt->execute([$_SESSION['user_id'], $rejection_reason, $note_id]);
                }
            }
            
            $pdo->commit();
            $_SESSION['success_message'] = count($note_ids) . ' note(s) processed successfully.';
            header('Location: note_approvals.php?' . http_build_query($_GET));
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = 'Error processing notes: ' . $e->getMessage();
        }
    } else {
        $error_message = 'Invalid signature or no notes selected.';
    }
}

// Build query for pending notes
$query = "
    SELECT 
        s.session_id,
        s.date,
        s.time_start,
        s.time_end,
        s.duration_minutes,
        s.narrative_note,
        s.interventions_used,
        s.recommendations,
        s.participation_level,
        s.goal_achievement,
        s.incidents,
        s.created_at,
        s.approval_status,
        TIMESTAMPDIFF(HOUR, s.created_at, NOW()) as hours_pending,
        c.client_id,
        c.first_name as client_first,
        c.last_name as client_last,
        c.medicaid_id,
        st.name as service_type,
        st.abbreviation as service_abbr,
        st.category as service_category,
        e.first_name as provider_first,
        e.last_name as provider_last,
        e.email as provider_email,
        COUNT(DISTINCT obj.obj_id) as objectives_worked,
        COUNT(DISTINCT inc.incident_id) as incident_count
    FROM autism_session s
    JOIN autism_client c ON s.client_id = c.client_id
    JOIN autism_service_type st ON s.service_type_id = st.service_type_id
    JOIN autism_employee e ON s.employee_id = e.employee_id
    LEFT JOIN autism_session_activity sa ON s.session_id = sa.session_id
    LEFT JOIN autism_objective obj ON sa.objective_id = obj.obj_id
    LEFT JOIN autism_session_incident inc ON s.session_id = inc.session_id
    WHERE 1=1
";

$params = [];

// Apply filters
if ($filter_status) {
    if ($filter_status === 'pending') {
        $query .= " AND (s.approval_status IS NULL OR s.approval_status = 'pending')";
    } else {
        $query .= " AND s.approval_status = ?";
        $params[] = $filter_status;
    }
}

if ($filter_client) {
    $query .= " AND c.client_id = ?";
    $params[] = $filter_client;
}

if ($filter_provider) {
    $query .= " AND e.employee_id = ?";
    $params[] = $filter_provider;
}

if ($filter_date_start) {
    $query .= " AND s.date >= ?";
    $params[] = $filter_date_start;
}

if ($filter_date_end) {
    $query .= " AND s.date <= ?";
    $params[] = $filter_date_end;
}

$query .= " GROUP BY s.session_id ORDER BY s.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get lists for filters
$clients_stmt = $pdo->query("
    SELECT DISTINCT c.client_id, c.first_name, c.last_name 
    FROM autism_client c 
    JOIN autism_session s ON c.client_id = s.client_id 
    ORDER BY c.last_name, c.first_name
");
$clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);

$providers_stmt = $pdo->query("
    SELECT DISTINCT e.employee_id, e.first_name, e.last_name 
    FROM autism_employee e 
    JOIN autism_session s ON e.employee_id = s.employee_id 
    ORDER BY e.last_name, e.first_name
");
$providers = $providers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for required fields
function checkRequiredFields($note) {
    $missing_fields = [];
    
    if (empty($note['narrative_note']) || strlen($note['narrative_note']) < 50) {
        $missing_fields[] = 'Narrative too short';
    }
    if (empty($note['participation_level'])) {
        $missing_fields[] = 'Participation level missing';
    }
    if (empty($note['goal_achievement'])) {
        $missing_fields[] = 'Goal achievement missing';
    }
    if ($note['objectives_worked'] == 0) {
        $missing_fields[] = 'No objectives documented';
    }
    
    return $missing_fields;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📝 Note Approvals - Case Manager Portal</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #2563eb;
            --secondary-color: #f0f9ff;
            --accent-color: #059669;
            --warning-color: #f59e0b;
            --danger-color: #dc2626;
            --success-color: #10b981;
            --text-color: #1e293b;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            color: var(--text-color);
        }
        
        .navbar {
            background: white;
            box-shadow: var(--shadow);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .nav-link {
            color: var(--text-color);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        .nav-link:hover {
            background: var(--secondary-color);
            color: var(--primary-color);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
        }
        
        .filters-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-label {
            font-weight: 600;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .form-control {
            padding: 0.5rem 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .notes-table {
            background: white;
            border-radius: 1rem;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .table-header {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-title {
            font-weight: 600;
            font-size: 1.125rem;
        }
        
        .note-card {
            border-bottom: 1px solid var(--border-color);
            padding: 1.5rem;
            transition: all 0.2s;
        }
        
        .note-card:hover {
            background: #f8fafc;
        }
        
        .note-card.urgent {
            border-left: 4px solid var(--warning-color);
            background: #fef3c7;
        }
        
        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .note-info {
            flex: 1;
        }
        
        .client-name {
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }
        
        .note-meta {
            display: flex;
            gap: 1.5rem;
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .note-status {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
        }
        
        .time-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .time-ok {
            background: #dcfce7;
            color: #166534;
        }
        
        .time-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .time-urgent {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .missing-fields {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #fee2e2;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            color: #991b1b;
        }
        
        .note-content {
            margin-bottom: 1rem;
        }
        
        .content-section {
            margin-bottom: 1rem;
        }
        
        .content-label {
            font-weight: 600;
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .content-text {
            color: var(--text-color);
            line-height: 1.6;
            font-size: 0.875rem;
        }
        
        .objectives-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .objective-item {
            background: #f8fafc;
            padding: 0.5rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
        }
        
        .note-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox {
            width: 1.25rem;
            height: 1.25rem;
            cursor: pointer;
        }
        
        .expand-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }
        
        .expand-btn:hover {
            background: var(--secondary-color);
        }
        
        .expanded-content {
            display: none;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        .expanded-content.show {
            display: block;
        }
        
        .revision-input {
            width: 100%;
            margin-top: 0.5rem;
            padding: 0.5rem;
            border: 2px solid var(--warning-color);
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        
        .rejection-input {
            width: 100%;
            margin-top: 0.5rem;
            padding: 0.5rem;
            border: 2px solid var(--danger-color);
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        
        .batch-actions {
            position: sticky;
            bottom: 0;
            background: white;
            border-top: 2px solid var(--border-color);
            padding: 1rem;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
            display: none;
        }
        
        .batch-actions.show {
            display: block;
        }
        
        .batch-actions-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }
        
        .signature-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }
        
        .signature-input {
            max-width: 300px;
        }
        
        .batch-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .note-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .note-meta {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .batch-actions-inner {
                flex-direction: column;
            }
            
            .signature-section {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="case_manager_portal.php" class="navbar-brand">
                📋 Case Manager Portal
            </a>
            <div class="navbar-nav">
                <a href="case_manager_portal.php" class="nav-link">Dashboard</a>
                <a href="note_approvals.php" class="nav-link" style="background: var(--secondary-color); color: var(--primary-color);">Note Approvals</a>
                <a href="treatment_plan_manager.php" class="nav-link">Treatment Plans</a>
                <a href="reports.php" class="nav-link">Reports</a>
                <span class="nav-link">👤 <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">📝 Session Note Approvals</h1>
            <button class="btn btn-primary" onclick="selectAll()">
                Select All Visible
            </button>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                ✅ <?php echo htmlspecialchars($_SESSION['success_message']); ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                ❌ <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters-card">
            <form method="get" action="">
                <div class="filter-row">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                            <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="revision_requested" <?php echo $filter_status === 'revision_requested' ? 'selected' : ''; ?>>Revision Requested</option>
                            <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="" <?php echo $filter_status === '' ? 'selected' : ''; ?>>All</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Client</label>
                        <select name="client" class="form-control" onchange="this.form.submit()">
                            <option value="">All Clients</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['client_id']; ?>" <?php echo $filter_client == $client['client_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($client['last_name'] . ', ' . $client['first_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Provider</label>
                        <select name="provider" class="form-control" onchange="this.form.submit()">
                            <option value="">All Providers</option>
                            <?php foreach ($providers as $provider): ?>
                                <option value="<?php echo $provider['employee_id']; ?>" <?php echo $filter_provider == $provider['employee_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($provider['last_name'] . ', ' . $provider['first_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_start" class="form-control" value="<?php echo $filter_date_start; ?>" onchange="this.form.submit()">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_end" class="form-control" value="<?php echo $filter_date_end; ?>" onchange="this.form.submit()">
                    </div>
                </div>
            </form>
        </div>

        <!-- Statistics -->
        <div class="stats-row">
            <?php
            $pending_count = 0;
            $urgent_count = 0;
            $missing_fields_count = 0;
            
            foreach ($notes as $note) {
                if ($note['approval_status'] === null || $note['approval_status'] === 'pending') {
                    $pending_count++;
                    if ($note['hours_pending'] > 72) {
                        $urgent_count++;
                    }
                    if (!empty(checkRequiredFields($note))) {
                        $missing_fields_count++;
                    }
                }
            }
            ?>
            
            <div class="stat-card">
                <div class="stat-number" style="color: var(--primary-color);"><?php echo $pending_count; ?></div>
                <div class="stat-label">Pending Approval</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number" style="color: var(--warning-color);"><?php echo $urgent_count; ?></div>
                <div class="stat-label">Pending > 72 Hours</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number" style="color: var(--danger-color);"><?php echo $missing_fields_count; ?></div>
                <div class="stat-label">Missing Required Fields</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number" style="color: var(--success-color);"><?php echo count($notes); ?></div>
                <div class="stat-label">Total Notes</div>
            </div>
        </div>

        <!-- Notes List -->
        <form method="post" action="" id="notesForm">
            <div class="notes-table">
                <div class="table-header">
                    <h3 class="table-title">Session Notes</h3>
                    <span><?php echo count($notes); ?> notes found</span>
                </div>
                
                <?php foreach ($notes as $note): ?>
                    <?php 
                    $missing_fields = checkRequiredFields($note);
                    $is_urgent = $note['hours_pending'] > 72;
                    ?>
                    
                    <div class="note-card <?php echo $is_urgent ? 'urgent' : ''; ?>">
                        <div class="note-header">
                            <div class="note-info">
                                <h4 class="client-name">
                                    <?php echo htmlspecialchars($note['client_last'] . ', ' . $note['client_first']); ?>
                                    <span style="font-weight: normal; color: #64748b;">(MA: <?php echo htmlspecialchars($note['medicaid_id']); ?>)</span>
                                </h4>
                                <div class="note-meta">
                                    <span>📅 <?php echo date('M d, Y', strtotime($note['date'])); ?></span>
                                    <span>⏰ <?php echo date('g:i A', strtotime($note['time_start'])) . ' - ' . date('g:i A', strtotime($note['time_end'])); ?></span>
                                    <span>⏱️ <?php echo $note['duration_minutes']; ?> minutes</span>
                                    <span>🏥 <?php echo htmlspecialchars($note['service_type']); ?></span>
                                    <span>👤 <?php echo htmlspecialchars($note['provider_first'] . ' ' . $note['provider_last']); ?></span>
                                </div>
                                <?php if ($note['objectives_worked'] > 0): ?>
                                    <div style="margin-top: 0.5rem; color: var(--accent-color); font-size: 0.875rem;">
                                        🎯 <?php echo $note['objectives_worked']; ?> objective(s) worked on
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="note-status">
                                <?php if ($note['hours_pending'] <= 24): ?>
                                    <span class="time-badge time-ok">< 24 hours</span>
                                <?php elseif ($note['hours_pending'] <= 72): ?>
                                    <span class="time-badge time-warning"><?php echo round($note['hours_pending']); ?> hours</span>
                                <?php else: ?>
                                    <span class="time-badge time-urgent">⚠️ <?php echo round($note['hours_pending'] / 24); ?> days</span>
                                <?php endif; ?>
                                
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" name="note_ids[]" value="<?php echo $note['session_id']; ?>" class="checkbox note-checkbox">
                                    <label>Select</label>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($missing_fields)): ?>
                            <div class="missing-fields">
                                ⚠️ Missing required fields: <?php echo implode(', ', $missing_fields); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="note-content">
                            <div class="content-section">
                                <div class="content-label">Session Narrative</div>
                                <div class="content-text"><?php echo nl2br(htmlspecialchars(substr($note['narrative_note'], 0, 300))); ?>...</div>
                            </div>
                            
                            <button type="button" class="expand-btn" onclick="toggleExpanded(<?php echo $note['session_id']; ?>)">
                                View Full Details ▼
                            </button>
                            
                            <div id="expanded_<?php echo $note['session_id']; ?>" class="expanded-content">
                                <div class="content-section">
                                    <div class="content-label">Full Narrative</div>
                                    <div class="content-text"><?php echo nl2br(htmlspecialchars($note['narrative_note'])); ?></div>
                                </div>
                                
                                <?php if ($note['interventions_used']): ?>
                                    <div class="content-section">
                                        <div class="content-label">Interventions Used</div>
                                        <div class="content-text"><?php echo nl2br(htmlspecialchars($note['interventions_used'])); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($note['recommendations']): ?>
                                    <div class="content-section">
                                        <div class="content-label">Recommendations</div>
                                        <div class="content-text"><?php echo nl2br(htmlspecialchars($note['recommendations'])); ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="content-section">
                                    <div class="content-label">Session Details</div>
                                    <div class="content-text">
                                        <strong>Participation Level:</strong> <?php echo ucfirst($note['participation_level'] ?? 'Not specified'); ?><br>
                                        <strong>Goal Achievement:</strong> <?php echo ucfirst(str_replace('_', ' ', $note['goal_achievement'] ?? 'Not specified')); ?><br>
                                        <strong>Incidents:</strong> <?php echo $note['incident_count'] > 0 ? $note['incident_count'] . ' incident(s) reported' : 'None'; ?><br>
                                        <strong>Documentation Time:</strong> <?php echo date('M d, Y g:i A', strtotime($note['created_at'])); ?>
                                    </div>
                                </div>
                                
                                <div class="note-actions">
                                    <button type="button" class="btn btn-success" onclick="approveNote(<?php echo $note['session_id']; ?>)">
                                        ✅ Approve
                                    </button>
                                    <button type="button" class="btn btn-warning" onclick="requestRevision(<?php echo $note['session_id']; ?>)">
                                        ✏️ Request Revision
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="rejectNote(<?php echo $note['session_id']; ?>)">
                                        ❌ Reject
                                    </button>
                                </div>
                                
                                <div id="revision_reason_<?php echo $note['session_id']; ?>" style="display: none;">
                                    <textarea name="revision_reason[<?php echo $note['session_id']; ?>]" class="revision-input" placeholder="Please explain what needs to be revised..."></textarea>
                                </div>
                                
                                <div id="rejection_reason_<?php echo $note['session_id']; ?>" style="display: none;">
                                    <textarea name="rejection_reason[<?php echo $note['session_id']; ?>]" class="rejection-input" placeholder="Please provide reason for rejection..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($notes)): ?>
                    <div style="padding: 3rem; text-align: center; color: #64748b;">
                        No notes found matching your filters.
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Batch Actions -->
            <div class="batch-actions" id="batchActions">
                <div class="batch-actions-inner">
                    <div class="signature-section">
                        <label for="electronic_signature" style="font-weight: 600;">Electronic Signature:</label>
                        <input type="text" name="electronic_signature" id="electronic_signature" class="form-control signature-input" placeholder="Type your full name">
                        <span style="color: #64748b; font-size: 0.875rem;">
                            <span id="selectedCount">0</span> note(s) selected
                        </span>
                    </div>
                    
                    <div class="batch-buttons">
                        <button type="submit" name="action" value="approve" class="btn btn-success">
                            ✅ Approve Selected
                        </button>
                        <button type="submit" name="action" value="request_revision" class="btn btn-warning">
                            ✏️ Request Revisions
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger">
                            ❌ Reject Selected
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Track selected notes
        let selectedNotes = new Set();
        
        // Initialize checkboxes
        document.querySelectorAll('.note-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateBatchActions);
        });
        
        function updateBatchActions() {
            selectedNotes.clear();
            document.querySelectorAll('.note-checkbox:checked').forEach(checkbox => {
                selectedNotes.add(checkbox.value);
            });
            
            const batchActions = document.getElementById('batchActions');
            const selectedCount = document.getElementById('selectedCount');
            
            if (selectedNotes.size > 0) {
                batchActions.classList.add('show');
                selectedCount.textContent = selectedNotes.size;
            } else {
                batchActions.classList.remove('show');
            }
        }
        
        function selectAll() {
            const checkboxes = document.querySelectorAll('.note-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
            
            updateBatchActions();
        }
        
        function toggleExpanded(noteId) {
            const content = document.getElementById('expanded_' + noteId);
            content.classList.toggle('show');
        }
        
        function approveNote(noteId) {
            // Check the note's checkbox
            const checkbox = document.querySelector(`input[value="${noteId}"]`);
            checkbox.checked = true;
            updateBatchActions();
            
            // Focus on signature field
            document.getElementById('electronic_signature').focus();
        }
        
        function requestRevision(noteId) {
            // Show revision reason textarea
            const revisionDiv = document.getElementById('revision_reason_' + noteId);
            revisionDiv.style.display = 'block';
            revisionDiv.querySelector('textarea').focus();
            
            // Check the note's checkbox
            const checkbox = document.querySelector(`input[value="${noteId}"]`);
            checkbox.checked = true;
            updateBatchActions();
        }
        
        function rejectNote(noteId) {
            // Show rejection reason textarea
            const rejectionDiv = document.getElementById('rejection_reason_' + noteId);
            rejectionDiv.style.display = 'block';
            rejectionDiv.querySelector('textarea').focus();
            
            // Check the note's checkbox
            const checkbox = document.querySelector(`input[value="${noteId}"]`);
            checkbox.checked = true;
            updateBatchActions();
        }
        
        // Form validation
        document.getElementById('notesForm').addEventListener('submit', function(e) {
            const signature = document.getElementById('electronic_signature').value.trim();
            
            if (!signature) {
                e.preventDefault();
                alert('Please provide your electronic signature to approve notes.');
                document.getElementById('electronic_signature').focus();
                return false;
            }
            
            if (selectedNotes.size === 0) {
                e.preventDefault();
                alert('Please select at least one note to process.');
                return false;
            }
            
            // Validate revision/rejection reasons if applicable
            const action = e.submitter.value;
            if (action === 'request_revision' || action === 'reject') {
                let missingReasons = false;
                
                selectedNotes.forEach(noteId => {
                    const reasonField = document.querySelector(`textarea[name="${action === 'request_revision' ? 'revision_reason' : 'rejection_reason'}[${noteId}]"]`);
                    if (reasonField && reasonField.closest('div').style.display !== 'none' && !reasonField.value.trim()) {
                        missingReasons = true;
                    }
                });
                
                if (missingReasons) {
                    e.preventDefault();
                    alert('Please provide reasons for all revision requests or rejections.');
                    return false;
                }
            }
            
            return confirm(`Are you sure you want to ${action.replace('_', ' ')} ${selectedNotes.size} note(s)?`);
        });
        
        // Auto-refresh pending count
        setInterval(function() {
            if (window.location.search.includes('status=pending') || window.location.search === '') {
                // Could implement AJAX refresh here
            }
        }, 60000); // Check every minute
    </script>
</body>
</html>