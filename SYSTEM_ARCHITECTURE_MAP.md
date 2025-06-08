# System Architecture Map - Scrive ACI

## Overview
This document provides a comprehensive mapping of all system components, paths, APIs, and endpoints to prevent authentication and routing issues.

## Directory Structure

### Host Machine Paths
```
/Users/dydact/Desktop/dydact/labs/scrive-aci/
├── docker-compose.yml          # Container orchestration
├── Dockerfile                  # Container build instructions
├── .htaccess                   # URL routing rules
├── index.php                   # Main entry point
├── config/                     # Configuration files
│   ├── domain-config.php       # Domain settings
│   └── claimmd.php            # Claim.MD API credentials
├── src/                        # Core application files
│   ├── config.php             # Database configuration
│   ├── login.php              # Main login page
│   ├── dashboard.php          # User dashboard
│   └── openemr_integration.php # Authentication functions
├── autism_waiver_app/          # Main application modules
│   ├── simple_login.php       # Alternative login (SHOULD BE REMOVED)
│   └── [various modules]      # Billing, scheduling, etc.
└── sites/americancaregivers/   # Site-specific config
    └── sqlconf.php            # Database connection (CURRENTLY EMPTY - ISSUE!)
```

### Docker Container Paths
```
/var/www/localhost/htdocs/      # Document root in container
├── .htaccess                   # Copied from host
├── index.php                   # Copied from host
├── src/                        # Copied from host
├── autism_waiver_app/          # Copied from host
├── config/                     # VOLUME MOUNTED - may override
├── pages/                      # VOLUME MOUNTED - may override
└── openemr/                    # OpenEMR base installation
    └── sites/americancaregivers/ # Site config
```

## Port Mappings

### Development (docker.env)
- HTTP: 8080 → 80 (container)
- HTTPS: 8443 → 443 (container)
- MySQL: 3306 → 3306 (container)

### Production (should be)
- HTTP: 80 → 80 (container)
- HTTPS: 443 → 443 (container)
- MySQL: 3306 → 3306 (container)

## Database Configuration

### Container Database
- Host: `mysql` (container name)
- Database: `openemr`
- User: `openemr`
- Password: `openemr`
- Port: 3306

### Critical Tables
- `autism_users` - User authentication
- `autism_clients` - Client records
- `autism_sessions` - Session documentation
- `autism_claims` - Billing claims
- `autism_schedules` - Scheduling

## URL Routing (.htaccess)

### Authentication Routes
- `/login` → `/src/login.php` ✅ CORRECT
- `/logout` → `/pages/auth/logout.php`
- `/dashboard` → `/src/dashboard.php`

### Admin Routes
- `/admin` → `/src/admin_dashboard.php`
- `/admin/users` → `/src/admin_users.php`
- `/admin/employees` → `/src/admin_employees.php`
- `/admin/organization` → `/src/admin_organization.php`

### Staff Portal Routes
- `/staff` → `/autism_waiver_app/staff_portal_router.php`
- `/staff/dashboard` → `/autism_waiver_app/staff_dashboard.php`
- `/staff/clock` → `/autism_waiver_app/api_time_clock.php`
- `/staff/notes` → `/autism_waiver_app/new_session.php`

### Billing Routes
- `/billing` → `/autism_waiver_app/billing_integration.php`
- `/billing/dashboard` → `/autism_waiver_app/billing_dashboard.php`
- `/billing/claims` → `/autism_waiver_app/billing_claims.php`
- `/billing/edi` → `/autism_waiver_app/edi_processing.php`

### API Endpoints
- `/api/time-clock` → `/autism_waiver_app/api_time_clock.php`
- `/api/clients` → `/autism_waiver_app/api.php?endpoint=clients`
- `/api/sessions` → `/autism_waiver_app/api.php?endpoint=sessions`
- `/api/billing` → `/autism_waiver_app/api.php?endpoint=billing`

## Authentication Flow

### 1. User Access
```
User → aci.dydact.io/login
      ↓
Apache → .htaccess rewrite
      ↓
Route to → /src/login.php
```

### 2. Login Processing
```
/src/login.php
      ↓
Loads → /src/config.php (DB config)
      ↓
Loads → /src/openemr_integration.php
      ↓
Calls → authenticateOpenEMRUser()
      ↓
Function → authenticateAutismUser()
      ↓
Query → autism_users table
      ↓
Verify → password_hash with password_verify()
```

### 3. Session Creation
```
Success → Set $_SESSION variables
       → Redirect to /dashboard
```

## Apache Configuration Issues

### Current Problem (aci-dydact-io.conf)
```apache
DocumentRoot /var/www/scrive-aci  # WRONG PATH!
```

