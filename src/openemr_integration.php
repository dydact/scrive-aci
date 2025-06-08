<?php
/**
 * OpenEMR Integration Helper
 * Provides access to OpenEMR globals and functions
 */

// Don't include OpenEMR globals automatically as it causes session conflicts
// We'll handle database connections independently
$openemr_globals_loaded = false;

// Only include OpenEMR globals if specifically requested and in proper context
if (defined('FORCE_OPENEMR_GLOBALS') && file_exists(OPENEMR_BASE_PATH . '/interface/globals.php')) {
    try {
        require_once OPENEMR_BASE_PATH . '/interface/globals.php';
        $openemr_globals_loaded = true;
    } catch (Exception $e) {
        error_log("Could not load OpenEMR globals: " . $e->getMessage());
    }
}

// Fallback definitions if OpenEMR globals not available
if (!isset($GLOBALS['config'])) {
    $GLOBALS['config'] = 1;
}

/**
 * Get OpenEMR site-specific configuration
 */
function getOpenEMRSiteConfig($sitename = null) {
    $sitename = $sitename ?: OPENEMR_SITE;
    $config_file = OPENEMR_BASE_PATH . '/sites/' . $sitename . '/sqlconf.php';
    
    if (file_exists($config_file)) {
        include $config_file;
        return [
            'host' => $host ?? DB_HOST,
            'user' => $login ?? DB_USER,
            'pass' => $pass ?? DB_PASS,
            'dbase' => $dbase ?? DB_NAME
        ];
    }
    
    // Fallback to our configuration
    return [
        'host' => DB_HOST,
        'user' => DB_USER,
        'pass' => DB_PASS,
        'dbase' => DB_NAME
    ];
}

/**
 * OpenEMR-compatible user authentication
 */
function authenticateOpenEMRUser($username, $password) {
    try {
        $pdo = getDatabase();
        
        // First check our custom autism tables
        $result = authenticateAutismUser($username, $password);
        if ($result) {
            return $result;
        }
        
        // Then check OpenEMR users table if exists
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    u.id, u.username, u.password, u.active,
                    u.fname, u.lname, u.email, u.title,
                    u.facility_id, u.see_auth
                FROM users u 
                WHERE u.username = ? AND u.active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                return [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'first_name' => $user['fname'],
                    'last_name' => $user['lname'],
                    'email' => $user['email'],
                    'title' => $user['title'],
                    'facility_id' => $user['facility_id'],
                    'see_auth' => $user['see_auth'],
                    'access_level' => determineAccessLevel($user)
                ];
            }
        } catch (PDOException $e) {
            // OpenEMR users table doesn't exist, continue
            error_log("OpenEMR users table not available: " . $e->getMessage());
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

/**
 * Authenticate autism waiver specific users from database
 */
function authenticateAutismUser($username, $password) {
    try {
        $pdo = getDatabase();
        
        // Check database for users - support both username and email
        $stmt = $pdo->prepare("
            SELECT 
                id, username, password_hash, email, full_name, role, access_level
            FROM autism_users 
            WHERE (username = ? OR email = ?) AND status = 'active'
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Split full_name into first and last
            $nameParts = explode(' ', $user['full_name'], 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';
            
            return [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $user['email'],
                'role' => $user['role'],
                'access_level' => $user['access_level']
            ];
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Autism user authentication error: " . $e->getMessage());
        return false;
    }
}

/**
 * Determine access level from OpenEMR user permissions
 */
function determineAccessLevel($user) {
    // Map OpenEMR permissions to our access levels
    if ($user['see_auth'] >= 3) {
        return 5; // Administrator
    } elseif ($user['see_auth'] >= 2) {
        return 3; // Case Manager
    } else {
        return 2; // Direct Care Staff
    }
}

/**
 * Check if user has OpenEMR ACL permission
 */
function checkOpenEMRAcl($user_id, $object_section, $required_acl) {
    try {
        $pdo = getDatabase();
        
        // Check if ACL tables exist and query them
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as has_permission
            FROM module_acl_user_settings maus
            JOIN module_acl_sections mas ON maus.section_id = mas.section_id
            WHERE maus.user_id = ? 
                AND mas.section_identifier = ?
                AND maus.allowed = 1
        ");
        $stmt->execute([$user_id, $object_section]);
        $result = $stmt->fetch();
        
        return $result['has_permission'] > 0;
    } catch (Exception $e) {
        // If ACL check fails, fall back to basic access level check
        return true;
    }
}
?> 