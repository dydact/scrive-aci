# Supervisor Approvals System Guide

## Overview

The Supervisor Approvals system provides a centralized interface for supervisors to review and approve various types of pending items in the autism waiver management system. This ensures quality control, compliance, and proper oversight of all services.

## Access Requirements

- **Minimum Access Level**: 4 (Supervisor) or 5 (Administrator)
- **URL**: `/supervisor_approvals` or `/supervisor_approvals.php`

## Features

### 1. Dashboard Statistics
The approvals page displays real-time statistics at the top:
- **Late Sessions**: Session notes submitted more than 48 hours after service
- **Session Notes**: All pending session note approvals
- **Time Entries**: Time clock entries with discrepancies
- **Schedule Changes**: Requested schedule modifications
- **Time Off**: Staff time off requests
- **Billing**: Billing adjustments and disputes

### 2. Approval Types

#### Session Notes
- Reviews completed session notes from direct care staff
- Highlights late submissions (>48 hours) in red
- Shows client name, service type, duration, and notes
- Ensures documentation compliance

#### Time Entries
- Flags entries with manual overrides
- Identifies discrepancies > 5 minutes between clock times and recorded hours
- Helps prevent time theft and ensures accurate payroll

#### Schedule Changes
- Reviews requests for schedule modifications
- Types: Reschedule, Cancel, Add, Swap
- Shows reason for change and affected parties

#### Time Off Requests
- Manages vacation, sick, personal, and other leave types
- Shows coverage arrangements if applicable
- Displays total days requested

#### Billing Adjustments
- Reviews disputed or adjusted billing entries
- Shows financial impact of changes
- Ensures billing accuracy before claim submission

### 3. Filtering Options
- **Approval Type**: Filter by specific type or view all
- **Staff Member**: Filter by individual staff
- **Date Range**: Set custom date ranges
- **Status**: View pending, approved, or rejected items

### 4. Bulk Actions
- **Select All**: Quick selection of all visible items
- **Bulk Approve**: Approve multiple items with optional comments
- **Bulk Reject**: Reject items with required reason
- **Individual Review**: Click eye icon to view full details

### 5. Audit Trail
All approval actions are logged with:
- Supervisor name and ID
- Timestamp of action
- Comments or rejection reasons
- IP address (for security)

## Workflow

### Approving Items
1. Review pending items in the list
2. Select items using checkboxes
3. Add optional comments in the approval field
4. Click "Approve Selected"
5. Confirmation message appears

### Rejecting Items
1. Select items to reject
2. Click "Reject Selected"
3. Enter required rejection reason
4. Confirm rejection
5. Staff member receives notification with reason

### Priority Review
Items are sorted with priorities:
1. Late session notes (red border)
2. Time discrepancies (yellow border)
3. Standard pending items

## Database Tables Required

### Core Tables
- `autism_session_notes` - Session documentation
- `autism_time_clock` - Time tracking entries
- `autism_schedule_changes` - Schedule modification requests
- `autism_time_off_requests` - Staff leave requests
- `autism_billing_entries` - Billing records
- `autism_audit_log` - Approval history

### Configuration
- `autism_approval_rules` - Customizable approval thresholds
- `autism_approval_notifications` - Notification settings

## Setup Instructions

1. **Database Setup**:
   ```sql
   -- Run the supervisor approval tables script
   mysql -u username -p database_name < sql/supervisor_approval_tables.sql
   ```

2. **File Deployment**:
   - Copy `autism_waiver_app/supervisor_approvals.php` to your web directory
   - Ensure proper permissions (644 for files)

3. **Navigation Update**:
   - The dashboard has been updated to show approval links for supervisors
   - Pending approval counts appear in dashboard statistics

## Compliance Features

### HIPAA Compliance
- All actions are logged for audit purposes
- Access restricted by role-based permissions
- Secure session management

### Medicaid Requirements
- Enforces 48-hour documentation rule
- Tracks billing accuracy
- Maintains approval chain of custody

## Troubleshooting

### Common Issues

1. **"No pending approvals" message**:
   - Check date range filters
   - Ensure items exist in pending status
   - Verify database connections

2. **Cannot approve/reject items**:
   - Verify user has supervisor access (level 4+)
   - Check if items are already processed
   - Ensure database tables exist

3. **Missing statistics**:
   - Run database migration script
   - Check for missing columns in tables
   - Verify proper indexes exist

### Performance Optimization
- Indexes are created on date and status columns
- Consider archiving old approvals after 90 days
- Use date filters to reduce query load

## Future Enhancements

1. **Email Notifications**: Send alerts for pending approvals
2. **Mobile App**: Approve items from mobile devices
3. **Auto-Approval Rules**: Configure automatic approvals for routine items
4. **Delegation**: Allow temporary approval delegation
5. **Analytics**: Approval turnaround time reports

## Support

For assistance with the Supervisor Approvals system:
1. Check system logs in `/var/log/`
2. Review audit trail for recent actions
3. Contact system administrator
4. Reference this guide for workflows

---

*Last Updated: January 2025*
*Version: 1.0*