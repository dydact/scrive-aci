# Denial Management System
## ACI Autism Waiver Program

### Overview
This comprehensive denial management system is specifically designed for the ACI Autism Waiver Program to track, manage, and resolve claim denials from Maryland Medicaid. The system focuses on reducing denials, improving collection rates, and streamlining the appeals process.

### Key Features

#### 🎯 Core Functionality
- **Comprehensive Denial Tracking** - Monitor all claim denials with detailed information
- **Maryland Medicaid Integration** - Pre-configured with MD-specific denial codes and requirements
- **Appeal Management** - Create, track, and manage appeals with automated workflows
- **Task Management** - Assign and track follow-up tasks with deadline monitoring
- **Document Management** - Attach and organize supporting documentation
- **Analytics Dashboard** - Detailed reporting and trend analysis

#### 📊 Dashboard Features
1. **Real-time Statistics**
   - Open denials count and total amount
   - Appeal success rates
   - Pending appeals tracking
   - Aging analysis (0-30, 31-60, 61-90, 90+ days)

2. **Denial Worklist**
   - Priority-based sorting
   - Filterable by status, assigned user, age
   - Quick action buttons for common tasks
   - Deadline alerts and overdue notifications

3. **Staff Productivity Metrics**
   - Denials worked vs. resolved
   - Average resolution time
   - Success rates by individual
   - Recovery amounts tracked

#### 🔍 Advanced Analytics
- **Denial Trends** - Monthly patterns and volume analysis
- **Reason Analysis** - Top denial codes with success rates
- **Provider Performance** - Denial rates by provider
- **Service Type Analysis** - Denial patterns by service
- **Preventable Denials** - Identification of avoidable denials
- **Recovery Tracking** - Financial impact measurement

### File Structure

```
autism_waiver_app/billing/
├── denial_management.php      # Main dashboard
├── appeal_claim.php          # Appeal creation and tracking
├── denial_analytics.php      # Analytics dashboard
├── denial_history.php        # Individual denial history view
├── update_denial.php         # Ajax endpoint for updates
├── bulk_denial_operations.php # Bulk operations handler
└── README_DENIAL_MANAGEMENT.md # This documentation

sql/
└── denial_management_tables.sql # Database schema

autism_waiver_app/
└── setup_denial_management.php # Setup script
```

### Database Schema

#### Main Tables
1. **claim_denials** - Core denial tracking
2. **claim_appeals** - Appeal management
3. **denial_activities** - Activity log and notes
4. **denial_tasks** - Follow-up task management
5. **denial_attachments** - Document storage
6. **appeal_attachments** - Appeal-specific documents
7. **denial_prevention_strategies** - Prevention guidance

#### Views and Analytics
- **denial_dashboard_stats** - Pre-calculated dashboard metrics
- **staff_denial_productivity** - Staff performance tracking

### Maryland Medicaid Denial Codes

The system includes all standard Maryland Medicaid denial codes:

| Code | Description | Prevention Strategy |
|------|-------------|-------------------|
| M01 | Missing/Invalid Prior Auth | Automated auth verification |
| M02 | Service Not Covered | Coverage verification |
| M03 | Duplicate Claim | Claim tracking system |
| M04 | Invalid Provider Number | Provider enrollment monitoring |
| M05 | Invalid Member ID | Real-time eligibility checking |
| M06 | Service Date Outside Coverage | Eligibility date verification |
| M07 | Invalid Procedure Code | Code validation system |
| M08 | Invalid Diagnosis Code | ICD-10 validation |
| M09 | Timely Filing Limit Exceeded | Submission tracking with alerts |
| M10 | Invalid Place of Service | POS code training |
| M11 | Invalid Modifier | Modifier validation |
| M12 | Service Limit Exceeded | Utilization monitoring |
| M13 | Invalid Units | Unit calculation validation |
| M14 | Missing Documentation | Documentation checklists |
| M15 | Invalid NPI | NPI verification |
| M16 | Service Requires Referral | Referral tracking |
| M17 | Invalid Rate Code | Rate schedule maintenance |
| M18 | Provider Not Enrolled | Enrollment status tracking |
| M19 | Invalid Service Date | Date validation |
| M20 | Coordination of Benefits Issue | Insurance verification |

### Installation & Setup

1. **Database Setup**
   ```bash
   # Run the setup script
   php autism_waiver_app/setup_denial_management.php
   ```

2. **File Permissions**
   ```bash
   # Ensure upload directories exist and are writable
   mkdir -p autism_waiver_app/uploads/denials
   mkdir -p autism_waiver_app/uploads/appeals
   chmod 755 autism_waiver_app/uploads/denials
   chmod 755 autism_waiver_app/uploads/appeals
   ```

3. **Configuration**
   - Verify database connection in `config_sqlite.php`
   - Configure user roles and permissions
   - Set up email notifications (if desired)

### User Roles & Permissions

#### Admin
- Full access to all denial management features
- Can assign denials to any user
- Access to all analytics and reports
- Can perform bulk operations
- Can escalate denials

#### Billing Specialist
- Can work assigned denials
- Can file appeals
- Can create tasks and notes
- Limited analytics access
- Can update denial status

#### Employee
- Read-only access to assigned denials
- Can add notes and attachments
- Cannot change status or assignments

### Workflow Guide

#### 1. New Denial Processing
1. Denial is entered into the system (manually or via EDI)
2. System automatically calculates appeal deadline
3. Denial is prioritized based on amount, age, and deadline
4. Assignment can be automatic or manual

