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

### Core Structure
- **OpenEMR Base**: Uses OpenEMR 7.0.2 Docker image with custom branding as "Iris EMR"
- **Multi-Portal System**: Role-based portals (staff, case manager, supervisor, admin)
- **Clean URLs**: Apache mod_rewrite strips .php extensions (handled by UrlManager.php)
- **Database**: MariaDB with autism_* prefixed tables for custom functionality

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

### Testing Credentials
- Admin: admin@aci.com / Admin123!
- Supervisor: supervisor@aci.com / Super123!
- Case Manager: case@aci.com / Case123!
- Staff: staff@aci.com / Staff123!

### Important Security Notes
- All user inputs must be sanitized with htmlspecialchars()
- Use prepared statements for all database queries
- Check access_level before sensitive operations
- Audit logging enabled for billing and clinical changes