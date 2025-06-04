-- Create real ACG user accounts with proper roles and permissions
-- First update the domain in config

-- Delete test users
DELETE FROM autism_users WHERE email LIKE '%@aci.com';

-- Create Supreme Admin - Frank (Level 6 - highest possible)
INSERT INTO autism_users (username, email, password_hash, access_level, full_name, created_at) 
VALUES ('frank', 'frank@acgcares.com', '$2y$10$YourHashHere', 6, 'Frank (Supreme Admin)', NOW());

-- Create CEO - Mary Emah (Level 5 - Admin without technical features)
INSERT INTO autism_users (username, email, password_hash, access_level, full_name, created_at) 
VALUES ('mary.emah', 'mary.emah@acgcares.com', '$2y$10$YourHashHere', 5, 'Mary Emah (CEO)', NOW());

-- Create Executive - Dr. Ukpeh (Level 5 - Same as CEO)
INSERT INTO autism_users (username, email, password_hash, access_level, full_name, created_at) 
VALUES ('drukpeh', 'drukpeh@duck.com', '$2y$10$YourHashHere', 5, 'Dr. Ukpeh (Executive)', NOW());

-- Create HR Officer - Amanda Georgi (Level 4 - Supervisor with HR access)
INSERT INTO autism_users (username, email, password_hash, access_level, full_name, created_at) 
VALUES ('amanda.georgi', 'amanda.georgi@acgcares.com', '$2y$10$YourHashHere', 4, 'Amanda Georgi (HR Officer)', NOW());

-- Create Site Supervisor - Edwin Recto (Level 4 - Clinical Supervisor)
INSERT INTO autism_users (username, email, password_hash, access_level, full_name, created_at) 
VALUES ('edwin.recto', 'edwin.recto@acgcares.com', '$2y$10$YourHashHere', 4, 'Edwin Recto (Site Supervisor)', NOW());

-- Create Billing Admin - Pam Pastor (Level 4 - Billing Admin)
INSERT INTO autism_users (username, email, password_hash, access_level, full_name, created_at) 
VALUES ('pam.pastor', 'pam.pastor@acgcares.com', '$2y$10$YourHashHere', 4, 'Pam Pastor (Billing Admin)', NOW());

-- Create Billing Admin - Yanika Crosse (Level 4 - Billing Admin)
INSERT INTO autism_users (username, email, password_hash, access_level, full_name, created_at) 
VALUES ('yanika.crosse', 'yanika.crosse@acgcares.com', '$2y$10$YourHashHere', 4, 'Yanika Crosse (Billing Admin)', NOW());

-- Create System Admin - Alvin Ukpeh (Level 5 - Admin with less privileges than Frank)
INSERT INTO autism_users (username, email, password_hash, access_level, full_name, created_at) 
VALUES ('alvin.ukpeh', 'alvin.ukpeh@acgcares.com', '$2y$10$YourHashHere', 5, 'Alvin Ukpeh (System Admin)', NOW());

-- Add role-specific permissions in a new table
CREATE TABLE IF NOT EXISTS autism_user_permissions (
    user_id INT,
    permission VARCHAR(100),
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES autism_users(id),
    PRIMARY KEY (user_id, permission)
);

-- Grant specific permissions
-- Frank gets all permissions
INSERT INTO autism_user_permissions (user_id, permission) 
SELECT id, 'supreme_admin' FROM autism_users WHERE email = 'frank@acgcares.com';

-- Mary and Dr. Ukpeh get executive permissions (no technical)
INSERT INTO autism_user_permissions (user_id, permission) 
SELECT id, 'executive_dashboard' FROM autism_users WHERE email IN ('mary.emah@acgcares.com', 'drukpeh@duck.com');

INSERT INTO autism_user_permissions (user_id, permission) 
SELECT id, 'financial_overview' FROM autism_users WHERE email IN ('mary.emah@acgcares.com', 'drukpeh@duck.com');

-- Amanda gets HR permissions
INSERT INTO autism_user_permissions (user_id, permission) 
SELECT id, 'hr_management' FROM autism_users WHERE email = 'amanda.georgi@acgcares.com';

INSERT INTO autism_user_permissions (user_id, permission) 
SELECT id, 'employee_records' FROM autism_users WHERE email = 'amanda.georgi@acgcares.com';

-- Edwin gets clinical supervision permissions
INSERT INTO autism_user_permissions (user_id, permission) 
SELECT id, 'clinical_oversight' FROM autism_users WHERE email = 'edwin.recto@acgcares.com';

INSERT INTO autism_user_permissions (user_id, permission) 
SELECT id, 'waiver_administration' FROM autism_users WHERE email = 'edwin.recto@acgcares.com';

-- Pam and Yanika get billing permissions
INSERT INTO autism_user_permissions (user_id, permission) 
SELECT id, 'billing_management' FROM autism_users WHERE email IN ('pam.pastor@acgcares.com', 'yanika.crosse@acgcares.com');

INSERT INTO autism_user_permissions (user_id, permission) 
SELECT id, 'hours_entry' FROM autism_users WHERE email IN ('pam.pastor@acgcares.com', 'yanika.crosse@acgcares.com');

-- Alvin gets system admin (but not supreme)
INSERT INTO autism_user_permissions (user_id, permission) 
SELECT id, 'system_admin' FROM autism_users WHERE email = 'alvin.ukpeh@acgcares.com';