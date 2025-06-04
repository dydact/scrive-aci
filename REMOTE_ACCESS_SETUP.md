# Remote Access Setup for aci.dydact.io

## Overview
This guide explains how to set up remote access to the Scrive ACI system using the domain aci.dydact.io.

## Domain Configuration

### 1. DNS Setup (Already Configured)
The following DNS records are configured:
- **A Record**: aci.dydact.io → Your Server IP
- **A Record**: www.aci.dydact.io → Your Server IP
- **A Record**: staff.aci.dydact.io → Your Server IP
- **A Record**: admin.aci.dydact.io → Your Server IP
- **A Record**: api.aci.dydact.io → Your Server IP

### 2. Local Development Testing
For local testing with the domain, add these entries to your hosts file:

#### Mac/Linux (/etc/hosts):
```
127.0.0.1   aci.dydact.io
127.0.0.1   www.aci.dydact.io
127.0.0.1   staff.aci.dydact.io
127.0.0.1   admin.aci.dydact.io
127.0.0.1   api.aci.dydact.io
```

#### Windows (C:\Windows\System32\drivers\etc\hosts):
```
127.0.0.1   aci.dydact.io
127.0.0.1   www.aci.dydact.io
127.0.0.1   staff.aci.dydact.io
127.0.0.1   admin.aci.dydact.io
127.0.0.1   api.aci.dydact.io
```

### 3. Port Forwarding for Remote Access
To access the system remotely, you need to forward ports from your router to the Docker host:

#### Required Port Forwards:
- **External Port 80** → Internal Port 8080 (HTTP)
- **External Port 443** → Internal Port 8443 (HTTPS)
- **External Port 3306** → Internal Port 3306 (MySQL - optional, for remote DB access)

#### Verizon Router Configuration:
1. Access router at http://192.168.1.1
2. Go to Advanced → Port Forwarding
3. Add rules:
   - Service: HTTP, External: 80, Internal: 8080, Device: [Your Server]
   - Service: HTTPS, External: 443, Internal: 8443, Device: [Your Server]

### 4. Firewall Configuration
Ensure your server firewall allows incoming connections:

```bash
# Mac (if using firewall)
sudo pfctl -d  # Temporarily disable to test

# Linux (Ubuntu/Debian)
sudo ufw allow 8080/tcp
sudo ufw allow 8443/tcp
sudo ufw allow 3306/tcp  # If MySQL access needed

# Linux (CentOS/RHEL)
sudo firewall-cmd --add-port=8080/tcp --permanent
sudo firewall-cmd --add-port=8443/tcp --permanent
sudo firewall-cmd --add-port=3306/tcp --permanent
sudo firewall-cmd --reload
```

## Docker Configuration Updates

The Docker configuration has been updated to support the domain:
- Apache configured with ServerName aci.dydact.io
- Virtual hosts for subdomains (staff, admin, api)
- SSL certificates generated for aci.dydact.io

## Accessing the System

### Local Access:
- **Main Site**: http://localhost:8080 or http://aci.dydact.io:8080
- **Staff Portal**: http://staff.aci.dydact.io:8080
- **Admin Portal**: http://admin.aci.dydact.io:8080
- **API**: http://api.aci.dydact.io:8080

### Remote Access (after port forwarding):
- **Main Site**: http://aci.dydact.io
- **Staff Portal**: http://staff.aci.dydact.io
- **Admin Portal**: http://admin.aci.dydact.io
- **API**: http://api.aci.dydact.io

### HTTPS Access (self-signed certificate):
- **Main Site**: https://aci.dydact.io:8443
- **Staff Portal**: https://staff.aci.dydact.io:8443
- **Admin Portal**: https://admin.aci.dydact.io:8443

## SSL Certificate Setup (Production)

For production use, obtain a proper SSL certificate:

### Option 1: Let's Encrypt (Free)
```bash
# Install certbot
sudo apt-get install certbot

# Obtain certificate
sudo certbot certonly --standalone -d aci.dydact.io -d www.aci.dydact.io -d staff.aci.dydact.io -d admin.aci.dydact.io -d api.aci.dydact.io

# Certificates will be in /etc/letsencrypt/live/aci.dydact.io/
```

### Option 2: Commercial Certificate
1. Generate CSR
2. Purchase certificate
3. Install in /etc/ssl/certs/

## Troubleshooting

### Cannot access remotely:
1. Check DNS: `nslookup aci.dydact.io`
2. Check port forwarding: `telnet aci.dydact.io 80`
3. Check Docker: `docker-compose ps`
4. Check firewall: `sudo iptables -L` or `sudo ufw status`

### SSL Certificate Warnings:
- Expected with self-signed certificates
- Add exception in browser for development
- Use proper certificate for production

### Subdomain Not Working:
1. Verify DNS records
2. Check Apache virtual host configuration
3. Ensure Docker container has latest configuration

## Security Considerations

1. **Change Default Passwords**:
   - Admin password (currently admin123)
   - MySQL root password
   - Create strong passwords

2. **Restrict Access**:
   - Consider IP whitelisting for admin subdomain
   - Use VPN for sensitive access
   - Enable fail2ban for brute force protection

3. **SSL/TLS**:
   - Always use HTTPS in production
   - Implement HSTS headers
   - Use strong cipher suites

4. **Database Security**:
   - Don't expose MySQL port publicly unless necessary
   - Use SSL for MySQL connections
   - Regular backups

## Next Steps

1. Rebuild Docker containers with new configuration
2. Set up port forwarding on router
3. Test remote access
4. Implement proper SSL certificates
5. Configure production security settings