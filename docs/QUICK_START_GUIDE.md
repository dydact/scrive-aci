# Quick Start Guide - aci.dydact.io Setup

## Your Specific Configuration
- **Static IP**: 71.244.253.183
- **Hosted Zone ID**: Z004717721ORURY4RNWKT
- **Domain**: aci.dydact.io

## Step-by-Step Setup

### 1. Configure Your Router (15 minutes)
📋 **Follow**: `network/verizon-router-config.md`

**Quick Steps:**
1. Access router: http://192.168.1.1
2. Add port forwarding rules:
   - Port 80 → your server's local IP
   - Port 443 → your server's local IP
3. Set static IP for your server

### 2. Set Up DNS (5 minutes)
```bash
cd dns/
chmod +x setup-route53.sh
./setup-route53.sh
```

**This will create:**
- aci.dydact.io → 71.244.253.183
- www.aci.dydact.io → aci.dydact.io
- staff.aci.dydact.io → aci.dydact.io
- admin.aci.dydact.io → aci.dydact.io
- api.aci.dydact.io → aci.dydact.io

### 3. Test DNS (5 minutes)
```bash
# Wait 5-10 minutes for propagation, then test:
nslookup aci.dydact.io
# Should return: 71.244.253.183

# Test HTTP access
curl -I http://aci.dydact.io
```

### 4. Configure Apache (10 minutes)
```bash
# Copy virtual host config
sudo cp apache/aci-dydact-io.conf /etc/apache2/sites-available/

# Update document root path in the config file if needed
sudo nano /etc/apache2/sites-available/aci-dydact-io.conf

# Enable site and modules
sudo a2ensite aci-dydact-io.conf
sudo a2enmod rewrite ssl headers

# Test config
sudo apache2ctl configtest

# Restart Apache
sudo systemctl restart apache2
```

### 5. Set Up SSL Certificates (10 minutes)
```bash
# Run SSL setup
chmod +x ssl/setup-ssl.sh
sudo ./ssl/setup-ssl.sh
```

### 6. Configure Application (5 minutes)
```bash
# Copy production htaccess
cp htaccess-production .htaccess

# Set permissions
chmod 644 .htaccess
```

### 7. Final Testing (10 minutes)
```bash
# Test HTTPS
curl -I https://aci.dydact.io

# Test subdomains
curl -I https://staff.aci.dydact.io
curl -I https://admin.aci.dydact.io

# Test in browser
open https://aci.dydact.io
```

## Troubleshooting Quick Fixes

### DNS Not Working?
```bash
# Check if DNS is set up correctly
dig aci.dydact.io
# Should show A record with 71.244.253.183
```

### Can't Access from Internet?
1. Test local access first: `curl -I http://192.168.1.XXX`
2. Check router port forwarding settings
3. Verify server firewall allows ports 80, 443

### SSL Issues?
1. Ensure DNS is working first
2. Check port 80 is accessible (needed for Let's Encrypt)
3. Verify Apache is running: `sudo systemctl status apache2`

## What Each File Does

| File | Purpose |
|------|---------|
| `dns/setup-route53.sh` | Creates DNS records in AWS Route 53 |
| `apache/aci-dydact-io.conf` | Apache virtual host configuration |
| `ssl/setup-ssl.sh` | Automated SSL certificate setup |
| `htaccess-production` | URL rewriting and security rules |
| `config/domain-config.php` | Application domain configuration |

## Expected Timeline
- **Total Setup Time**: ~60 minutes
- **DNS Propagation**: 5-10 minutes
- **SSL Certificate**: 2-3 minutes
- **Testing**: 10 minutes

## Success Indicators

✅ **DNS Working**: `nslookup aci.dydact.io` returns your IP  
✅ **HTTP Working**: `curl -I http://aci.dydact.io` returns 200 or 301  
✅ **HTTPS Working**: `curl -I https://aci.dydact.io` returns 200  
✅ **Website Loading**: Browser shows your website at https://aci.dydact.io  

## Need Help?

- **Router Issues**: Check `network/verizon-router-config.md`
- **DNS Issues**: Check `dns/README.md`
- **Full Checklist**: Check `setup/deployment-checklist.md`
- **Apache Logs**: `sudo tail -f /var/log/apache2/error.log`

## Your Next Actions:

1. **Start with router configuration** - this is usually the biggest hurdle
2. **Run the DNS setup script** - I've already configured it with your details
3. **Follow the steps above in order**
4. **Test each step before moving to the next**

Ready to begin? Start with `network/verizon-router-config.md`!