<?php

/**
 * Secure Autism Waiver API with Role-Based Access Control
 * 
 * @package   Scrive
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
 * @license   MIT License
 */

// Database connection with error handling
function getSecureDbConnection() {
    try {
        $dsn = "mysql:host=localhost;dbname=openemr;charset=utf8mb4";
        $pdo = new PDO($dsn, 'openemr', 'openemr');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
}

// Get user permissions from role-based system
function getUserPermissions($userId) {
    try {
        $pdo = getSecureDbConnection();
        $stmt = $pdo->prepare("
            SELECT 
                ur.user_id,
                u.username,
                sr.role_name,
                sr.role_level,
                sr.can_view_org_ma_numbers,
                sr.can_view_client_ma_numbers,
                sr.can_edit_client_data,
                sr.can_schedule_sessions,
                sr.can_manage_staff,
                sr.can_view_billing,
                sr.can_manage_authorizations
            FROM autism_user_roles ur
            JOIN autism_staff_roles sr ON ur.role_id = sr.role_id
            JOIN users u ON ur.user_id = u.id
            WHERE ur.user_id = ? AND ur.is_active = TRUE
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching user permissions: " . $e->getMessage());
        return false;
    }
}

// Security audit logging
function logSecurityEvent($userId, $action, $resource, $result) {
    try {
        $pdo = getSecureDbConnection();
        
        // Create security log table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS autism_security_log (
                log_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                resource VARCHAR(100) NOT NULL,
                result VARCHAR(255) NOT NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $stmt = $pdo->prepare("
            INSERT INTO autism_security_log 
            (user_id, action, resource, result, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            $resource,
            $result,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Security logging failed: " . $e->getMessage());
    }
}

// Check if user can perform action
function checkPermission($userId, $permission) {
    $permissions = getUserPermissions($userId);
    if (!$permissions) {
        logSecurityEvent($userId, 'permission_check', $permission, 'DENIED - No role found');
        return false;
    }
    
    $hasPermission = $permissions[$permission] ?? false;
    $result = $hasPermission ? 'GRANTED' : 'DENIED';
    logSecurityEvent($userId, 'permission_check', $permission, $result);
    
    return $hasPermission;
}

// Initialize CORS and headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get current user ID (in production, this would come from session/JWT)
$currentUserId = 1; // Default for demo - replace with actual auth

// Route handling
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['endpoint'] ?? '';

try {
    switch ("$method:$path") {
        
        case 'GET:clients':
            handleGetClients($currentUserId);
            break;
            
        case 'POST:clients':
            handleCreateClient($currentUserId);
            break;
            
        case 'PUT:clients':
            handleUpdateClient($currentUserId);
            break;
            
        case 'GET:programs':
            handleGetPrograms($currentUserId);
            break;
            
        case 'GET:org_ma_numbers':
            handleGetOrgMANumbers($currentUserId);
            break;
            
        case 'GET:user_permissions':
            handleGetUserPermissions($currentUserId);
            break;
            
        case 'GET:security_log':
            handleGetSecurityLog($currentUserId);
            break;
            
        case 'POST:demo_data':
            handleCreateDemoData($currentUserId);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
    }
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Get clients with role-based filtering
 */
function handleGetClients($userId) {
    // Check if user can view client MA numbers
    if (!checkPermission($userId, 'can_view_client_ma_numbers')) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied: Cannot view client MA numbers']);
        return;
    }
    
    try {
        $pdo = getSecureDbConnection();
        
        // Base query for client data
        $sql = "
            SELECT 
                c.client_id,
                c.first_name,
                c.last_name,
                c.date_of_birth,
                c.county,
                c.parent_guardian,
                c.emergency_contact,
                c.emergency_phone,
                c.address,
                c.school,
                ce.ma_number as individual_ma_number,
                p.name as program_name,
                p.abbreviation as program_code,
                ce.status,
                ce.enrollment_date
            FROM autism_clients c
            LEFT JOIN autism_client_enrollments ce ON c.client_id = ce.client_id
            LEFT JOIN autism_programs p ON ce.program_id = p.program_id
            WHERE ce.is_active = TRUE
            ORDER BY c.last_name, c.first_name
        ";
        
        $stmt = $pdo->query($sql);
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format response - only include individual client MA numbers
        $response = [];
        foreach ($clients as $client) {
            $clientData = [
                'id' => $client['client_id'],
                'first_name' => $client['first_name'],
                'last_name' => $client['last_name'],
                'date_of_birth' => $client['date_of_birth'],
                'county' => $client['county'],
                'parent_guardian' => $client['parent_guardian'],
                'emergency_contact' => $client['emergency_contact'],
                'emergency_phone' => $client['emergency_phone'],
                'address' => $client['address'],
                'school' => $client['school'],
                'individual_ma_number' => $client['individual_ma_number'], // Client's personal MA
                'program' => [
                    'name' => $client['program_name'],
                    'code' => $client['program_code']
                ],
                'status' => $client['status'],
                'enrollment_date' => $client['enrollment_date']
            ];
            
            // NOTE: Organizational MA numbers are NOT included here
            // They are handled separately in handleGetOrgMANumbers()
            
            $response[] = $clientData;
        }
        
        logSecurityEvent($userId, 'view_clients', 'client_ma_numbers', 'SUCCESS - ' . count($response) . ' records');
        echo json_encode($response);
        
    } catch (Exception $e) {
        logSecurityEvent($userId, 'view_clients', 'client_ma_numbers', 'ERROR - ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Get organizational MA numbers (ADMIN ONLY)
 */
function handleGetOrgMANumbers($userId) {
    // CRITICAL: Only administrators can access organizational MA numbers
    if (!checkPermission($userId, 'can_view_org_ma_numbers')) {
        http_response_code(403);
        logSecurityEvent($userId, 'view_org_ma', 'billing_numbers', 'DENIED - Insufficient privileges');
        echo json_encode([
            'error' => 'Access denied: Organizational MA numbers are restricted to administrators only',
            'required_role' => 'Administrator (Level 5)',
            'your_access' => 'Insufficient privileges',
            'security_note' => 'These are American Caregivers billing identification numbers - highly confidential'
        ]);
        return;
    }
    
    try {
        $pdo = getSecureDbConnection();
        
        $sql = "
            SELECT 
                oma.org_ma_id,
                oma.ma_number as organizational_ma_number,
                oma.description,
                oma.is_active,
                oma.effective_date,
                oma.expiration_date,
                p.name as program_name,
                p.abbreviation as program_code
            FROM autism_org_ma_numbers oma
            JOIN autism_programs p ON oma.program_id = p.program_id
            WHERE oma.is_active = TRUE
            ORDER BY p.abbreviation
        ";
        
        $stmt = $pdo->query($sql);
        $orgNumbers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logSecurityEvent($userId, 'view_org_ma', 'billing_numbers', 'SUCCESS - ADMIN ACCESS');
        echo json_encode([
            'organizational_ma_numbers' => $orgNumbers,
            'warning' => 'These are American Caregivers billing identification numbers - highly confidential',
            'access_level' => 'Administrator Only'
        ]);
        
    } catch (Exception $e) {
        logSecurityEvent($userId, 'view_org_ma', 'billing_numbers', 'ERROR - ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Get user permissions for current user
 */
function handleGetUserPermissions($userId) {
    $permissions = getUserPermissions($userId);
    
    if (!$permissions) {
        echo json_encode([
            'user_permissions' => null,
            'message' => 'No role assigned - using default permissions',
            'default_access' => [
                'can_view_org_ma_numbers' => false,
                'can_view_client_ma_numbers' => false,
                'can_edit_client_data' => false,
                'can_schedule_sessions' => false,
                'can_manage_staff' => false,
                'can_view_billing' => false,
                'can_manage_authorizations' => false
            ]
        ]);
        return;
    }
    
    echo json_encode([
        'user_permissions' => $permissions,
        'security_note' => 'Permissions determine access to different MA number types'
    ]);
}

/**
 * Create demo data for testing
 */
function handleCreateDemoData($userId) {
    try {
        $pdo = getSecureDbConnection();
        
        // Create demo clients with individual MA numbers
        $demoClients = [
            [
                'first_name' => 'Emma',
                'last_name' => 'Rodriguez',
                'date_of_birth' => '2015-08-12',
                'county' => 'Montgomery',
                'ma_number' => 'MA123456789', // Individual client MA
                'program' => 'AW',
                'parent_guardian' => 'Maria Rodriguez'
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Johnson', 
                'date_of_birth' => '2012-03-20',
                'county' => 'Baltimore County',
                'ma_number' => 'MA987654321', // Individual client MA
                'program' => 'DDA',
                'parent_guardian' => 'Sarah Johnson'
            ],
            [
                'first_name' => 'Aiden',
                'last_name' => 'Chen',
                'date_of_birth' => '2018-11-05',
                'county' => 'Howard',
                'ma_number' => 'MA555888999', // Individual client MA
                'program' => 'CFC',
                'parent_guardian' => 'Lisa Chen'
            ]
        ];
        
        $created = [];
        foreach ($demoClients as $client) {
            // Insert client
            $clientSql = "
                INSERT INTO autism_clients 
                (first_name, last_name, date_of_birth, county, parent_guardian, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $stmt = $pdo->prepare($clientSql);
            $stmt->execute([
                $client['first_name'],
                $client['last_name'],
                $client['date_of_birth'],
                $client['county'],
                $client['parent_guardian'],
                $userId
            ]);
            
            $clientId = $pdo->lastInsertId();
            
            // Get program ID
            $programStmt = $pdo->prepare("SELECT program_id FROM autism_programs WHERE abbreviation = ?");
            $programStmt->execute([$client['program']]);
            $programId = $programStmt->fetchColumn();
            
            if ($programId) {
                // Insert enrollment with individual MA number
                $enrollSql = "
                    INSERT INTO autism_client_enrollments 
                    (client_id, program_id, ma_number, status, enrollment_date, created_by) 
                    VALUES (?, ?, ?, 'active', CURDATE(), ?)
                ";
                $stmt = $pdo->prepare($enrollSql);
                $stmt->execute([
                    $clientId,
                    $programId,
                    $client['ma_number'], // Individual client MA number
                    $userId
                ]);
            }
            
            $created[] = $client['first_name'] . ' ' . $client['last_name'];
        }
        
        logSecurityEvent($userId, 'create_demo_data', 'clients', 'SUCCESS - ' . count($created) . ' clients');
        echo json_encode([
            'success' => true,
            'message' => 'Demo data created successfully',
            'clients_created' => $created,
            'note' => 'Individual client MA numbers assigned - organizational billing numbers remain secure'
        ]);
        
    } catch (Exception $e) {
        logSecurityEvent($userId, 'create_demo_data', 'clients', 'ERROR - ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Get programs (no special permissions needed)
 */
function handleGetPrograms($userId) {
    try {
        $pdo = getSecureDbConnection();
        
        $sql = "
            SELECT 
                program_id,
                name,
                abbreviation,
                description,
                is_active
            FROM autism_programs
            WHERE is_active = TRUE
            ORDER BY abbreviation
        ";
        
        $stmt = $pdo->query($sql);
        $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($programs);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Create new client (role-based access)
 */
function handleCreateClient($userId) {
    if (!checkPermission($userId, 'can_edit_client_data')) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied: Cannot create client records']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['first_name', 'last_name', 'date_of_birth', 'ma_number', 'program_id', 'county'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }
    
    try {
        $pdo = getSecureDbConnection();
        $pdo->beginTransaction();
        
        // Create client record
        $clientSql = "
            INSERT INTO autism_clients 
            (first_name, last_name, date_of_birth, county, parent_guardian, 
             emergency_contact, emergency_phone, address, school, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $pdo->prepare($clientSql);
        $stmt->execute([
            $input['first_name'],
            $input['last_name'],
            $input['date_of_birth'],
            $input['county'],
            $input['parent_guardian'] ?? null,
            $input['emergency_contact'] ?? null,
            $input['emergency_phone'] ?? null,
            $input['address'] ?? null,
            $input['school'] ?? null,
            $userId
        ]);
        
        $clientId = $pdo->lastInsertId();
        
        // Create enrollment record with INDIVIDUAL client MA number
        $enrollmentSql = "
            INSERT INTO autism_client_enrollments 
            (client_id, program_id, ma_number, status, enrollment_date, created_by) 
            VALUES (?, ?, ?, 'active', CURDATE(), ?)
        ";
        $stmt = $pdo->prepare($enrollmentSql);
        $stmt->execute([
            $clientId,
            $input['program_id'],
            $input['ma_number'], // This is the INDIVIDUAL client's MA number
            $userId
        ]);
        
        $pdo->commit();
        
        logSecurityEvent($userId, 'create_client', "client_id:$clientId", 'SUCCESS');
        echo json_encode([
            'success' => true,
            'client_id' => $clientId,
            'message' => 'Client created successfully with individual MA number'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        logSecurityEvent($userId, 'create_client', 'unknown', 'ERROR - ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Update client (role-based access)
 */
function handleUpdateClient($userId) {
    if (!checkPermission($userId, 'can_edit_client_data')) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied: Cannot edit client records']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $clientId = $input['client_id'] ?? null;
    
    if (!$clientId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing client_id']);
        return;
    }
    
    try {
        $pdo = getSecureDbConnection();
        $pdo->beginTransaction();
        
        // Update client record
        $clientSql = "
            UPDATE autism_clients 
            SET first_name = ?, last_name = ?, date_of_birth = ?, county = ?,
                parent_guardian = ?, emergency_contact = ?, emergency_phone = ?,
                address = ?, school = ?, updated_by = ?, updated_at = NOW()
            WHERE client_id = ?
        ";
        $stmt = $pdo->prepare($clientSql);
        $stmt->execute([
            $input['first_name'],
            $input['last_name'],
            $input['date_of_birth'],
            $input['county'],
            $input['parent_guardian'] ?? null,
            $input['emergency_contact'] ?? null,
            $input['emergency_phone'] ?? null,
            $input['address'] ?? null,
            $input['school'] ?? null,
            $userId,
            $clientId
        ]);
        
        // Update individual MA number if provided
        if (isset($input['ma_number'])) {
            $enrollmentSql = "
                UPDATE autism_client_enrollments 
                SET ma_number = ?, updated_by = ?, updated_at = NOW()
                WHERE client_id = ?
            ";
            $stmt = $pdo->prepare($enrollmentSql);
            $stmt->execute([
                $input['ma_number'], // Individual client MA number
                $userId,
                $clientId
            ]);
        }
        
        $pdo->commit();
        
        logSecurityEvent($userId, 'update_client', "client_id:$clientId", 'SUCCESS');
        echo json_encode([
            'success' => true,
            'message' => 'Client updated successfully'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        logSecurityEvent($userId, 'update_client', "client_id:$clientId", 'ERROR - ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Get security audit log (ADMIN ONLY)
 */
function handleGetSecurityLog($userId) {
    if (!checkPermission($userId, 'can_manage_staff')) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied: Security log requires management privileges']);
        return;
    }
    
    try {
        $pdo = getSecureDbConnection();
        
        $sql = "
            SELECT 
                sl.log_id,
                sl.user_id,
                u.username,
                sl.action,
                sl.resource,
                sl.result,
                sl.ip_address,
                sl.timestamp
            FROM autism_security_log sl
            LEFT JOIN users u ON sl.user_id = u.id
            ORDER BY sl.timestamp DESC
            LIMIT 100
        ";
        
        $stmt = $pdo->query($sql);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'security_log' => $logs,
            'note' => 'Recent security events and access attempts'
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

?> 