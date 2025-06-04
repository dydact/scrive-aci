-- Denial Management System Tables for ACI Autism Waiver Program
-- Comprehensive denial tracking, appeals, and recovery management

-- Main table for tracking claim denials
CREATE TABLE IF NOT EXISTS claim_denials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    claim_id INTEGER,
    claim_number VARCHAR(50) NOT NULL,
    client_id INTEGER,
    provider_id INTEGER,
    service_type VARCHAR(50),
    service_date DATE NOT NULL,
    denial_date DATE NOT NULL,
    denial_code VARCHAR(10) NOT NULL,
    denial_reason TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    
    -- Status tracking
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'in_progress', 'appealed', 'resubmitted', 'resolved', 'escalated')),
    priority VARCHAR(10) DEFAULT 'medium' CHECK (priority IN ('low', 'medium', 'high')),
    
    -- Assignment and dates
    assigned_to INTEGER,
    assigned_date DATETIME,
    appeal_deadline DATE NOT NULL,
    follow_up_date DATE,
    
    -- Appeal tracking
    appeal_status VARCHAR(20) CHECK (appeal_status IN ('not_filed', 'submitted', 'pending', 'approved', 'denied')),
    appeal_id INTEGER,
    appeal_submission_date DATE,
    appeal_response_date DATE,
    
    -- Resolution tracking
    resolution_type VARCHAR(20) CHECK (resolution_type IN ('paid', 'denied', 'adjusted', 'withdrawn')),
    resolution_amount DECIMAL(10,2) DEFAULT 0,
    resolution_date DATE,
    resolution_notes TEXT,
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER,
    updated_by INTEGER,
    
    -- Indexes for performance
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

CREATE INDEX IF NOT EXISTS idx_denial_status ON claim_denials(status);
CREATE INDEX IF NOT EXISTS idx_denial_code ON claim_denials(denial_code);
CREATE INDEX IF NOT EXISTS idx_denial_date ON claim_denials(denial_date);
CREATE INDEX IF NOT EXISTS idx_appeal_deadline ON claim_denials(appeal_deadline);
CREATE INDEX IF NOT EXISTS idx_assigned_to ON claim_denials(assigned_to);
CREATE INDEX IF NOT EXISTS idx_client_id_denial ON claim_denials(client_id);

