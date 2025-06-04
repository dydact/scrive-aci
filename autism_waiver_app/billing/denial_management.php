<?php
require_once dirname(__DIR__) . '/config_sqlite.php';

// Check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /autism_waiver_app/simple_login.php');
    exit;
}

// Get current user info
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'employee';

// Maryland Medicaid Denial Codes
$marylandDenialCodes = [
    'M01' => 'Missing or Invalid Prior Authorization',
    'M02' => 'Service Not Covered',
    'M03' => 'Duplicate Claim',
    'M04' => 'Invalid Provider Number',
    'M05' => 'Invalid Member ID',
    'M06' => 'Service Date Outside Coverage Period',
    'M07' => 'Invalid Procedure Code',
    'M08' => 'Invalid Diagnosis Code',
    'M09' => 'Timely Filing Limit Exceeded',
    'M10' => 'Invalid Place of Service',
    'M11' => 'Invalid Modifier',
    'M12' => 'Service Limit Exceeded',
    'M13' => 'Invalid Units',
    'M14' => 'Missing Documentation',
    'M15' => 'Invalid NPI',
    'M16' => 'Service Requires Referral',
    'M17' => 'Invalid Rate Code',
    'M18' => 'Provider Not Enrolled',
    'M19' => 'Invalid Service Date',
    'M20' => 'Coordination of Benefits Issue'
];

