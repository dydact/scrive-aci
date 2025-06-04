# Missing Routes and Pages Analysis

## Summary
This analysis identifies missing route targets, broken links, and orphaned pages in the Scrive ACI project.

## 1. CRITICAL MISSING FILES (Routes exist but files don't)

### Staff Portal Routes
- **❌ `/staff/notes/edit/{id}`** → `autism_waiver_app/edit_session.php`
  - Purpose: Edit existing session notes
  - Impact: Staff cannot edit submitted notes
  
- **❌ `/staff/schedule`** → `autism_waiver_app/employee_schedule.php`
  - Purpose: View employee schedules
  - Impact: Staff cannot view their schedules
  
- **❌ `/staff/hours`** → `autism_waiver_app/my_hours.php`
  - Purpose: View logged hours/timesheets
  - Impact: Staff cannot review their hours

### Case Manager Routes
- **❌ `/case-manager/approvals`** → `autism_waiver_app/note_approvals.php`
  - Purpose: Approve session notes
  - Impact: Case managers cannot approve notes

### Supervisor Portal Routes
- **❌ `/supervisor`** → `autism_waiver_app/supervisor_portal.php`
  - Purpose: Main supervisor dashboard
  - Impact: Supervisors have no portal access
  
- **❌ `/supervisor/approvals`** → `autism_waiver_app/supervisor_approvals.php`
  - Purpose: Approve staff actions
  - Impact: Approval workflow broken
  
- **❌ `/supervisor/reports`** → `autism_waiver_app/supervisor_reports.php`
  - Purpose: View supervisor-level reports
  - Impact: No supervisor reporting

### Reporting Routes
- **❌ `/reports/billing`** → `autism_waiver_app/billing_reports.php`
  - Purpose: Billing reports and analytics
  - Impact: No billing reporting functionality
  
- **❌ `/reports/clinical`** → `autism_waiver_app/clinical_reports.php`
  - Purpose: Clinical reports and outcomes
  - Impact: No clinical reporting

### Help System Routes
- **❌ `/help/guide`** → `pages/public/help_guide.php`
  - Purpose: User guide documentation
  - Impact: No user documentation
  
- **❌ `/help/training`** → `pages/public/training.php`
  - Purpose: Training materials
  - Impact: No training resources

## 2. EXISTING FILES WITHOUT ROUTES

### Authentication/Access Files
- ✅ `autism_waiver_app/logout.php` - Has route at `/logout`
- ✅ `autism_waiver_app/simple_login.php` - Alternative login (no route needed)

### Utility Files (Don't need public routes)
- `autism_waiver_app/check_existing_clients.php` - Database utility
- `autism_waiver_app/check_patient_data.php` - Data verification
- `autism_waiver_app/process_application.php` - Form processor

### Mobile Portal Files
- `autism_waiver_app/mobile_employee_portal_redirect.php` - Redirect handler
- `autism_waiver_app/mobile_employee_portal_production.php` - Production version

## 3. DUPLICATE FILES (Need Cleanup)
Found 40+ files with "2" suffix indicating duplicates:
- `add_client 2.php`, `billing_dashboard 2.php`, etc.
- These appear to be backup copies that should be removed

## 4. ROUTE DUPLICATES/CONFLICTS
- ✅ `payroll_report.php` has 2 routes (intentional):
  - `/billing/payroll`
  - `/reports/payroll`
  
- ✅ `employee_portal.php` correctly mapped to `/staff/clients`

## 5. RECOMMENDATIONS

### Immediate Actions Required:
1. **Create missing critical files** for staff/supervisor functionality
2. **Implement help system files** for user documentation
3. **Clean up duplicate files** with "2" suffix
4. **Create placeholder pages** for missing routes to prevent 404 errors

### Priority Order:
1. Staff portal missing pages (edit_session, employee_schedule, my_hours)
2. Supervisor portal pages (all missing)
3. Reporting pages (billing_reports, clinical_reports)
4. Case manager approval page
5. Help/training pages

### Security Consideration:
All missing pages should include proper authentication checks when created.