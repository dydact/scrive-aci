# Claim Management System

## Overview
The Claim Management System is a comprehensive solution for handling Maryland Medicaid autism waiver claims. It provides end-to-end functionality from claim generation through payment posting.

## Features

### 1. Dashboard & Statistics
- Real-time claim statistics (pending, submitted, paid, denied)
- Total payment tracking
- Visual indicators for claim status
- Quick access to all claim management functions

### 2. Claim Generation
- **Batch Generation**: Generate claims from all unbilled sessions
- **Date Range Selection**: Generate claims for specific periods
- **Client Filtering**: Generate claims for specific clients
- **Authorization Validation**: Automatic checking of authorization requirements
- **Timely Filing Validation**: Ensures claims meet Maryland's 95-day filing limit
- **Smart Grouping**: Groups sessions by month and service type per Maryland requirements

### 3. Claim Validation
- **Comprehensive Checks**:
  - Required field validation
  - Medicaid ID format (9 alphanumeric characters)
  - Valid autism waiver service codes
  - Timely filing limits
  - Authorization requirements
  - Client age eligibility
  - Duplicate claim detection
- **Batch Validation**: Validate all pending claims at once
- **Auto-fix Options**: Attempt to fix common errors automatically

### 4. Claim Submission
- **Individual Submission**: Submit single claims
- **Batch Submission**: Submit all validated claims
- **837P Format**: Generates proper 837 Professional format
- **Clearinghouse Integration**: Ready for real clearinghouse API integration
- **Submission Tracking**: Track clearinghouse IDs and responses

### 5. Claim Management
- **Status Tracking**: Monitor claim lifecycle (pending → submitted → paid/denied)
- **Claim Editing**: Edit pending claims before submission
- **Denial Management**: View denial reasons and codes
- **Resubmission**: Correct and resubmit denied claims
- **Activity Logging**: Complete audit trail of all claim activities

### 6. Reporting & Export
- **Multiple Export Formats**:
  - CSV for spreadsheet analysis
  - 837 format for clearinghouse submission
  - Excel format (coming soon)
- **Flexible Filtering**: Export by status, date range, client

## Maryland Medicaid Specific Features

### Service Codes
The system supports all Maryland autism waiver service codes:
- **W1727**: Intensive Individual Support Services
- **W1728**: Therapeutic Integration
- **W7061**: Respite Care
- **W7060**: Family Consultation
- **W7069**: Adult Life Planning
- **W7235**: Environmental Accessibility Adaptations

### Business Rules
- 95-day timely filing limit
- Authorization required for W1727 and W1728
- Age limit warnings for clients 21+
- Proper unit calculations (15-minute increments)
- Monthly grouping for claims

## File Structure

```
/autism_waiver_app/billing/
├── claim_management.php        # Main interface
├── generate_claims.php         # Claim generation endpoint
├── validate_claim.php          # Validation endpoint
├── submit_claims.php           # Submission endpoint
├── get_claim_details.php       # Claim detail viewer
├── update_claim.php            # Claim editing endpoint
├── export_claims.php           # Export functionality
├── validation_modal.php        # Validation UI component
└── setup_claim_management.php  # Database setup script
```

## Database Tables

### Core Tables
- `billing_claims`: Main claims table
- `claim_activity_log`: Audit trail for all claim activities
- `claim_batches`: Batch submission tracking
- `organization_settings`: Organization/provider information

### Supporting Tables
- `authorization_usage_log`: Track authorization utilization
- `remittance_advice`: Payment posting header
- `remittance_details`: Payment posting details

## Setup Instructions

1. **Run Database Setup**:
   ```
   Navigate to: /autism_waiver_app/billing/setup_claim_management.php
   ```

2. **Configure Organization Settings**:
   - Update organization NPI, Tax ID, and address information
   - Configure clearinghouse credentials (when available)

3. **Set Service Rates**:
   - Verify service codes and rates match current Maryland Medicaid rates

## Usage Workflow

1. **Generate Claims**:
   - Click "Generate New Claims"
   - Select date range and filters
   - Review unbilled sessions
   - Generate claims

2. **Validate Claims**:
   - Click "Validate Claims"
   - Review validation results
   - Fix any errors identified

3. **Submit Claims**:
   - Use "Batch Submit" for validated claims
   - Monitor submission status
   - Track clearinghouse responses

4. **Manage Denials**:
   - Review denied claims
   - Make necessary corrections
   - Resubmit corrected claims

5. **Post Payments**:
   - Import remittance advice
   - Auto-match payments to claims
   - Post payments in batch

## Security Features

- Role-based access control (admin, billing_specialist)
- Complete audit trail
- Session-based authentication
- Input validation and sanitization

## Future Enhancements

- Direct clearinghouse API integration
- Electronic remittance advice (ERA) import
- Automated denial management workflows
- Real-time eligibility verification
- Secondary billing support

## Support

For issues or questions:
- Check claim activity logs for detailed history
- Review validation errors for specific issues
- Contact system administrator for access issues