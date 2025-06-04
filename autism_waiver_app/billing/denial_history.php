<?php
require_once dirname(__DIR__) . '/config_sqlite.php';

// Check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /autism_waiver_app/simple_login.php');
    exit;
}

$denialId = $_GET['id'] ?? null;

if (!$denialId) {
    echo "Denial ID required";
    exit;
}

// Get denial information
try {
    $stmt = $db->prepare("
        SELECT cd.*, c.client_name, c.medicaid_id,
               u.full_name as assigned_to_name,
               cb.full_name as created_by_name
        FROM claim_denials cd
        LEFT JOIN clients c ON cd.client_id = c.id
        LEFT JOIN users u ON cd.assigned_to = u.id
        LEFT JOIN users cb ON cd.created_by = cb.id
        WHERE cd.id = ?
    ");
    $stmt->execute([$denialId]);
    $denial = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$denial) {
        echo "Denial not found";
        exit;
    }
    
    // Get activity history
    $stmt = $db->prepare("
        SELECT da.*, u.full_name as created_by_name
        FROM denial_activities da
        LEFT JOIN users u ON da.created_by = u.id
        WHERE da.denial_id = ?
        ORDER BY da.created_at DESC
    ");
    $stmt->execute([$denialId]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get appeals
    $stmt = $db->prepare("
        SELECT ca.*, u.full_name as created_by_name
        FROM claim_appeals ca
        LEFT JOIN users u ON ca.created_by = u.id
        WHERE ca.denial_id = ?
        ORDER BY ca.appeal_date DESC
    ");
    $stmt->execute([$denialId]);
    $appeals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get tasks
    $stmt = $db->prepare("
        SELECT dt.*, u.full_name as assigned_to_name, cb.full_name as created_by_name
        FROM denial_tasks dt
        LEFT JOIN users u ON dt.assigned_to = u.id
        LEFT JOIN users cb ON dt.created_by = cb.id
        WHERE dt.denial_id = ?
        ORDER BY dt.due_date ASC
    ");
    $stmt->execute([$denialId]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get attachments
    $stmt = $db->prepare("
        SELECT da.*, u.full_name as uploaded_by_name
        FROM denial_attachments da
        LEFT JOIN users u ON da.uploaded_by = u.id
        WHERE da.denial_id = ?
        ORDER BY da.uploaded_at DESC
    ");
    $stmt->execute([$denialId]);
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching denial history: " . $e->getMessage());
    echo "Error loading denial history";
    exit;
}

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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Denial History - Claim <?php echo htmlspecialchars($denial['claim_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            font-size: 0.9rem;
        }
        .timeline {
            position: relative;
            padding: 0;
            list-style: none;
        }
        .timeline:before {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: 40px;
            width: 2px;
            margin-left: -1.5px;
            background-color: #e9ecef;
        }
        .timeline > li {
            position: relative;
            margin-bottom: 50px;
            min-height: 50px;
        }
        .timeline > li:before,
        .timeline > li:after {
            content: " ";
            display: table;
        }
        .timeline > li:after {
            clear: both;
        }
        .timeline > li .timeline-panel {
            float: right;
            position: relative;
            width: calc(100% - 90px);
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            background: #fff;
        }
        .timeline > li .timeline-badge {
            color: #fff;
            width: 24px;
            height: 24px;
            line-height: 24px;
            font-size: 12px;
            text-align: center;
            position: absolute;
            top: 16px;
            left: 28px;
            z-index: 100;
            border-radius: 50%;
        }
        .timeline-badge.primary { background-color: #007bff; }
        .timeline-badge.success { background-color: #28a745; }
        .timeline-badge.warning { background-color: #ffc107; }
        .timeline-badge.danger { background-color: #dc3545; }
        .timeline-badge.info { background-color: #17a2b8; }
        
        .timeline-title {
            margin-top: 0;
            color: inherit;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .timeline-body {
            margin-top: 10px;
        }
        .timeline-footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e9ecef;
            font-size: 0.8rem;
            color: #6c757d;
        }
        .header-section {
            background: linear-gradient(135deg, #0066cc 0%, #004499 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 4px 8px;
        }
        .section-header {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 6px;
            margin: 1.5rem 0 1rem 0;
            border-left: 4px solid #0066cc;
        }
    </style>
</head>
<body>
    <div class="container-fluid p-3">
        <!-- Header Section -->
        <div class="header-section">
            <div class="row">
                <div class="col-md-8">
                    <h4 class="mb-2">Denial History - Claim #<?php echo htmlspecialchars($denial['claim_number']); ?></h4>
                    <p class="mb-0">
                        <strong>Client:</strong> <?php echo htmlspecialchars($denial['client_name']); ?> 
                        (<?php echo htmlspecialchars($denial['medicaid_id']); ?>)
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="mb-2">
                        <span class="badge bg-<?php 
                            echo $denial['status'] === 'resolved' ? 'success' : 
                                ($denial['status'] === 'appealed' ? 'info' : 
                                ($denial['status'] === 'pending' ? 'warning' : 'secondary')); 
                        ?> status-badge">
                            <?php echo ucfirst($denial['status']); ?>
                        </span>
                    </div>
                    <div>
                        <strong>Amount:</strong> $<?php echo number_format($denial['amount'], 2); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Denial Details -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Denial Details</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Denial Code:</strong></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($denial['denial_code']); ?></span>
                                    <?php echo htmlspecialchars($marylandDenialCodes[$denial['denial_code']] ?? $denial['denial_reason']); ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Service Date:</strong></td>
                                <td><?php echo date('m/d/Y', strtotime($denial['service_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Denial Date:</strong></td>
                                <td><?php echo date('m/d/Y', strtotime($denial['denial_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Appeal Deadline:</strong></td>
                                <td>
                                    <?php 
                                    $deadline = new DateTime($denial['appeal_deadline']);
                                    $today = new DateTime();
                                    $isPastDue = $today > $deadline;
                                    ?>
                                    <span class="<?php echo $isPastDue ? 'text-danger fw-bold' : ''; ?>">
                                        <?php echo $deadline->format('m/d/Y'); ?>
                                        <?php if ($isPastDue): ?>
                                            <i class="bi bi-exclamation-triangle text-danger"></i>
                                        <?php endif; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Assigned To:</strong></td>
                                <td><?php echo htmlspecialchars($denial['assigned_to_name'] ?? 'Unassigned'); ?></td>
                            </tr>
                            <?php if ($denial['resolution_date']): ?>
                            <tr>
                                <td><strong>Resolution:</strong></td>
                                <td>
                                    <?php echo ucfirst($denial['resolution_type']); ?> - 
                                    $<?php echo number_format($denial['resolution_amount'], 2); ?>
                                    <br><small><?php echo date('m/d/Y', strtotime($denial['resolution_date'])); ?></small>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-graph-up"></i> Key Metrics</h6>
                    </div>
                    <div class="card-body">
                        <?php 
                        $ageDays = (new DateTime())->diff(new DateTime($denial['denial_date']))->days;
                        $ageClass = $ageDays <= 30 ? 'success' : ($ageDays <= 60 ? 'warning' : 'danger');
                        ?>
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="h5 text-<?php echo $ageClass; ?> mb-0"><?php echo $ageDays; ?></div>
                                    <small class="text-muted">Days Old</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="h5 text-info mb-0"><?php echo count($appeals); ?></div>
                                    <small class="text-muted">Appeals Filed</small>
                                </div>
                            </div>
                        </div>
                        <div class="row text-center mt-2">
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="h5 text-primary mb-0"><?php echo count($activities); ?></div>
                                    <small class="text-muted">Activities</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="h5 text-secondary mb-0"><?php echo count($attachments); ?></div>
                                    <small class="text-muted">Attachments</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appeals Section -->
        <?php if (!empty($appeals)): ?>
        <div class="section-header">
            <h5 class="mb-0"><i class="bi bi-file-earmark-arrow-up"></i> Appeals History</h5>
        </div>
        
        <div class="table-responsive mb-4">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Appeal Date</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Expedited</th>
                        <th>Response Date</th>
                        <th>Outcome</th>
                        <th>Amount</th>
                        <th>Filed By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appeals as $appeal): ?>
                    <tr>
                        <td><?php echo date('m/d/Y', strtotime($appeal['appeal_date'])); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $appeal['appeal_type'])); ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $appeal['status'] === 'approved' ? 'success' : 
                                    ($appeal['status'] === 'pending' ? 'warning' : 
                                    ($appeal['status'] === 'denied' ? 'danger' : 'secondary')); 
                            ?>">
                                <?php echo ucfirst($appeal['status']); ?>
                            </span>
                        </td>
                        <td><?php echo $appeal['expedited'] ? 'Yes' : 'No'; ?></td>
                        <td><?php echo $appeal['response_date'] ? date('m/d/Y', strtotime($appeal['response_date'])) : '-'; ?></td>
                        <td><?php echo htmlspecialchars($appeal['response_reason'] ?? '-'); ?></td>
                        <td>$<?php echo number_format($appeal['outcome_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($appeal['created_by_name']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Tasks Section -->
        <?php if (!empty($tasks)): ?>
        <div class="section-header">
            <h5 class="mb-0"><i class="bi bi-check2-square"></i> Tasks & Follow-ups</h5>
        </div>
        
        <div class="table-responsive mb-4">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Task Type</th>
                        <th>Description</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Created By</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?php echo ucfirst(str_replace('_', ' ', $task['task_type'])); ?></td>
                        <td><?php echo htmlspecialchars($task['description']); ?></td>
                        <td>
                            <?php 
                            $dueDate = new DateTime($task['due_date']);
                            $today = new DateTime();
                            $isOverdue = $today > $dueDate && $task['status'] !== 'completed';
                            ?>
                            <span class="<?php echo $isOverdue ? 'text-danger fw-bold' : ''; ?>">
                                <?php echo $dueDate->format('m/d/Y'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $task['status'] === 'completed' ? 'success' : 
                                    ($task['status'] === 'in_progress' ? 'info' : 'warning'); 
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($task['assigned_to_name'] ?? 'Unassigned'); ?></td>
                        <td><?php echo htmlspecialchars($task['created_by_name']); ?></td>
                        <td><?php echo $task['completed_at'] ? date('m/d/Y', strtotime($task['completed_at'])) : '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Activity Timeline -->
        <div class="section-header">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Activity Timeline</h5>
        </div>
        
        <ul class="timeline">
            <?php foreach ($activities as $index => $activity): ?>
            <li>
                <div class="timeline-badge <?php 
                    echo $activity['activity_type'] === 'resolution' ? 'success' : 
                        ($activity['activity_type'] === 'escalation' ? 'danger' : 
                        ($activity['activity_type'] === 'appeal_filed' ? 'info' : 'primary')); 
                ?>">
                    <i class="bi bi-<?php 
                        echo $activity['activity_type'] === 'resolution' ? 'check' : 
                            ($activity['activity_type'] === 'escalation' ? 'arrow-up' : 
                            ($activity['activity_type'] === 'appeal_filed' ? 'file-earmark' : 'chat')); 
                    ?>"></i>
                </div>
                <div class="timeline-panel">
                    <div class="timeline-heading">
                        <h6 class="timeline-title">
                            <?php echo ucfirst(str_replace('_', ' ', $activity['activity_type'])); ?>
                        </h6>
                    </div>
                    <div class="timeline-body">
                        <?php echo nl2br(htmlspecialchars($activity['description'])); ?>
                    </div>
                    <div class="timeline-footer">
                        <i class="bi bi-person"></i> <?php echo htmlspecialchars($activity['created_by_name']); ?> • 
                        <i class="bi bi-clock"></i> <?php echo date('m/d/Y g:i A', strtotime($activity['created_at'])); ?>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>

        <!-- Attachments Section -->
        <?php if (!empty($attachments)): ?>
        <div class="section-header">
            <h5 class="mb-0"><i class="bi bi-paperclip"></i> Attachments</h5>
        </div>
        
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Description</th>
                        <th>Uploaded By</th>
                        <th>Upload Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attachments as $attachment): ?>
                    <tr>
                        <td>
                            <i class="bi bi-file-earmark"></i>
                            <?php echo htmlspecialchars($attachment['filename']); ?>
                        </td>
                        <td><?php echo htmlspecialchars($attachment['description'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($attachment['uploaded_by_name']); ?></td>
                        <td><?php echo date('m/d/Y g:i A', strtotime($attachment['uploaded_at'])); ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewAttachment(<?php echo $attachment['id']; ?>)">
                                <i class="bi bi-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewAttachment(attachmentId) {
            // Implementation for viewing attachments
            alert('Attachment viewing functionality would be implemented here');
        }
        
        // Auto-close window after 30 seconds if opened as popup
        if (window.opener) {
            setTimeout(function() {
                if (confirm('Close this window?')) {
                    window.close();
                }
            }, 30000);
        }
    </script>
</body>
</html>