<?php
/**
 * Time Clock API Endpoint
 * Handles clock in/out for mobile employee portal
 */

session_start();
require_once 'auth_helper.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Database connection
$database = getenv('MARIADB_DATABASE') ?: 'iris';
$username = getenv('MARIADB_USER') ?: 'iris_user';
$password = getenv('MARIADB_PASSWORD') ?: '';
$host = getenv('MARIADB_HOST') ?: 'localhost';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
$action = $input['action'] ?? '';
$employeeId = $input['employee_id'] ?? $_SESSION['user_id'];
$location = $input['location'] ?? null;

if (!in_array($action, ['clock_in', 'clock_out', 'status'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Ensure employee can only clock themselves
if ($employeeId != $_SESSION['user_id'] && $_SESSION['access_level'] < 4) {
    echo json_encode(['success' => false, 'message' => 'Cannot clock for other employees']);
    exit;
}

try {
    switch ($action) {
        case 'status':
            // Get current clock status
            $stmt = $pdo->prepare("
                SELECT id, clock_in, clock_out, status
                FROM autism_time_clock
                WHERE employee_id = :employee_id
                ORDER BY clock_in DESC
                LIMIT 1
            ");
            $stmt->execute(['employee_id' => $employeeId]);
            $lastEntry = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $isClockedIn = $lastEntry && !$lastEntry['clock_out'];
            
            echo json_encode([
                'success' => true,
                'clocked_in' => $isClockedIn,
                'clock_in_time' => $lastEntry['clock_in'] ?? null,
                'entry_id' => $lastEntry['id'] ?? null
            ]);
            break;
            
        case 'clock_in':
            // Check if already clocked in
            $stmt = $pdo->prepare("
                SELECT id FROM autism_time_clock
                WHERE employee_id = :employee_id
                AND clock_out IS NULL
                ORDER BY clock_in DESC
                LIMIT 1
            ");
            $stmt->execute(['employee_id' => $employeeId]);
            
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Already clocked in']);
                exit;
            }
            
            // Clock in
            $stmt = $pdo->prepare("
                INSERT INTO autism_time_clock 
                (employee_id, clock_in, clock_in_location, status)
                VALUES (:employee_id, NOW(), :location, 'clocked_in')
            ");
            $stmt->execute([
                'employee_id' => $employeeId,
                'location' => $location
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Clocked in successfully',
                'entry_id' => $pdo->lastInsertId()
            ]);
            break;
            
        case 'clock_out':
            // Find active clock entry
            $stmt = $pdo->prepare("
                SELECT id, clock_in
                FROM autism_time_clock
                WHERE employee_id = :employee_id
                AND clock_out IS NULL
                ORDER BY clock_in DESC
                LIMIT 1
            ");
            $stmt->execute(['employee_id' => $employeeId]);
            $activeEntry = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$activeEntry) {
                echo json_encode(['success' => false, 'message' => 'Not clocked in']);
                exit;
            }
            
            // Calculate total hours
            $clockIn = new DateTime($activeEntry['clock_in']);
            $clockOut = new DateTime();
            $interval = $clockIn->diff($clockOut);
            $totalHours = $interval->h + ($interval->i / 60);
            
            // Clock out
            $stmt = $pdo->prepare("
                UPDATE autism_time_clock
                SET clock_out = NOW(),
                    clock_out_location = :location,
                    total_hours = :total_hours,
                    status = 'clocked_out'
                WHERE id = :id
            ");
            $stmt->execute([
                'location' => $location,
                'total_hours' => round($totalHours, 2),
                'id' => $activeEntry['id']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Clocked out successfully',
                'total_hours' => round($totalHours, 2)
            ]);
            break;
    }
} catch (PDOException $e) {
    error_log("Time clock API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>