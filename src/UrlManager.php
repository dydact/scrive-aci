<?php
/**
 * URL Manager - Handles clean URL generation and redirects
 * Eliminates hardcoded .php extensions throughout the application
 */
class UrlManager {
    private static $routes = [
        // Authentication
        'login' => '/login',
        'logout' => '/logout', 
        'dashboard' => '/dashboard',
        
        // Admin Panel
        'admin' => '/admin',
        'admin_users' => '/admin/users',
        'admin_employees' => '/admin/employees',
        'admin_organization' => '/admin/organization',
        
        // Client Management
        'clients' => '/clients',
        'add_client' => '/clients/add',
        'secure_clients' => '/clients/secure',
        
        // Billing & Financial
        'billing' => '/billing',
        'billing_dashboard' => '/billing/dashboard',
        'billing_claims' => '/billing/claims',
        'billing_edi' => '/billing/edi',
        'payroll_report' => '/billing/payroll',
        
        // Staff Portal
        'staff' => '/staff',
        'staff_dashboard' => '/staff/dashboard',
        'staff_clock' => '/staff/clock',
        'staff_notes' => '/staff/notes',
        'staff_schedule' => '/staff/schedule',
        'staff_clients' => '/staff/clients',
        'staff_hours' => '/staff/hours',
        
        // Case Manager Portal
        'case_manager' => '/case-manager',
        'case_manager_plans' => '/case-manager/plans',
        'case_manager_approvals' => '/case-manager/approvals',
        
        // Supervisor Portal
        'supervisor' => '/supervisor',
        'supervisor_approvals' => '/supervisor/approvals',
        'supervisor_reports' => '/supervisor/reports',
        
        // Scheduling
        'schedule' => '/schedule',
        'calendar' => '/calendar',
        
        // Reports
        'reports' => '/reports',
        'reports_payroll' => '/reports/payroll',
        'reports_billing' => '/reports/billing',
        'reports_clinical' => '/reports/clinical',
        
        // Forms
        'forms' => '/forms',
        'forms_session' => '/forms/session',
        'forms_treatment' => '/forms/treatment',
        
        // Public pages
        'about' => '/about',
        'services' => '/services', 
        'contact' => '/contact',
        'apply' => '/apply',
        'help' => '/help',
        
        // API
        'api_time_clock' => '/api/time-clock',
        'api_clients' => '/api/clients',
        'api_sessions' => '/api/sessions',
        'api_billing' => '/api/billing'
    ];
    
    /**
     * Get clean URL for a route key
     */
    public static function url($route, $params = []) {
        if (!isset(self::$routes[$route])) {
            error_log("Unknown route: $route");
            return '/dashboard'; // Safe fallback
        }
        
        $url = self::$routes[$route];
        
        // Handle dynamic parameters like client ID
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $url = str_replace('{' . $key . '}', $value, $url);
            }
            
            // Add query parameters
            $query = [];
            foreach ($params as $key => $value) {
                if (strpos($url, '{' . $key . '}') === false) {
                    $query[$key] = $value;
                }
            }
            if (!empty($query)) {
                $url .= '?' . http_build_query($query);
            }
        }
        
        return $url;
    }
    
    /**
     * Redirect to a clean URL
     */
    public static function redirect($route, $params = []) {
        $url = self::url($route, $params);
        header("Location: $url");
        exit;
    }
    
    /**
     * Redirect with success message
     */
    public static function redirectWithSuccess($route, $message, $params = []) {
        $params['success'] = urlencode($message);
        self::redirect($route, $params);
    }
    
    /**
     * Redirect with error message  
     */
    public static function redirectWithError($route, $message, $params = []) {
        $params['error'] = urlencode($message);
        self::redirect($route, $params);
    }
    
    /**
     * Get route based on current URI
     */
    public static function getCurrentRoute() {
        $uri = $_SERVER['REQUEST_URI'];
        $path = parse_url($uri, PHP_URL_PATH);
        
        foreach (self::$routes as $key => $route) {
            if ($path === $route || $path === $route . '/') {
                return $key;
            }
        }
        
        return null;
    }
    
    /**
     * Check if user has access to route based on access level
     */
    public static function checkAccess($route, $accessLevel) {
        $routePermissions = [
            // Public routes (no auth required)
            'about' => 0,
            'services' => 0, 
            'contact' => 0,
            'apply' => 0,
            'login' => 0,
            
            // Basic authenticated routes
            'dashboard' => 1,
            'logout' => 1,
            'staff' => 1,
            'staff_dashboard' => 1,
            'staff_clock' => 1,
            'staff_schedule' => 1,
            
            // DSP Level (2)
            'staff_notes' => 2,
            'staff_clients' => 2,
            'staff_hours' => 2,
            
            // Case Manager Level (3) 
            'clients' => 3,
            'add_client' => 3,
            'case_manager' => 3,
            'case_manager_plans' => 3,
            'case_manager_approvals' => 3,
            'billing' => 3,
            'billing_claims' => 3,
            'billing_edi' => 3,
            'reports' => 3,
            'schedule' => 3,
            'calendar' => 3,
            
            // Supervisor Level (4)
            'supervisor' => 4,
            'supervisor_approvals' => 4,
            'supervisor_reports' => 4,
            'billing_dashboard' => 4,
            'payroll_report' => 4,
            
            // Admin Level (5)
            'admin' => 5,
            'admin_users' => 5,
            'admin_employees' => 5,
            'admin_organization' => 5,
            'secure_clients' => 5
        ];
        
        $requiredLevel = $routePermissions[$route] ?? 3; // Default to Case Manager level
        return $accessLevel >= $requiredLevel;
    }
    
    /**
     * Strip .php from URL and redirect if present
     */
    public static function stripPhpExtension() {
        $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, '.php') !== false) {
            $cleanUri = preg_replace('/\.php(\?.*)?$/', '$1', $uri);
            header("Location: $cleanUri", true, 301);
            exit;
        }
    }
}