-- Table for tracking appeals
CREATE TABLE IF NOT EXISTS claim_appeals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    denial_id INTEGER NOT NULL,
    appeal_date DATE NOT NULL,
    appeal_type VARCHAR(30) NOT NULL CHECK (appeal_type IN ('reconsideration', 'peer_review', 'formal_appeal', 'expedited', 'external_review')),
    appeal_reason TEXT NOT NULL,
    supporting_documentation TEXT,
    
    -- Contact information
    contact_person VARCHAR(100) NOT NULL,
    contact_phone VARCHAR(20) NOT NULL,
    
    -- Appeal status and outcome
    status VARCHAR(20) DEFAULT 'submitted' CHECK (status IN ('submitted', 'pending', 'under_review', 'approved', 'denied', 'withdrawn')),
    expedited BOOLEAN DEFAULT 0,
    
    -- Response tracking
    response_date DATE,
    response_reason TEXT,
    outcome_amount DECIMAL(10,2) DEFAULT 0,
    
    -- Follow-up
    follow_up_required BOOLEAN DEFAULT 0,
    follow_up_date DATE,
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER NOT NULL,
    updated_by INTEGER,
    
    FOREIGN KEY (denial_id) REFERENCES claim_denials(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

CREATE INDEX IF NOT EXISTS idx_appeal_denial_id ON claim_appeals(denial_id);
CREATE INDEX IF NOT EXISTS idx_appeal_status ON claim_appeals(status);
CREATE INDEX IF NOT EXISTS idx_appeal_date ON claim_appeals(appeal_date);

-- Table for tracking denial-related activities and notes
CREATE TABLE IF NOT EXISTS denial_activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    denial_id INTEGER NOT NULL,
    activity_type VARCHAR(30) NOT NULL CHECK (activity_type IN ('status_update', 'note', 'assignment', 'escalation', 'resolution', 'appeal_filed', 'communication')),
    description TEXT NOT NULL,
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER NOT NULL,
    
    FOREIGN KEY (denial_id) REFERENCES claim_denials(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE INDEX IF NOT EXISTS idx_activity_denial_id ON denial_activities(denial_id);
CREATE INDEX IF NOT EXISTS idx_activity_type ON denial_activities(activity_type);
CREATE INDEX IF NOT EXISTS idx_activity_date ON denial_activities(created_at);

-- Table for tracking follow-up tasks
CREATE TABLE IF NOT EXISTS denial_tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    denial_id INTEGER NOT NULL,
    task_type VARCHAR(30) NOT NULL CHECK (task_type IN ('follow_up', 'appeal_deadline', 'documentation_request', 'provider_contact', 'resubmission')),
    description TEXT NOT NULL,
    due_date DATE NOT NULL,
    
    -- Assignment
    assigned_to INTEGER,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'in_progress', 'completed', 'cancelled')),
    
    -- Completion tracking
    completed_at DATETIME,
    completion_notes TEXT,
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER NOT NULL,
    
    FOREIGN KEY (denial_id) REFERENCES claim_denials(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE INDEX IF NOT EXISTS idx_task_denial_id ON denial_tasks(denial_id);
CREATE INDEX IF NOT EXISTS idx_task_due_date ON denial_tasks(due_date);
CREATE INDEX IF NOT EXISTS idx_task_assigned_to ON denial_tasks(assigned_to);
CREATE INDEX IF NOT EXISTS idx_task_status ON denial_tasks(status);

-- Table for storing file attachments related to denials and appeals
CREATE TABLE IF NOT EXISTS denial_attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    denial_id INTEGER,
    appeal_id INTEGER,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INTEGER,
    description TEXT,
    
    -- Metadata
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INTEGER NOT NULL,
    
    FOREIGN KEY (denial_id) REFERENCES claim_denials(id) ON DELETE CASCADE,
    FOREIGN KEY (appeal_id) REFERENCES claim_appeals(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

CREATE INDEX IF NOT EXISTS idx_attachment_denial_id ON denial_attachments(denial_id);
CREATE INDEX IF NOT EXISTS idx_attachment_appeal_id ON denial_attachments(appeal_id);

-- Table for storing appeal attachments separately
CREATE TABLE IF NOT EXISTS appeal_attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    appeal_id INTEGER NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INTEGER,
    description TEXT,
    
    -- Metadata
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INTEGER NOT NULL,
    
    FOREIGN KEY (appeal_id) REFERENCES claim_appeals(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Table for tracking denial reasons and their prevention strategies
CREATE TABLE IF NOT EXISTS denial_prevention_strategies (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    denial_code VARCHAR(10) NOT NULL,
    denial_reason VARCHAR(255) NOT NULL,
    prevention_strategy TEXT NOT NULL,
    training_required BOOLEAN DEFAULT 0,
    system_check BOOLEAN DEFAULT 0,
    
    -- Effectiveness tracking
    occurrences_before INTEGER DEFAULT 0,
    occurrences_after INTEGER DEFAULT 0,
    implementation_date DATE,
    
    -- Metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by INTEGER,
    
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE UNIQUE INDEX IF NOT EXISTS idx_prevention_code ON denial_prevention_strategies(denial_code);

-- Table for tracking denial analytics and reporting
CREATE TABLE IF NOT EXISTS denial_analytics_cache (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    report_type VARCHAR(50) NOT NULL,
    report_date DATE NOT NULL,
    data_json TEXT NOT NULL,
    
    -- Cache metadata
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME,
    
    UNIQUE(report_type, report_date)
);

-- Insert Maryland Medicaid specific denial codes
INSERT OR IGNORE INTO denial_prevention_strategies (denial_code, denial_reason, prevention_strategy, training_required, system_check) VALUES
('M01', 'Missing or Invalid Prior Authorization', 'Implement automated prior auth verification before claim submission. Train staff on authorization requirements.', 1, 1),
('M02', 'Service Not Covered', 'Verify coverage before service delivery. Maintain updated coverage guidelines.', 1, 0),
('M03', 'Duplicate Claim', 'Implement claim tracking system to prevent duplicate submissions.', 0, 1),
('M04', 'Invalid Provider Number', 'Verify provider enrollment status regularly. Update NPI information.', 1, 1),
('M05', 'Invalid Member ID', 'Verify member eligibility before each service. Implement real-time eligibility checking.', 1, 1),
('M06', 'Service Date Outside Coverage Period', 'Check member eligibility dates before service delivery.', 1, 1),
('M07', 'Invalid Procedure Code', 'Regular training on current CPT/HCPCS codes. Implement code validation.', 1, 1),
('M08', 'Invalid Diagnosis Code', 'Train staff on ICD-10 coding. Implement diagnosis code validation.', 1, 1),
('M09', 'Timely Filing Limit Exceeded', 'Implement claim submission tracking with alerts. Submit claims within 90 days.', 1, 1),
('M10', 'Invalid Place of Service', 'Train staff on correct place of service codes for autism waiver services.', 1, 1),
('M11', 'Invalid Modifier', 'Educate staff on proper modifier usage. Implement modifier validation.', 1, 1),
('M12', 'Service Limit Exceeded', 'Track service utilization against authorization limits. Implement utilization alerts.', 1, 1),
('M13', 'Invalid Units', 'Train staff on proper unit calculation. Implement unit validation.', 1, 1),
('M14', 'Missing Documentation', 'Implement documentation checklist. Require complete documentation before billing.', 1, 0),
('M15', 'Invalid NPI', 'Maintain current provider database. Verify NPI numbers regularly.', 0, 1),
('M16', 'Service Requires Referral', 'Implement referral tracking system. Verify referral requirements.', 1, 1),
('M17', 'Invalid Rate Code', 'Maintain current rate schedules. Train staff on proper rate code usage.', 1, 0),
('M18', 'Provider Not Enrolled', 'Verify provider enrollment before service delivery. Maintain enrollment status.', 1, 1),
('M19', 'Invalid Service Date', 'Implement date validation. Train staff on proper date entry.', 1, 1),
('M20', 'Coordination of Benefits Issue', 'Verify insurance coverage and coordination requirements before service.', 1, 1);

-- Create view for denial dashboard statistics
CREATE VIEW IF NOT EXISTS denial_dashboard_stats AS
SELECT 
    COUNT(*) as total_denials,
    SUM(amount) as total_amount,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_denials,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_denials,
    COUNT(CASE WHEN status = 'appealed' THEN 1 END) as appealed_denials,
    COUNT(CASE WHEN status = 'resolved' AND resolution_type = 'paid' THEN 1 END) as successful_appeals,
    SUM(CASE WHEN status = 'resolved' AND resolution_type = 'paid' THEN resolution_amount ELSE 0 END) as recovered_amount,
    COUNT(CASE WHEN julianday('now') - julianday(denial_date) > 90 AND status != 'resolved' THEN 1 END) as aged_denials,
    COUNT(CASE WHEN appeal_deadline < date('now') AND status NOT IN ('resolved', 'appealed') THEN 1 END) as missed_deadlines
FROM claim_denials
WHERE denial_date >= date('now', '-12 months');

-- Create view for staff productivity
CREATE VIEW IF NOT EXISTS staff_denial_productivity AS
SELECT 
    u.id as user_id,
    u.full_name,
    COUNT(cd.id) as assigned_denials,
    COUNT(CASE WHEN cd.status = 'resolved' THEN 1 END) as resolved_denials,
    COUNT(CASE WHEN cd.status = 'resolved' AND cd.resolution_type = 'paid' THEN 1 END) as successful_recoveries,
    SUM(CASE WHEN cd.status = 'resolved' AND cd.resolution_type = 'paid' THEN cd.resolution_amount ELSE 0 END) as total_recovered,
    ROUND(AVG(CASE WHEN cd.status = 'resolved' THEN julianday(cd.resolution_date) - julianday(cd.assigned_date) END), 1) as avg_resolution_days,
    ROUND(COUNT(CASE WHEN cd.status = 'resolved' AND cd.resolution_type = 'paid' THEN 1 END) * 100.0 / 
          NULLIF(COUNT(CASE WHEN cd.status = 'resolved' THEN 1 END), 0), 1) as success_rate_percent
FROM users u
LEFT JOIN claim_denials cd ON u.id = cd.assigned_to 
    AND cd.assigned_date >= date('now', '-30 days')
WHERE u.role IN ('admin', 'billing_specialist')
GROUP BY u.id, u.full_name;

-- Insert sample denial data for testing (if needed)
-- This would typically be populated by actual denial data from claims processing

-- Add triggers to maintain data integrity
CREATE TRIGGER IF NOT EXISTS update_denial_timestamp 
    AFTER UPDATE ON claim_denials
    FOR EACH ROW 
BEGIN
    UPDATE claim_denials SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS update_appeal_timestamp 
    AFTER UPDATE ON claim_appeals
    FOR EACH ROW 
BEGIN
    UPDATE claim_appeals SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger to automatically update appeal_id in claim_denials when appeal is created
CREATE TRIGGER IF NOT EXISTS link_appeal_to_denial
    AFTER INSERT ON claim_appeals
    FOR EACH ROW
BEGIN
    UPDATE claim_denials 
    SET appeal_id = NEW.id, 
        appeal_status = 'submitted',
        appeal_submission_date = NEW.appeal_date
    WHERE id = NEW.denial_id;
END;