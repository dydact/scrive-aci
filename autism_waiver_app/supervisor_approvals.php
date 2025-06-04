<?php
require_once '../src/init.php';
requireAuth(4); // Supervisor+ access

$error = null;
$success = null;
$currentUser = getCurrentUser();

// Get filter parameters
$approvalType = $_GET['type'] ?? 'all';
$staffFilter = $_GET['staff_id'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
$statusFilter = $_GET['status'] ?? 'pending';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDatabase();
        $action = $_POST['action'] ?? '';
        
        if ($action === 'approve_items') {
            $itemIds = $_POST['item_ids'] ?? [];
            $comments = $_POST['approval_comments'] ?? '';
            
            if (!empty($itemIds)) {
                $pdo->beginTransaction();
                
                foreach ($itemIds as $itemId) {
                    $parts = explode('_', $itemId);
                    $type = $parts[0];
                    $id = $parts[1];
                    
                    switch ($type) {
                        case 'session':
                            $stmt = $pdo->prepare("
                                UPDATE autism_session_notes 
                                SET status = 'approved', 
                                    approved_by = ?, 
                                    approved_at = NOW(),
                                    supervisor_comments = CONCAT(IFNULL(supervisor_comments, ''), ?)
                                WHERE id = ? AND status IN ('completed', 'pending_approval')
                            ");
                            $stmt->execute([
                                $currentUser['user_id'],
                                $comments ? "\n[Approved] " . $comments : '',
                                $id
                            ]);
                            break;
                            
                        case 'time':
                            $stmt = $pdo->prepare("
                                UPDATE autism_time_clock 
                                SET status = 'approved',
                                    approved_by = ?,
                                    approved_at = NOW(),
                                    supervisor_notes = CONCAT(IFNULL(supervisor_notes, ''), ?)
                                WHERE id = ? AND status = 'pending'
                            ");
                            $stmt->execute([
                                $currentUser['user_id'],
                                $comments ? "\n[Approved] " . $comments : '',
                                $id
                            ]);
                            break;
                            
                        case 'schedule':
                            $stmt = $pdo->prepare("
                                UPDATE autism_schedule_changes 
                                SET status = 'approved',
                                    approved_by = ?,
                                    approved_at = NOW(),
                                    approval_notes = ?
                                WHERE id = ? AND status = 'pending'
                            ");
                            $stmt->execute([
                                $currentUser['user_id'],
                                $comments,
                                $id
                            ]);
                            break;
                            
                        case 'timeoff':
                            $stmt = $pdo->prepare("
                                UPDATE autism_time_off_requests 
                                SET status = 'approved',
                                    approved_by = ?,
                                    approved_at = NOW(),
                                    approval_comments = ?
                                WHERE id = ? AND status = 'pending'
                            ");
                            $stmt->execute([
                                $currentUser['user_id'],
                                $comments,
                                $id
                            ]);
                            break;
                            
                        case 'billing':
                            $stmt = $pdo->prepare("
                                UPDATE autism_billing_entries 
                                SET status = 'approved',
                                    approved_by = ?,
                                    approved_at = NOW(),
                                    notes = CONCAT(IFNULL(notes, ''), ?)
                                WHERE entry_id = ? AND status IN ('pending', 'disputed')
                            ");
                            $stmt->execute([
                                $currentUser['user_id'],
                                $comments ? "\n[Approved] " . $comments : '',
                                $id
                            ]);
                            break;
                    }
                    
                    // Log the approval
                    $stmt = $pdo->prepare("
                        INSERT INTO autism_audit_log (user_id, action, record_type, record_id, details)
                        VALUES (?, 'approve', ?, ?, ?)
                    ");
                    $stmt->execute([
                        $currentUser['user_id'],
                        $type,
                        $id,
                        json_encode(['comments' => $comments])
                    ]);
                }
                
                $pdo->commit();
                $success = count($itemIds) . " item(s) approved successfully!";
            }
            
        } elseif ($action === 'reject_items') {
            $itemIds = $_POST['item_ids'] ?? [];
            $reason = $_POST['rejection_reason'] ?? '';
            
            if (!empty($itemIds) && $reason) {
                $pdo->beginTransaction();
                
                foreach ($itemIds as $itemId) {
                    $parts = explode('_', $itemId);
                    $type = $parts[0];
                    $id = $parts[1];
                    
                    switch ($type) {
                        case 'session':
                            $stmt = $pdo->prepare("
                                UPDATE autism_session_notes 
                                SET status = 'rejected',
                                    approved_by = ?,
                                    approved_at = NOW(),
                                    supervisor_comments = CONCAT(IFNULL(supervisor_comments, ''), ?)
                                WHERE id = ? AND status IN ('completed', 'pending_approval')
                            ");
                            $stmt->execute([
                                $currentUser['user_id'],
                                "\n[Rejected] " . $reason,
                                $id
                            ]);
                            break;
                            
                        case 'time':
                            $stmt = $pdo->prepare("
                                UPDATE autism_time_clock 
                                SET status = 'rejected',
                                    approved_by = ?,
                                    approved_at = NOW(),
                                    supervisor_notes = CONCAT(IFNULL(supervisor_notes, ''), ?)
                                WHERE id = ? AND status = 'pending'
                            ");
                            $stmt->execute([
                                $currentUser['user_id'],
                                "\n[Rejected] " . $reason,
                                $id
                            ]);
                            break;
                            
                        case 'schedule':
                            $stmt = $pdo->prepare("
                                UPDATE autism_schedule_changes 
                                SET status = 'rejected',
                                    approved_by = ?,
                                    approved_at = NOW(),
                                    approval_notes = ?
                                WHERE id = ? AND status = 'pending'
                            ");
                            $stmt->execute([
                                $currentUser['user_id'],
                                "Rejected: " . $reason,
                                $id
                            ]);
                            break;
                            
                        case 'timeoff':
                            $stmt = $pdo->prepare("
                                UPDATE autism_time_off_requests 
                                SET status = 'rejected',
                                    approved_by = ?,
                                    approved_at = NOW(),
                                    approval_comments = ?
                                WHERE id = ? AND status = 'pending'
                            ");
                            $stmt->execute([
                                $currentUser['user_id'],
                                "Rejected: " . $reason,
                                $id
                            ]);
                            break;
                            
                        case 'billing':
                            $stmt = $pdo->prepare("
                                UPDATE autism_billing_entries 
                                SET status = 'rejected',
                                    approved_by = ?,
                                    approved_at = NOW(),
                                    notes = CONCAT(IFNULL(notes, ''), ?)
                                WHERE entry_id = ? AND status IN ('pending', 'disputed')
                            ");
                            $stmt->execute([
                                $currentUser['user_id'],
                                "\n[Rejected] " . $reason,
                                $id
                            ]);
                            break;
                    }
                    
                    // Log the rejection
                    $stmt = $pdo->prepare("
                        INSERT INTO autism_audit_log (user_id, action, record_type, record_id, details)
                        VALUES (?, 'reject', ?, ?, ?)
                    ");
                    $stmt->execute([
                        $currentUser['user_id'],
                        $type,
                        $id,
                        json_encode(['reason' => $reason])
                    ]);
                }
                
                $pdo->commit();
                $success = count($itemIds) . " item(s) rejected.";
            } else {
                $error = "Please select items and provide a rejection reason.";
            }
        }
        
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Error processing approval: " . $e->getMessage();
    }
}

// Fetch pending items based on filters
try {
    $pdo = getDatabase();
    $pendingItems = [];
    
    // Base query conditions
    $dateCondition = "AND DATE(created_at) BETWEEN ? AND ?";
    $staffCondition = $staffFilter ? "AND staff_id = ?" : "";
    
    // Fetch session notes requiring approval
    if ($approvalType === 'all' || $approvalType === 'sessions') {
        $query = "
            SELECT 
                'session' as item_type,
                sn.id,
                sn.session_date,
                sn.created_at,
                sn.staff_id,
                CONCAT(sm.first_name, ' ', sm.last_name) as staff_name,
                CONCAT(c.first_name, ' ', c.last_name) as client_name,
                st.service_name,
                sn.duration_minutes,
                sn.status,
                CASE 
                    WHEN sn.created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR) THEN 1
                    ELSE 0
                END as is_late,
                sn.progress_notes as notes
            FROM autism_session_notes sn
            JOIN autism_staff_members sm ON sn.staff_id = sm.id
            JOIN autism_clients c ON sn.client_id = c.id
            LEFT JOIN autism_service_types st ON sn.service_type_id = st.id
            WHERE sn.status IN ('completed', 'pending_approval')
            $dateCondition
            $staffCondition
            ORDER BY is_late DESC, sn.created_at ASC
        ";
        
        $params = [$startDate, $endDate];
        if ($staffFilter) $params[] = $staffFilter;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pendingItems = array_merge($pendingItems, $sessions);
    }
    
    // Fetch time entries with discrepancies
    if ($approvalType === 'all' || $approvalType === 'time') {
        $query = "
            SELECT 
                'time' as item_type,
                tc.id,
                tc.clock_in as session_date,
                tc.clock_in as created_at,
                tc.employee_id as staff_id,
                CONCAT(sm.first_name, ' ', sm.last_name) as staff_name,
                CONCAT(c.first_name, ' ', c.last_name) as client_name,
                'Time Entry' as service_name,
                tc.total_hours * 60 as duration_minutes,
                tc.status,
                CASE 
                    WHEN tc.manual_override = 1 THEN 1
                    WHEN ABS(TIME_TO_SEC(TIMEDIFF(tc.clock_out, tc.clock_in)) - (tc.total_hours * 3600)) > 300 THEN 1
                    ELSE 0
                END as is_discrepancy,
                tc.notes
            FROM autism_time_clock tc
            LEFT JOIN autism_staff_members sm ON tc.employee_id = sm.id
            LEFT JOIN autism_clients c ON tc.client_id = c.id
            WHERE tc.status = 'pending'
            AND (tc.manual_override = 1 OR 
                 ABS(TIME_TO_SEC(TIMEDIFF(tc.clock_out, tc.clock_in)) - (tc.total_hours * 3600)) > 300)
            $dateCondition
            " . ($staffFilter ? "AND tc.employee_id = ?" : "") . "
            ORDER BY tc.clock_in DESC
        ";
        
        $params = [$startDate, $endDate];
        if ($staffFilter) $params[] = $staffFilter;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $timeEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pendingItems = array_merge($pendingItems, $timeEntries);
    }
    
    // Fetch schedule changes
    if ($approvalType === 'all' || $approvalType === 'schedules') {
        $query = "
            SELECT 
                'schedule' as item_type,
                sc.id,
                sc.change_date as session_date,
                sc.created_at,
                sc.requested_by as staff_id,
                CONCAT(sm.first_name, ' ', sm.last_name) as staff_name,
                CONCAT(c.first_name, ' ', c.last_name) as client_name,
                'Schedule Change' as service_name,
                0 as duration_minutes,
                sc.status,
                0 as is_late,
                CONCAT(sc.change_type, ': ', sc.reason) as notes
            FROM autism_schedule_changes sc
            LEFT JOIN autism_staff_members sm ON sc.requested_by = sm.id
            LEFT JOIN autism_schedules s ON sc.schedule_id = s.id
            LEFT JOIN autism_clients c ON s.client_id = c.id
            WHERE sc.status = 'pending'
            $dateCondition
            " . ($staffFilter ? "AND sc.requested_by = ?" : "") . "
            ORDER BY sc.created_at DESC
        ";
        
        $params = [$startDate, $endDate];
        if ($staffFilter) $params[] = $staffFilter;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $scheduleChanges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pendingItems = array_merge($pendingItems, $scheduleChanges);
    }
    
    // Fetch time off requests
    if ($approvalType === 'all' || $approvalType === 'timeoff') {
        $query = "
            SELECT 
                'timeoff' as item_type,
                tor.id,
                tor.start_date as session_date,
                tor.created_at,
                tor.staff_id,
                CONCAT(sm.first_name, ' ', sm.last_name) as staff_name,
                '' as client_name,
                CONCAT('Time Off: ', tor.request_type) as service_name,
                DATEDIFF(tor.end_date, tor.start_date) + 1 as duration_minutes,
                tor.status,
                0 as is_late,
                tor.reason as notes
            FROM autism_time_off_requests tor
            LEFT JOIN autism_staff_members sm ON tor.staff_id = sm.id
            WHERE tor.status = 'pending'
            AND tor.start_date >= ?
            " . ($staffFilter ? "AND tor.staff_id = ?" : "") . "
            ORDER BY tor.start_date ASC
        ";
        
        $params = [$startDate];
        if ($staffFilter) $params[] = $staffFilter;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $timeoffRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pendingItems = array_merge($pendingItems, $timeoffRequests);
    }
    
    // Fetch billing adjustments
    if ($approvalType === 'all' || $approvalType === 'billing') {
        $query = "
            SELECT 
                'billing' as item_type,
                be.entry_id as id,
                be.billing_date as session_date,
                be.created_at,
                be.employee_id as staff_id,
                CONCAT(sm.first_name, ' ', sm.last_name) as staff_name,
                CONCAT(c.first_name, ' ', c.last_name) as client_name,
                CONCAT('Billing: ', IFNULL(st.service_name, 'Unknown')) as service_name,
                be.billable_minutes as duration_minutes,
                be.status,
                0 as is_late,
                CONCAT('Amount: $', be.total_amount, ' - ', IFNULL(be.notes, '')) as notes
            FROM autism_billing_entries be
            LEFT JOIN autism_staff_members sm ON be.employee_id = sm.id
            LEFT JOIN autism_clients c ON be.client_id = c.id
            LEFT JOIN autism_service_types st ON be.service_type_id = st.id
            WHERE be.status IN ('pending', 'disputed')
            $dateCondition
            " . ($staffFilter ? "AND be.employee_id = ?" : "") . "
            ORDER BY be.created_at DESC
        ";
        
        $params = [$startDate, $endDate];
        if ($staffFilter) $params[] = $staffFilter;
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $billingEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pendingItems = array_merge($pendingItems, $billingEntries);
    }
    
    // Get staff list for filter
    $stmt = $pdo->query("
        SELECT id, CONCAT(first_name, ' ', last_name) as full_name 
        FROM autism_staff_members 
        WHERE status = 'active' 
        ORDER BY last_name, first_name
    ");
    $staffList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get approval statistics
    $stats = [
        'pending_sessions' => 0,
        'late_sessions' => 0,
        'time_discrepancies' => 0,
        'pending_schedules' => 0,
        'pending_timeoff' => 0,
        'pending_billing' => 0
    ];
    
    foreach ($pendingItems as $item) {
        switch ($item['item_type']) {
            case 'session':
                $stats['pending_sessions']++;
                if ($item['is_late']) $stats['late_sessions']++;
                break;
            case 'time':
                $stats['time_discrepancies']++;
                break;
            case 'schedule':
                $stats['pending_schedules']++;
                break;
            case 'timeoff':
                $stats['pending_timeoff']++;
                break;
            case 'billing':
                $stats['pending_billing']++;
                break;
        }
    }
    
} catch (Exception $e) {
    $error = "Error loading approval items: " . $e->getMessage();
    $pendingItems = [];
    $staffList = [];
    $stats = [
        'pending_sessions' => 0,
        'late_sessions' => 0,
        'time_discrepancies' => 0,
        'pending_schedules' => 0,
        'pending_timeoff' => 0,
        'pending_billing' => 0
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Approvals - Scrive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: 600;
        }
        .approvals-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .approval-item {
            border-left: 4px solid transparent;
            transition: all 0.2s ease;
        }
        .approval-item:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }
        .approval-item.late {
            border-left-color: #dc3545;
        }
        .approval-item.discrepancy {
            border-left-color: #ffc107;
        }
        .approval-checkbox {
            width: 1.2rem;
            height: 1.2rem;
            cursor: pointer;
        }
        .tab-content {
            background: white;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            padding: 1rem 1.5rem;
        }
        .nav-tabs .nav-link.active {
            color: #667eea;
            background: white;
            border-bottom: 3px solid #667eea;
        }
        .badge-late {
            background-color: #dc3545;
        }
        .approval-actions {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 1rem;
            border-top: 2px solid #e9ecef;
            box-shadow: 0 -4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= UrlManager::url('dashboard') ?>">
                <i class="fas fa-brain me-2"></i>
                ACI Supervisor Portal
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
    <div class="approvals-header py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="mb-1">
                        <i class="fas fa-check-double me-2"></i>
                        Supervisor Approvals
                    </h2>
                    <p class="mb-0">Review and approve pending items across all departments</p>
                </div>
                <div class="col-auto">
                    <button class="btn btn-light" onclick="exportApprovals()">
                        <i class="fas fa-file-export me-2"></i>
                        Export Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-4">
        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-4 col-lg-2 mb-3">
                <div class="card stat-card <?= $stats['late_sessions'] > 0 ? 'border-danger' : '' ?>">
                    <div class="card-body text-center">
                        <div class="stat-value text-danger"><?= $stats['late_sessions'] ?></div>
                        <div class="text-muted">Late Sessions</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-value text-primary"><?= $stats['pending_sessions'] ?></div>
                        <div class="text-muted">Session Notes</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-value text-warning"><?= $stats['time_discrepancies'] ?></div>
                        <div class="text-muted">Time Entries</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-value text-info"><?= $stats['pending_schedules'] ?></div>
                        <div class="text-muted">Schedule Changes</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-value text-success"><?= $stats['pending_timeoff'] ?></div>
                        <div class="text-muted">Time Off</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2 mb-3">
                <div class="card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-value text-secondary"><?= $stats['pending_billing'] ?></div>
                        <div class="text-muted">Billing</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Controls -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="type" class="form-label">Approval Type</label>
                        <select class="form-select" id="type" name="type">
                            <option value="all" <?= $approvalType === 'all' ? 'selected' : '' ?>>All Types</option>
                            <option value="sessions" <?= $approvalType === 'sessions' ? 'selected' : '' ?>>Session Notes</option>
                            <option value="time" <?= $approvalType === 'time' ? 'selected' : '' ?>>Time Entries</option>
                            <option value="schedules" <?= $approvalType === 'schedules' ? 'selected' : '' ?>>Schedule Changes</option>
                            <option value="timeoff" <?= $approvalType === 'timeoff' ? 'selected' : '' ?>>Time Off Requests</option>
                            <option value="billing" <?= $approvalType === 'billing' ? 'selected' : '' ?>>Billing Adjustments</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="staff_id" class="form-label">Staff Member</label>
                        <select class="form-select" id="staff_id" name="staff_id">
                            <option value="">All Staff</option>
                            <?php foreach ($staffList as $staff): ?>
                                <option value="<?= $staff['id'] ?>" <?= $staffFilter == $staff['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($staff['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?= htmlspecialchars($startDate) ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?= htmlspecialchars($endDate) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Approval Items -->
        <form id="approvalForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="">
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-0">
                                <i class="fas fa-list-check me-2"></i>
                                Pending Approvals
                            </h5>
                        </div>
                        <div class="col-auto">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleAllItems()">
                                <label class="form-check-label" for="selectAll">
                                    Select All
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <?php if (empty($pendingItems)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle" style="font-size: 3rem;"></i>
                            <p class="mt-3">No pending approvals found for the selected criteria</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40"></th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Staff Member</th>
                                        <th>Client/Details</th>
                                        <th>Service/Duration</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingItems as $item): ?>
                                        <tr class="approval-item <?= $item['item_type'] === 'session' && $item['is_late'] ? 'late' : '' ?> 
                                                   <?= $item['item_type'] === 'time' && isset($item['is_discrepancy']) && $item['is_discrepancy'] ? 'discrepancy' : '' ?>">
                                            <td>
                                                <input type="checkbox" class="form-check-input approval-checkbox" 
                                                       name="item_ids[]" 
                                                       value="<?= $item['item_type'] ?>_<?= $item['id'] ?>">
                                            </td>
                                            <td>
                                                <?php
                                                $typeIcons = [
                                                    'session' => 'fa-notes-medical',
                                                    'time' => 'fa-clock',
                                                    'schedule' => 'fa-calendar-alt',
                                                    'timeoff' => 'fa-umbrella-beach',
                                                    'billing' => 'fa-dollar-sign'
                                                ];
                                                $typeColors = [
                                                    'session' => 'primary',
                                                    'time' => 'warning',
                                                    'schedule' => 'info',
                                                    'timeoff' => 'success',
                                                    'billing' => 'secondary'
                                                ];
                                                $icon = $typeIcons[$item['item_type']] ?? 'fa-file';
                                                $color = $typeColors[$item['item_type']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $color ?>">
                                                    <i class="fas <?= $icon ?> me-1"></i>
                                                    <?= ucfirst($item['item_type']) ?>
                                                </span>
                                                <?php if ($item['item_type'] === 'session' && $item['is_late']): ?>
                                                    <span class="badge badge-late ms-1">Late</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('m/d/Y', strtotime($item['session_date'])) ?></td>
                                            <td><?= htmlspecialchars($item['staff_name']) ?></td>
                                            <td>
                                                <?php if ($item['client_name']): ?>
                                                    <?= htmlspecialchars($item['client_name']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($item['service_name']) ?>
                                                <?php if ($item['duration_minutes'] > 0): ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php if ($item['item_type'] === 'timeoff'): ?>
                                                            <?= $item['duration_minutes'] ?> days
                                                        <?php else: ?>
                                                            <?= $item['duration_minutes'] ?> min
                                                        <?php endif; ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars(substr($item['notes'] ?? '', 0, 100)) ?>
                                                <?= strlen($item['notes'] ?? '') > 100 ? '...' : '' ?></small>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewDetails('<?= $item['item_type'] ?>', <?= $item['id'] ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Approval Actions (Sticky) -->
            <?php if (!empty($pendingItems)): ?>
            <div class="approval-actions mt-4">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="mb-2">
                            <label for="approval_comments" class="form-label">Comments (Optional)</label>
                            <textarea class="form-control" id="approval_comments" name="approval_comments" 
                                      rows="2" placeholder="Add comments for approved items..."></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-success btn-lg" onclick="approveSelected()">
                                <i class="fas fa-check me-2"></i>
                                Approve Selected
                            </button>
                            <button type="button" class="btn btn-danger btn-lg" onclick="rejectSelected()">
                                <i class="fas fa-times me-2"></i>
                                Reject Selected
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Rejection Modal -->
    <div class="modal fade" id="rejectionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Items</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Please provide a reason for rejection. This will be sent to the staff member.
                    </div>
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Rejection Reason</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" 
                                  rows="4" required placeholder="Enter reason for rejection..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmRejection()">
                        <i class="fas fa-times me-2"></i>
                        Confirm Rejection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Item Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailsContent">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleAllItems() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.approval-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        }

        function getSelectedCount() {
            return document.querySelectorAll('.approval-checkbox:checked').length;
        }

        function approveSelected() {
            const count = getSelectedCount();
            if (count === 0) {
                alert('Please select at least one item to approve.');
                return;
            }
            
            if (confirm(`Are you sure you want to approve ${count} item(s)?`)) {
                document.getElementById('formAction').value = 'approve_items';
                document.getElementById('approvalForm').submit();
            }
        }

        function rejectSelected() {
            const count = getSelectedCount();
            if (count === 0) {
                alert('Please select at least one item to reject.');
                return;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('rejectionModal'));
            modal.show();
        }

        function confirmRejection() {
            const reason = document.getElementById('rejection_reason').value.trim();
            if (!reason) {
                alert('Please provide a rejection reason.');
                return;
            }
            
            // Add rejection reason to form
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'rejection_reason';
            input.value = reason;
            document.getElementById('approvalForm').appendChild(input);
            
            document.getElementById('formAction').value = 'reject_items';
            document.getElementById('approvalForm').submit();
        }

        function viewDetails(type, id) {
            // In a real implementation, this would load details via AJAX
            const modal = new bootstrap.Modal(document.getElementById('detailsModal'));
            document.getElementById('detailsContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Loading details...</p>
                </div>
            `;
            modal.show();
            
            // Simulate loading
            setTimeout(() => {
                document.getElementById('detailsContent').innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Detailed view for ${type} item #${id} would be loaded here via AJAX.
                    </div>
                    <p>This would include:</p>
                    <ul>
                        <li>Complete session notes or time entry details</li>
                        <li>Historical data for the staff member</li>
                        <li>Previous approvals/rejections</li>
                        <li>Related documentation</li>
                        <li>Audit trail</li>
                    </ul>
                `;
            }, 1000);
        }

        function exportApprovals() {
            if (confirm('Export current approval list to CSV?')) {
                window.location.href = '?export=csv&' + new URLSearchParams(window.location.search);
            }
        }

        // Update checkbox count in real-time
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.approval-checkbox');
            checkboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    const count = getSelectedCount();
                    const selectAll = document.getElementById('selectAll');
                    selectAll.checked = count === checkboxes.length && count > 0;
                    selectAll.indeterminate = count > 0 && count < checkboxes.length;
                });
            });
        });
    </script>
</body>
</html>