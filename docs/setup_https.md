# Setting up HTTPS for aci.dydact.io

## Current Status
- ✅ HTTP working at: http://aci.dydact.io:8080
- ❌ HTTPS not configured yet

## Steps to Enable HTTPS Access

### Option 1: Using Let's Encrypt (Recommended for Production)

1. **Update Router Port Forwarding**:
   ```
   External Port 80 → Internal 71.244.253.183:8080
   External Port 443 → Internal 71.244.253.183:8443
   ```

2. **Install Certbot in Container**:
   ```bash
   docker-compose exec iris-emr apt-get update
   docker-compose exec iris-emr apt-get install -y certbot python3-certbot-apache
   ```

3. **Generate SSL Certificate**:
   ```bash
   docker-compose exec iris-emr certbot --apache -d aci.dydact.io -d www.aci.dydact.io
   ```

4. **Update Domain Config**:
   Edit `/config/domain-config.php`:
   ```php
   define('FORCE_SSL', true);
   define('BASE_URL', 'https://' . PRIMARY_DOMAIN);
   ```

### Option 2: Self-Signed Certificate (For Testing)

1. **Generate Self-Signed Cert**:
   ```bash
   docker-compose exec iris-emr openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
     -keyout /etc/ssl/private/aci.key \
     -out /etc/ssl/certs/aci.crt \
     -subj "/C=US/ST=Maryland/L=Baltimore/O=American Caregivers Inc/CN=aci.dydact.io"
   ```

2. **Update Apache Config**:
   ```bash
   docker-compose exec iris-emr a2enmod ssl
   docker-compose exec iris-emr service apache2 reload
   ```

### Option 3: Use Cloudflare (Easiest)

1. **Add site to Cloudflare**
2. **Update DNS to point to your IP**
3. **Enable "Flexible SSL" in Cloudflare**
4. **Access via**: https://aci.dydact.io (no port needed)

## Current Access URLs

### With Port Numbers (Working Now):
- http://aci.dydact.io:8080 - Main site
- http://aci.dydact.io:8080/login - Staff login
- http://aci.dydact.io:8080/admin - Admin panel

### Without Port Numbers (Requires Router Config):
Configure your router to forward:
- Port 80 → 71.244.253.183:8080
- Port 443 → 71.244.253.183:8443

Then access at:
- http://aci.dydact.io
- https://aci.dydact.io (after SSL setup)