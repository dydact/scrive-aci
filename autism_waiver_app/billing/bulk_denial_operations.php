<?php
require_once dirname(__DIR__) . '/config_sqlite.php';

// Check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'employee';

// Only allow bulk operations for admins and billing specialists
if (!in_array($userRole, ['admin', 'billing_specialist'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

header('Content-Type: application/json');

$operation = $_POST['operation'] ?? '';
$denialIds = $_POST['denial_ids'] ?? [];

if (empty($operation) || empty($denialIds)) {
    echo json_encode(['success' => false, 'message' => 'Operation and denial IDs required']);
    exit;
}

// Validate denial IDs
$denialIds = array_filter(array_map('intval', $denialIds));
if (empty($denialIds)) {
    echo json_encode(['success' => false, 'message' => 'Valid denial IDs required']);
    exit;
}

try {
    $db->beginTransaction();
    
    switch ($operation) {
        case 'assign':
            $assignTo = $_POST['assign_to'] ?? '';
            if (!$assignTo) {
                throw new Exception('Assignee required');
            }
            
            // Verify assignee exists
            $stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND role IN ('admin', 'billing_specialist')");
            $stmt->execute([$assignTo]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid assignee');
            }
            
            // Update denials
            $placeholders = str_repeat('?,', count($denialIds) - 1) . '?';
            $stmt = $db->prepare("
                UPDATE claim_denials 
                SET assigned_to = ?, 
                    assigned_date = datetime('now'),
                    updated_at = datetime('now'),
                    updated_by = ?
                WHERE id IN ($placeholders) AND status NOT IN ('resolved')
            ");
            
            $params = array_merge([$assignTo, $userId], $denialIds);
            $stmt->execute($params);
            $affectedRows = $stmt->rowCount();
            
            // Log activities
            foreach ($denialIds as $denialId) {
                $stmt = $db->prepare("
                    INSERT INTO denial_activities (
                        denial_id, activity_type, description, created_by, created_at
                    ) VALUES (?, 'assignment', ?, ?, datetime('now'))
                ");
                $stmt->execute([$denialId, "Bulk assigned via bulk operations", $userId]);
            }
            
            $message = "Successfully assigned $affectedRows denials";
            break;
            
        case 'priority':
            $priority = $_POST['priority'] ?? '';
            if (!in_array($priority, ['low', 'medium', 'high'])) {
                throw new Exception('Valid priority required');
            }
            
            $placeholders = str_repeat('?,', count($denialIds) - 1) . '?';
            $stmt = $db->prepare("
                UPDATE claim_denials 
                SET priority = ?, 
                    updated_at = datetime('now'),
                    updated_by = ?
                WHERE id IN ($placeholders) AND status NOT IN ('resolved')
            ");
            
            $params = array_merge([$priority, $userId], $denialIds);
            $stmt->execute($params);
            $affectedRows = $stmt->rowCount();
            
            // Log activities
            foreach ($denialIds as $denialId) {
                $stmt = $db->prepare("
                    INSERT INTO denial_activities (
                        denial_id, activity_type, description, created_by, created_at
                    ) VALUES (?, 'status_update', ?, ?, datetime('now'))
                ");
                $stmt->execute([$denialId, "Priority changed to $priority via bulk operations", $userId]);
            }
            
            $message = "Successfully updated priority for $affectedRows denials";
            break;
            
        case 'status':
            $status = $_POST['status'] ?? '';
            if (!in_array($status, ['pending', 'in_progress', 'appealed', 'resubmitted', 'escalated'])) {
                throw new Exception('Valid status required');
            }
            
            $placeholders = str_repeat('?,', count($denialIds) - 1) . '?';
            $stmt = $db->prepare("
                UPDATE claim_denials 
                SET status = ?, 
                    updated_at = datetime('now'),
                    updated_by = ?
                WHERE id IN ($placeholders) AND status NOT IN ('resolved')
            ");
            
            $params = array_merge([$status, $userId], $denialIds);
            $stmt->execute($params);
            $affectedRows = $stmt->rowCount();
            
            // Log activities
            foreach ($denialIds as $denialId) {
                $stmt = $db->prepare("
                    INSERT INTO denial_activities (
                        denial_id, activity_type, description, created_by, created_at
                    ) VALUES (?, 'status_update', ?, ?, datetime('now'))
                ");
                $stmt->execute([$denialId, "Status changed to $status via bulk operations", $userId]);
            }
            
            $message = "Successfully updated status for $affectedRows denials";
            break;
            
        case 'create_tasks':
            $taskType = $_POST['task_type'] ?? '';
            $taskDescription = $_POST['task_description'] ?? '';
            $dueDate = $_POST['due_date'] ?? '';
            $assignTo = $_POST['assign_to'] ?? $userId;
            
            if (!$taskType || !$taskDescription || !$dueDate) {
                throw new Exception('Task type, description, and due date required');
            }
            
            if (!in_array($taskType, ['follow_up', 'appeal_deadline', 'documentation_request', 'provider_contact', 'resubmission'])) {
                throw new Exception('Valid task type required');
            }
            
            $taskCount = 0;
            foreach ($denialIds as $denialId) {
                // Check if denial exists and is not resolved
                $stmt = $db->prepare("SELECT id FROM claim_denials WHERE id = ? AND status != 'resolved'");
                $stmt->execute([$denialId]);
                if ($stmt->fetch()) {
                    $stmt = $db->prepare("
                        INSERT INTO denial_tasks (
                            denial_id, task_type, description, due_date,
                            assigned_to, status, created_by, created_at
                        ) VALUES (?, ?, ?, ?, ?, 'pending', ?, datetime('now'))
                    ");
                    $stmt->execute([$denialId, $taskType, $taskDescription, $dueDate, $assignTo, $userId]);
                    $taskCount++;
                    
                    // Log activity
                    $stmt = $db->prepare("
                        INSERT INTO denial_activities (
                            denial_id, activity_type, description, created_by, created_at
                        ) VALUES (?, 'note', ?, ?, datetime('now'))
                    ");
                    $stmt->execute([$denialId, "Task created: $taskDescription", $userId]);
                }
            }
            
            $message = "Successfully created $taskCount tasks";
            break;
            
        case 'export':
            // Generate CSV export for selected denials
            $placeholders = str_repeat('?,', count($denialIds) - 1) . '?';
            $stmt = $db->prepare("
                SELECT cd.claim_number, cd.service_date, cd.denial_date, cd.denial_code,
                       cd.denial_reason, cd.amount, cd.status, cd.priority,
                       c.client_name, c.medicaid_id,
                       u.full_name as assigned_to_name,
                       cd.appeal_deadline, cd.resolution_type, cd.resolution_amount
                FROM claim_denials cd
                LEFT JOIN clients c ON cd.client_id = c.id
                LEFT JOIN users u ON cd.assigned_to = u.id
                WHERE cd.id IN ($placeholders)
                ORDER BY cd.denial_date DESC
            ");
            $stmt->execute($denialIds);
            $denials = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generate CSV content
            $csvContent = "Claim Number,Client Name,Medicaid ID,Service Date,Denial Date,Denial Code,Denial Reason,Amount,Status,Priority,Assigned To,Appeal Deadline,Resolution Type,Resolution Amount\n";
            
            foreach ($denials as $denial) {
                $csvContent .= sprintf(
                    '"%s","%s","%s","%s","%s","%s","%s","%.2f","%s","%s","%s","%s","%s","%.2f"' . "\n",
                    $denial['claim_number'],
                    $denial['client_name'],
                    $denial['medicaid_id'],
                    $denial['service_date'],
                    $denial['denial_date'],
                    $denial['denial_code'],
                    str_replace('"', '""', $denial['denial_reason']),
                    $denial['amount'],
                    $denial['status'],
                    $denial['priority'],
                    $denial['assigned_to_name'] ?? 'Unassigned',
                    $denial['appeal_deadline'],
                    $denial['resolution_type'] ?? '',
                    $denial['resolution_amount'] ?? 0
                );
            }
            
            // Save to temporary file and return download link
            $filename = 'denial_export_' . date('Y-m-d_H-i-s') . '.csv';
            $filepath = sys_get_temp_dir() . '/' . $filename;
            file_put_contents($filepath, $csvContent);
            
            // For this demo, we'll just return the CSV content
            $db->commit();
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            echo $csvContent;
            exit;
            
        case 'escalate':
            $escalationReason = $_POST['escalation_reason'] ?? 'Bulk escalation';
            
            // Find supervisor
            $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            $stmt->execute();
            $supervisor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$supervisor) {
                throw new Exception('No supervisor found for escalation');
            }
            
            $placeholders = str_repeat('?,', count($denialIds) - 1) . '?';
            $stmt = $db->prepare("
                UPDATE claim_denials 
                SET priority = 'high',
                    assigned_to = ?,
                    status = 'escalated',
                    updated_at = datetime('now'),
                    updated_by = ?
                WHERE id IN ($placeholders) AND status NOT IN ('resolved')
            ");
            
            $params = array_merge([$supervisor['id'], $userId], $denialIds);
            $stmt->execute($params);
            $affectedRows = $stmt->rowCount();
            
            // Log escalations
            foreach ($denialIds as $denialId) {
                $stmt = $db->prepare("
                    INSERT INTO denial_activities (
                        denial_id, activity_type, description, created_by, created_at
                    ) VALUES (?, 'escalation', ?, ?, datetime('now'))
                ");
                $stmt->execute([$denialId, "Bulk escalated: $escalationReason", $userId]);
            }
            
            $message = "Successfully escalated $affectedRows denials to supervisor";
            break;
            
        case 'bulk_appeal':
            $appealType = $_POST['appeal_type'] ?? 'reconsideration';
            $appealReason = $_POST['appeal_reason'] ?? '';
            $contactPerson = $_POST['contact_person'] ?? '';
            $contactPhone = $_POST['contact_phone'] ?? '';
            
            if (!$appealReason || !$contactPerson || !$contactPhone) {
                throw new Exception('Appeal reason, contact person, and phone required');
            }
            
            $appealCount = 0;
            foreach ($denialIds as $denialId) {
                // Check if denial exists, is not resolved, and doesn't already have an appeal
                $stmt = $db->prepare("
                    SELECT id FROM claim_denials 
                    WHERE id = ? AND status NOT IN ('resolved', 'appealed') 
                    AND appeal_status IS NULL
                ");
                $stmt->execute([$denialId]);
                if ($stmt->fetch()) {
                    // Create appeal
                    $stmt = $db->prepare("
                        INSERT INTO claim_appeals (
                            denial_id, appeal_date, appeal_type, appeal_reason,
                            contact_person, contact_phone, status, created_by, created_at
                        ) VALUES (?, date('now'), ?, ?, ?, ?, 'submitted', ?, datetime('now'))
                    ");
                    $stmt->execute([$denialId, $appealType, $appealReason, $contactPerson, $contactPhone, $userId]);
                    
                    $appealId = $db->lastInsertId();
                    
                    // Update denial status
                    $stmt = $db->prepare("
                        UPDATE claim_denials 
                        SET status = 'appealed',
                            appeal_status = 'submitted',
                            appeal_id = ?,
                            appeal_submission_date = date('now'),
                            updated_at = datetime('now'),
                            updated_by = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$appealId, $userId, $denialId]);
                    
                    // Log activity
                    $stmt = $db->prepare("
                        INSERT INTO denial_activities (
                            denial_id, activity_type, description, created_by, created_at
                        ) VALUES (?, 'appeal_filed', ?, ?, datetime('now'))
                    ");
                    $stmt->execute([$denialId, "Bulk appeal filed: $appealType", $userId]);
                    
                    $appealCount++;
                }
            }
            
            $message = "Successfully filed $appealCount appeals";
            break;
            
        default:
            throw new Exception('Invalid operation');
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Error in bulk denial operations: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>