# Scrive ACI Testing Guide

## System is Running!

Access the application at:
- **Main URL**: http://localhost:8080
- **HTTPS**: https://localhost:8443 (self-signed cert)

## Test Credentials

### Admin Access (Full System)
- **Email**: admin@aci.com
- **Password**: Admin123!
- **Access**: All features including admin panel

### Supervisor Access
- **Email**: supervisor@aci.com
- **Password**: Super123!
- **Access**: Approvals, reports, staff management

### Case Manager Access
- **Email**: case@aci.com
- **Password**: Case123!
- **Access**: Treatment plans, client management

### Direct Care Staff Access
- **Email**: staff@aci.com
- **Password**: Staff123!
- **Access**: Session notes, time clock, schedule

### Technician Access
- **Email**: tech@aci.com
- **Password**: Tech123!
- **Access**: Read-only, basic documentation

## What to Test

### 1. Authentication & Access Control
- [x] Login with each role
- [x] Verify role-based menu items
- [x] Test logout functionality
- [x] Check session timeout (1 hour)

### 2. Staff Portal Features
- **Time Clock**
  - Clock in/out at `/staff/clock`
  - View time records
  - Mobile-responsive design
  
- **Session Documentation**
  - Create new session at `/staff/notes`
  - Auto-populated goals from treatment plans
  - Save draft functionality
  
- **Schedule View**
  - View assigned clients at `/staff/schedule`
  - Weekly calendar view

### 3. Client Management
- **Add Clients** at `/clients/add`
  - Required: Name, DOB, MA Number
  - Optional: Email, phone, address
  
- **View/Edit Clients** at `/clients`
  - Search by name or MA number
  - View session history
  - Edit client details (supervisor+)

### 4. Billing System
- **Main Dashboard** at `/billing`
  - Revenue statistics
  - Claims summary
  - Outstanding receivables
  
- **Claims Management** at `/billing/claims`
  - Create new claims
  - View claim status
  - Process denials
  
- **EDI Processing** at `/billing/edi`
  - Generate 837 files
  - Process 835 remittances
  - View transmission logs

### 5. Treatment Planning
- **Case Manager Portal** at `/case-manager`
  - Create treatment plans
  - Add goals and objectives
  - Monitor progress
  
- **Approval Workflow**
  - Submit notes for approval
  - Supervisor review queue
  - Audit trail

### 6. Reports & Analytics
- **Financial Reports** at `/reports/billing`
  - Revenue by service type
  - Staff productivity
  - Denial analysis
  
- **Clinical Reports** at `/reports/clinical`
  - Client progress
  - Goal achievement
  - Service utilization

### 7. Schedule Management
- **Main Scheduler** at `/schedule`
  - Drag-drop interface
  - Conflict detection
  - Staff availability

### 8. Admin Features (Admin Only)
- **User Management** at `/admin/users`
  - Add/edit users
  - Reset passwords
  - Change access levels
  
- **Organization Settings** at `/admin/organization`
  - Update company info
  - Configure service types
  - Set billing rates

## Test Data Available

### Pre-loaded Clients
1. John Doe (MA: MA123456)
2. Jane Smith (MA: MA789012)
3. Michael Johnson (MA: MA345678)
4. Sarah Williams (MA: MA901234)
5. Robert Brown (MA: MA567890)

### Pre-loaded Staff
1. Emily Johnson (DSP)
2. Michael Chen (DSP)
3. Sarah Williams (Case Manager)
4. David Thompson (Supervisor)

### Service Types
- Individual Intensive Support Services (IISS)
- Personal Support
- Respite Care
- Community Development Services
- Supported Employment

## Known Issues to Watch For

1. **Email Notifications**: Not configured (SMTP needed)
2. **EDI Submission**: Needs real clearinghouse credentials
3. **Payment Processing**: Manual only (no gateway)
4. **SSL Certificate**: Self-signed (browser warning expected)

## Quick Functionality Tests

### 1. Create a Session Note
1. Login as staff@aci.com
2. Go to `/staff/notes`
3. Select a client
4. Document a session
5. Submit for approval

### 2. Process Time Clock
1. Login as staff@aci.com
2. Go to `/staff/clock`
3. Clock in
4. Wait a minute
5. Clock out

### 3. Create a Billing Claim
1. Login as admin@aci.com
2. Go to `/billing/claims`
3. Click "New Claim"
4. Select client and service
5. Submit claim

### 4. View Reports
1. Login as supervisor@aci.com
2. Go to `/reports`
3. Check each report type
4. Export to PDF/Excel

## Performance Notes

- First load may be slow (Docker initialization)
- Database queries optimized for <100ms response
- Session timeout: 1 hour
- File upload limit: 10MB

## Mobile Testing

The system is mobile-responsive. Test on:
- iPhone/Android browsers
- Tablet portrait/landscape
- Desktop browser mobile view

## Next Development Steps

When you find the EDI clearinghouse endpoints, we'll need:
1. API endpoint URLs
2. Authentication credentials
3. Test vs Production flags
4. Supported transaction types
5. Response format specifications

## Support & Issues

Document any issues found during testing:
- Feature not working as expected
- UI/UX improvements needed
- Performance problems
- Data inconsistencies

The system is designed for Maryland Autism Waiver compliance and includes all required fields and workflows.