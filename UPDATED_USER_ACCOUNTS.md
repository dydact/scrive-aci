# Updated ACG User Accounts

## System Access Information

### Domain Access
- **Local Access**: http://localhost:8080 or https://localhost:8443
- **Domain Access**: http://aci.dydact.io (requires DNS or hosts file configuration)

### Real User Accounts Created

#### 1. Supreme Administrator (Level 6)
- **Email**: frank@acgcares.com
- **Password**: Supreme2024!
- **Access**: Complete system control, database management, all technical features
- **Special**: Only user with Level 6 access

#### 2. Chief Executive Officer (Level 5)
- **Email**: mary.emah@acgcares.com
- **Password**: CEO2024!
- **Access**: All business features, reports, financial overview (no technical settings)

#### 3. Executive (Level 5)
- **Email**: drukpeh@duck.com
- **Password**: Executive2024!
- **Access**: Same as CEO - all business features without technical access

#### 4. Human Resources Officer (Level 4)
- **Email**: amanda.georgi@acgcares.com
- **Password**: HR2024!
- **Access**: Employee management, payroll access, staff records

#### 5. Site Supervisor / Clinical Lead (Level 4)
- **Email**: edwin.recto@acgcares.com
- **Password**: Clinical2024!
- **Access**: Clinical oversight, waiver administration, note approvals

#### 6. Billing Administrator - Pam (Level 4)
- **Email**: pam.pastor@acgcares.com
- **Password**: Billing2024!
- **Access**: Billing management, claims processing, payment posting

#### 7. Billing Administrator - Yanika (Level 4)
- **Email**: yanika.crosse@acgcares.com
- **Password**: Billing2024!
- **Access**: Billing management, claims processing, payment posting

#### 8. System Administrator (Level 5)
- **Email**: alvin.ukpeh@acgcares.com
- **Password**: SysAdmin2024!
- **Access**: System administration (less than Frank), user management, technical support

## Email Configuration Note

The system email domain has been updated to @acgcares.com. Email functionality is currently disabled (`MAIL_ENABLED = false`) until the webhost mail server is configured.

## Access Levels Summary

- **Level 6**: Supreme Admin (Frank only) - Full system control
- **Level 5**: Admin (CEO, Executive, System Admin) - Administrative access
- **Level 4**: Supervisor (HR, Clinical, Billing) - Department-specific management
- **Level 3**: Case Manager - Client and treatment plan management
- **Level 2**: Direct Support Professional - Session documentation, time clock
- **Level 1**: Technician - Read-only access

## Special Permissions

Each user has been granted role-specific permissions:

- **Frank**: supreme_admin, all_access, system_config, database_management
- **Mary/Dr. Ukpeh**: executive_dashboard, financial_overview, reports_all
- **Amanda**: hr_management, employee_records, payroll_access
- **Edwin**: clinical_oversight, waiver_administration, note_approval
- **Pam/Yanika**: billing_management, hours_entry, claims_processing
- **Alvin**: system_admin, user_management, technical_support

## Testing the Accounts

1. Go to http://localhost:8080
2. Login with any of the accounts above
3. Each user will see a customized dashboard based on their role
4. Frank will have access to all features including database management
5. Mary and Dr. Ukpeh will see executive dashboards without technical options
6. Department heads will see their specific management tools

## Security Notes

- All passwords should be changed on first login in production
- Session timeout is set to 1 hour
- Failed login attempts are tracked
- All actions are logged in the audit trail