# Docker Authentication Audit Report
## Authentication Failure Analysis for aci.dydact.io

### Executive Summary
The authentication system is failing at aci.dydact.io due to several critical configuration mismatches and deployment issues. The primary issues are:

1. **Apache configuration mismatch**: The `aci-dydact-io.conf` file references incorrect paths
2. **Empty SQL configuration file**: The `sites/americancaregivers/sqlconf.php` file is empty
3. **Volume mounting issues**: Key directories are being overridden by Docker volumes
4. **Multiple login endpoints**: Inconsistent routing between different login files

### Critical Issues Found

#### 1. Apache Configuration Path Mismatch
**Issue**: The `apache/aci-dydact-io.conf` file is configured for a different document root than what Docker uses.
- **Production config**: Points to `/var/www/scrive-aci`
- **Docker config**: Uses `/var/www/localhost/htdocs`
- **Impact**: Apache cannot find the PHP files, causing 404 errors

#### 2. Empty Database Configuration
**Issue**: The `sites/americancaregivers/sqlconf.php` file is empty.
- **Expected**: Should contain database connection details
- **Impact**: OpenEMR integration cannot connect to the database

#### 3. Volume Mounting Conflicts
**Issue**: Docker volumes are overriding critical files:
```yaml
volumes:
  - ./config:/var/www/localhost/htdocs/config
  - ./pages:/var/www/localhost/htdocs/pages
```
- **Impact**: Files copied during Docker build may be replaced with local versions

#### 4. Multiple Login Endpoints
**Issue**: There are multiple login files with different implementations:
- `/src/login.php` - Main login with OpenEMR integration
- `/autism_waiver_app/simple_login.php` - Simplified login
- **Impact**: Inconsistent authentication behavior

#### 5. Database Connection Configuration
**Issue**: The application expects environment variables that may not be passed correctly:
- Docker uses `DB_HOST=mysql` (container name)
- Production may need `DB_HOST=localhost` or actual IP

### Root Cause Analysis

The authentication failure is caused by a combination of:

1. **Deployment Configuration Error**: The Apache configuration file (`aci-dydact-io.conf`) was not updated for the Docker environment. It still references paths from a non-containerized deployment.

2. **Missing OpenEMR Integration**: The `sqlconf.php` file is empty, breaking the OpenEMR database connection that the authentication system relies on.

3. **Volume Override**: The volume mounts in `docker-compose.yml` are overriding files that were properly configured during the Docker build process.

### Immediate Fixes Required

#### 1. Fix Apache Configuration
Create a Docker-specific Apache configuration:
```apache
# /apache/aci-docker.conf
DocumentRoot /var/www/localhost/htdocs
```

#### 2. Create Database Configuration
```php
<?php
// sites/americancaregivers/sqlconf.php
$host = getenv('DB_HOST') ?: 'mysql';
$login = getenv('DB_USER') ?: 'openemr';
$pass = getenv('DB_PASS') ?: 'openemr';
$dbase = getenv('DB_NAME') ?: 'openemr';
```

#### 3. Update Volume Mappings
Remove or modify volume mappings that override critical files:
```yaml
volumes:
  - logvolume:/var/log
  - sitevolume:/var/www/localhost/htdocs/openemr/sites
  - uploadvolume:/var/www/localhost/htdocs/uploads
  # Remove config and pages volume mappings
```

#### 4. Consolidate Login Endpoints
Ensure `.htaccess` consistently routes to one login implementation:
```apache
RewriteRule ^login/?$ src/login.php [L]
```

### Testing Recommendations

1. **Container Shell Access**: 
   ```bash
   docker exec -it scrive-aci-iris-emr-1 /bin/sh
   ```

2. **Check Database Connection**:
   ```bash
   docker exec -it scrive-aci-iris-emr-1 mysql -h mysql -u openemr -popenemr openemr -e "SELECT * FROM autism_users LIMIT 1;"
   ```

3. **Verify File Paths**:
   ```bash
   docker exec -it scrive-aci-iris-emr-1 ls -la /var/www/localhost/htdocs/src/login.php
   ```

4. **Check Apache Configuration**:
   ```bash
   docker exec -it scrive-aci-iris-emr-1 cat /etc/apache2/conf.d/aci-domain.conf
   ```

### Long-term Recommendations

1. **Environment-Specific Configurations**: Create separate configuration files for Docker vs production deployments
2. **Configuration Management**: Use environment variables consistently across all configuration files
3. **Volume Strategy**: Only mount directories that need to persist data, not configuration files
4. **Single Sign-On**: Implement a unified authentication system instead of multiple login endpoints
5. **Health Checks**: Add Docker health checks to verify database connectivity on startup

### Conclusion

The authentication failures are due to configuration mismatches between the production Apache configuration and the Docker environment. The immediate priority should be fixing the Apache document root configuration and ensuring the database configuration file is properly populated. Once these issues are resolved, authentication should work correctly.