<?php

/**
 * API Endpoints for Enhanced Service System
 * 
 * @package   Scrive
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
 * @license   MIT License
 */

// Include Scrive authentication and API
require_once 'auth.php';
require_once 'api.php';

// Initialize authentication
initScriveAuth();

$api = new OpenEMRAPI();

// Set JSON content type
header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'unit_warnings':
            // Get unit depletion warnings
            $alertLevel = $_GET['level'] ?? 'warning';
            $warnings = $api->getUnitDepletionWarnings($alertLevel);
            echo json_encode($warnings);
            break;
            
        case 'client_unit_status':
            // Get unit status for specific client
            $clientId = $_GET['client_id'] ?? 0;
            if (!$clientId) {
                throw new Exception('Client ID required');
            }
            $unitStatus = $api->getClientWeeklyUnitStatus($clientId);
            echo json_encode($unitStatus);
            break;
            
        case 'available_services':
            // Get available services for a program
            $program = $_GET['program'] ?? '';
            if (!$program) {
                throw new Exception('Program abbreviation required');
            }
            $services = $api->getAvailableServicesForProgram($program);
            echo json_encode($services);
            break;
            
        case 'update_weekly_units':
            // Update weekly units for a client service
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $clientId = $_POST['client_id'] ?? 0;
            $serviceAbbr = $_POST['service'] ?? '';
            $weeklyUnits = $_POST['weekly_units'] ?? 0;
            $userId = getCurrentScriveUser()['user_id'] ?? 1;
            
            if (!$clientId || !$serviceAbbr || !$weeklyUnits) {
                throw new Exception('Missing required parameters');
            }
            
            $result = $api->updateClientWeeklyUnits($clientId, $serviceAbbr, $weeklyUnits, $userId);
            echo json_encode(['success' => true, 'message' => 'Weekly units updated successfully']);
            break;
            
        case 'record_usage':
            // Record service usage
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $clientId = $_POST['client_id'] ?? 0;
            $serviceAbbr = $_POST['service'] ?? '';
            $unitsUsed = $_POST['units_used'] ?? 0;
            $sessionDate = $_POST['session_date'] ?? null;
            
            if (!$clientId || !$serviceAbbr || !$unitsUsed) {
                throw new Exception('Missing required parameters');
            }
            
            $result = $api->recordServiceUsage($clientId, $serviceAbbr, $unitsUsed, $sessionDate);
            echo json_encode(['success' => true, 'message' => 'Service usage recorded successfully']);
            break;
            
        case 'program_service_config':
            // Get service configuration for a specific program and service
            $programAbbr = $_GET['program'] ?? '';
            $serviceAbbr = $_GET['service'] ?? '';
            
            if (!$programAbbr || !$serviceAbbr) {
                throw new Exception('Program and service abbreviations required');
            }
            
            $config = $api->getProgramServiceConfig($programAbbr, $serviceAbbr);
            echo json_encode($config);
            break;
            
        case 'dashboard_stats':
            // Get dashboard statistics
            $stats = [
                'total_clients' => 0,
                'active_sessions_today' => 0,
                'critical_warnings' => 0,
                'exhausted_services' => 0
            ];
            
            // Total clients
            $clientCount = sqlQuery("SELECT COUNT(*) as count FROM patient_data");
            $stats['total_clients'] = $clientCount['count'] ?? 0;
            
            // Sessions today
            $sessionCount = sqlQuery("SELECT COUNT(*) as count FROM autism_session WHERE date = CURDATE()");
            $stats['active_sessions_today'] = $sessionCount['count'] ?? 0;
            
            // Unit warnings
            $warnings = $api->getUnitDepletionWarnings('all');
            $stats['critical_warnings'] = count(array_filter($warnings, function($w) { return $w['status'] === 'critical'; }));
            $stats['exhausted_services'] = count(array_filter($warnings, function($w) { return $w['status'] === 'exhausted'; }));
            
            echo json_encode($stats);
            break;
            
        case 'weekly_reset_check':
            // Check and reset weekly counters if needed
            try {
                $sql = "UPDATE autism_client_authorizations 
                        SET current_week_used = 0,
                            last_week_reset = CURDATE(),
                            alert_level = 'none'
                        WHERE last_week_reset IS NULL 
                           OR DATEDIFF(CURDATE(), last_week_reset) >= 7";
                
                $result = sqlStatement($sql);
                $affectedRows = sqlAffectedRows();
                
                echo json_encode([
                    'success' => true, 
                    'message' => "Reset {$affectedRows} weekly counters",
                    'affected_rows' => $affectedRows
                ]);
            } catch (Exception $e) {
                throw new Exception('Failed to reset weekly counters: ' . $e->getMessage());
            }
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}

?> 