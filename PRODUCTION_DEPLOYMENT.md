# 🚀 Scrive ACI Production Deployment Guide

## Overview
This guide covers the complete deployment process for the Scrive Autism Waiver Management System to production at aci.dydact.io.

## 🔐 Initial Superadmin Setup

### 1. Run the Production Setup Script
After deploying the code to the server, run:
```bash
cd /var/www/localhost/htdocs
php setup_production.php
```

This script will:
- ✅ Create all database tables
- ✅ Set up the initial superadmin user
- ✅ Import default programs and service types
- ✅ Create sample staff members and clients
- ✅ Display the initial admin credentials

**⚠️ IMPORTANT**: Save the generated admin password and change it after first login!

### 2. Initial Login Credentials
- **URL**: https://aci.dydact.io/src/login.php
- **Username**: admin
- **Password**: (generated during setup)

## 🏢 Key Staff Members Created
The setup script creates these initial staff accounts:
- Mary Emah (CEO) - memah@acgcares.com
- Amanda Georgie (Executive Staff) - ageorgie@acgcares.com
- John Smith (Clinical Supervisor) - jsmith@acgcares.com
- Sarah Johnson (Case Manager) - sjohnson@acgcares.com
- Michael Davis (Direct Care Professional) - mdavis@acgcares.com

## 🔄 Automatic Weekly Updates

### Setting Up Auto-Updates
1. Make scripts executable:
```bash
chmod +x scripts/auto-update.sh
chmod +x scripts/setup-cron.sh
```

2. Run the cron setup (as root):
```bash
sudo scripts/setup-cron.sh
```

This configures:
- ✅ Weekly updates every Monday at 2:00 AM
- ✅ Automatic backup of configuration files
- ✅ Safe git pull without affecting database
- ✅ Restoration of local configurations
- ✅ Apache restart after updates

### Manual Update Test
To test the update process:
```bash
sudo /var/www/localhost/htdocs/scripts/auto-update.sh
```

### Monitoring Updates
View update logs:
```bash
tail -f /var/log/scrive-aci-update.log
```

## 🔒 Security Considerations

### 1. OpenEMR Integration (Hidden)
- All OpenEMR references are hidden from user-facing UI
- Integration remains functional in backend
- Users see only "Scrive ACI" branding

### 2. Database Security
- Organizational MA numbers restricted to admin role
- Client MA numbers visible based on role permissions
- All actions logged in security audit table

### 3. File Permissions
After deployment, ensure proper permissions:
```bash
chown -R apache:apache /var/www/localhost/htdocs
find /var/www/localhost/htdocs -type f -exec chmod 644 {} \;
find /var/www/localhost/htdocs -type d -exec chmod 755 {} \;
chmod 600 /var/www/localhost/htdocs/.env
chmod 600 /var/www/localhost/htdocs/src/config.php
```

## 📊 Database Structure

### Core Tables Created:
- `autism_programs` - AW, DDA, CFC, CS programs
- `autism_service_types` - IISS, TI, Respite, FC services
- `autism_staff_members` - Employee records
- `autism_staff_roles` - Permission levels
- `autism_clients` - Client information
- `autism_treatment_plans` - Treatment planning
- `autism_session_notes` - Service documentation
- `autism_security_log` - Audit trail

### Role Hierarchy:
1. **Administrator** (Level 5) - Full access including org MA numbers
2. **Supervisor** (Level 4) - Management and billing access
3. **Case Manager** (Level 3) - Treatment planning
4. **Direct Care Staff** (Level 2) - Session documentation
5. **Technician** (Level 1) - Basic documentation only

## 🌐 Environment Configuration

### Production .env File
```env
# Application
APP_NAME="Scrive ACI"
APP_ENV=production
APP_URL=https://aci.dydact.io

# Database
DB_HOST=localhost
DB_NAME=scrive_aci
DB_USER=scrive_user
DB_PASS=[secure_password]

# Security
SESSION_SECURE=true
SESSION_HTTPONLY=true
```

## 📋 Post-Deployment Checklist

- [ ] Run `setup_production.php` and save admin credentials
- [ ] Change admin password after first login
- [ ] Configure SSL certificate for HTTPS
- [ ] Set up automated backups for database
- [ ] Configure cron job for weekly updates
- [ ] Test all navigation links
- [ ] Verify role-based access control
- [ ] Enable error logging to file (not display)
- [ ] Configure email settings for notifications
- [ ] Review and update firewall rules

## 🆘 Troubleshooting

### Common Issues:

1. **Login page not loading**
   - Check .htaccess is being processed
   - Verify Apache mod_rewrite is enabled
   - Check error logs: `/var/log/apache2/error.log`

2. **Database connection errors**
   - Verify MariaDB is running
   - Check credentials in `.env` file
   - Test connection: `mysql -u [user] -p[pass] [database]`

3. **Auto-update failures**
   - Check git repository access
   - Verify file permissions
   - Review logs: `/var/log/scrive-aci-update.log`

## 📞 Support

For technical support or questions:
- **Email**: admin@acgcares.com
- **Phone**: 301-408-0100

---
**Last Updated**: December 2024
**Version**: 1.0.0 