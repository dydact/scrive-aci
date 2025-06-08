# Authentication Issue - Final Root Cause & Solution

## Problem Analysis
The authentication was "unresponsive" because of two key issues:

### 1. Port Mapping Issue
- Container was running on development ports (8080/8443) instead of production ports (80/443)
- When testing at `http://localhost/login`, it was trying to reach port 80 but container was on 8080
- This caused connection failures that appeared as "unresponsive" login

### 2. Missing Users After Container Rebuild
- The fixed database initialization script correctly removed hardcoded users
- But users need to be created after container startup through the application
- Without users in the database, authentication would always fail

## Authentication Flow Traced

The authentication follows this path:

1. **User submits login form** → `/src/login.php:25`
2. **Calls `authenticateOpenEMRUser()`** → `/src/openemr_integration.php:55`
3. **First tries `authenticateAutismUser()`** → `/src/openemr_integration.php:107`
4. **Queries `autism_users` table** → Line 112-118
5. **Verifies password with `password_verify()`** → Line 121
6. **Returns user data or false**

## Working Solution

### Database Lookup Location
Authentication uses this SQL query in `authenticateAutismUser()`:

```sql
SELECT id, username, password_hash, email, full_name, role, access_level
FROM autism_users 
WHERE (username = ? OR email = ?) AND status = 'active'
```

Location: `/src/openemr_integration.php:112-116`

### Database Connection
- Uses PDO connection via `getDatabase()` in `/src/config.php:61`
- Connects to: `mysql:host=mysql;dbname=openemr`
- Credentials: `openemr/openemr`

## Deployment Steps for Production

```bash
# 1. Stop any existing containers
docker-compose down

# 2. Start with production ports
export HOST_HTTP_PORT=80
export HOST_HTTPS_PORT=443
docker-compose up -d

# 3. Wait for startup then create users
sleep 15
docker cp setup_users.php scrive-aci-iris-emr-1:/var/www/localhost/htdocs/
curl http://localhost/setup_users

# 4. Clean up
docker exec scrive-aci-iris-emr-1 rm /var/www/localhost/htdocs/setup_users.php
```

## Verification Commands

```bash
# Test login
curl -X POST http://localhost/login -d "username=drukpeh&password=Executive2024!" -i

# Check container ports
docker ps | grep iris-emr

# Check users in database
docker exec scrive-aci-iris-emr-1 mysql -h mysql -u openemr -popenemr openemr -e "SELECT username, email FROM autism_users;"
```

## Working Credentials

✅ **All users can now login at http://localhost/**

- **drukpeh** / Executive2024!
- **admin** / AdminPass123!
- **frank** / Supreme2024!
- All other configured users

## Key Lessons

1. **Port mapping matters** - Always verify container is running on expected ports
2. **Database initialization ≠ Data population** - Schema creation and user creation are separate steps
3. **Environment variables** - Must be properly exported when using docker-compose
4. **Debug systematically** - Trace the exact authentication flow to find bottlenecks

## Prevention

- Add port validation to startup scripts
- Consider automating user creation in Docker entrypoint
- Add health checks that verify both connectivity and data presence
- Document exact startup sequence

---
*Issue Resolved: June 5, 2025*
*System ready for production deployment*