### Should Be
```apache
DocumentRoot /var/www/localhost/htdocs
```

## Volume Mount Issues

### Current docker-compose.yml
```yaml
volumes:
  - ./config:/var/www/localhost/htdocs/config    # May override build files
  - ./pages:/var/www/localhost/htdocs/pages      # May override build files
```

### Recommendation
Only mount directories that need runtime data persistence, not configuration.

## Authentication Components

### Core Files
1. `/src/config.php` - Database connection
2. `/src/openemr_integration.php` - Auth functions
3. `/src/login.php` - Login page
4. `/autism_waiver_app/simple_login.php` - REMOVE THIS

### Key Functions
- `getDatabase()` - PDO connection
- `authenticateOpenEMRUser($username, $password)` - Main auth
- `authenticateAutismUser($username, $password)` - Autism-specific auth

### Database Schema
```sql
CREATE TABLE autism_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    full_name VARCHAR(255),
    role VARCHAR(100) DEFAULT 'User',
    access_level INT(1) DEFAULT 1,
    status ENUM('active','inactive','pending') DEFAULT 'active'
);
```

## Claim.MD Integration

### Configuration
- File: `/config/claimmd.php`
- Account Key: `24127_YF!7zAClm!R@qS^UknmlN#jo`
- API URL: `https://svc.claim.md/services/`

### API Class
- File: `/autism_waiver_app/integrations/claim_md_api.php`
- Methods:
  - `submitClaim()` - Submit 837 claims
  - `checkEligibility()` - Verify coverage
  - `getERA()` - Retrieve 835 remittances

## Critical Issues to Fix

### 1. Apache DocumentRoot
```bash
# Fix in apache/aci-dydact-io.conf
DocumentRoot /var/www/localhost/htdocs
<Directory /var/www/localhost/htdocs>
    AllowOverride All
    Require all granted
</Directory>
```

### 2. Database Configuration
```php
# Fix in sites/americancaregivers/sqlconf.php
<?php
$host = 'mysql';
$port = '3306';
$login = 'openemr';
$pass = 'openemr';
$dbase = 'openemr';
?>
```

### 3. Remove Duplicate Login
```bash
# Remove simple_login.php
rm autism_waiver_app/simple_login.php
```

### 4. Fix Volume Mounts
```yaml
# Update docker-compose.yml
volumes:
  - sitevolume:/var/www/localhost/htdocs/openemr/sites
  - uploadvolume:/var/www/localhost/htdocs/uploads
  # Remove config and pages volume mounts
```

## Testing Checklist

### 1. Container Health
```bash
docker ps                          # Check running
docker logs scrive-aci-iris-emr-1  # Check errors
```

### 2. File Locations
```bash
docker exec scrive-aci-iris-emr-1 ls -la /var/www/localhost/htdocs/
docker exec scrive-aci-iris-emr-1 ls -la /var/www/localhost/htdocs/src/
```

### 3. Database Connection
```bash
docker exec scrive-aci-iris-emr-1 mysql -h mysql -u openemr -popenemr -e "SHOW TABLES LIKE 'autism_%';"
```

### 4. Authentication Test
```bash
curl -I http://localhost/login
curl -I http://localhost/test_auth_status
```

## User Access Levels

| Level | Role | Permissions |
|-------|------|-------------|
| 6 | Supreme Admin | Full system access |
| 5 | Executive/Admin | Organization management |
| 4 | Department Head | Department operations |
| 3 | Case Manager | Client management |
| 2 | Direct Support | Session documentation |
| 1 | Basic User | Limited access |

## Service Types (Maryland Medicaid)

| Code | Service | Rate | Unit |
|------|---------|------|------|
| W9306 | IISS | $12.80 | 15 min |
| W9307 | Regular Integration | $9.28 | 15 min |
| W9308 | Intensive Integration | $11.60 | 15 min |
| W9314 | Respite | $9.07 | 15 min |
| W9315 | Family Consultation | $38.10 | 15 min |

## Environment Variables

### Container Environment
```
DB_HOST=mysql
DB_NAME=openemr
DB_USER=openemr
DB_PASS=openemr
OPENEMR_BASE_PATH=/var/www/localhost/htdocs/openemr
OPENEMR_SITE=americancaregivers
```

### Domain Configuration
```
VIRTUAL_HOST=aci.dydact.io,www.aci.dydact.io,staff.aci.dydact.io
SERVER_NAME=aci.dydact.io
```

---

**Last Updated**: June 5, 2025
**Purpose**: Prevent authentication and routing issues by providing clear system architecture reference