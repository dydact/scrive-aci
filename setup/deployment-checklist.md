# Production Deployment Checklist for aci.dydact.io

## Prerequisites ✅ / ❌

### Domain & DNS
- [ ] Verizon FiOS static IP address confirmed
- [ ] AWS Route 53 access configured
- [ ] dydact.io domain hosted on Route 53
- [ ] DNS records created (run `dns/setup-route53.sh`)

### Server Requirements
- [ ] Web server running (Apache/Nginx)
- [ ] PHP 7.4+ installed
- [ ] MySQL/MariaDB installed
- [ ] SSL certificates configured
- [ ] Firewall configured

### Network Configuration
- [ ] Router port forwarding: 80 → server
- [ ] Router port forwarding: 443 → server
- [ ] Server has static local IP
- [ ] Internet connectivity tested

## Deployment Steps

### 1. DNS Configuration
```bash
cd dns/
# Edit setup-route53.sh with your values
nano setup-route53.sh
# Run the setup
chmod +x setup-route53.sh
./setup-route53.sh
```

### 2. Apache Configuration
```bash
# Copy virtual host configuration
sudo cp apache/aci-dydact-io.conf /etc/apache2/sites-available/

# Enable site
sudo a2ensite aci-dydact-io.conf

# Enable required modules
sudo a2enmod rewrite ssl headers

# Test configuration
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

### 3. SSL Certificate Setup
```bash
# Run SSL setup script
chmod +x ssl/setup-ssl.sh
sudo ./ssl/setup-ssl.sh
```

### 4. Application Configuration
```bash
# Copy production htaccess
cp htaccess-production .htaccess

# Set proper permissions
chmod 644 .htaccess
chmod 755 pages/
chmod 644 pages/public/*.php
```

### 5. Database Configuration
```bash
# Update database connection settings
nano config/domain-config.php
nano sites/americancaregivers/sqlconf.php
```

### 6. Testing
```bash
# Test DNS resolution
nslookup aci.dydact.io

# Test HTTP connectivity
curl -I http://aci.dydact.io

# Test HTTPS connectivity
curl -I https://aci.dydact.io

# Test subdomains
curl -I https://staff.aci.dydact.io
curl -I https://admin.aci.dydact.io
```

## Configuration Files to Edit

### Required Information:
- **Static IP Address**: Your Verizon FiOS IP
- **Route 53 Hosted Zone ID**: For dydact.io domain
- **Database Credentials**: MySQL username/password
- **Email Settings**: SMTP configuration for contact forms

### Files to Configure:

1. **dns/setup-route53.sh**
   ```bash
   HOSTED_ZONE_ID="Z1234567890ABC"  # Your hosted zone ID
   STATIC_IP="123.456.789.101"     # Your static IP
   ```

2. **sites/americancaregivers/sqlconf.php**
   ```php
   $host = "localhost";
   $username = "your_db_user";
   $password = "your_db_password";
   $database = "your_db_name";
   ```

3. **apache/aci-dydact-io.conf**
   ```apache
   DocumentRoot /var/www/scrive-aci  # Update path as needed
   ```

## Security Checklist

- [ ] SSL certificates installed and working
- [ ] HTTP redirects to HTTPS
- [ ] Security headers configured
- [ ] Sensitive files protected (.sql, .env, .log)
- [ ] Database credentials secured
- [ ] Admin area access restricted
- [ ] Regular backups configured
- [ ] Firewall rules applied
- [ ] Server updates applied

## Post-Deployment Verification

### Functionality Tests:
- [ ] Main website loads: https://aci.dydact.io
- [ ] About page works: https://aci.dydact.io/about
- [ ] Services page works: https://aci.dydact.io/services
- [ ] Contact page works: https://aci.dydact.io/contact
- [ ] Staff login accessible: https://staff.aci.dydact.io
- [ ] Admin dashboard accessible: https://admin.aci.dydact.io

### SSL/Security Tests:
- [ ] SSL certificate valid and trusted
- [ ] A+ rating on SSL Labs test
- [ ] HTTPS enforcement working
- [ ] Security headers present
- [ ] No mixed content warnings

### Performance Tests:
- [ ] Page load times acceptable (<3 seconds)
- [ ] Images and assets loading
- [ ] Mobile responsiveness working
- [ ] Caching headers configured

## Troubleshooting

### Common Issues:

1. **DNS Not Resolving**
   - Wait 5-10 minutes for propagation
   - Check Route 53 configuration
   - Verify hosted zone ID

2. **SSL Certificate Errors**
   - Ensure port 80 is accessible
   - Check domain DNS pointing to correct IP
   - Verify Apache is running

3. **Can't Access from Internet**
   - Check router port forwarding
   - Verify server firewall rules
   - Test local vs external access

4. **Database Connection Errors**
   - Check MySQL service status
   - Verify database credentials
   - Ensure database exists

## Support Resources

- **Apache Logs**: `/var/log/apache2/`
- **SSL Certificate Status**: `sudo certbot certificates`
- **DNS Propagation**: Use online DNS checker tools
- **SSL Testing**: https://www.ssllabs.com/ssltest/

## Maintenance Tasks

### Daily:
- Monitor server resources
- Check error logs for issues

### Weekly:
- Review security logs
- Check SSL certificate status
- Update application if needed

### Monthly:
- Server security updates
- Database optimization
- Backup verification
- Performance review