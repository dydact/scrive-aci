<?php

/**
 * Treatment Plan & Goal Management API
 * 
 * @package   Scrive
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
 * @license   MIT License
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database connection
function getDbConnection() {
    try {
        $dsn = "mysql:host=localhost;dbname=openemr;charset=utf8mb4";
        $pdo = new PDO($dsn, 'openemr', 'openemr');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
}

// Create treatment plan tables if they don't exist
function createTreatmentPlanTables() {
    try {
        $pdo = getDbConnection();
        
        // Treatment plans table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS autism_treatment_plans (
                plan_id INT AUTO_INCREMENT PRIMARY KEY,
                client_id INT NOT NULL,
                plan_name VARCHAR(255) NOT NULL,
                created_date DATE NOT NULL,
                start_date DATE NOT NULL,
                end_date DATE,
                status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
                created_by INT NOT NULL,
                updated_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_client_id (client_id),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Treatment goals table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS autism_treatment_goals (
                goal_id INT AUTO_INCREMENT PRIMARY KEY,
                plan_id INT NOT NULL,
                goal_category VARCHAR(100) NOT NULL,
                goal_title VARCHAR(255) NOT NULL,
                goal_description TEXT NOT NULL,
                target_criteria TEXT,
                current_progress INT DEFAULT 0,
                target_date DATE,
                priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
                status ENUM('active', 'achieved', 'discontinued') DEFAULT 'active',
                created_by INT NOT NULL,
                updated_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (plan_id) REFERENCES autism_treatment_plans(plan_id) ON DELETE CASCADE,
                INDEX idx_plan_id (plan_id),
                INDEX idx_category (goal_category),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Goal progress tracking
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS autism_goal_progress (
                progress_id INT AUTO_INCREMENT PRIMARY KEY,
                goal_id INT NOT NULL,
                session_date DATE NOT NULL,
                progress_rating INT NOT NULL CHECK (progress_rating BETWEEN 1 AND 5),
                progress_notes TEXT,
                data_collected TEXT,
                staff_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (goal_id) REFERENCES autism_treatment_goals(goal_id) ON DELETE CASCADE,
                INDEX idx_goal_id (goal_id),
                INDEX idx_session_date (session_date),
                INDEX idx_staff_id (staff_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        return true;
    } catch (Exception $e) {
        error_log("Error creating treatment plan tables: " . $e->getMessage());
        return false;
    }
}

// Route handling
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$clientId = $_GET['client_id'] ?? null;

try {
    // Ensure tables exist
    createTreatmentPlanTables();
    
    switch ("$method:$endpoint") {
        
        case 'GET:client_goals':
            handleGetClientGoals($clientId);
            break;
            
        case 'POST:session_progress':
            handleRecordSessionProgress();
            break;
            
        case 'GET:treatment_plans':
            handleGetTreatmentPlans($clientId);
            break;
            
        case 'POST:create_demo_plans':
            handleCreateDemoPlans();
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
    
} catch (Exception $e) {
    error_log("Treatment Plan API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Get client's active treatment goals for session note auto-population
 */
