-- EDI 835 Remittance Processing Tables
-- These tables support the EDI 835 parser for payment posting

-- Payment batches table for grouping payments from remittance files
CREATE TABLE IF NOT EXISTS payment_batches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_type VARCHAR(20) NOT NULL DEFAULT 'manual', -- 'manual', 'EDI_835', 'lockbox'
    batch_number VARCHAR(50),
    check_number VARCHAR(50),
    payment_date DATE,
    payer_name VARCHAR(255),
    payer_id VARCHAR(50),
    total_amount DECIMAL(10,2) DEFAULT 0,
    claim_count INTEGER DEFAULT 0,
    posted_amount DECIMAL(10,2) DEFAULT 0,
    adjustment_amount DECIMAL(10,2) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'processing', 'completed', 'error'
    notes TEXT,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Individual claim payments
CREATE TABLE IF NOT EXISTS claim_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    claim_id INTEGER NOT NULL,
    batch_id INTEGER,
    payment_date DATE NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    patient_responsibility DECIMAL(10,2) DEFAULT 0,
    interest_amount DECIMAL(10,2) DEFAULT 0,
    late_filing_charge DECIMAL(10,2) DEFAULT 0,
    payer_claim_number VARCHAR(50),
    claim_status_code VARCHAR(10), -- EDI 835 claim status codes
    payment_method VARCHAR(20), -- 'check', 'eft', 'credit_card'
    check_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER,
    FOREIGN KEY (claim_id) REFERENCES billing_claims(id),
    FOREIGN KEY (batch_id) REFERENCES payment_batches(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Service line payments
CREATE TABLE IF NOT EXISTS service_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payment_id INTEGER NOT NULL,
    service_line_id INTEGER,
    procedure_code VARCHAR(10),
    modifiers VARCHAR(20),
    charge_amount DECIMAL(10,2),
    payment_amount DECIMAL(10,2),
    units_paid DECIMAL(5,2),
    revenue_code VARCHAR(10),
    service_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES claim_payments(id),
    FOREIGN KEY (service_line_id) REFERENCES claim_service_lines(id)
);

-- Payment adjustments (CAS segments)
CREATE TABLE IF NOT EXISTS payment_adjustments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    payment_id INTEGER NOT NULL,
    service_payment_id INTEGER,
    adjustment_level VARCHAR(10) DEFAULT 'claim', -- 'claim' or 'service'
    group_code VARCHAR(5) NOT NULL, -- CO, PI, PR, OA, CR
    reason_code VARCHAR(10) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    quantity DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES claim_payments(id),
    FOREIGN KEY (service_payment_id) REFERENCES service_payments(id)
);

-- Provider level adjustments (PLB segments)
CREATE TABLE IF NOT EXISTS provider_adjustments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_id INTEGER NOT NULL,
    provider_id VARCHAR(50),
    provider_npi VARCHAR(20),
    fiscal_period_date DATE,
    adjustment_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES payment_batches(id)
);

-- Provider adjustment details
CREATE TABLE IF NOT EXISTS provider_adjustment_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    provider_adjustment_id INTEGER NOT NULL,
    reason_code VARCHAR(50),
    reference_id VARCHAR(50),
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_adjustment_id) REFERENCES provider_adjustments(id)
);

-- EDI transaction log (expanded for 835)
CREATE TABLE IF NOT EXISTS edi_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_type VARCHAR(20) NOT NULL, -- 'inbound' or 'outbound'
    transaction_set VARCHAR(10) NOT NULL, -- '837', '835', '277', '999'
    interchange_control_number VARCHAR(20),
    group_control_number VARCHAR(20),
    transaction_control_number VARCHAR(20),
    filename VARCHAR(255),
    file_path TEXT,
    file_size INTEGER,
    sender_id VARCHAR(50),
    receiver_id VARCHAR(50),
    batch_id INTEGER, -- For 835 files
    claim_count INTEGER,
    total_amount DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'received', 'processed', 'error'
    error_message TEXT,
    created_by INTEGER,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES payment_batches(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- EDI processing logs
CREATE TABLE IF NOT EXISTS edi_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_id INTEGER,
    log_level VARCHAR(20) NOT NULL, -- 'info', 'warning', 'error'
    message TEXT,
    context TEXT, -- JSON string for additional context
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES edi_transactions(id)
);

