# Autism Waiver Management System - Deployment Guide

## Overview
This guide provides step-by-step instructions for deploying the Autism Waiver Management System to aci.dydact.io for employee testing.

## System Components

### 1. Clinical Documentation & Care Management
- IISS Session Notes Interface
- Treatment Plan Manager with Goal Tracking
- Behavior Incident Tracking
- Client Assessment Tools

### 2. Scheduling & Resource Management
- Visual Weekly Calendar
- Recurring Appointment Templates
- Staff Availability Management
- Resource/Room Booking System
- Waitlist Management

### 3. Financial & Billing Enhancement
- EDI 837/835 Integration Structure
- Prior Authorization Tracking
- Claims Management
- Insurance Verification
- Payment Processing

## Pre-Deployment Checklist

### 1. Environment Variables
Ensure the following environment variables are set in your production environment:

```bash
MARIADB_DATABASE=iris
MARIADB_USER=iris_user
MARIADB_PASSWORD=[secure_password]
MARIADB_HOST=localhost
MARIADB_ROOT_PASSWORD=[secure_root_password]
```

### 2. Database Requirements
- MariaDB 10.5 or higher
- Minimum 2GB RAM allocated to database
- UTF8MB4 character set support

### 3. PHP Requirements
- PHP 7.4 or higher
- PDO MySQL extension
- JSON extension
- Session support
- OpenSSL (for future API integrations)

## Deployment Steps

### Step 1: Backup Current System
```bash
# Run the backup script
./backup_repository.sh

# Verify backup was created
ls -la backups/
```

### Step 2: Database Migration
```bash
# Connect to production database
mysql -u root -p

# Create database if not exists
CREATE DATABASE IF NOT EXISTS iris CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Import SQL schemas in order
mysql -u root -p iris < sql/clinical_documentation_system.sql
mysql -u root -p iris < sql/scheduling_resource_management.sql
mysql -u root -p iris < sql/financial_billing_enhancement.sql
```

### Step 3: File Deployment
```bash
# Copy autism waiver app files
rsync -avz autism_waiver_app/ /var/www/html/aci.dydact.io/autism_waiver_app/

# Set proper permissions
chown -R www-data:www-data /var/www/html/aci.dydact.io/autism_waiver_app/
chmod -R 755 /var/www/html/aci.dydact.io/autism_waiver_app/
```

### Step 4: Apache Configuration
Add the following to your Apache virtual host configuration:

```apache
<Directory /var/www/html/aci.dydact.io/autism_waiver_app>
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

# Protect sensitive files
<FilesMatch "\.(sql|csv|md)$">
    Require all denied
</FilesMatch>
```

### Step 5: Security Configuration

1. Remove sensitive CSV files:
```bash
rm -f autism_waiver_app/*.csv
```

2. Create .htaccess file:
```bash
cat > /var/www/html/aci.dydact.io/autism_waiver_app/.htaccess << 'EOF'
# Deny access to sensitive files
<FilesMatch "\.(sql|csv|md|db)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Disable directory listing
Options -Indexes

# Set PHP configuration
php_value session.gc_maxlifetime 3600
php_value session.cookie_httponly 1
php_value session.cookie_secure 1
EOF
```

### Step 6: Initial Data Setup

1. Create admin user:
```sql
INSERT INTO autism_users (username, password, email, first_name, last_name, access_level, user_type)
VALUES ('admin', '$2y$10$[hashed_password]', 'admin@aci.com', 'System', 'Administrator', 5, 'admin');
```

2. Add service types:
```sql
INSERT INTO autism_service_types (service_name, service_code, billing_code, billing_rate, is_active)
VALUES 
('IISS', 'IISS', 'H2019', 25.00, 1),
('Therapeutic Integration', 'TI', 'H2019TI', 20.00, 1),
('Respite Care', 'RESP', 'S5151', 15.00, 1);
```

### Step 7: Test Access Points

After deployment, test the following URLs:

1. **Login Page**: https://aci.dydact.io/autism_waiver_app/login.php
2. **Dashboard**: https://aci.dydact.io/src/dashboard.php
3. **IISS Session Notes**: https://aci.dydact.io/autism_waiver_app/iiss_session_note.php
4. **Treatment Plans**: https://aci.dydact.io/autism_waiver_app/treatment_plan_manager.php
5. **Schedule Manager**: https://aci.dydact.io/autism_waiver_app/schedule_manager.php

## Post-Deployment Tasks

### 1. User Training
- Schedule training sessions for staff
- Provide user guides for each module
- Set up test clients for practice

### 2. Data Migration
- Import existing client data
- Set up staff assignments
- Configure recurring schedules

### 3. Integration Testing
- Test time clock integration with billing
- Verify session notes create billing entries
- Check schedule synchronization

### 4. Performance Monitoring
- Set up database query monitoring
- Configure error logging
- Monitor session management

## Rollback Plan

If issues arise during deployment:

1. Restore database from backup:
```bash
mysql -u root -p iris < backups/[backup_file].sql
```

2. Restore application files:
```bash
tar -xzf backups/autism_waiver_backup_[date].tar.gz -C /var/www/html/aci.dydact.io/
```

3. Clear application cache:
```bash
rm -rf /tmp/sess_*
```

## Support Contacts

- Technical Issues: [IT Support Email]
- Training Questions: [Training Coordinator]
- Billing/Compliance: [Billing Manager]

## Future Enhancements (Phase 2)

1. **Communication Features**
   - Secure messaging between staff
   - Family portal access
   - Automated appointment reminders
   - Telehealth integration

2. **Advanced Integrations**
   - Real Maryland Medicaid EVS API
   - QuickBooks integration
   - Electronic signature integration
   - Mobile app development

## Compliance Notes

- System maintains HIPAA compliance through encrypted connections
- All PHI is stored in encrypted database fields
- Session timeout set to 60 minutes for security
- Audit logs track all data access and modifications

---

Last Updated: December 2024
Version: 1.0