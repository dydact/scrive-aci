# Billing and Time Integration Documentation

## Overview

The Scrive ACI system now has a comprehensive billing and time tracking integration that connects employee time collection with both payroll processing and client billing.

## System Architecture

### 1. Time Collection Flow

```
Employee Clock In/Out → Time Clock Table → Payroll Aggregation
                                        ↓
                                   Session Notes → Billing Entries → Client Billing
```

### 2. Key Components

#### Time Clock System (`autism_time_clock`)
- **Purpose**: Track all employee work hours
- **Features**:
  - Clock in/out with GPS location tracking
  - Automatic duration calculation
  - Billable vs non-billable time tracking
  - Activity type classification (direct service, documentation, admin, etc.)
  - Links to session notes when applicable

#### Billing Entries (`autism_billing_entries`)
- **Purpose**: Convert completed sessions into billable units
- **Features**:
  - Automatic generation from session notes
  - Service-specific billing codes and rates
  - Unit calculation (15-min, 30-min, hourly)
  - Approval workflow (draft → pending → approved → billed → paid)
  - Links to both time clock and session notes

#### Payroll Summary (`autism_payroll_summary`)
- **Purpose**: Aggregate hours for payroll processing
- **Features**:
  - Pay period aggregation
  - Regular vs overtime calculation
  - Billable vs non-billable hours
  - QuickBooks integration ready

## Integration Points

### 1. Session Documentation → Billing

When a session note is created:
1. Time clock entry is linked via trigger
2. Billing entry is generated with appropriate units
3. MA number and program type are captured
4. Billing amount calculated based on service type

### 2. Time Clock → Payroll

Time clock entries aggregate to show:
- Total hours worked per employee
- Billable vs non-billable breakdown
- Overtime calculations (>40 hrs/week)
- Activity type distribution

### 3. Billing Dashboard Integration

The billing dashboard (`billing_dashboard.php`) now shows:
- Revenue by employee
- Units billed by service type
- Pending approvals
- Payment status tracking

## Key Tables and Views

### Tables Created
1. `autism_billing_entries` - Main billing records
2. `autism_payroll_summary` - Payroll period summaries
3. `autism_time_clock` (enhanced) - Added session linkage

### Views Created
1. `v_payroll_hours` - Daily hours aggregation
2. `v_billing_summary` - Billing aggregation by employee/date

### Stored Procedure
- `sp_generate_billing_entries` - Creates billing entries from session notes

## Usage Instructions

### For Employees

1. **Clock In/Out**:
   - Use mobile portal or employee portal
   - System tracks location automatically
   - Shows total hours upon clock out

2. **Document Sessions**:
   - Complete session notes as usual
   - System automatically links to time clock
   - Billing entries created automatically

### For Supervisors/Administrators

1. **Run Payroll Report**:
   ```
   Navigate to: /autism_waiver_app/payroll_report.php
   ```
   - Select date range
   - View hours aggregation
   - Export to CSV for processing
   - Identify discrepancies between clock and session hours

2. **Generate Billing Entries**:
   - From payroll report, click "Generate Billing"
   - System creates pending entries from completed sessions
   - Review and approve in billing dashboard

3. **Monitor Discrepancies**:
   - Report highlights when clock hours ≠ session hours
   - Allows investigation of missing documentation
   - Ensures accurate billing and payroll

## Billing Codes and Rates

| Service Code | Description | Billing Unit | Rate |
|-------------|-------------|--------------|------|
| T1019 | IISS - Individual Support | 15 min | $12.00 |
| H2014 | Therapeutic Integration | 15 min | $15.00 |
| T1005 | Respite Care | Hour | $25.00 |
| H0045 | Community Support | 15 min | $11.00 |
| T1016 | Service Coordination | Month | $150.00 |

## Compliance Features

1. **Audit Trail**: All entries timestamped with created/updated tracking
2. **MA Number Security**: Role-based visibility of Medicaid numbers
3. **Approval Workflow**: Multi-level approval for billing entries
4. **Time Accuracy**: GPS tracking and clock restrictions

## Deployment Steps

1. **Run Database Updates**:
   ```bash
   php apply_billing_integration.php
   ```

2. **Configure Service Types**:
   - Verify billing codes match Maryland Medicaid
   - Update rates as needed

3. **Test Integration**:
   - Create test clock entry
   - Document test session
   - Verify billing entry generation
   - Run payroll report

## Future Enhancements

1. **QuickBooks Integration**:
   - Direct export of payroll summaries
   - Automatic invoice generation
   - Payment tracking

2. **Real-time EVS**:
   - Verify eligibility before billing
   - Automatic claim submission
   - Rejection management

3. **Mobile Enhancements**:
   - Offline time tracking
   - Photo documentation
   - Digital signatures

## Troubleshooting

### Common Issues

1. **Missing Billing Entries**:
   - Ensure session notes have status = 'completed'
   - Check service type has billing code
   - Run stored procedure manually

2. **Time Discrepancies**:
   - Review activity types in time clock
   - Check for missing clock out entries
   - Verify session durations

3. **Payroll Report Errors**:
   - Ensure all tables created successfully
   - Check employee hourly rates set
   - Verify date range parameters

## Support

For billing integration issues:
- Technical: Review error logs
- Business Logic: Check service type configuration
- Compliance: Ensure MA numbers and codes correct

---

*Integration Version: 1.0*
*Last Updated: [Current Date]*