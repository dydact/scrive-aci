# Comprehensive Fix Plan for American Caregivers Inc

## Phase 1: URL Rewriting Solution

### Dynamic .htaccess Generator
Instead of manually entering each route, create a dynamic solution:

```php
// routes.php - Central routing configuration
$routes = [
    // Public routes
    'about' => 'about.php',
    'services' => 'services.php',
    'contact' => 'contact.php',
    'apply' => 'application_form.php',
    
    // Auth routes
    'login' => 'src/login.php',
    'logout' => 'autism_waiver_app/logout.php',
    'dashboard' => 'src/dashboard.php',
    
    // Admin routes
    'admin' => 'src/admin_dashboard.php',
    'admin/users' => 'src/admin_users.php',
    'admin/employees' => 'src/admin_employees.php',
    'admin/organization' => 'src/admin_organization.php',
    
    // Client Management
    'clients' => 'autism_waiver_app/clients.php',
    'clients/add' => 'autism_waiver_app/add_client.php',
    'clients/secure' => 'autism_waiver_app/secure_clients.php',
    'client/{id}' => 'autism_waiver_app/client_detail.php',
    
    // Billing
    'billing' => 'autism_waiver_app/billing_integration.php',
    'billing/dashboard' => 'autism_waiver_app/billing_dashboard.php',
    'billing/payroll' => 'autism_waiver_app/payroll_report.php',
    
    // Staff Portal (Mobile)
    'staff' => 'autism_waiver_app/mobile_employee_portal.php',
    'staff/clock' => 'autism_waiver_app/api_time_clock.php',
    'staff/notes' => 'autism_waiver_app/new_session.php',
    'staff/schedule' => 'autism_waiver_app/employee_schedule.php',
    
    // Case Manager Portal
    'case-manager' => 'autism_waiver_app/case_manager_portal.php',
    'case-manager/plans' => 'autism_waiver_app/treatment_plan_manager.php',
    
    // Schedule
    'schedule' => 'autism_waiver_app/schedule_manager.php',
    'calendar' => 'autism_waiver_app/calendar.php',
    
    // Reports
    'reports' => 'autism_waiver_app/reports.php',
    
    // Help
    'help' => 'help_center.php',
];
```

## Phase 2: Fix Empty Pages

### 1. Clients Page (autism_waiver_app/clients.php)
- Connect to autism_clients table
- Display client list with search/filter
- Add/Edit/View functionality
- Role-based access control

### 2. Billing Dashboard (autism_waiver_app/billing_dashboard.php)
- Connect to autism_billing_claims, autism_billing_entries
- Show pending claims, revenue summary
- Generate Medicaid claims
- EDI 837/835 processing

### 3. Secure Clients (autism_waiver_app/secure_clients.php)
- Replace static HTML with PHP
- Show only clients assigned to logged-in staff
- Based on autism_staff_assignments table

## Phase 3: Staff Portal Redesign

### Mobile Employee Portal → Staff Data Entry System
```
/staff
├── /clock-in-out     (Time clock management)
├── /my-schedule      (View assigned shifts)
├── /session-notes    (Enter IISS/TI notes)
├── /my-clients       (Assigned clients only)
├── /my-hours         (Payroll summary)
└── /profile          (Update contact info)
```

### Role-Based Portal Access:
- **DSP/Technician (Level 1-2)**: Basic staff portal only
- **Case Manager (Level 3)**: Staff portal + case management
- **Supervisor (Level 4)**: All portals + approval workflows
- **Admin (Level 5)**: Full system access

## Phase 4: Database Connection Strategy

### Create Centralized Database Layer
```php
// src/database/ClientRepository.php
class ClientRepository {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function getAllClients() {
        $stmt = $this->db->query("
            SELECT c.*, 
                   COUNT(DISTINCT sa.staff_id) as assigned_staff,
                   COUNT(DISTINCT sn.id) as total_sessions
            FROM autism_clients c
            LEFT JOIN autism_staff_assignments sa ON c.id = sa.client_id
            LEFT JOIN autism_session_notes sn ON c.id = sn.client_id
            GROUP BY c.id
            ORDER BY c.last_name, c.first_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getClientsByStaff($staff_id) {
        // For secure_clients.php - only assigned clients
    }
}
```

## Phase 5: Implementation Order

1. **Fix URL Rewriting** (30 min)
   - Implement dynamic routing system
   - Update all navigation links
   
2. **Fix Database Connections** (2 hours)
   - Create repository classes
   - Update empty pages with data
   
3. **Redesign Staff Portal** (3 hours)
   - Create role-based routing
   - Implement time clock
   - Build session note entry
   
4. **Test & Deploy** (1 hour)
   - Test all user roles
   - Verify data flow
   - Document any issues

## Phase 6: Missing Resources to Create

### New Files Needed:
1. `src/database/repositories/*.php` - Data access layer
2. `autism_waiver_app/employee_schedule.php` - Staff schedule view
3. `autism_waiver_app/staff_portal_router.php` - Role-based routing
4. `autism_waiver_app/components/` - Reusable UI components
5. `src/middleware/auth_check.php` - Authentication middleware

### Database Views Needed:
```sql
-- Staff dashboard view
CREATE VIEW v_staff_dashboard AS
SELECT 
    s.id as staff_id,
    COUNT(DISTINCT sa.client_id) as assigned_clients,
    COUNT(DISTINCT sn.id) as sessions_this_week,
    SUM(TIME_TO_SEC(TIMEDIFF(tc.clock_out, tc.clock_in))/3600) as hours_this_week
FROM autism_staff_members s
LEFT JOIN autism_staff_assignments sa ON s.id = sa.staff_id
LEFT JOIN autism_session_notes sn ON s.id = sn.staff_id 
    AND sn.session_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
LEFT JOIN autism_time_clock tc ON s.id = tc.employee_id
    AND tc.clock_in >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY s.id;
```

## Execution Plan

Would you like me to proceed with this comprehensive fix? I'll start with:

1. Creating the dynamic routing system
2. Fixing the empty pages (clients, billing) 
3. Redesigning the mobile portal as a proper staff data entry system
4. Implementing role-based access throughout

This will ensure project integrity while fixing all the issues systematically.