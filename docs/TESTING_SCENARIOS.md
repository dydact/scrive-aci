# Autism Waiver Management System - Testing Scenarios

## Overview
This document provides comprehensive testing scenarios for validating the Autism Waiver Management System functionality before and after deployment.

## 1. Authentication & Access Control Testing

### Test Case 1.1: Login Functionality
**Objective**: Verify user authentication works correctly

**Steps**:
1. Navigate to `/autism_waiver_app/login.php`
2. Enter valid credentials
3. Click "Login"

**Expected Result**: 
- User redirected to dashboard
- Session created with appropriate access level
- User info displayed in header

### Test Case 1.2: Role-Based Access
**Objective**: Verify access restrictions by role

**Test Users**:
```
DSP (Level 2): Can create session notes, view assigned clients
Case Manager (Level 3): Can manage treatment plans, view all clients
Supervisor (Level 4): Can manage staff, run reports
Admin (Level 5): Full system access
```

**Steps**:
1. Login as each user type
2. Attempt to access restricted areas
3. Verify appropriate menu items displayed

**Expected Result**: Users only see/access features for their role

## 2. Clinical Documentation Testing

### Test Case 2.1: Create IISS Session Note
**Objective**: Verify session note creation and billing integration

**Steps**:
1. Login as DSP user
2. Navigate to IISS Session Note
3. Select client "John Doe"
4. Enter session details:
   - Date: Today
   - Time: 2:00 PM - 4:00 PM
   - Location: Client's Home
   - Select 2 goals to work on
   - Rate progress (3/5 for first, 4/5 for second)
   - Enter activities and response
5. Submit note

**Expected Result**:
- Note saved successfully
- Billing entry created for 120 minutes
- Goal progress recorded
- Redirected to client detail page

### Test Case 2.2: Treatment Plan Management
**Objective**: Verify treatment plan creation with goals

**Steps**:
1. Login as Case Manager
2. Navigate to Treatment Plan Manager
3. Select client "Jane Smith"
4. Create new plan:
   - Name: "2024 Annual Treatment Plan"
   - Type: Annual Review
   - Add 3 goals (Communication, Social Skills, Daily Living)
   - Set baselines and target criteria
5. Save plan

**Expected Result**:
- Plan created with active status
- Goals saved with proper categories
- Plan appears in client record

### Test Case 2.3: Goal Progress Tracking
**Objective**: Verify progress visualization

**Steps**:
1. Create multiple session notes with goal progress
2. View treatment plan
3. Check Progress Tracking tab

**Expected Result**:
- Chart displays average ratings
- Progress entries listed chronologically
- Percentage calculations correct

## 3. Scheduling Module Testing

### Test Case 3.1: Create Single Appointment
**Objective**: Verify appointment scheduling

**Steps**:
1. Navigate to Schedule Manager
2. Click "New Appointment"
3. Fill in:
   - Client: John Doe
   - Service: IISS
   - Date: Tomorrow
   - Time: 10:00 AM - 12:00 PM
   - Location: ACI Center
4. Create appointment

**Expected Result**:
- Appointment appears on calendar
- Correct duration calculated
- Status shows as "scheduled"

### Test Case 3.2: Recurring Schedule Template
**Objective**: Verify recurring appointments

**Test via SQL**:
```sql
-- Create template
INSERT INTO autism_schedule_templates 
(template_name, client_id, staff_id, service_type_id, day_of_week, start_time, end_time, location, effective_date)
VALUES ('John Weekly IISS', 1, 1, 1, 'monday', '14:00:00', '16:00:00', 'Home', CURDATE());

-- Generate appointments
CALL sp_generate_recurring_appointments(LAST_INSERT_ID(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH));
```

**Expected Result**:
- 4-5 Monday appointments created
- All linked to template
- No duplicate appointments

### Test Case 3.3: Appointment Status Updates
**Objective**: Verify appointment workflow

**Steps**:
1. View scheduled appointment
2. Click appointment to see details
3. Mark as "Completed"
4. Create another and mark as "No Show"
5. Cancel an appointment with reason

**Expected Result**:
- Status updates reflected on calendar
- Cancelled appointments show reason
- Statistics update accordingly

## 4. Financial Module Testing

### Test Case 4.1: Insurance Information
**Objective**: Verify insurance data management

**Test via SQL**:
```sql
-- Add insurance for client
INSERT INTO autism_insurance_info 
(client_id, insurance_type, payer_name, policy_number, effective_date)
VALUES (1, 'primary', 'Maryland Medicaid', 'MA123456789', '2024-01-01');
```

### Test Case 4.2: Prior Authorization
**Objective**: Verify auth tracking

**Test via SQL**:
```sql
-- Add prior auth
INSERT INTO autism_prior_authorizations
(client_id, insurance_id, auth_number, service_type_id, start_date, end_date, authorized_units, status)
VALUES (1, 1, 'AUTH2024001', 1, '2024-01-01', '2024-12-31', 1040, 'approved');
```

### Test Case 4.3: Claim Creation
**Objective**: Verify claim generation from billing entries

**Steps**:
1. Ensure billing entries exist from session notes
2. Create claim via SQL:

```sql
-- Create claim
INSERT INTO autism_claims
(claim_number, client_id, insurance_id, service_date_from, service_date_to, billed_amount, status)
VALUES ('CLM2024001', 1, 1, '2024-01-01', '2024-01-31', 2500.00, 'ready');
```

## 5. Integration Testing

