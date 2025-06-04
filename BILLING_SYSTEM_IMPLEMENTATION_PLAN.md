# Comprehensive Billing System Implementation Plan

## Overview
This plan outlines the development of a production-ready billing system for Scrive ACI with real EDI processing, payment handling, and Maryland Medicaid compliance.

## Phase 1: EDI Infrastructure (Week 1-2)

### 1.1 EDI 837 Professional Claim Implementation
- [ ] Create EDI parser/generator library
- [ ] Implement ISA/GS envelope generation
- [ ] Build claim segments (CLM, SBR, NM1, etc.)
- [ ] Add service line details (SV1, DTP)
- [ ] Implement diagnosis code handling (HI)
- [ ] Create batch file generation

### 1.2 EDI 835 Remittance Processing
- [ ] Build 835 parser for payment files
- [ ] Extract payment details (BPR segment)
- [ ] Parse claim adjustments (CAS)
- [ ] Handle service line payments (SVC)
- [ ] Process denial codes (CRC)
- [ ] Create reconciliation logic

### 1.3 Clearinghouse Integration
- [ ] Design clearinghouse interface
- [ ] Implement SFTP/API connectivity
- [ ] Build file submission workflow
- [ ] Create acknowledgment processing
- [ ] Implement error handling

## Phase 2: Claims Management (Week 2-3)

### 2.1 Claim Generation Engine
- [ ] Build claim validation rules
- [ ] Implement Maryland Medicaid requirements
- [ ] Add authorization checking
- [ ] Create claim scrubbing logic
- [ ] Build duplicate checking
- [ ] Implement timely filing validation

### 2.2 Claim Workflow
- [ ] Create claim queue management
- [ ] Build supervisor approval process
- [ ] Implement batch processing
- [ ] Add claim status tracking
- [ ] Create resubmission workflow
- [ ] Build correction handling

### 2.3 Billing Rules Engine
- [ ] Maryland Medicaid billing rules
- [ ] Service authorization limits
- [ ] Modifier requirements
- [ ] Unit calculations
- [ ] Rate schedules
- [ ] Coverage determination

## Phase 3: Payment Processing (Week 3-4)

### 3.1 Payment Posting
- [ ] Build ERA processing engine
- [ ] Create automated posting logic
- [ ] Implement manual posting interface
- [ ] Add payment matching algorithm
- [ ] Build adjustment handling
- [ ] Create payment reports

### 3.2 Denial Management
- [ ] Create denial tracking system
- [ ] Build reason code library
- [ ] Implement appeal workflows
- [ ] Add follow-up reminders
- [ ] Create denial analytics
- [ ] Build corrective action tracking

### 3.3 Patient Billing
- [ ] Calculate patient responsibility
- [ ] Generate statements
- [ ] Create payment plans
- [ ] Build credit card processing
- [ ] Implement collections workflow
- [ ] Add write-off management

## Phase 4: Financial Reporting (Week 4-5)

### 4.1 Revenue Cycle Reports
- [ ] Aging analysis (detailed)
- [ ] Cash flow projections
- [ ] Collection rate tracking
- [ ] Denial rate analysis
- [ ] Payer performance metrics
- [ ] Service profitability

### 4.2 Operational Reports
- [ ] Unbilled claims report
- [ ] Claims in process
- [ ] Authorization utilization
- [ ] Productivity metrics
- [ ] Exception reports
- [ ] Audit trails

### 4.3 Compliance Reports
- [ ] Timely filing compliance
- [ ] Documentation compliance
- [ ] Authorization compliance
- [ ] Billing accuracy metrics
- [ ] Maryland Medicaid specific reports

## Phase 5: Integration & Automation (Week 5-6)

### 5.1 System Integrations
- [ ] QuickBooks integration
- [ ] Bank reconciliation
- [ ] Eligibility verification (270/271)
- [ ] Authorization status (278)
- [ ] Maryland Medicaid portal
- [ ] Document management