// Get denial statistics
$stats = [];
try {
    // Total denials
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_denials,
               SUM(amount) as total_amount,
               COUNT(DISTINCT denial_code) as unique_reasons
        FROM claim_denials
        WHERE status != 'resolved'
    ");
    $stmt->execute();
    $stats['overview'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Denials by reason
    $stmt = $db->prepare("
        SELECT denial_code, denial_reason, 
               COUNT(*) as count,
               SUM(amount) as total_amount,
               AVG(JULIANDAY('now') - JULIANDAY(denial_date)) as avg_age_days
        FROM claim_denials
        WHERE status != 'resolved'
        GROUP BY denial_code
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute();
    $stats['by_reason'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Aging analysis
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN JULIANDAY('now') - JULIANDAY(denial_date) <= 30 THEN 1 ELSE 0 END) as '0_30_days',
            SUM(CASE WHEN JULIANDAY('now') - JULIANDAY(denial_date) BETWEEN 31 AND 60 THEN 1 ELSE 0 END) as '31_60_days',
            SUM(CASE WHEN JULIANDAY('now') - JULIANDAY(denial_date) BETWEEN 61 AND 90 THEN 1 ELSE 0 END) as '61_90_days',
            SUM(CASE WHEN JULIANDAY('now') - JULIANDAY(denial_date) > 90 THEN 1 ELSE 0 END) as 'over_90_days'
        FROM claim_denials
        WHERE status != 'resolved'
    ");
    $stmt->execute();
    $stats['aging'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Success rates
    $stmt = $db->prepare("
        SELECT 
            COUNT(CASE WHEN status = 'resolved' AND resolution_type = 'paid' THEN 1 END) as successful_appeals,
            COUNT(CASE WHEN status = 'resolved' THEN 1 END) as total_resolved,
            COUNT(CASE WHEN appeal_status = 'submitted' THEN 1 END) as pending_appeals
        FROM claim_denials
        WHERE created_at >= date('now', '-6 months')
    ");
    $stmt->execute();
    $stats['success'] = $stmt->fetch(PDO::FETCH_ASSOC);

    // Staff productivity
    $stmt = $db->prepare("
        SELECT u.full_name, u.id as user_id,
               COUNT(cd.id) as denials_worked,
               COUNT(CASE WHEN cd.status = 'resolved' THEN 1 END) as denials_resolved,
               AVG(CASE WHEN cd.status = 'resolved' 
                   THEN JULIANDAY(cd.resolution_date) - JULIANDAY(cd.assigned_date) 
                   END) as avg_resolution_days
        FROM users u
        LEFT JOIN claim_denials cd ON u.id = cd.assigned_to
        WHERE cd.assigned_date >= date('now', '-30 days')
        GROUP BY u.id
        ORDER BY denials_resolved DESC
    ");
    $stmt->execute();
    $stats['staff_productivity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error fetching denial statistics: " . $e->getMessage());
}

// Get denial worklist
$denialFilters = [
    'status' => $_GET['status'] ?? 'pending',
    'priority' => $_GET['priority'] ?? 'all',
    'age' => $_GET['age'] ?? 'all',
    'assigned' => $_GET['assigned'] ?? 'all'
];

$worklistQuery = "
    SELECT cd.*, c.client_name, c.medicaid_id,
           u.full_name as assigned_to_name,
           JULIANDAY('now') - JULIANDAY(cd.denial_date) as age_days,
           CASE 
               WHEN cd.appeal_deadline < date('now', '+7 days') THEN 'high'
               WHEN cd.amount > 500 THEN 'high'
               WHEN JULIANDAY('now') - JULIANDAY(cd.denial_date) > 60 THEN 'high'
               WHEN cd.amount > 200 THEN 'medium'
               ELSE 'low'
           END as calculated_priority
    FROM claim_denials cd
    LEFT JOIN clients c ON cd.client_id = c.id
    LEFT JOIN users u ON cd.assigned_to = u.id
    WHERE 1=1
";

$params = [];

if ($denialFilters['status'] !== 'all') {
    $worklistQuery .= " AND cd.status = :status";
    $params[':status'] = $denialFilters['status'];
}

if ($denialFilters['priority'] !== 'all') {
    $worklistQuery .= " AND cd.priority = :priority";
    $params[':priority'] = $denialFilters['priority'];
}

if ($denialFilters['assigned'] === 'me') {
    $worklistQuery .= " AND cd.assigned_to = :user_id";
    $params[':user_id'] = $userId;
} elseif ($denialFilters['assigned'] === 'unassigned') {
    $worklistQuery .= " AND cd.assigned_to IS NULL";
}

$worklistQuery .= " ORDER BY calculated_priority DESC, cd.appeal_deadline ASC LIMIT 100";

try {
    $stmt = $db->prepare($worklistQuery);
    $stmt->execute($params);
    $denials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching denial worklist: " . $e->getMessage());
    $denials = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Denial Management - ACI Autism Waiver</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .stat-card {
            border-left: 4px solid #0066cc;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .priority-high {
            color: #dc3545;
            font-weight: bold;
        }
        .priority-medium {
            color: #ffc107;
            font-weight: 500;
        }
        .priority-low {
            color: #28a745;
        }
        .denial-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .denial-row:hover {
            background-color: #f8f9fa;
        }
        .aging-0-30 { background-color: #d4edda; }
        .aging-31-60 { background-color: #fff3cd; }
        .aging-61-90 { background-color: #f8d7da; }
        .aging-over-90 { background-color: #f5c6cb; }
        .action-buttons button {
            margin: 0 2px;
        }
        .progress-thin {
            height: 5px;
        }
        .dashboard-header {
            background: linear-gradient(135deg, #0066cc 0%, #004499 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/autism_waiver_app/simple_dashboard.php">
                <i class="bi bi-house-door"></i> ACI Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="/autism_waiver_app/billing_dashboard.php">Billing Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/autism_waiver_app/billing/denial_management.php">Denial Management</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/autism_waiver_app/billing/denial_analytics.php">Denial Analytics</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/autism_waiver_app/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="dashboard-header">
            <h1 class="mb-3"><i class="bi bi-exclamation-triangle"></i> Denial Management Center</h1>
            <p class="lead mb-0">Track, manage, and resolve claim denials to improve collection rates</p>
        </div>

        <!-- Key Metrics Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Open Denials</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['overview']['total_denials'] ?? 0); ?></h2>
                        <p class="text-danger mb-0">
                            $<?php echo number_format($stats['overview']['total_amount'] ?? 0, 2); ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Appeal Success Rate</h6>
                        <?php 
                        $successRate = 0;
                        if (($stats['success']['total_resolved'] ?? 0) > 0) {
                            $successRate = ($stats['success']['successful_appeals'] / $stats['success']['total_resolved']) * 100;
                        }
                        ?>
                        <h2 class="mb-0"><?php echo number_format($successRate, 1); ?>%</h2>
                        <p class="text-success mb-0">
                            <?php echo $stats['success']['successful_appeals'] ?? 0; ?> wins
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Pending Appeals</h6>
                        <h2 class="mb-0"><?php echo $stats['success']['pending_appeals'] ?? 0; ?></h2>
                        <p class="text-warning mb-0">
                            Awaiting response
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <h6 class="text-muted">Over 90 Days</h6>
                        <h2 class="mb-0 text-danger"><?php echo $stats['aging']['over_90_days'] ?? 0; ?></h2>
                        <p class="text-muted mb-0">
                            Require immediate action
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Denial Aging Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Denial Aging Analysis</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="p-3 aging-0-30 rounded">
                                    <h6>0-30 Days</h6>
                                    <h3><?php echo $stats['aging']['0_30_days'] ?? 0; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 aging-31-60 rounded">
                                    <h6>31-60 Days</h6>
                                    <h3><?php echo $stats['aging']['31_60_days'] ?? 0; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 aging-61-90 rounded">
                                    <h6>61-90 Days</h6>
                                    <h3><?php echo $stats['aging']['61_90_days'] ?? 0; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 aging-over-90 rounded">
                                    <h6>Over 90 Days</h6>
                                    <h3><?php echo $stats['aging']['over_90_days'] ?? 0; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Denial Reasons -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Top Denial Reasons</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Reason</th>
                                        <th>Count</th>
                                        <th>Amount</th>
                                        <th>Avg Age</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['by_reason'] ?? [] as $reason): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($reason['denial_code']); ?></span></td>
                                        <td><?php echo htmlspecialchars($reason['denial_reason']); ?></td>
                                        <td><?php echo $reason['count']; ?></td>
                                        <td>$<?php echo number_format($reason['total_amount'], 2); ?></td>
                                        <td><?php echo number_format($reason['avg_age_days'], 0); ?>d</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Staff Productivity -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Staff Productivity (30 Days)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Staff Member</th>
                                        <th>Worked</th>
                                        <th>Resolved</th>
                                        <th>Avg Days</th>
                                        <th>Success</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['staff_productivity'] ?? [] as $staff): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($staff['full_name']); ?></td>
                                        <td><?php echo $staff['denials_worked']; ?></td>
                                        <td><?php echo $staff['denials_resolved']; ?></td>
                                        <td><?php echo number_format($staff['avg_resolution_days'] ?? 0, 1); ?></td>
                                        <td>
                                            <?php 
                                            $successPct = $staff['denials_worked'] > 0 
                                                ? ($staff['denials_resolved'] / $staff['denials_worked']) * 100 
                                                : 0;
                                            ?>
                                            <div class="progress progress-thin">
                                                <div class="progress-bar bg-success" style="width: <?php echo $successPct; ?>%"></div>
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
        </div>

        <!-- Denial Worklist -->
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Denial Worklist</h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="statusFilter" style="width: auto;">
                            <option value="pending" <?php echo $denialFilters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $denialFilters['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="appealed" <?php echo $denialFilters['status'] === 'appealed' ? 'selected' : ''; ?>>Appealed</option>
                            <option value="all" <?php echo $denialFilters['status'] === 'all' ? 'selected' : ''; ?>>All</option>
                        </select>
                        <select class="form-select form-select-sm" id="assignedFilter" style="width: auto;">
                            <option value="all" <?php echo $denialFilters['assigned'] === 'all' ? 'selected' : ''; ?>>All Assigned</option>
                            <option value="me" <?php echo $denialFilters['assigned'] === 'me' ? 'selected' : ''; ?>>Assigned to Me</option>
                            <option value="unassigned" <?php echo $denialFilters['assigned'] === 'unassigned' ? 'selected' : ''; ?>>Unassigned</option>
                        </select>
                        <button class="btn btn-sm btn-primary" onclick="exportDenials()">
                            <i class="bi bi-download"></i> Export
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Priority</th>
                                <th>Claim #</th>
                                <th>Client</th>
                                <th>Denial Code</th>
                                <th>Amount</th>
                                <th>Age</th>
                                <th>Appeal Deadline</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($denials as $denial): ?>
                            <tr class="denial-row" data-denial-id="<?php echo $denial['id']; ?>">
                                <td>
                                    <span class="priority-<?php echo $denial['calculated_priority']; ?>">
                                        <?php echo ucfirst($denial['calculated_priority']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($denial['claim_number']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($denial['client_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($denial['medicaid_id']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($denial['denial_code']); ?></span><br>
                                    <small><?php echo htmlspecialchars($marylandDenialCodes[$denial['denial_code']] ?? $denial['denial_reason']); ?></small>
                                </td>
                                <td>$<?php echo number_format($denial['amount'], 2); ?></td>
                                <td>
                                    <?php 
                                    $ageDays = $denial['age_days'];
                                    $ageClass = '';
                                    if ($ageDays <= 30) $ageClass = 'text-success';
                                    elseif ($ageDays <= 60) $ageClass = 'text-warning';
                                    else $ageClass = 'text-danger';
                                    ?>
                                    <span class="<?php echo $ageClass; ?>"><?php echo number_format($ageDays, 0); ?> days</span>
                                </td>
                                <td>
                                    <?php 
                                    $deadline = new DateTime($denial['appeal_deadline']);
                                    $today = new DateTime();
                                    $daysUntilDeadline = $today->diff($deadline)->days;
                                    $deadlineClass = $daysUntilDeadline <= 7 ? 'text-danger fw-bold' : '';
                                    ?>
                                    <span class="<?php echo $deadlineClass; ?>">
                                        <?php echo $deadline->format('m/d/Y'); ?>
                                        <?php if ($daysUntilDeadline <= 7): ?>
                                            <br><small>(<?php echo $daysUntilDeadline; ?> days)</small>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $denial['status'] === 'pending' ? 'warning' : 
                                            ($denial['status'] === 'appealed' ? 'info' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst($denial['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($denial['assigned_to_name'] ?? 'Unassigned'); ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-sm btn-primary" onclick="workDenial(<?php echo $denial['id']; ?>)" title="Work Denial">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="appealClaim(<?php echo $denial['id']; ?>)" title="File Appeal">
                                        <i class="bi bi-file-earmark-arrow-up"></i>
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="viewHistory(<?php echo $denial['id']; ?>)" title="View History">
                                        <i class="bi bi-clock-history"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Work Denial Modal -->
    <div class="modal fade" id="workDenialModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Work Denial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="workDenialForm">
                        <input type="hidden" id="denialId" name="denial_id">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="appealed">Appealed</option>
                                    <option value="resubmitted">Resubmitted</option>
                                    <option value="resolved">Resolved</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Assign To</label>
                                <select class="form-select" name="assigned_to">
                                    <option value="">Unassigned</option>
                                    <?php
                                    $stmt = $db->query("SELECT id, full_name FROM users WHERE role IN ('admin', 'billing_specialist') ORDER BY full_name");
                                    while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $user['id'] . '">' . htmlspecialchars($user['full_name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Follow-up Date</label>
                                <input type="date" class="form-control" name="follow_up_date">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Action Taken</label>
                            <textarea class="form-control" name="action_taken" rows="3" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Internal Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Attachments</label>
                            <input type="file" class="form-control" name="attachments[]" multiple>
                            <small class="text-muted">Attach supporting documentation</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveDenialWork()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Filter handling
        $('#statusFilter, #assignedFilter').change(function() {
            const params = new URLSearchParams(window.location.search);
            params.set('status', $('#statusFilter').val());
            params.set('assigned', $('#assignedFilter').val());
            window.location.search = params.toString();
        });

        // Work denial
        function workDenial(denialId) {
            $('#denialId').val(denialId);
            
            // Load current denial data
            $.get('update_denial.php', { action: 'get', id: denialId }, function(data) {
                if (data.success) {
                    const denial = data.denial;
                    $('select[name="status"]').val(denial.status);
                    $('select[name="assigned_to"]').val(denial.assigned_to || '');
                    $('select[name="priority"]').val(denial.priority);
                    $('input[name="follow_up_date"]').val(denial.follow_up_date);
                }
            });
            
            $('#workDenialModal').modal('show');
        }

        // Save denial work
        function saveDenialWork() {
            const formData = new FormData($('#workDenialForm')[0]);
            formData.append('action', 'update');
            
            $.ajax({
                url: 'update_denial.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Denial updated successfully');
                        $('#workDenialModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error updating denial');
                }
            });
        }

        // Appeal claim
        function appealClaim(denialId) {
            window.location.href = 'appeal_claim.php?denial_id=' + denialId;
        }

        // View history
        function viewHistory(denialId) {
            window.open('denial_history.php?id=' + denialId, '_blank', 'width=800,height=600');
        }

        // Export denials
        function exportDenials() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = 'denial_management.php?' + params.toString();
        }

        // Auto-refresh every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>