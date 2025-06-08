# Authentication Root Cause Analysis & Fix

## Problem Identified
The authentication issue was caused by hardcoded password hashes in the database initialization script that were generated in a different environment and incompatible with our web context.

## Root Cause
In `/docker/init-database.sh` line 156-157:
```sql
-- Insert default admin user (password: AdminPass123!)
INSERT IGNORE INTO autism_users (username, password_hash, email, full_name, role, access_level) 
VALUES ('admin', '$2y$10$VZF3KjJzCV1lqH5.Qm8Tq.0H9Eo5jF1Bxh8WJZxO1fHNx0jNqVkHy', 'admin@americancaregivers.com', 'System Administrator', 'Administrator', 5);
```

This hardcoded hash was created in a different PHP environment and would never verify correctly in our Docker container's web context.

## Solution Implemented

### 1. Created Fixed Database Initialization Script
- Created `/docker/init-database-fixed.sh` that creates tables WITHOUT any hardcoded users
- Removed all `INSERT` statements for users with hardcoded password hashes
- Tables are created empty, allowing proper user creation through the application

### 2. Updated Docker Build Process
- Modified `Dockerfile` to copy `init-database-fixed.sh` instead of the problematic script
- Updated `/docker/startup.sh` to use the fixed initialization script

### 3. Created User Setup Script
- Created `setup_users.php` that creates all users with passwords hashed in the web context
- This ensures password hashes are compatible with the runtime environment

## Files Modified
1. `/docker/init-database-fixed.sh` - New clean initialization script
2. `/docker/startup.sh` - Updated to use fixed script
3. `/Dockerfile` - Updated to copy fixed script
4. `setup_users.php` - New user creation script (temporary, used once)

## Deployment Steps
```bash
# 1. Stop and remove existing containers and volumes
docker-compose down -v

# 2. Rebuild with fixed scripts
HOST_HTTP_PORT=80 HOST_HTTPS_PORT=443 docker-compose up -d --build

# 3. Wait for startup then create users
sleep 15
docker cp setup_users.php scrive-aci-iris-emr-1:/var/www/localhost/htdocs/
curl http://localhost/setup_users

# 4. Clean up
docker exec scrive-aci-iris-emr-1 rm /var/www/localhost/htdocs/setup_users.php
```

## Verification
All users can now login successfully:
- ✅ admin / AdminPass123!
- ✅ drukpeh / Executive2024!
- ✅ frank@acgcares.com / Supreme2024!
- ✅ All other configured users

## Key Lesson
Never hardcode password hashes in initialization scripts. Password hashes must be generated in the same environment where they will be verified to ensure compatibility with PHP's password_hash() and password_verify() functions.

## Prevention
- Database initialization scripts should only create schema, not user data
- User creation should happen through application setup processes
- If default users are needed, create them via a setup script that runs in the web context

---
*Fixed: June 5, 2025*