### 5.2 Automation Features
- [ ] Auto-generate claims from sessions
- [ ] Scheduled batch processing
- [ ] Automated eligibility checks
- [ ] Payment auto-posting
- [ ] Denial auto-workflows
- [ ] Report scheduling

## Technical Architecture

### Database Schema Additions
```sql
-- EDI transaction tables
CREATE TABLE autism_edi_files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    file_type ENUM('837','835','997','277','271'),
    direction ENUM('inbound','outbound'),
    filename VARCHAR(255),
    content LONGTEXT,
    status ENUM('pending','processing','completed','error'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE autism_clearinghouse_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    edi_file_id INT,
    clearinghouse VARCHAR(100),
    submission_id VARCHAR(100),
    status ENUM('submitted','accepted','rejected','processing'),
    response TEXT,
    submitted_at TIMESTAMP,
    response_at TIMESTAMP
);

CREATE TABLE autism_payment_batches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    check_number VARCHAR(50),
    payment_date DATE,
    payer_name VARCHAR(100),
    total_amount DECIMAL(10,2),
    era_file_id INT,
    posted_by INT,
    posted_at TIMESTAMP
);

CREATE TABLE autism_claim_lines (
    id INT PRIMARY KEY AUTO_INCREMENT,
    claim_id INT,
    line_number INT,
    service_date DATE,
    procedure_code VARCHAR(10),
    modifier1 VARCHAR(2),
    modifier2 VARCHAR(2),
    units DECIMAL(5,2),
    charge_amount DECIMAL(10,2),
    paid_amount DECIMAL(10,2),
    adjustment_amount DECIMAL(10,2)
);
```

### Key Components

1. **EDI Engine** (`/autism_waiver_app/edi/`)
   - EDI837Generator.php
   - EDI835Parser.php
   - EDIValidator.php
   - ClearinghouseConnector.php

2. **Claims Processing** (`/autism_waiver_app/billing/`)
   - ClaimGenerator.php
   - ClaimValidator.php
   - BillingRulesEngine.php
   - AuthorizationChecker.php

3. **Payment Processing** (`/autism_waiver_app/payments/`)
   - PaymentPoster.php
   - RemittanceProcessor.php
   - DenialManager.php
   - PatientBilling.php

4. **Reporting Engine** (`/autism_waiver_app/reports/`)
   - RevenueReports.php
   - OperationalReports.php
   - ComplianceReports.php
   - FinancialAnalytics.php

## Implementation Priority

### Week 1: Foundation
1. Database schema updates
2. Basic EDI 837 generation
3. Claim validation framework
4. Simple claim generation UI

### Week 2: Core Billing
1. Complete EDI 837 implementation
2. Claim queue management
3. Basic billing rules
4. Batch processing

### Week 3: Payment Processing
1. EDI 835 parser
2. Payment posting interface
3. Basic denial tracking
4. Payment reports

### Week 4: Advanced Features
1. Automated workflows
2. Denial management
3. Advanced reporting
4. Compliance tracking

### Week 5: Integration
1. Clearinghouse connection
2. QuickBooks integration
3. Eligibility checking
4. Portal integration

### Week 6: Testing & Deployment
1. End-to-end testing
2. Performance optimization
3. Security audit
4. Production deployment

## Success Metrics

1. **Claim Acceptance Rate**: >95%
2. **Auto-posting Rate**: >80%
3. **Days in A/R**: <30 days
4. **Denial Rate**: <5%
5. **Clean Claim Rate**: >90%
6. **Payment Posting Time**: <24 hours

## Risk Mitigation

1. **EDI Compliance**: Use validated test files
2. **Data Security**: Encrypt all PHI/PII
3. **Audit Trail**: Log all transactions
4. **Backup**: Daily backups of billing data
5. **Testing**: Comprehensive test suite
6. **Training**: Staff training materials

## Next Steps

1. Begin with database schema updates
2. Create EDI 837 generator
3. Build basic claim generation UI
4. Implement claim validation
5. Test with sample data