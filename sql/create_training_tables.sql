-- Training System Tables for Autism Waiver App
-- This script creates tables for the comprehensive training management system

-- Training modules table
CREATE TABLE IF NOT EXISTS training_modules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration_minutes INTEGER,
    content_url VARCHAR(255),
    video_url VARCHAR(255),
    is_required BOOLEAN DEFAULT 0,
    requires_renewal BOOLEAN DEFAULT 0,
    renewal_months INTEGER DEFAULT 12,
    passing_score INTEGER DEFAULT 80,
    order_index INTEGER DEFAULT 0,
    role_specific VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Training progress tracking
CREATE TABLE IF NOT EXISTS training_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    module_id INTEGER NOT NULL,
    started_date DATETIME,
    completed_date DATETIME,
    score INTEGER,
    attempts INTEGER DEFAULT 0,
    certificate_url VARCHAR(255),
    certificate_number VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (module_id) REFERENCES training_modules(id),
    UNIQUE(user_id, module_id)
);

-- Quiz questions for training modules
CREATE TABLE IF NOT EXISTS training_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    module_id INTEGER NOT NULL,
    question_text TEXT NOT NULL,
    question_type VARCHAR(20) DEFAULT 'multiple_choice',
    correct_answer TEXT,
    options TEXT, -- JSON array of options
    explanation TEXT,
    points INTEGER DEFAULT 1,
    order_index INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES training_modules(id)
);

-- User quiz responses
CREATE TABLE IF NOT EXISTS training_responses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    module_id INTEGER NOT NULL,
    question_id INTEGER NOT NULL,
    user_answer TEXT,
    is_correct BOOLEAN,
    attempt_number INTEGER DEFAULT 1,
    answered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (module_id) REFERENCES training_modules(id),
    FOREIGN KEY (question_id) REFERENCES training_questions(id)
);

-- Training resources and materials
CREATE TABLE IF NOT EXISTS training_resources (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    module_id INTEGER NOT NULL,
    resource_name VARCHAR(255) NOT NULL,
    resource_type VARCHAR(50), -- pdf, video, link, etc.
    resource_url VARCHAR(255),
    file_size INTEGER,
    is_downloadable BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES training_modules(id)
);

-- Training paths by role
CREATE TABLE IF NOT EXISTS training_paths (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role VARCHAR(50) NOT NULL,
    module_id INTEGER NOT NULL,
    sequence_order INTEGER NOT NULL,
    is_mandatory BOOLEAN DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES training_modules(id),
    UNIQUE(role, module_id)
);

-- Training notifications and reminders
CREATE TABLE IF NOT EXISTS training_notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    module_id INTEGER NOT NULL,
    notification_type VARCHAR(50), -- due_soon, overdue, expiring, etc.
    message TEXT,
    is_read BOOLEAN DEFAULT 0,
    sent_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (module_id) REFERENCES training_modules(id)
);

-- Insert sample training modules
INSERT INTO training_modules (category, title, description, duration_minutes, is_required, requires_renewal, role_specific, order_index) VALUES
-- Orientation modules
('orientation', 'Welcome to ACI', 'Introduction to American Caregivers Inc and our mission', 30, 1, 0, NULL, 1),
('orientation', 'Company Policies & Procedures', 'Overview of company policies, procedures, and expectations', 45, 1, 0, NULL, 2),
('orientation', 'Introduction to ABA', 'Basic principles of Applied Behavior Analysis', 60, 1, 0, NULL, 3),

-- Compliance modules
('compliance', 'HIPAA Privacy & Security', 'Understanding HIPAA requirements and protecting patient information', 45, 1, 1, NULL, 1),
('compliance', 'Mandated Reporter Training', 'Legal requirements for reporting suspected abuse or neglect', 30, 1, 1, NULL, 2),
('compliance', 'Workplace Safety', 'Safety procedures and emergency protocols', 30, 1, 1, NULL, 3),
('compliance', 'Maryland Medicaid Compliance', 'State-specific Medicaid requirements and regulations', 60, 1, 1, NULL, 4),

