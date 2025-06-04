#!/bin/bash
# Deployment script for Scrive ACI to production server
# This creates a deployment package with all necessary files

echo "Creating deployment package for aci.dydact.io..."

# Create deployment directory
DEPLOY_DIR="deployment_$(date +%Y%m%d_%H%M%S)"
mkdir -p $DEPLOY_DIR

# Copy application files
echo "Copying application files..."
cp -r autism_waiver_app $DEPLOY_DIR/
cp -r src $DEPLOY_DIR/
cp -r config $DEPLOY_DIR/
cp -r public $DEPLOY_DIR/
cp .htaccess $DEPLOY_DIR/
cp index.php $DEPLOY_DIR/

# Copy SQL updates
echo "Copying database updates..."
mkdir -p $DEPLOY_DIR/sql_updates
cat > $DEPLOY_DIR/sql_updates/01_update_users.sql << 'EOF'
-- Update user accounts for production
-- First, update the simple_login.php to use database authentication

-- Delete old test users
DELETE FROM autism_users WHERE email LIKE '%@aci.com';

-- Create real ACG user accounts
INSERT INTO autism_users (username, email, password_hash, access_level, full_name, role, created_at) VALUES
('frank', 'frank@acgcares.com', '$2y$10$vQx5.VcPpF0tBVFt4xwDLO65eYQHxPuREGxqVRiGKt5E2KpKGAkpG', 6, 'Frank (Supreme Admin)', 'Supreme Administrator', NOW()),
('mary.emah', 'mary.emah@acgcares.com', '$2y$10$M7qxdHQosG0I7b8LaRnLVuT/3hxNqD5D4n6QGf3wW0Uqvd3gMxaKK', 5, 'Mary Emah', 'Chief Executive Officer', NOW()),
('drukpeh', 'drukpeh@duck.com', '$2y$10$kOy8JT.cGzLxwL3A7BWy8emd7kRISxkmxs3KEMNEKa3H/AKkqkUGO', 5, 'Dr. Ukpeh', 'Executive', NOW()),
('amanda.georgi', 'amanda.georgi@acgcares.com', '$2y$10$Fmb.b0HW0G4hRzx/DlP3GOK4B2JWdJ8T5YhqLmKZaLKQUZGCtKQQu', 4, 'Amanda Georgi', 'Human Resources Officer', NOW()),
('edwin.recto', 'edwin.recto@acgcares.com', '$2y$10$YJA5fUlGxo9bRkCxVpHIauAvUNq8YnZ8PFdmLkLQJlcYMfLg4zOv2', 4, 'Edwin Recto', 'Site Supervisor / Clinical Lead', NOW()),
('pam.pastor', 'pam.pastor@acgcares.com', '$2y$10$X6k5mjqhvRKHb7fGQQy4KeAnMHYJHlQq0W0tQxdmNPG7YXQGnxdMS', 4, 'Pam Pastor', 'Billing Administrator', NOW()),
('yanika.crosse', 'yanika.crosse@acgcares.com', '$2y$10$X6k5mjqhvRKHb7fGQQy4KeAnMHYJHlQq0W0tQxdmNPG7YXQGnxdMS', 4, 'Yanika Crosse', 'Billing Administrator', NOW()),
('alvin.ukpeh', 'alvin.ukpeh@acgcares.com', '$2y$10$/Nh0QeCMNyD8CJjKhLnYmOKv6tRAkGxZ2.LoT0J4FGJQz5c/C7EQq', 5, 'Alvin Ukpeh', 'System Administrator', NOW())
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), access_level=VALUES(access_level);

-- Note: Passwords are pre-hashed versions of the passwords in LOGIN_CREDENTIALS.md
EOF

cat > $DEPLOY_DIR/sql_updates/02_update_services.sql << 'EOF'
-- Update service types with Maryland Medicaid rates
TRUNCATE TABLE autism_service_types;

INSERT INTO autism_service_types (service_code, service_name, description, rate, unit_type, is_active) VALUES
('W9307', 'Regular Therapeutic Integration', 'Support services for individuals with autism in community settings (80 units/week limit)', 9.28, 'unit', 1),
('W9308', 'Intensive Therapeutic Integration', 'Enhanced support services requiring higher staff qualifications (60 units/week limit)', 11.60, 'unit', 1),
('W9306', 'Intensive Individual Support Services (IISS)', 'One-on-one intensive support for individuals with complex needs (160 units/week limit)', 12.80, 'unit', 1),
('W9314', 'Respite Care', 'Temporary relief for primary caregivers (96 units/day, 1344 units/year limit)', 9.07, 'unit', 1),
('W9315', 'Family Consultation', 'Training and support for family members (24 units/day, 160 units/year limit)', 38.10, 'unit', 1);
EOF

# Create deployment instructions
cat > $DEPLOY_DIR/DEPLOYMENT_INSTRUCTIONS.txt << 'EOF'
DEPLOYMENT INSTRUCTIONS FOR ACI.DYDACT.IO
=========================================

1. BACKUP CURRENT PRODUCTION
   - Backup database: mysqldump -u [user] -p [database] > backup_$(date +%Y%m%d).sql
   - Backup files: tar -czf files_backup_$(date +%Y%m%d).tar.gz /path/to/current/files

2. UPLOAD FILES
   - Upload all files in this directory to the production server
   - Maintain the directory structure
   - Set proper permissions (755 for directories, 644 for files)

3. UPDATE CONFIGURATION
   - Edit config/claimmd.php and update with production database credentials
   - Edit src/config.php and update database connection for production

4. RUN DATABASE UPDATES
   - mysql -u [user] -p [database] < sql_updates/01_update_users.sql
   - mysql -u [user] -p [database] < sql_updates/02_update_services.sql

5. UPDATE AUTHENTICATION
   - The system now uses database authentication instead of hardcoded credentials
   - Make sure src/openemr_integration.php is using the autism_users table

6. CLEAR CACHE
   - Clear any server-side cache
   - Clear browser cache for testing

7. TEST
   - Test login with new credentials (see LOGIN_CREDENTIALS.md)
   - Test billing features
   - Verify Claim.MD integration

8. IMPORTANT SECURITY NOTES
   - Remove the hardcoded credentials from simple_login.php
   - Ensure config files have proper permissions (600 or 640)
   - Keep Claim.MD API key secure
EOF

# Create archive
echo "Creating deployment archive..."
tar -czf ${DEPLOY_DIR}.tar.gz $DEPLOY_DIR/

echo "Deployment package created: ${DEPLOY_DIR}.tar.gz"
echo "Transfer this file to your production server and follow DEPLOYMENT_INSTRUCTIONS.txt"