-- Claim service lines (if not already exists)
CREATE TABLE IF NOT EXISTS claim_service_lines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    claim_id INTEGER NOT NULL,
    line_number INTEGER NOT NULL,
    service_date DATE NOT NULL,
    procedure_code VARCHAR(10) NOT NULL,
    modifier1 VARCHAR(5),
    modifier2 VARCHAR(5),
    modifier3 VARCHAR(5),
    modifier4 VARCHAR(5),
    diagnosis_pointer VARCHAR(10),
    charge_amount DECIMAL(10,2) NOT NULL,
    units DECIMAL(5,2) NOT NULL,
    place_of_service VARCHAR(5),
    revenue_code VARCHAR(10),
    ndc_code VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (claim_id) REFERENCES billing_claims(id)
);

-- Adjustment reason codes reference table
CREATE TABLE IF NOT EXISTS adjustment_reason_codes (
    code VARCHAR(10) PRIMARY KEY,
    group_code VARCHAR(5),
    description TEXT,
    patient_responsibility BOOLEAN DEFAULT 0,
    contractual_obligation BOOLEAN DEFAULT 0,
    active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert common adjustment reason codes
INSERT OR IGNORE INTO adjustment_reason_codes (code, group_code, description, patient_responsibility, contractual_obligation) VALUES
('1', 'PR', 'Deductible Amount', 1, 0),
('2', 'PR', 'Coinsurance Amount', 1, 0),
('3', 'PR', 'Co-payment Amount', 1, 0),
('4', 'CO', 'The procedure code is inconsistent with the modifier used', 0, 1),
('16', 'CO', 'Claim/service lacks information needed for adjudication', 0, 1),
('18', 'CO', 'Duplicate claim/service', 0, 1),
('45', 'CO', 'Charges exceed your contracted/legislated fee arrangement', 0, 1),
('50', 'CO', 'These are non-covered services because this is not deemed a medical necessity', 0, 1),
('96', 'CO', 'Non-covered charges', 0, 1),
('97', 'CO', 'Payment adjusted because this procedure/service is not paid separately', 0, 1),
('109', 'OA', 'Claim not covered by this payer/contractor', 0, 0),
('119', 'CO', 'Benefit maximum for this time period has been reached', 0, 1),
('136', 'CO', 'Failure to follow prior authorization guidelines', 0, 1),
('197', 'CO', 'Precertification/authorization absent', 0, 1);

-- Payment reconciliation view
CREATE VIEW IF NOT EXISTS payment_reconciliation AS
SELECT 
    c.claim_number,
    c.service_date,
    cl.first_name || ' ' || cl.last_name as patient_name,
    c.total_amount as claim_amount,
    COALESCE(SUM(cp.payment_amount), 0) as total_paid,
    COALESCE(SUM(cp.patient_responsibility), 0) as patient_responsibility,
    c.total_amount - COALESCE(SUM(cp.payment_amount), 0) - COALESCE(SUM(cp.patient_responsibility), 0) as balance,
    c.status,
    pb.check_number,
    pb.payment_date as check_date,
    pb.payer_name
FROM billing_claims c
LEFT JOIN clients cl ON c.client_id = cl.id
LEFT JOIN claim_payments cp ON c.id = cp.claim_id
LEFT JOIN payment_batches pb ON cp.batch_id = pb.id
GROUP BY c.id;

-- Denial analysis view
CREATE VIEW IF NOT EXISTS denial_analysis AS
SELECT 
    pa.group_code,
    pa.reason_code,
    arc.description as reason_description,
    COUNT(DISTINCT cp.claim_id) as claim_count,
    SUM(pa.amount) as total_adjustment_amount,
    pb.payer_name,
    strftime('%Y-%m', pb.payment_date) as payment_month
FROM payment_adjustments pa
JOIN claim_payments cp ON pa.payment_id = cp.id
JOIN payment_batches pb ON cp.batch_id = pb.id
LEFT JOIN adjustment_reason_codes arc ON pa.reason_code = arc.code
WHERE pa.amount > 0
GROUP BY pa.group_code, pa.reason_code, pb.payer_name, payment_month
ORDER BY claim_count DESC;

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_payment_batches_status ON payment_batches(status);
CREATE INDEX IF NOT EXISTS idx_payment_batches_date ON payment_batches(payment_date);
CREATE INDEX IF NOT EXISTS idx_claim_payments_claim ON claim_payments(claim_id);
CREATE INDEX IF NOT EXISTS idx_claim_payments_batch ON claim_payments(batch_id);
CREATE INDEX IF NOT EXISTS idx_payment_adjustments_payment ON payment_adjustments(payment_id);
CREATE INDEX IF NOT EXISTS idx_payment_adjustments_reason ON payment_adjustments(reason_code);
CREATE INDEX IF NOT EXISTS idx_edi_transactions_status ON edi_transactions(status);
CREATE INDEX IF NOT EXISTS idx_edi_transactions_set ON edi_transactions(transaction_set);