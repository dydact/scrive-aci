<?php
require_once '../src/init.php';
requireAuth(4); // Supervisor+ access

header('Content-Type: application/json');

try {
    $pdo = getDatabase();
    $currentUser = getCurrentUser();
    
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    $action = $_POST['action'] ?? '';
    $type = $_POST['type'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    if (!$action || !$type || !$id) {
        throw new Exception('Missing required parameters');
    }
    
    switch ($action) {
        case 'approve':
            if ($type === 'session_note') {
                // Approve session note
                $stmt = $pdo->prepare("
                    UPDATE autism_session_notes 
                    SET status = 'approved',
                        approved_by = ?,
                        approved_at = NOW(),
                        supervisor_review_needed = 0
                    WHERE id = ? AND status IN ('draft', 'completed')
                ");
                $stmt->execute([$currentUser['id'], $id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Session note approved']);
                } else {
                    throw new Exception('Session note not found or already approved');
                }
                
            } elseif ($type === 'time_entry') {
                // Approve time clock entry
                $stmt = $pdo->prepare("
                    UPDATE autism_time_clock 
                    SET status = 'approved',
                        approved_by = ?,
                        approved_at = NOW()
                    WHERE id = ? AND status = 'clocked_out'
                ");
                $stmt->execute([$currentUser['id'], $id]);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'Time entry approved']);
                } else {
                    throw new Exception('Time entry not found or already approved');
                }
                
            } else {
                throw new Exception('Invalid type');
            }
            break;
            
        case 'reject':
            $reason = $_POST['reason'] ?? '';
            
            if ($type === 'session_note') {
                // Reject session note
                $stmt = $pdo->prepare("
                    UPDATE autism_session_notes 
                    SET status = 'draft',
                        approved_by = NULL,
                        approved_at = NULL,
                        additional_notes = CONCAT(IFNULL(additional_notes, ''), '\n\nSupervisor Feedback: ', ?)
                    WHERE id = ?
                ");
                $stmt->execute([$reason, $id]);
                
                echo json_encode(['success' => true, 'message' => 'Session note returned for revision']);
                
            } elseif ($type === 'time_entry') {
                // Reject time entry
                $stmt = $pdo->prepare("
                    UPDATE autism_time_clock 
                    SET status = 'rejected',
                        approved_by = ?,
                        approved_at = NOW(),
                        notes = CONCAT(IFNULL(notes, ''), '\n\nRejection Reason: ', ?)
                    WHERE id = ?
                ");
                $stmt->execute([$currentUser['id'], $reason, $id]);
                
                echo json_encode(['success' => true, 'message' => 'Time entry rejected']);
                
            } else {
                throw new Exception('Invalid type');
            }
            break;
            
        case 'bulk_approve':
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            
            if (empty($ids)) {
                throw new Exception('No items selected');
            }
            
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            
            if ($type === 'session_notes') {
                $stmt = $pdo->prepare("
                    UPDATE autism_session_notes 
                    SET status = 'approved',
                        approved_by = ?,
                        approved_at = NOW(),
                        supervisor_review_needed = 0
                    WHERE id IN ($placeholders) AND status IN ('draft', 'completed')
                ");
                $params = array_merge([$currentUser['id']], $ids);
                $stmt->execute($params);
                
                $approved = $stmt->rowCount();
                echo json_encode(['success' => true, 'message' => "$approved session notes approved"]);
                
            } elseif ($type === 'time_entries') {
                $stmt = $pdo->prepare("
                    UPDATE autism_time_clock 
                    SET status = 'approved',
                        approved_by = ?,
                        approved_at = NOW()
                    WHERE id IN ($placeholders) AND status = 'clocked_out'
                ");
                $params = array_merge([$currentUser['id']], $ids);
                $stmt->execute($params);
                
                $approved = $stmt->rowCount();
                echo json_encode(['success' => true, 'message' => "$approved time entries approved"]);
                
            } else {
                throw new Exception('Invalid type for bulk approval');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}