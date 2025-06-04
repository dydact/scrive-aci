# Claim.MD Integration Status

## ✅ Completed Configuration

### 1. API Credentials
- **AccountKey**: Configured in `/config/claimmd.php`
- **API URL**: https://svc.claim.md/services/

### 2. Organization Information
- **NPI**: 1013104314
- **EIN**: 52-2305229
- **Taxonomy**: 251C00000X (Home Infusion Therapy Services)
- **Address**: 2301 Broadbirch Drive, Suite 135, Silver Spring, MD 20904

### 3. Maryland Medicaid Configuration
- **Payer IDs**: MDMCD, SKMD0, MCDMD
- **Service Types**: All 5 autism waiver services configured with 2024 rates

### 4. Service Rates Updated in Database
| Service Code | Service Name | Rate | Limits |
|-------------|--------------|------|---------|
| W9307 | Regular Therapeutic Integration | $9.28 | 80 units/week |
| W9308 | Intensive Therapeutic Integration | $11.60 | 60 units/week |
| W9306 | Intensive Individual Support (IISS) | $12.80 | 160 units/week |
| W9314 | Respite Care | $9.07 | 96 units/day, 1344/year |
| W9315 | Family Consultation | $38.10 | 24 units/day, 160/year |

## 🚀 Implemented Features

### 1. API Integration Class
**Location**: `/autism_waiver_app/integrations/claim_md_api.php`
- Claim submission (837P and JSON formats)
- Status checking and updates
- Eligibility verification
- ERA retrieval and processing
- Payer list management

### 2. Automated Claim Submission
**Location**: `/autism_waiver_app/cron/submit_claims_to_claimmd.php`
- Daily batch processing of pending claims
- Automatic status updates
- Error handling and logging
- Email alerts to billing team

### 3. Real-time Eligibility Checking
**Location**: `/autism_waiver_app/check_eligibility.php`
- Client eligibility verification
- Coverage details display
- Audit trail logging

## 📋 Next Steps for Full Production

### 1. Testing Phase
- [ ] Test claim submission with a test claim
- [ ] Verify eligibility checking works
- [ ] Test ERA retrieval
- [ ] Confirm status updates are received

### 2. Individual Provider NPIs
When you have them, update the `autism_staff_members` table:
```sql
UPDATE autism_staff_members SET npi = 'provider_npi_here' WHERE id = X;
```

### 3. Cron Job Setup
Add to server crontab:
```bash
# Submit claims daily at 2 AM
0 2 * * * php /path/to/autism_waiver_app/cron/submit_claims_to_claimmd.php

# Check claim status every 4 hours
0 */4 * * * php /path/to/autism_waiver_app/cron/check_claim_status.php

# Download ERAs daily at 3 AM
0 3 * * * php /path/to/autism_waiver_app/cron/download_era.php
```

### 4. Production Checklist
- [ ] Verify all provider enrollments are active
- [ ] Test with one real claim first
- [ ] Monitor first week of submissions closely
- [ ] Set up alerts for failed submissions

## 🔧 Access Points

### Billing Dashboard
- **URL**: http://localhost:8080/billing
- **Features**: Claims overview, submission status

### Eligibility Check
- **URL**: http://localhost:8080/autism_waiver_app/check_eligibility.php
- **Access**: Case Managers and above

### Manual Claim Submission
- **URL**: http://localhost:8080/billing/claims
- **Process**: Create claim → Submit to Claim.MD

## 📊 Monitoring

### Log Files
- **API Transactions**: `/logs/claimmd_transactions.log`
- **Claim Submissions**: Monitor via billing dashboard
- **Error Alerts**: Sent to pam.pastor@acgcares.com, yanika.crosse@acgcares.com

### Key Metrics to Track
1. Daily claim submission count
2. Rejection rate
3. Average days to payment
4. Denial reasons

## 🔒 Security Notes

1. API key is stored securely in config file
2. All transmissions use HTTPS
3. PHI is encrypted in transit
4. Audit logging for all operations
5. Rate limiting enforced (100 req/min)

## 📞 Support Contacts

- **Claim.MD Support**: For API issues
- **Your Billing Team**: Pam Pastor & Yanika Crosse
- **Technical Issues**: Frank (Supreme Admin)

The integration is now ready for testing! Start with a test claim to verify everything works before going live with production claims.