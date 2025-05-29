# Initial Login Guide - Autism Waiver Management System

## Quick Start - Login Credentials

### Master Admin Account
- **Username**: `admin`
- **Password**: `AdminPass123!`
- **Access Level**: Full System Administrator (Level 5)

### Test Accounts
| Role | Username | Password | Access Level |
|------|----------|----------|--------------|
| DSP (Direct Support) | `dsp_test` | `TestPass123!` | Level 2 |
| Case Manager | `cm_test` | `TestPass123!` | Level 3 |
| Supervisor | `supervisor_test` | `TestPass123!` | Level 4 |

## First Time Setup

### Step 1: Create Admin User
After deploying the database schemas, run this command to create the admin user:

```bash
mysql -u root -p iris < sql/create_admin_user.sql
```

### Step 2: Access the Login Page
Navigate to: `https://aci.dydact.io/autism_waiver_app/login.php`

### Step 3: Login as Admin
1. Enter username: `admin`
2. Enter password: `AdminPass123!`
3. Click "Login"

### Step 4: Change Default Password (Recommended)
For security, you should immediately change the default admin password. Currently, this would need to be done via SQL:

```sql
-- Generate new password hash (example for 'YourNewSecurePassword123!')
UPDATE autism_users 
SET password = '$2y$10$[your-bcrypt-hash-here]'
WHERE username = 'admin';
```

To generate a bcrypt hash for a new password, you can use PHP:
```php
<?php
echo password_hash('YourNewSecurePassword123!', PASSWORD_BCRYPT);
?>
```

## Access Levels Explained

### Level 1 - Read Only
- View client information
- No data entry capabilities

### Level 2 - DSP (Direct Support Professional)
- Create IISS session notes
- View assigned clients only
- Clock in/out
- View own schedule

### Level 3 - Case Manager
- All Level 2 permissions
- Create/edit treatment plans
- Manage goals and objectives
- View all clients
- Run client reports

### Level 4 - Supervisor
- All Level 3 permissions
- Manage staff schedules
- View all staff members
- Run administrative reports
- Approve timesheets

### Level 5 - Administrator
- Full system access
- User management
- System configuration
- Financial management
- All reports and features

## What Each User Can Do

### DSP User (`dsp_test`)
After login, can access:
- IISS Session Notes (for assigned clients)
- View assigned client details
- Clock in/out
- View personal schedule

### Case Manager (`cm_test`)
After login, can access:
- Everything DSP can access
- Treatment Plan Manager
- Goal progress tracking
- All clients (not just assigned)
- Clinical reports

### Supervisor (`supervisor_test`)
After login, can access:
- Everything Case Manager can access
- Schedule Manager (all staff)
- Staff management features
- Administrative reports
- Billing oversight

### Administrator (`admin`)
After login, can access:
- All system features
- User management
- System settings
- Financial/billing configuration
- Database maintenance

## Navigation After Login

Once logged in, users will see the main dashboard with menu items based on their access level:

1. **Dashboard** - Overview and quick stats
2. **Clients** - Client management (based on access)
3. **Clinical** - Session notes, treatment plans
4. **Scheduling** - Calendar and appointments
5. **Billing** - Financial management (admin only)
6. **Reports** - Various reports (based on access)

## Troubleshooting Login Issues

### "Invalid Credentials" Error
- Verify username is typed correctly (case-sensitive)
- Check password (case-sensitive)
- Ensure user exists in database

### "Access Denied" After Login
- User's access_level may be insufficient
- Check if user is marked as active

### Forgot Password
Currently, password resets must be done by an administrator through database access.

### Session Timeout
Sessions expire after 60 minutes of inactivity. Users will need to login again.

## Security Best Practices

1. **Change default passwords immediately**
2. **Use strong passwords** (minimum 8 characters, mix of letters, numbers, symbols)
3. **Don't share login credentials**
4. **Log out when finished** (especially on shared computers)
5. **Report suspicious activity** to administrators

## Creating Additional Users

As an admin, you can create new users via SQL:

```sql
-- Example: Create a new DSP user
INSERT INTO autism_users (username, password, email, first_name, last_name, access_level, user_type, is_active)
VALUES (
    'new_dsp',
    '$2y$10$YW5QpC5zYXczFPmHkYcxHOzwGmzpHyBcQrFymLh1FSW/7N11jEU2a', -- AdminPass123!
    'newdsp@aci.com',
    'New',
    'Staff',
    2,
    'staff',
    1
);

-- Don't forget to create corresponding staff member entry
INSERT INTO autism_staff_members (user_id, employee_id, first_name, last_name, email, role, department, status)
VALUES (LAST_INSERT_ID(), 'DSP999', 'New', 'Staff', 'newdsp@aci.com', 'DSP', 'Direct Care', 'active');
```

---

**Important**: The default passwords provided are for initial testing only. They should be changed immediately in a production environment.