function handleGetClientGoals($clientId) {
    if (!$clientId) {
        http_response_code(400);
        echo json_encode(['error' => 'Client ID required']);
        return;
    }
    
    try {
        $pdo = getDbConnection();
        
        $sql = "
            SELECT 
                tp.plan_id,
                tp.plan_name,
                tg.goal_id,
                tg.goal_category,
                tg.goal_title,
                tg.goal_description,
                tg.target_criteria,
                tg.current_progress,
                tg.priority,
                tg.target_date,
                ce.ma_number as client_ma_number,
                p.abbreviation as program_code
            FROM autism_treatment_plans tp
            JOIN autism_treatment_goals tg ON tp.plan_id = tg.plan_id
            LEFT JOIN autism_client_enrollments ce ON tp.client_id = ce.client_id
            LEFT JOIN autism_programs p ON ce.program_id = p.program_id
            WHERE tp.client_id = ? 
                AND tp.status = 'active' 
                AND tg.status = 'active'
                AND ce.is_active = TRUE
            ORDER BY tg.priority DESC, tg.goal_category, tg.goal_title
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$clientId]);
        $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($goals)) {
            // If no goals found, return demo data structure
            echo json_encode([
                'client_id' => $clientId,
                'goals' => [],
                'message' => 'No active treatment goals found for this client',
                'demo_mode' => true
            ]);
            return;
        }
        
        // Group goals by category
        $groupedGoals = [];
        $clientInfo = [];
        
        foreach ($goals as $goal) {
            if (empty($clientInfo)) {
                $clientInfo = [
                    'client_ma_number' => $goal['client_ma_number'],
                    'program_code' => $goal['program_code'],
                    'plan_name' => $goal['plan_name']
                ];
            }
            
            $category = $goal['goal_category'];
            if (!isset($groupedGoals[$category])) {
                $groupedGoals[$category] = [];
            }
            
            $groupedGoals[$category][] = [
                'goal_id' => $goal['goal_id'],
                'title' => $goal['goal_title'],
                'description' => $goal['goal_description'],
                'target_criteria' => $goal['target_criteria'],
                'current_progress' => $goal['current_progress'],
                'priority' => $goal['priority'],
                'target_date' => $goal['target_date']
            ];
        }
        
        echo json_encode([
            'client_id' => $clientId,
            'client_info' => $clientInfo,
            'goals_by_category' => $groupedGoals,
            'total_goals' => count($goals)
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Record session progress for treatment goals
 */
function handleRecordSessionProgress() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required = ['goal_id', 'session_date', 'progress_rating', 'staff_id'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    try {
        $pdo = getDbConnection();
        $pdo->beginTransaction();
        
        // Record progress entry
        $progressSql = "
            INSERT INTO autism_goal_progress 
            (goal_id, session_date, progress_rating, progress_notes, data_collected, staff_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        $stmt = $pdo->prepare($progressSql);
        $stmt->execute([
            $input['goal_id'],
            $input['session_date'],
            $input['progress_rating'],
            $input['progress_notes'] ?? null,
            $input['data_collected'] ?? null,
            $input['staff_id']
        ]);
        
        // Update goal's current progress (average of recent ratings)
        $updateSql = "
            UPDATE autism_treatment_goals 
            SET current_progress = (
                SELECT AVG(progress_rating * 20) 
                FROM autism_goal_progress 
                WHERE goal_id = ? 
                    AND session_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ),
            updated_by = ?,
            updated_at = NOW()
            WHERE goal_id = ?
        ";
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute([
            $input['goal_id'],
            $input['staff_id'],
            $input['goal_id']
        ]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Goal progress recorded successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Get treatment plans for a client
 */
function handleGetTreatmentPlans($clientId) {
    if (!$clientId) {
        http_response_code(400);
        echo json_encode(['error' => 'Client ID required']);
        return;
    }
    
    try {
        $pdo = getDbConnection();
        
        $sql = "
            SELECT 
                tp.plan_id,
                tp.plan_name,
                tp.created_date,
                tp.start_date,
                tp.end_date,
                tp.status,
                COUNT(tg.goal_id) as total_goals,
                COUNT(CASE WHEN tg.status = 'achieved' THEN 1 END) as achieved_goals,
                AVG(tg.current_progress) as avg_progress
            FROM autism_treatment_plans tp
            LEFT JOIN autism_treatment_goals tg ON tp.plan_id = tg.plan_id
            WHERE tp.client_id = ?
            GROUP BY tp.plan_id
            ORDER BY tp.created_date DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$clientId]);
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'client_id' => $clientId,
            'treatment_plans' => $plans
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Create demo treatment plans and goals for testing
 */
function handleCreateDemoPlans() {
    try {
        $pdo = getDbConnection();
        
        // Demo treatment plans and goals
        $demoPlans = [
            [
                'client_id' => 1,
                'client_name' => 'Emma Rodriguez',
                'plan_name' => 'Communication & Social Skills Development',
                'goals' => [
                    [
                        'category' => 'Communication Skills',
                        'title' => 'Verbal Request Skills',
                        'description' => 'Increase verbal requests using 3-4 word phrases',
                        'target_criteria' => 'Will request preferred items using 3-4 word phrases with 80% accuracy across 3 consecutive sessions',
                        'current_progress' => 75,
                        'priority' => 'high'
                    ],
                    [
                        'category' => 'Social Interaction',
                        'title' => 'Peer Interaction',
                        'description' => 'Initiate play activities with peers for 10+ minutes',
                        'target_criteria' => 'Will initiate and maintain play interactions with peers for minimum 10 minutes with minimal adult support',
                        'current_progress' => 60,
                        'priority' => 'high'
                    ],
                    [
                        'category' => 'Behavioral Regulation',
                        'title' => 'Emotional Self-Regulation',
                        'description' => 'Use coping strategies when experiencing frustration',
                        'target_criteria' => 'Will implement learned coping strategies (deep breathing, counting) when frustrated in 8/10 opportunities',
                        'current_progress' => 45,
                        'priority' => 'medium'
                    ]
                ]
            ],
            [
                'client_id' => 2,
                'client_name' => 'Michael Chen',
                'plan_name' => 'Independent Living Skills',
                'goals' => [
                    [
                        'category' => 'Daily Living Skills',
                        'title' => 'Morning Routine Independence',
                        'description' => 'Complete morning routine independently',
                        'target_criteria' => 'Will complete 8-step morning routine (wake up, bathroom, brush teeth, get dressed, breakfast, pack bag, shoes, ready) with 90% independence',
                        'current_progress' => 85,
                        'priority' => 'high'
                    ],
                    [
                        'category' => 'Behavioral Regulation',
                        'title' => 'Frustration Management',
                        'description' => 'Use coping strategies when frustrated',
                        'target_criteria' => 'Will use taught coping strategies instead of aggressive behaviors when frustrated in 9/10 opportunities',
                        'current_progress' => 70,
                        'priority' => 'high'
                    ],
                    [
                        'category' => 'Social Skills',
                        'title' => 'Community Integration',
                        'description' => 'Navigate community settings appropriately',
                        'target_criteria' => 'Will follow safety rules and social expectations in community settings (store, library, restaurant) with minimal prompting',
                        'current_progress' => 55,
                        'priority' => 'medium'
                    ]
                ]
            ]
        ];
        
        $created = [];
        
        foreach ($demoPlans as $plan) {
            // Create treatment plan
            $planSql = "
                INSERT INTO autism_treatment_plans 
                (client_id, plan_name, created_date, start_date, status, created_by) 
                VALUES (?, ?, CURDATE(), CURDATE(), 'active', 1)
            ";
            $stmt = $pdo->prepare($planSql);
            $stmt->execute([
                $plan['client_id'],
                $plan['plan_name']
            ]);
            
            $planId = $pdo->lastInsertId();
            
            // Create treatment goals
            foreach ($plan['goals'] as $goal) {
                $goalSql = "
                    INSERT INTO autism_treatment_goals 
                    (plan_id, goal_category, goal_title, goal_description, target_criteria, 
                     current_progress, priority, status, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'active', 1)
                ";
                $stmt = $pdo->prepare($goalSql);
                $stmt->execute([
                    $planId,
                    $goal['category'],
                    $goal['title'],
                    $goal['description'],
                    $goal['target_criteria'],
                    $goal['current_progress'],
                    $goal['priority']
                ]);
            }
            
            $created[] = $plan['client_name'] . ' - ' . $plan['plan_name'];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Demo treatment plans created successfully',
            'plans_created' => $created,
            'note' => 'Treatment goals are now available for auto-population in session notes'
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

?> 