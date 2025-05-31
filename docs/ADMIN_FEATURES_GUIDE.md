# Administrator Features Guide

## Overview
The American Caregivers Inc (ACI) application now includes comprehensive administrator features for managing the organization, employees, and users.

## Accessing Admin Features

### Admin Panel Access
- **Requirement**: User must have access level 5 (Administrator)
- **Location**: Red "Admin Panel" button on main dashboard
- **URL**: `/src/admin_dashboard.php`

## Administrator Dashboard

The admin dashboard provides a centralized control panel with the following sections:

### 1. Employee Management (`/src/admin_employees.php`)
- **View all employees**: List of all staff members with status, role, and contact info
- **Add new employees**: Create employee records with:
  - Personal information (name, email, phone)
  - Role assignment (Direct Care Worker, RN, LPN, etc.)
  - Address details
  - Hourly rate
  - Active/inactive status
- **Edit employees**: Update existing employee information
- **Delete employees**: Remove employee records from the system

### 2. User Management (`/src/admin_users.php`)
- **Create user accounts**: Generate login credentials for system access
  - Auto-generated temporary passwords
  - Access level assignment (1-5)
  - Email notifications
- **Manage permissions**: Set access levels:
  - Level 1: Basic User
  - Level 2: Employee
  - Level 3: Supervisor
  - Level 4: Manager
  - Level 5: Administrator
- **Reset passwords**: Generate new temporary passwords for users
- **Deactivate accounts**: Disable user access without deletion

### 3. Organization Settings (`/src/admin_organization.php`)
- **Basic Information**:
  - Organization name
  - Physical address
  - Contact details
  - Website
- **Provider Information**:
  - Tax ID (EIN)
  - NPI Number
  - Medicaid Provider ID
- **Billing Contact**:
  - Dedicated billing contact person
  - Billing email and phone

### 4. Additional Admin Features (Coming Soon)
- **Reports & Analytics**: Performance metrics and insights
- **System Administration**: Backup, restore, and maintenance
- **Financial Management**: Claims and payment tracking
- **Schedule Management**: Employee scheduling system
- **Role Management**: Custom role creation

## Database Tables

### Organization Settings Table
```sql
CREATE TABLE autism_organization_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    org_name VARCHAR(255) NOT NULL,
    org_address VARCHAR(255),
    org_city VARCHAR(100),
    org_state VARCHAR(2),
    org_zip VARCHAR(10),
    org_phone VARCHAR(20),
    org_email VARCHAR(255),
    org_website VARCHAR(255),
    tax_id VARCHAR(20),
    npi_number VARCHAR(20),
    medicaid_provider_id VARCHAR(50),
    billing_contact_name VARCHAR(255),
    billing_contact_email VARCHAR(255),
    billing_contact_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Security Features
- All admin pages require access level 5
- Session-based authentication
- Protection against deleting own account
- Input validation and sanitization
- SQL injection prevention

## Best Practices
1. **Regular Updates**: Keep employee and user information current
2. **Access Control**: Only grant admin access to trusted personnel
3. **Password Management**: Share temporary passwords securely
4. **Data Backup**: Regular backups before major changes
5. **Audit Trail**: Monitor user activities (coming soon)

## Troubleshooting

### Common Issues
1. **"Access Denied" Error**: Ensure user has access level 5
2. **Database Errors**: Check database connectivity and table existence
3. **Missing Features**: Some linked pages may still be under development

### Support
For technical support or feature requests, contact the system administrator. 