<?php
/**
 * Central Routing Configuration
 * Defines all application routes and their corresponding files
 */

$routes = [
    // Public routes
    'about' => 'about.php',
    'services' => 'services.php',
    'contact' => 'contact.php',
    'apply' => 'application_form.php',
    
    // Authentication
    'login' => 'src/login.php',
    'logout' => 'autism_waiver_app/logout.php',
    'dashboard' => 'src/dashboard.php',
    
    // Admin Panel
    'admin' => 'src/admin_dashboard.php',
    'admin/users' => 'src/admin_users.php',
    'admin/employees' => 'src/admin_employees.php',
    'admin/organization' => 'src/admin_organization.php',
    
    // Client Management
    'clients' => 'autism_waiver_app/clients.php',
    'clients/add' => 'autism_waiver_app/add_client.php',
    'clients/secure' => 'autism_waiver_app/secure_clients.php',
    'client/(\d+)' => 'autism_waiver_app/client_detail.php?id=$1',
    
    // Billing & Financial
    'billing' => 'autism_waiver_app/billing/billing_dashboard.php',
    'billing/dashboard' => 'autism_waiver_app/billing/billing_dashboard.php',
    'billing/payroll' => 'autism_waiver_app/payroll_report.php',
    'billing/claims' => 'autism_waiver_app/billing/claim_management.php',
    'billing/payments' => 'autism_waiver_app/billing/payment_posting.php',
    'billing/denials' => 'autism_waiver_app/billing/denial_management.php',
    'billing/edi' => 'autism_waiver_app/edi_processing.php',
    'billing/edi/process' => 'autism_waiver_app/edi/process_remittance.php',
    
    // Staff Portal (formerly Mobile Portal)
    'staff' => 'autism_waiver_app/staff_portal_router.php',
    'staff/dashboard' => 'autism_waiver_app/staff_dashboard.php',
    'staff/clock' => 'autism_waiver_app/api_time_clock.php',
    'staff/notes' => 'autism_waiver_app/new_session.php',
    'staff/notes/edit' => 'autism_waiver_app/edit_session.php',
    'staff/notes/iiss' => 'autism_waiver_app/iiss_session_note.php',
    'staff/schedule' => 'autism_waiver_app/employee_schedule.php',
    'staff/clients' => 'autism_waiver_app/employee_portal.php',
    'staff/hours' => 'autism_waiver_app/my_hours.php',
    
    // Case Manager Portal
    'case-manager' => 'autism_waiver_app/case_manager_portal.php',
    'case-manager/plans' => 'autism_waiver_app/treatment_plan_manager.php',
    'case-manager/approvals' => 'autism_waiver_app/note_approvals.php',
    
    // Supervisor Portal
    'supervisor' => 'autism_waiver_app/supervisor_portal.php',
    'supervisor/approvals' => 'autism_waiver_app/supervisor_approvals.php',
    'supervisor/reports' => 'autism_waiver_app/supervisor_reports.php',
    
    // Scheduling
    'schedule' => 'autism_waiver_app/schedule_manager.php',
    'calendar' => 'autism_waiver_app/calendar.php',
    
    // Reports
    'reports' => 'autism_waiver_app/reports.php',
    'reports/payroll' => 'autism_waiver_app/payroll_report.php',
    'reports/billing' => 'autism_waiver_app/billing_reports.php',
    'reports/clinical' => 'autism_waiver_app/clinical_reports.php',
    
    // Forms & Documentation
    'forms' => 'autism_waiver_app/application_form.php',
    'forms/session' => 'autism_waiver_app/new_session.php',
    'forms/treatment' => 'autism_waiver_app/treatment_plan_manager.php',
    
    // API Endpoints
    'api/time-clock' => 'autism_waiver_app/api_time_clock.php',
    'api/clients' => 'autism_waiver_app/api.php?endpoint=clients',
    'api/sessions' => 'autism_waiver_app/api.php?endpoint=sessions',
    'api/billing' => 'autism_waiver_app/api.php?endpoint=billing',
    
    // Help & Support
    'help' => 'help_center.php',
    'help/guide' => 'autism_waiver_app/help_guide.php',
    'training' => 'autism_waiver_app/training.php',
    
    // Role Switcher
    'role-switcher' => 'autism_waiver_app/admin_role_switcher.php',
];

// Generate .htaccess rules from routes
function generateHtaccessRules($routes) {
    $rules = [];
    
    // Header
    $rules[] = "RewriteEngine On";
    $rules[] = "SetEnvIf Authorization \"(.*)\" HTTP_AUTHORIZATION=$1";
    $rules[] = "";
    $rules[] = "# Remove .php extension from URLs";
    $rules[] = "RewriteCond %{THE_REQUEST} ^[A-Z]+\s([^\s]+)\.php[\s?] [NC]";
    $rules[] = "RewriteRule ^ /%1 [R=301,L]";
    $rules[] = "";
    $rules[] = "# Dynamic routes from routes.php";
    
    foreach ($routes as $pattern => $target) {
        // Convert route pattern to regex
        $regex = '^' . str_replace('/', '\/', $pattern) . '\/?$';
        
        // Handle dynamic segments like (\d+)
        if (strpos($pattern, '(') !== false) {
            $rules[] = "RewriteRule $regex $target [L]";
        } else {
            $rules[] = "RewriteRule $regex $target [L]";
        }
    }
    
    // Fallback rules
    $rules[] = "";
    $rules[] = "# Fallback - map /page to page.php if exists";
    $rules[] = "RewriteCond %{REQUEST_FILENAME} !-d";
    $rules[] = "RewriteCond %{REQUEST_FILENAME} !-f";
    $rules[] = "RewriteCond %{REQUEST_FILENAME}.php -f";
    $rules[] = "RewriteRule ^(.*)$ $1.php [L]";
    $rules[] = "";
    $rules[] = "# OpenEMR compatibility";
    $rules[] = "RewriteCond %{REQUEST_FILENAME} !-f";
    $rules[] = "RewriteCond %{REQUEST_FILENAME} !-d";
    $rules[] = "RewriteCond %{REQUEST_FILENAME} !-l";
    $rules[] = "RewriteRule (.*) authorize.php?_REWRITE_COMMAND=$1 [QSA,L]";
    
    return implode("\n", $rules);
}

// Function to get route URL from file path
function getRouteUrl($filePath) {
    global $routes;
    
    // Remove query strings for comparison
    $cleanPath = explode('?', $filePath)[0];
    
    // Search for matching route
    foreach ($routes as $route => $target) {
        $cleanTarget = explode('?', $target)[0];
        if ($cleanTarget === $cleanPath) {
            return '/' . $route;
        }
    }
    
    // Fallback to removing .php extension
    return '/' . str_replace('.php', '', $filePath);
}

// Export routes for use in other files
return $routes;