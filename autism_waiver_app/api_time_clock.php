<?php
/**
 * Time Clock API Endpoint
 * Handles clock in/out for staff portal
 */

session_start();
require_once '../src/config.php';
require_once '../src/openemr_integration.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = getDatabase();
    $staff_id = $_SESSION['staff_id'] ?? $_SESSION['user_id'];
    
    switch ($action) {
        case 'clock_in':
            // Check if already clocked in
            $stmt = $pdo->prepare("
                SELECT id FROM autism_time_clock 
                WHERE employee_id = ? AND clock_out IS NULL
            ");
            $stmt->execute([$staff_id]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Already clocked in']);
                exit;
            }
            
            // Clock in
            $stmt = $pdo->prepare("
                INSERT INTO autism_time_clock (employee_id, clock_in) 
                VALUES (?, NOW())
            ");
            $stmt->execute([$staff_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Clocked in successfully',
                'clock_id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'clock_out':
            // Find active clock in
            $stmt = $pdo->prepare("
                SELECT id, clock_in FROM autism_time_clock 
                WHERE employee_id = ? AND clock_out IS NULL
                ORDER BY clock_in DESC LIMIT 1
            ");
            $stmt->execute([$staff_id]);
            $clock = $stmt->fetch();
            
            if (!$clock) {
                echo json_encode(['success' => false, 'message' => 'No active clock in found']);
                exit;
            }
            
            // Clock out and calculate hours
            $stmt = $pdo->prepare("
                UPDATE autism_time_clock 
                SET clock_out = NOW(),
                    total_hours = TIME_TO_SEC(TIMEDIFF(NOW(), clock_in)) / 3600,
                    status = 'clocked_out'
                WHERE id = ?
            ");
            $stmt->execute([$clock['id']]);
            
            // Calculate total hours
            $hours = (time() - strtotime($clock['clock_in'])) / 3600;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Clocked out successfully',
                'hours' => round($hours, 2)
            ]);
            break;
            
        case 'status':
            // Get current clock status
            $stmt = $pdo->prepare("
                SELECT * FROM autism_time_clock 
                WHERE employee_id = ? 
                ORDER BY clock_in DESC LIMIT 1
            ");
            $stmt->execute([$staff_id]);
            $clock = $stmt->fetch();
            
            if ($clock && !$clock['clock_out']) {
                $duration = (time() - strtotime($clock['clock_in'])) / 3600;
                echo json_encode([
                    'success' => true,
                    'status' => 'clocked_in',
                    'clock_in' => $clock['clock_in'],
                    'duration' => round($duration, 2)
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'status' => 'clocked_out',
                    'last_clock_out' => $clock['clock_out'] ?? null
                ]);
            }
            break;
            
        case 'history':
            // Get clock history
            $date_from = $input['date_from'] ?? date('Y-m-01');
            $date_to = $input['date_to'] ?? date('Y-m-t');
            
            $stmt = $pdo->prepare("
                SELECT * FROM autism_time_clock 
                WHERE employee_id = ? 
                  AND DATE(clock_in) BETWEEN ? AND ?
                ORDER BY clock_in DESC
            ");
            $stmt->execute([$staff_id, $date_from, $date_to]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate totals
            $total_hours = 0;
            $total_days = 0;
            $days_worked = [];
            
            foreach ($history as $entry) {
                if ($entry['total_hours']) {
                    $total_hours += $entry['total_hours'];
                    $day = date('Y-m-d', strtotime($entry['clock_in']));
                    if (!in_array($day, $days_worked)) {
                        $days_worked[] = $day;
                        $total_days++;
                    }
                }
            }
            
            echo json_encode([
                'success' => true,
                'history' => $history,
                'summary' => [
                    'total_hours' => round($total_hours, 2),
                    'total_days' => $total_days,
                    'average_hours' => $total_days > 0 ? round($total_hours / $total_days, 2) : 0
                ]
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}