-- Clinical modules
('clinical', 'Data Collection Methods', 'Accurate data collection techniques for behavior tracking', 60, 0, 0, 'RBT', 1),
('clinical', 'Behavior Intervention Plans', 'Implementing and following behavior intervention plans', 90, 0, 0, 'RBT', 2),
('clinical', 'Parent Training Techniques', 'Effective strategies for parent education and involvement', 45, 0, 0, 'BCBA', 3),
('clinical', 'Supervision Best Practices', 'Effective supervision techniques for BCBAs', 60, 0, 1, 'BCBA', 4),

-- Billing modules
('billing', 'Maryland Medicaid Guidelines', 'Understanding Maryland Medicaid billing requirements', 60, 1, 1, NULL, 1),
('billing', 'CPT Coding for ABA', 'Proper CPT coding for ABA services', 45, 0, 1, 'billing', 2),
('billing', 'Documentation Requirements', 'Clinical documentation standards for billing', 30, 1, 0, NULL, 3),
('billing', 'Claims Processing', 'How to process and submit claims correctly', 45, 0, 0, 'billing', 4),

-- System modules
('system', 'Using the Client Portal', 'Navigation and features of the client management system', 20, 1, 0, NULL, 1),
('system', 'Mobile App Tutorial', 'Using the mobile app for session documentation', 15, 0, 0, NULL, 2),
('system', 'Reporting Features', 'Generating and understanding system reports', 30, 0, 0, NULL, 3),

-- Professional development
('professional', 'Ethics for Behavior Analysts', 'BACB ethics code and professional conduct', 90, 1, 1, 'BCBA', 1),
('professional', 'Cultural Competency', 'Working effectively with diverse populations', 45, 0, 0, NULL, 2),
('professional', 'Communication Skills', 'Effective communication with clients and families', 30, 0, 0, NULL, 3);

-- Insert role-specific training paths
INSERT INTO training_paths (role, module_id, sequence_order, is_mandatory) 
SELECT 'RBT', id, 
    CASE 
        WHEN title = 'Welcome to ACI' THEN 1
        WHEN title = 'Company Policies & Procedures' THEN 2
        WHEN title = 'Introduction to ABA' THEN 3
        WHEN title = 'HIPAA Privacy & Security' THEN 4
        WHEN title = 'Data Collection Methods' THEN 5
        WHEN title = 'Documentation Requirements' THEN 6
    END,
    1
FROM training_modules 
WHERE title IN ('Welcome to ACI', 'Company Policies & Procedures', 'Introduction to ABA', 
                'HIPAA Privacy & Security', 'Data Collection Methods', 'Documentation Requirements');

INSERT INTO training_paths (role, module_id, sequence_order, is_mandatory) 
SELECT 'BCBA', id, 
    CASE 
        WHEN title = 'HIPAA Privacy & Security' THEN 1
        WHEN title = 'Maryland Medicaid Compliance' THEN 2
        WHEN title = 'Supervision Best Practices' THEN 3
        WHEN title = 'Ethics for Behavior Analysts' THEN 4
        WHEN title = 'Documentation Requirements' THEN 5
    END,
    1
FROM training_modules 
WHERE title IN ('HIPAA Privacy & Security', 'Maryland Medicaid Compliance', 
                'Supervision Best Practices', 'Ethics for Behavior Analysts', 'Documentation Requirements');

INSERT INTO training_paths (role, module_id, sequence_order, is_mandatory) 
SELECT 'billing', id, 
    CASE 
        WHEN title = 'Maryland Medicaid Guidelines' THEN 1
        WHEN title = 'CPT Coding for ABA' THEN 2
        WHEN title = 'Claims Processing' THEN 3
        WHEN title = 'Documentation Requirements' THEN 4
        WHEN title = 'HIPAA Privacy & Security' THEN 5
    END,
    1
FROM training_modules 
WHERE title IN ('Maryland Medicaid Guidelines', 'CPT Coding for ABA', 'Claims Processing',
                'Documentation Requirements', 'HIPAA Privacy & Security');

-- Create indexes for performance
CREATE INDEX idx_training_progress_user ON training_progress(user_id);
CREATE INDEX idx_training_progress_module ON training_progress(module_id);
CREATE INDEX idx_training_questions_module ON training_questions(module_id);
CREATE INDEX idx_training_responses_user ON training_responses(user_id);
CREATE INDEX idx_training_paths_role ON training_paths(role);
CREATE INDEX idx_training_notifications_user ON training_notifications(user_id);