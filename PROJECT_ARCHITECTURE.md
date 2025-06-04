# Scrive ACI Project Architecture

## Directory Structure
```
/
├── .htaccess (main routing file)
├── index.php (homepage)
├── docker-compose.yml
├── Dockerfile
├── autism_waiver_app/ (main application)
├── config/ (configuration files)
├── docs/ (documentation)
├── pages/ (public pages organized by type)
│   ├── auth/ (authentication pages)
│   ├── public/ (public-facing pages)
│   ├── setup/ (setup utilities)
│   └── utilities/ (system utilities)
├── scripts/ (shell scripts)
├── src/ (core source files)
│   ├── admin_*.php (admin pages)
│   ├── config.php (database config)
│   ├── init.php (auth initialization)
│   ├── openemr_integration.php (database functions)
│   └── UrlManager.php (URL handling)
├── sql/ (database schemas)
└── public/ (assets)
```

## Main Application Pages (autism_waiver_app/)

### Authentication & Access
- `login.php` → `/login` (main login page)
- `logout.php` → `/logout` (logout handler)
- `simple_login.php` (simplified login - direct access)
- `auth.php`, `auth_helper.php` (authentication helpers)

### Client Management
- `clients.php` → `/clients` (client list)
- `add_client.php` → `/clients/add` (add new client - ENHANCED VERSION)
- `client_detail.php` → `/client/{id}` (view/edit client)
- `secure_clients.php` → `/clients/secure` (secure client view)

### Staff & Employee Management
- `employee_portal.php` (employee portal)
- `staff_dashboard.php` → `/staff/dashboard` (staff dashboard)
- `staff_portal_router.php` → `/staff` (staff portal router)
- `mobile_employee_portal.php` (mobile version)

### Scheduling
- `schedule_manager.php` → `/schedule` (schedule management)
- `calendar.php` → `/calendar` (calendar view)
- `new_session.php` → `/staff/notes` (add session note)
- `iiss_session_note.php` → `/staff/notes/iiss` (IISS specific notes)

### Billing & Financial
- `billing_integration.php` → `/billing` (main billing page)
- `billing_dashboard.php` → `/billing/dashboard` (billing overview)
- `payroll_report.php` → `/billing/payroll` (payroll reports)
- `billing_claims.php` → `/billing/claims` (claims management)
- `edi_processing.php` → `/billing/edi` (EDI processing)

### Reports
- `reports.php` → `/reports` (main reports page)

### Admin Tools
- `admin_role_switcher.php` → `/role-switcher` (role switching)
- `admin_database.php` (database management - NEW)
- `case_manager_portal.php` → `/case-manager` (case manager portal)
- `treatment_plan_manager.php` → `/case-manager/plans` (treatment plans)

### API Endpoints
- `api.php` (main API handler)
- `api_time_clock.php` → `/api/time-clock` (time clock API)
- `api_endpoints.php` (API endpoint definitions)
- `treatment_plan_api.php` (treatment plan API)

### Service Management
- `service_types.php` (service type management)

### Setup & Utilities
- `setup.php` (general setup)
- `setup_comprehensive.php` → `/setup_comprehensive` (comprehensive setup)
- Various debug and check files

## Core System Pages (src/)

### Admin Dashboard Components
- `admin_dashboard.php` → `/admin` (main admin dashboard)
- `admin_users.php` → `/admin/users` (user management)
- `admin_employees.php` → `/admin/employees` (employee management)
- `admin_organization.php` → `/admin/organization` (organization settings)

### User Dashboard
- `dashboard.php` → `/dashboard` (user dashboard)
- `login.php` → `/login` (core login handler)

## Public Pages (pages/)

### Authentication (pages/auth/)
- `authorize.php` (OpenEMR compatibility)
- `logout.php` (logout handler)
- `test_login.php` (login testing)

### Public Pages (pages/public/)
- `about.php` → `/about` (about page)
- `services.php` → `/services` (services page)
- `contact.php` → `/contact` (contact page)
- `application_form.php` → `/apply` (job application)
- `help_center.php` → `/help` (help center)
- `quick_start_guide.php` (quick start guide)

### Setup Pages (pages/setup/)
- `apply_billing_integration.php` (billing setup)
- `apply_production_updates.php` (production updates)
- `setup_production.php` (production setup)
- `setup_schedules_table.php` (schedule setup)

### Utilities (pages/utilities/)
- `fix_database.php` (database fix script)
- `generate_htaccess.php` (htaccess generator)
- `system_features.php` (system features)

## Database Tables (autism_*)
1. `autism_users` - System users
2. `autism_clients` - Client records
3. `autism_staff_members` - Staff records
4. `autism_schedules` - Schedule entries
5. `autism_sessions` - Session records
6. `autism_service_types` - Service definitions
7. `autism_waiver_types` - Waiver types
8. `autism_client_authorizations` - Service authorizations
9. `autism_claims` - Billing claims
10. `autism_organization_settings` - Organization config

## Routes Summary (.htaccess)

### Public Routes
- `/about` → pages/public/about.php
- `/services` → pages/public/services.php
- `/contact` → pages/public/contact.php
- `/apply` → pages/public/application_form.php

### Authentication
- `/login` → src/login.php
- `/logout` → pages/auth/logout.php
- `/dashboard` → src/dashboard.php

### Admin Panel
- `/admin` → src/admin_dashboard.php
- `/admin/users` → src/admin_users.php
- `/admin/employees` → src/admin_employees.php
- `/admin/organization` → src/admin_organization.php

### Client Management
- `/clients` → autism_waiver_app/clients.php
- `/clients/add` → autism_waiver_app/add_client.php
- `/client/{id}` → autism_waiver_app/client_detail.php

### Billing
- `/billing` → autism_waiver_app/billing_integration.php
- `/billing/dashboard` → autism_waiver_app/billing_dashboard.php
- `/billing/claims` → autism_waiver_app/billing_claims.php

### Staff Portal
- `/staff` → autism_waiver_app/staff_portal_router.php
- `/staff/dashboard` → autism_waiver_app/staff_dashboard.php
- `/staff/clock` → autism_waiver_app/api_time_clock.php
- `/staff/notes` → autism_waiver_app/new_session.php

### Other Routes
- `/schedule` → autism_waiver_app/schedule_manager.php
- `/calendar` → autism_waiver_app/calendar.php
- `/reports` → autism_waiver_app/reports.php
- `/help` → pages/public/help_center.php
- `/setup_comprehensive` → autism_waiver_app/setup_comprehensive.php

## Known Issues to Fix
1. Database table `autism_sessions` needs to be created
2. Some links may still use route paths instead of direct .php references
3. Need to ensure all database tables exist before pages load

## Development Notes
- Always use direct .php links for internal navigation within autism_waiver_app/
- Routes in .htaccess are for clean URLs from external access
- Database management available at /autism_waiver_app/admin_database.php
- All tables can be created via admin database tool or fix_database.php