### Test Case 5.1: Session to Billing Flow
**Objective**: Verify complete workflow from session to billing

**Steps**:
1. Create IISS session note (2 hours)
2. Check billing_entries table
3. Verify entry created with correct:
   - Employee ID
   - Client ID
   - Session note ID
   - Minutes (120)
   - Status (pending)

### Test Case 5.2: Time Clock Integration
**Objective**: Verify time entries link to billing

**Test Query**:
```sql
SELECT 
    e.clock_in_time,
    e.clock_out_time,
    b.total_minutes as billed_minutes,
    b.billable_minutes
FROM autism_employee_time_clock e
LEFT JOIN autism_billing_entries b 
    ON e.employee_id = b.employee_id 
    AND DATE(e.clock_in_time) = b.billing_date
WHERE e.employee_id = 1;
```

## 6. Performance Testing

### Test Case 6.1: Calendar Load Time
**Objective**: Ensure calendar loads quickly with many appointments

**Setup**:
- Create 500+ appointments across multiple staff
- Load schedule manager

**Expected Result**: Page loads in < 3 seconds

### Test Case 6.2: Report Generation
**Objective**: Verify reports handle large datasets

**Test Query**:
```sql
-- Aging report with 1000+ claims
SELECT * FROM v_aging_report LIMIT 1000;
```

**Expected Result**: Query completes in < 5 seconds

## 7. Security Testing

### Test Case 7.1: SQL Injection Prevention
**Objective**: Verify prepared statements prevent injection

**Steps**:
1. In login form, try username: `admin' OR '1'='1`
2. In client search, try: `'; DROP TABLE autism_clients; --`

**Expected Result**: 
- Login fails with invalid credentials
- Search returns no results
- No database errors

### Test Case 7.2: XSS Prevention
**Objective**: Verify output escaping

**Steps**:
1. Create client with name: `<script>alert('XSS')</script>`
2. Add session note with activities: `<img src=x onerror=alert('XSS')>`

**Expected Result**: 
- Text displayed as-is without execution
- No JavaScript alerts shown

### Test Case 7.3: Session Security
**Objective**: Verify session timeout and security

**Steps**:
1. Login and note session ID
2. Wait 61 minutes (or modify session.gc_maxlifetime)
3. Try to access protected page

**Expected Result**: Redirected to login

## 8. Data Validation Testing

### Test Case 8.1: Required Fields
**Objective**: Verify form validation

**Steps**:
1. Try submitting IISS note without:
   - Client selection
   - Time entries
   - Activities
   - Goals selected

**Expected Result**: Form validation prevents submission

### Test Case 8.2: Business Rules
**Objective**: Verify business logic

**Tests**:
- End time before start time (should fail)
- Overlapping appointments (should warn)
- Billing amount exceeds authorization (should flag)

## 9. User Experience Testing

### Test Case 9.1: Mobile Responsiveness
**Objective**: Verify mobile usability

**Steps**:
1. Access each module on mobile device
2. Test form inputs
3. Verify calendar display

**Expected Result**: All features usable on mobile

### Test Case 9.2: Print Functionality
**Objective**: Verify printable reports

**Steps**:
1. View treatment plan
2. Print preview
3. Check formatting

**Expected Result**: Clean, readable print layout

## 10. Error Handling Testing

### Test Case 10.1: Database Connection Failure
**Objective**: Verify graceful error handling

**Steps**:
1. Temporarily stop database service
2. Try to access application

**Expected Result**: User-friendly error message

### Test Case 10.2: Missing Data Handling
**Objective**: Verify null data handling

**Steps**:
1. Create appointment without staff assignment
2. View appointment details

**Expected Result**: Shows "Unassigned" instead of error

## Test Data Setup Script

```sql
-- Create test clients
INSERT INTO autism_clients (first_name, last_name, ma_number, date_of_birth, status)
VALUES 
('John', 'Doe', 'MA987654321', '2010-05-15', 'active'),
('Jane', 'Smith', 'MA123456789', '2012-08-22', 'active'),
('Test', 'Client', 'MA555555555', '2015-01-01', 'active');

-- Create test staff
INSERT INTO autism_staff_members (user_id, employee_id, first_name, last_name, role, department, status)
VALUES
(2, 'EMP001', 'Sarah', 'Johnson', 'DSP', 'Direct Care', 'active'),
(3, 'EMP002', 'Michael', 'Brown', 'Case Manager', 'Clinical', 'active');

-- Create test service types
INSERT INTO autism_service_types (service_name, service_code, billing_code, billing_rate, is_active)
VALUES 
('IISS', 'IISS', 'H2019', 25.00, 1),
('Therapeutic Integration', 'TI', 'H2019TI', 20.00, 1);
```

## Automated Test Checklist

```bash
#!/bin/bash
# Quick test verification script

echo "Running Autism Waiver System Tests..."

# Test database connectivity
mysql -u $MARIADB_USER -p$MARIADB_PASSWORD -e "SELECT COUNT(*) FROM autism_clients;" iris

# Test PHP syntax
find autism_waiver_app -name "*.php" -exec php -l {} \;

# Check for required tables
TABLES="autism_clients autism_staff_members autism_iiss_notes autism_treatment_plans autism_appointments"
for table in $TABLES; do
    echo "Checking table: $table"
    mysql -u $MARIADB_USER -p$MARIADB_PASSWORD -e "SELECT COUNT(*) FROM $table;" iris
done

echo "Basic tests completed!"
```

---

Version: 1.0
Last Updated: December 2024