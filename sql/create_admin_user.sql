-- Create Master Admin User for Autism Waiver Management System
-- Run this SQL after importing the main database schemas

-- Create the admin user with a temporary password
-- Default credentials: admin / AdminPass123!
-- IMPORTANT: Change this password immediately after first login!

INSERT INTO autism_users (
    username, 
    password, 
    email, 
    first_name, 
    last_name, 
    access_level, 
    user_type,
    is_active,
    created_at
) VALUES (
    'admin',
    '$2y$10$YW5QpC5zYXczFPmHkYcxHOzwGmzpHyBcQrFymLh1FSW/7N11jEU2a', -- AdminPass123!
    'admin@aci.com',
    'System',
    'Administrator',
    5, -- Full admin access
    'admin',
    1,
    NOW()
);

-- Also create a corresponding staff member entry for the admin
INSERT INTO autism_staff_members (
    user_id,
    employee_id,
    first_name,
    last_name,
    email,
    phone,
    role,
    department,
    hire_date,
    status
) VALUES (
    LAST_INSERT_ID(),
    'ADMIN001',
    'System',
    'Administrator',
    'admin@aci.com',
    '000-000-0000',
    'Administrator',
    'Administration',
    CURDATE(),
    'active'
);

-- Create some test users for different access levels
-- Password for all test users: TestPass123!

-- DSP User (Direct Support Professional - Level 2)
INSERT INTO autism_users (username, password, email, first_name, last_name, access_level, user_type, is_active)
VALUES (
    'dsp_test',
    '$2y$10$M0qz3JgDqJ8VGxJQxPtdPeWHQxvJQ9OPYmSlRYtg7CxOH7xC3Zqt2', -- TestPass123!
    'dsp@aci.com',
    'Sarah',
    'Johnson',
    2,
    'staff',
    1
);

INSERT INTO autism_staff_members (user_id, employee_id, first_name, last_name, email, role, department, status)
VALUES (LAST_INSERT_ID(), 'DSP001', 'Sarah', 'Johnson', 'dsp@aci.com', 'DSP', 'Direct Care', 'active');

-- Case Manager User (Level 3)
INSERT INTO autism_users (username, password, email, first_name, last_name, access_level, user_type, is_active)
VALUES (
    'cm_test',
    '$2y$10$M0qz3JgDqJ8VGxJQxPtdPeWHQxvJQ9OPYmSlRYtg7CxOH7xC3Zqt2', -- TestPass123!
    'cm@aci.com',
    'Michael',
    'Brown',
    3,
    'staff',
    1
);

INSERT INTO autism_staff_members (user_id, employee_id, first_name, last_name, email, role, department, status)
VALUES (LAST_INSERT_ID(), 'CM001', 'Michael', 'Brown', 'cm@aci.com', 'Case Manager', 'Clinical', 'active');

-- Supervisor User (Level 4)
INSERT INTO autism_users (username, password, email, first_name, last_name, access_level, user_type, is_active)
VALUES (
    'supervisor_test',
    '$2y$10$M0qz3JgDqJ8VGxJQxPtdPeWHQxvJQ9OPYmSlRYtg7CxOH7xC3Zqt2', -- TestPass123!
    'supervisor@aci.com',
    'Jennifer',
    'Davis',
    4,
    'staff',
    1
);

INSERT INTO autism_staff_members (user_id, employee_id, first_name, last_name, email, role, department, status)
VALUES (LAST_INSERT_ID(), 'SUP001', 'Jennifer', 'Davis', 'supervisor@aci.com', 'Supervisor', 'Management', 'active');

-- Display created users
SELECT 
    u.username,
    u.first_name,
    u.last_name,
    u.access_level,
    CASE u.access_level
        WHEN 1 THEN 'Read Only'
        WHEN 2 THEN 'DSP (Direct Support)'
        WHEN 3 THEN 'Case Manager'
        WHEN 4 THEN 'Supervisor'
        WHEN 5 THEN 'Administrator'
    END as access_level_name,
    u.user_type,
    u.is_active
FROM autism_users u
ORDER BY u.access_level DESC;

-- Create some test clients for testing
INSERT INTO autism_clients (first_name, last_name, ma_number, date_of_birth, gender, address, city, state, zip, phone, emergency_contact_name, emergency_contact_phone, status)
VALUES 
('John', 'Doe', 'MA987654321', '2010-05-15', 'male', '123 Main St', 'Baltimore', 'MD', '21201', '410-555-0001', 'Jane Doe', '410-555-0002', 'active'),
('Emily', 'Smith', 'MA123456789', '2012-08-22', 'female', '456 Oak Ave', 'Annapolis', 'MD', '21401', '410-555-0003', 'Robert Smith', '410-555-0004', 'active'),
('Michael', 'Johnson', 'MA555555555', '2015-01-01', 'male', '789 Pine Rd', 'Columbia', 'MD', '21044', '410-555-0005', 'Lisa Johnson', '410-555-0006', 'active');

-- Assign clients to staff for testing
INSERT INTO autism_staff_assignments (staff_id, client_id, assignment_type, start_date, status)
VALUES 
(2, 1, 'primary', CURDATE(), 'active'), -- Sarah (DSP) -> John Doe
(2, 2, 'primary', CURDATE(), 'active'), -- Sarah (DSP) -> Emily Smith
(3, 1, 'case_manager', CURDATE(), 'active'), -- Michael (CM) -> John Doe
(3, 2, 'case_manager', CURDATE(), 'active'), -- Michael (CM) -> Emily Smith
(3, 3, 'case_manager', CURDATE(), 'active'); -- Michael (CM) -> Michael Johnson

COMMIT;

-- Summary of login credentials:
-- ================================
-- Master Admin:
--   Username: admin
--   Password: AdminPass123!
--   Access: Full system (Level 5)
--
-- Test DSP:
--   Username: dsp_test
--   Password: TestPass123!
--   Access: Session notes, assigned clients (Level 2)
--
-- Test Case Manager:
--   Username: cm_test
--   Password: TestPass123!
--   Access: Treatment plans, all clients (Level 3)
--
-- Test Supervisor:
--   Username: supervisor_test
--   Password: TestPass123!
--   Access: Staff management, reports (Level 4)
--
-- IMPORTANT: Change all passwords after first login!