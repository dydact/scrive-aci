# Claim.MD Integration Requirements for American Caregivers

## Information Needed from You

### 1. Claim.MD API Credentials
- **AccountKey**: Located in Claim.MD portal under Settings → Account Settings
- **Test/Production Environment URLs** (if different from https://svc.claim.md)

### 2. Provider Information
- **Organization NPI**: American Caregivers' National Provider Identifier
- **Tax ID (EIN)**: Federal Tax ID for American Caregivers
- **Taxonomy Code**: For autism waiver services (likely 261QM0850X for Home Health)
- **Provider Address**: Official billing address for American Caregivers

### 3. Individual Provider NPIs
For each billing provider:
- Individual NPI numbers
- Provider names (First, Last)
- Whether they bill individually or under group

### 4. Payer Information
- **Primary Payer IDs** for Maryland Medicaid:
  - Maryland Medicaid payer ID in Claim.MD
  - Any managed care organization (MCO) payer IDs
- **Secondary payers** commonly used (if any)

### 5. Service Configuration
- **Current Medicaid reimbursement rates** for:
  - Individual Intensive Support Services (IISS)
  - Personal Support
  - Respite Care
  - Community Development Services
  - Supported Employment
- **Authorization requirements** per service type
- **Billing units** (15-min increments, hourly, etc.)

### 6. Enrollment Status
- **Electronic enrollment status** for:
  - 837P (Professional Claims) 
  - 835 (Electronic Remittance Advice)
  - 270/271 (Eligibility)
- **Existing payer enrollments** to verify

## What I'll Implement Once Provided

### 1. API Integration Module
```php
// Location: /autism_waiver_app/integrations/claim_md_api.php
class ClaimMDAPI {
    private $accountKey;
    private $apiUrl = 'https://svc.claim.md/services/';
    
    // Claim submission
    public function submitClaims($claims);
    
    // Check claim status
    public function getClaimStatus($responseId);
    
    // Download remittances
    public function getERA($eraId);
    
    // Eligibility verification
    public function checkEligibility($patient, $serviceDate);
}
```

### 2. Automated Workflows
- **Daily claim submission** batch process
- **Real-time eligibility checking** before sessions
- **Automatic ERA posting** to payment records
- **Denial management** with reason codes

### 3. Configuration Updates
```php
// In src/config.php
define('CLAIMMD_ACCOUNT_KEY', 'your_key_here');
define('CLAIMMD_API_URL', 'https://svc.claim.md/services/');

// Provider settings
define('ORGANIZATION_NPI', 'real_npi_here');
define('ORGANIZATION_TAX_ID', 'real_tax_id_here');
define('TAXONOMY_CODE', '261QM0850X'); // or correct code

// Payer settings
define('MD_MEDICAID_PAYER_ID', 'payer_id_here');
```

### 4. Database Updates Needed
```sql
-- Store payer enrollment status
CREATE TABLE autism_payer_enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payer_id VARCHAR(20),
    payer_name VARCHAR(100),
    enrollment_type ENUM('837P', '835', '270', 'attach'),
    enrollment_status ENUM('enrolled', 'pending', 'not_enrolled'),
    enrollment_date DATE,
    avg_era_days INT
);

-- Track Claim.MD specific IDs
ALTER TABLE autism_billing_claims 
ADD COLUMN claimmd_id VARCHAR(50),
ADD COLUMN remote_claim_id VARCHAR(50),
ADD COLUMN last_response_id VARCHAR(50);
```

## Testing Requirements

### 1. Test Credentials
- Test AccountKey for sandbox environment
- Test patient data with valid MA numbers
- Test provider NPIs

### 2. Test Scenarios
- Single claim submission
- Batch claim submission (up to 2000)
- Eligibility verification
- ERA retrieval and posting
- Denial handling

## Maryland Medicaid Specific Requirements

### 1. Required Fields
- Prior authorization numbers
- Diagnosis codes (autism-related ICD-10)
- Rendering provider NPI
- Service location (home vs facility)

### 2. Billing Rules
- Concurrent service restrictions
- Daily/weekly hour limits
- Documentation requirements
- Timely filing limits

## Next Steps

1. **Provide the required information** listed above
2. **Confirm test environment access** with Claim.MD
3. **Schedule testing window** for initial submissions
4. **Verify payer enrollment status** for all required transactions

## Security Considerations

- API keys will be stored encrypted
- All PHI transmitted over HTTPS
- Audit logging for all transactions
- Rate limiting (100 requests/minute max)

## Support Contacts

- **Claim.MD Support**: For API issues and enrollment
- **Maryland Medicaid Provider Services**: For payer-specific questions
- **Your billing team**: Pam Pastor & Yanika Crosse for testing

Once you provide this information, I can complete the integration and have the system ready for testing with Claim.MD!