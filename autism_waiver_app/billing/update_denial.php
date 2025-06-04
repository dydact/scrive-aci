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
$action = $_REQUEST['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'get':
            // Get denial details
            $denialId = $_GET['id'] ?? '';
            if (!$denialId) {
                throw new Exception('Denial ID required');
            }
            
            $stmt = $db->prepare("
                SELECT cd.*, c.client_name, c.medicaid_id
                FROM claim_denials cd
                LEFT JOIN clients c ON cd.client_id = c.id
                WHERE cd.id = ?
            ");
            $stmt->execute([$denialId]);
            $denial = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$denial) {
                throw new Exception('Denial not found');
            }
            
            echo json_encode(['success' => true, 'denial' => $denial]);
            break;
            
        case 'update':
            // Update denial record
            $denialId = $_POST['denial_id'] ?? '';
            if (!$denialId) {
                throw new Exception('Denial ID required');
            }
            
            $db->beginTransaction();
            
            // Update main denial record
            $stmt = $db->prepare("
                UPDATE claim_denials 
                SET status = ?, 
                    assigned_to = ?, 
                    priority = ?, 
                    follow_up_date = ?,
                    updated_at = datetime('now'),
                    updated_by = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST['status'],
                $_POST['assigned_to'] ?: null,
                $_POST['priority'],
                $_POST['follow_up_date'] ?: null,
                $userId,
                $denialId
            ]);
            
            // Add activity log entry
            $stmt = $db->prepare("
                INSERT INTO denial_activities (
                    denial_id, activity_type, description, 
                    created_by, created_at
                ) VALUES (?, 'status_update', ?, ?, datetime('now'))
            ");
            
            $description = "Status changed to: " . $_POST['status'];
            if (!empty($_POST['action_taken'])) {
                $description .= "\nAction: " . $_POST['action_taken'];
            }
            if (!empty($_POST['notes'])) {
                $description .= "\nNotes: " . $_POST['notes'];
            }
            
            $stmt->execute([$denialId, $description, $userId]);
            
            // Handle file uploads if any
            if (!empty($_FILES['attachments']['name'][0])) {
                $uploadDir = dirname(__DIR__) . '/uploads/denials/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                foreach ($_FILES['attachments']['name'] as $key => $filename) {
                    if ($_FILES['attachments']['error'][$key] == 0) {
                        $targetFile = $uploadDir . $denialId . '_' . time() . '_' . basename($filename);
                        if (move_uploaded_file($_FILES['attachments']['tmp_name'][$key], $targetFile)) {
                            // Record attachment
                            $stmt = $db->prepare("
                                INSERT INTO denial_attachments (
                                    denial_id, filename, file_path, uploaded_by, uploaded_at
                                ) VALUES (?, ?, ?, ?, datetime('now'))
                            ");
                            $stmt->execute([$denialId, $filename, $targetFile, $userId]);
                        }
                    }
                }
            }
            
            // Create follow-up task if needed
            if (!empty($_POST['follow_up_date']) && $_POST['status'] !== 'resolved') {
                $stmt = $db->prepare("
                    INSERT INTO denial_tasks (
                        denial_id, task_type, description, due_date,
                        assigned_to, created_by, created_at, status
                    ) VALUES (?, 'follow_up', ?, ?, ?, ?, datetime('now'), 'pending')
                ");
                
                $taskDescription = "Follow up on denial for claim";
                if (!empty($_POST['action_taken'])) {
                    $taskDescription .= " - " . $_POST['action_taken'];
                }
                
                $stmt->execute([
                    $denialId,
                    $taskDescription,
                    $_POST['follow_up_date'],
                    $_POST['assigned_to'] ?: $userId,
                    $userId
                ]);
            }
            
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Denial updated successfully']);
            break;
            
        case 'assign':
            // Bulk assign denials
            $denialIds = $_POST['denial_ids'] ?? [];
            $assignTo = $_POST['assign_to'] ?? '';
            
            if (empty($denialIds) || !$assignTo) {
                throw new Exception('Denial IDs and assignee required');
            }
            
            $db->beginTransaction();
            
            $placeholders = str_repeat('?,', count($denialIds) - 1) . '?';
            $stmt = $db->prepare("
                UPDATE claim_denials 
                SET assigned_to = ?, 
                    assigned_date = datetime('now'),
                    updated_at = datetime('now'),
                    updated_by = ?
                WHERE id IN ($placeholders)
            ");
            
            $params = array_merge([$assignTo, $userId], $denialIds);
            $stmt->execute($params);
            
            // Log bulk assignment
            foreach ($denialIds as $denialId) {
                $stmt = $db->prepare("
                    INSERT INTO denial_activities (
                        denial_id, activity_type, description, 
                        created_by, created_at
                    ) VALUES (?, 'assignment', ?, ?, datetime('now'))
                ");
                $stmt->execute([$denialId, "Bulk assigned for review", $userId]);
            }
            
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Denials assigned successfully']);
            break;
            
        case 'resolve':
            // Resolve denial
            $denialId = $_POST['denial_id'] ?? '';
            $resolutionType = $_POST['resolution_type'] ?? '';
            $resolutionAmount = $_POST['resolution_amount'] ?? 0;
            $resolutionNotes = $_POST['resolution_notes'] ?? '';
            
            if (!$denialId || !$resolutionType) {
                throw new Exception('Denial ID and resolution type required');
            }
            
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                UPDATE claim_denials 
                SET status = 'resolved',
                    resolution_type = ?,
                    resolution_amount = ?,
                    resolution_notes = ?,
                    resolution_date = datetime('now'),
                    updated_at = datetime('now'),
                    updated_by = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $resolutionType,
                $resolutionAmount,
                $resolutionNotes,
                $userId,
                $denialId
            ]);
            
            // Log resolution
            $stmt = $db->prepare("
                INSERT INTO denial_activities (
                    denial_id, activity_type, description, 
                    created_by, created_at
                ) VALUES (?, 'resolution', ?, ?, datetime('now'))
            ");
            
            $description = "Denial resolved: " . $resolutionType;
            if ($resolutionAmount > 0) {
                $description .= " - Amount: $" . number_format($resolutionAmount, 2);
            }
            if ($resolutionNotes) {
                $description .= " - " . $resolutionNotes;
            }
            
            $stmt->execute([$denialId, $description, $userId]);
            
            // Update any pending tasks
            $stmt = $db->prepare("
                UPDATE denial_tasks 
                SET status = 'completed', 
                    completed_at = datetime('now')
                WHERE denial_id = ? AND status = 'pending'
            ");
            $stmt->execute([$denialId]);
            
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Denial resolved successfully']);
            break;
            
        case 'add_note':
            // Add note to denial
            $denialId = $_POST['denial_id'] ?? '';
            $note = $_POST['note'] ?? '';
            
            if (!$denialId || !$note) {
                throw new Exception('Denial ID and note required');
            }
            
            $stmt = $db->prepare("
                INSERT INTO denial_activities (
                    denial_id, activity_type, description, 
                    created_by, created_at
                ) VALUES (?, 'note', ?, ?, datetime('now'))
            ");
            
            $stmt->execute([$denialId, $note, $userId]);
            
            echo json_encode(['success' => true, 'message' => 'Note added successfully']);
            break;
            
        case 'get_history':
            // Get denial activity history
            $denialId = $_GET['denial_id'] ?? '';
            if (!$denialId) {
                throw new Exception('Denial ID required');
            }
            
            $stmt = $db->prepare("
                SELECT da.*, u.full_name as created_by_name
                FROM denial_activities da
                LEFT JOIN users u ON da.created_by = u.id
                WHERE da.denial_id = ?
                ORDER BY da.created_at DESC
            ");
            $stmt->execute([$denialId]);
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'activities' => $activities]);
            break;
            
        case 'bulk_status':
            // Bulk status update
            $denialIds = $_POST['denial_ids'] ?? [];
            $status = $_POST['status'] ?? '';
            
            if (empty($denialIds) || !$status) {
                throw new Exception('Denial IDs and status required');
            }
            
            $db->beginTransaction();
            
            $placeholders = str_repeat('?,', count($denialIds) - 1) . '?';
            $stmt = $db->prepare("
                UPDATE claim_denials 
                SET status = ?, 
                    updated_at = datetime('now'),
                    updated_by = ?
                WHERE id IN ($placeholders)
            ");
            
            $params = array_merge([$status, $userId], $denialIds);
            $stmt->execute($params);
            
            // Log bulk status change
            foreach ($denialIds as $denialId) {
                $stmt = $db->prepare("
                    INSERT INTO denial_activities (
                        denial_id, activity_type, description, 
                        created_by, created_at
                    ) VALUES (?, 'status_update', ?, ?, datetime('now'))
                ");
                $stmt->execute([$denialId, "Bulk status change to: " . $status, $userId]);
            }
            
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Status updated for ' . count($denialIds) . ' denials']);
            break;
            
        case 'escalate':
            // Escalate denial to supervisor
            $denialId = $_POST['denial_id'] ?? '';
            $escalationReason = $_POST['escalation_reason'] ?? '';
            
            if (!$denialId || !$escalationReason) {
                throw new Exception('Denial ID and escalation reason required');
            }
            
            $db->beginTransaction();
            
            // Find supervisor
            $stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            $stmt->execute();
            $supervisor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$supervisor) {
                throw new Exception('No supervisor found for escalation');
            }
            
            // Update denial
            $stmt = $db->prepare("
                UPDATE claim_denials 
                SET priority = 'high',
                    assigned_to = ?,
                    status = 'escalated',
                    updated_at = datetime('now'),
                    updated_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$supervisor['id'], $userId, $denialId]);
            
            // Log escalation
            $stmt = $db->prepare("
                INSERT INTO denial_activities (
                    denial_id, activity_type, description, 
                    created_by, created_at
                ) VALUES (?, 'escalation', ?, ?, datetime('now'))
            ");
            $stmt->execute([$denialId, "Escalated to supervisor: " . $escalationReason, $userId]);
            
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Denial escalated successfully']);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Error in update_denial.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>