# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## System Overview

Scrive ACI is a comprehensive healthcare management system for autism waiver services in Maryland. It's built on OpenEMR with custom modules for employee management, billing, and clinical documentation.

## Commands

### Running the Application
```bash
# Start the application
docker-compose up --build

# Stop the application
docker-compose down

# View logs
docker-compose logs -f iris-emr

# Access MySQL database
docker exec -it scrive-aci_mysql_1 mysql -u openemr -p openemr
```

### Database Management
```bash
# Initialize database (runs automatically on first start)
docker exec scrive-aci_iris-emr_1 /docker/init-database.sh

# Apply database updates
docker exec -it scrive-aci_mysql_1 mysql -u openemr -p openemr < sql/[filename].sql

# Check database structure
docker exec -it scrive-aci_mysql_1 mysql -u openemr -p openemr -e "SHOW TABLES LIKE 'autism_%';"
```

### Lint and Type Checking
When modifying code, run these commands to ensure quality:
```bash
npm run lint      # Check JavaScript code style
npm run typecheck # Check TypeScript types
```

## Architecture

⚠️ **CRITICAL**: See [SYSTEM_ARCHITECTURE_MAP.md](./SYSTEM_ARCHITECTURE_MAP.md) for comprehensive component mapping, paths, and API endpoints to prevent authentication/routing issues.

### Core Structure
- **OpenEMR Base**: Uses OpenEMR 7.0.2 Docker image with custom branding as "Iris EMR"
- **Multi-Portal System**: Role-based portals (staff, case manager, supervisor, admin)
- **Clean URLs**: Apache mod_rewrite strips .php extensions (handled by UrlManager.php)
- **Database**: MariaDB with autism_* prefixed tables for custom functionality
- **Document Root**: `/var/www/localhost/htdocs` in Docker container

### Key Design Patterns
1. **Role-Based Access Control (RBAC)**:
   - Levels 1-5 defined in autism_users.access_level
   - Auth checks via requireAuth() in src/init.php
   - Session-based authentication with timeout

2. **URL Routing**:
   - .htaccess defines clean URL mappings
   - UrlManager::stripPhpExtension() removes .php from URLs
   - Pattern: /resource/action/id maps to files in autism_waiver_app/

3. **Database Access**:
   - PDO with prepared statements (no raw SQL)
   - Connection via getDatabase() in src/config.php
   - All custom tables prefixed with autism_

4. **Multi-Domain Support**:
   - Main: aci.dydact.io
   - Subdomains: staff.*, admin.*, api.*
   - All resolve to same codebase with role-based routing

### Critical Files
- `src/init.php` - Authentication and session management
- `src/config.php` - Database configuration and connection
- `src/UrlManager.php` - Clean URL handling
- `autism_waiver_app/billing_integration.php` - Main billing dashboard
- `autism_waiver_app/staff_portal_router.php` - Employee mobile portal entry

### Maryland Medicaid Integration
- Service codes and rates in autism_service_types table
- EDI 837/835 processing in autism_waiver_app/edi/
- Waiver types: COMAR, DDA, ACSW
- Required fields: MA number, diagnosis codes, prior auth

### Common Issues and Fixes
1. **Undefined array keys**: Use null coalescing operator (?? default_value)
2. **Missing database columns**: Check actual schema before adding WHERE clauses
3. **Broken links**: Use absolute paths (/autism_waiver_app/file.php)
4. **Session timeout**: Check $_SESSION['user_id'] before operations

### Production Login Credentials
All users now use database authentication (no hardcoded credentials):

| Name | Username/Email | Password | Access Level |
|------|----------------|----------|--------------|
| Frank (Supreme Admin) | frank@acgcares.com | Supreme2024! | 6 |
| Mary Emah (CEO) | mary.emah@acgcares.com | CEO2024! | 5 |
| Dr. Ukpeh | drukpeh or drukpeh@duck.com | Executive2024! | 5 |
| Amanda Georgi (HR) | amanda.georgi@acgcares.com | HR2024! | 4 |
| Edwin Recto (Clinical) | edwin.recto@acgcares.com | Clinical2024! | 4 |
| Pam Pastor (Billing) | pam.pastor@acgcares.com | Billing2024! | 4 |
| Yanika Crosse (Billing) | yanika.crosse@acgcares.com | Billing2024! | 4 |
| Alvin Ukpeh (SysAdmin) | alvin.ukpeh@acgcares.com | SysAdmin2024! | 5 |

### Authentication Fix Applied ✅ COMPLETED
- **FIXED**: simple_login.php now uses database authentication (autism_waiver_app/simple_login.php:22)
- **FIXED**: Removed hardcoded credentials from authenticateAutismUser function (src/openemr_integration.php:107)
- **FIXED**: Updated SQL query to check `status = 'active'` instead of non-existent `is_active` column
- **FIXED**: All password hashes updated in database using PHP password_hash() function
- **VERIFIED**: All login credentials tested and working at aci.dydact.io
- **PRODUCTION STATUS**: ✅ Ready - Database authentication fully functional

### Container Status
- Docker containers restarted and running
- All user accounts properly created in autism_users table
- Production authentication working for all credential combinations:
  - Username/password authentication
  - Email/password authentication

### Important Security Notes
- All user inputs must be sanitized with htmlspecialchars()
- Use prepared statements for all database queries

## Authentication Troubleshooting

### Common Issues and Fixes

1. **Login Not Working**
   - Check Apache DocumentRoot in `apache/aci-dydact-io.conf` (should be `/var/www/localhost/htdocs`)
   - Verify `sites/americancaregivers/sqlconf.php` has database credentials
   - Ensure password hashes were created in web context, not CLI
   - Check volume mounts aren't overriding config files

2. **"Page Not Found" Errors**
   - Verify .htaccess is in place and Apache AllowOverride is set to All
   - Check file exists at mapped path (see SYSTEM_ARCHITECTURE_MAP.md)
   - Ensure URL rewriting is enabled in Apache

3. **Database Connection Failed**
   - Container DB host is `mysql`, not `localhost`
   - Check MySQL container is running: `docker ps`
   - Verify credentials match docker-compose.yml

4. **Password Verification Fails**
   - Regenerate password hashes from web context, not CLI
   - Use the fix script: `curl http://localhost/fix_all_passwords`

### Quick Diagnostic Commands
```bash
# Check container status
docker ps

# View container logs
docker logs scrive-aci-iris-emr-1

# Test database connection
docker exec scrive-aci-iris-emr-1 mysql -h mysql -u openemr -popenemr -e "SELECT COUNT(*) FROM autism_users;"

# Check file locations
docker exec scrive-aci-iris-emr-1 ls -la /var/www/localhost/htdocs/src/

# Test authentication directly
docker exec scrive-aci-iris-emr-1 php -r "
require_once '/var/www/localhost/htdocs/src/config.php';
require_once '/var/www/localhost/htdocs/src/openemr_integration.php';
\$result = authenticateOpenEMRUser('admin', 'AdminPass123!');
echo \$result ? 'Auth works' : 'Auth failed';
"
```
- Check access_level before sensitive operations
- Audit logging enabled for billing and clinical changes