#### 2. Working a Denial
1. Access denial from worklist
2. Review denial reason and supporting information
3. Determine appropriate action:
   - Gather missing documentation
   - File appeal
   - Resubmit corrected claim
   - Accept denial
4. Update status and add notes
5. Set follow-up tasks if needed

#### 3. Filing an Appeal
1. Select denial from worklist
2. Choose appropriate appeal type
3. Use pre-built templates for common scenarios
4. Attach supporting documentation
5. Submit appeal and track status
6. Set follow-up reminders

#### 4. Analytics Review
1. Monitor key performance indicators
2. Identify trends and patterns
3. Focus on preventable denials
4. Track staff productivity
5. Generate reports for management

### Key Performance Indicators (KPIs)

#### Financial Metrics
- **Recovery Rate** - Percentage of denied amount recovered
- **Appeal Success Rate** - Percentage of appeals approved
- **Average Recovery Time** - Days from denial to resolution
- **Total Recovered Amount** - Dollar amount recovered

#### Operational Metrics
- **Denial Rate** - Percentage of claims denied
- **Preventable Denial Rate** - Percentage of avoidable denials
- **Appeal Timeliness** - Percentage filed before deadline
- **Staff Productivity** - Denials resolved per staff member

#### Quality Metrics
- **First Appeal Success Rate** - Success without re-appeals
- **Documentation Completeness** - Percentage with full docs
- **Deadline Compliance** - Percentage meeting deadlines
- **Error Rate** - Percentage of correctable denials

### Best Practices

#### Denial Prevention
1. **Pre-submission Validation**
   - Verify authorization before service
   - Validate member eligibility
   - Check service limits and frequency
   - Ensure proper coding

2. **Documentation Standards**
   - Complete progress notes
   - Medical necessity documentation
   - Authorization tracking
   - Service verification

#### Appeal Strategy
1. **Timing**
   - File appeals promptly
   - Monitor deadlines closely
   - Use expedited process when appropriate

2. **Documentation**
   - Include all supporting evidence
   - Use clear, professional language
   - Reference specific regulations
   - Provide medical necessity justification

#### Team Management
1. **Assignment Strategy**
   - Balance workload across team
   - Assign based on expertise
   - Prioritize high-value denials
   - Monitor individual productivity

2. **Training Focus**
   - Maryland Medicaid requirements
   - Denial code interpretation
   - Appeal writing techniques
   - System functionality

### Troubleshooting

#### Common Issues
1. **Slow Performance**
   - Check database indexes
   - Archive old denials
   - Optimize queries

2. **File Upload Problems**
   - Verify directory permissions
   - Check file size limits
   - Ensure adequate disk space

3. **Appeal Deadline Alerts**
   - Verify system date/time
   - Check deadline calculations
   - Review notification settings

#### Support Contacts
- **System Administrator** - Technical issues
- **Billing Manager** - Process questions
- **Compliance Officer** - Regulatory guidance

### Reporting & Analytics

#### Standard Reports
1. **Daily Denial Summary** - New denials and urgent items
2. **Weekly Productivity Report** - Staff performance metrics
3. **Monthly Analytics Report** - Trends and patterns
4. **Quarterly Review Report** - Strategic insights

#### Custom Analytics
- Filter by date ranges, providers, services
- Export data to CSV for external analysis
- Create custom dashboards for specific needs
- Schedule automated reports

### Integration Points

#### Existing Systems
- **Billing System** - Claim data import
- **EMR Integration** - Clinical documentation
- **Eligibility System** - Member verification
- **Authorization System** - Prior auth tracking

#### External Interfaces
- **Maryland Medicaid Portal** - Appeal submission
- **Clearinghouse** - EDI transactions
- **Banking Systems** - Payment posting
- **Reporting Tools** - Analytics export

### Security & Compliance

#### Data Protection
- **PHI Security** - HIPAA-compliant handling
- **Access Controls** - Role-based permissions
- **Audit Logging** - Complete activity tracking
- **Data Encryption** - Sensitive information protection

#### Compliance Requirements
- **Maryland Medicaid Regulations** - Appeal timelines
- **Federal Requirements** - CMS guidelines
- **State Reporting** - Compliance submissions
- **Internal Policies** - Organizational standards

### Future Enhancements

#### Planned Features
1. **Automated Appeal Generation** - AI-powered appeal writing
2. **Predictive Analytics** - Denial risk scoring
3. **Mobile Access** - Responsive design improvements
4. **Email Integration** - Automated notifications
5. **Document OCR** - Automated data extraction

#### Integration Roadmap
1. **Real-time Eligibility** - Live verification
2. **Provider Portal** - Self-service appeals
3. **Member Portal** - Status transparency
4. **Advanced Analytics** - Machine learning insights

### Maintenance Schedule

#### Daily Tasks
- Monitor new denials
- Review urgent deadlines
- Process appeal responses
- Update denial statuses

#### Weekly Tasks
- Generate productivity reports
- Review aged denials
- Update prevention strategies
- Staff performance reviews

#### Monthly Tasks
- Analytics review and reporting
- System performance optimization
- Training needs assessment
- Process improvement evaluation

---

## Contact Information

**System Owner:** ACI Billing Department  
**Technical Support:** IT Department  
**Process Questions:** Billing Manager  
**Training Requests:** HR Department

**Last Updated:** December 2024  
**Version:** 1.0.0

---

*This system is designed to improve denial management efficiency while ensuring compliance with Maryland Medicaid requirements and maximizing revenue recovery for the ACI Autism Waiver Program.*