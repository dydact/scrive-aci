-- Simple test data insertion

-- First, insert staff members
INSERT IGNORE INTO autism_staff_members (id, staff_id, first_name, last_name, email, job_title, role, status) VALUES
(1, 1, 'System', 'Administrator', 'admin@aci.com', 'Administrator', 'Administrator', 'active'),
(2, 2, 'Sarah', 'Johnson', 'sarah@aci.com', 'Direct Support Professional', 'DSP', 'active'),
(3, 3, 'Michael', 'Brown', 'michael@aci.com', 'Case Manager', 'Case Manager', 'active'),
(4, 4, 'Jennifer', 'Davis', 'jennifer@aci.com', 'Clinical Supervisor', 'Supervisor', 'active');

-- Insert clients only (no staff assignments yet)
INSERT IGNORE INTO autism_clients (first_name, last_name, date_of_birth, gender, ma_number, city, state, status, enrollment_date) VALUES
('John', 'Smith', '2010-05-15', 'male', 'MA123456789', 'Baltimore', 'MD', 'active', '2024-01-15'),
('Emily', 'Johnson', '2012-08-22', 'female', 'MA987654321', 'Columbia', 'MD', 'active', '2024-02-01'),
('Michael', 'Williams', '2008-03-10', 'male', 'MA456789123', 'Towson', 'MD', 'active', '2024-01-20'),
('Sophie', 'Brown', '2015-11-30', 'female', 'MA789123456', 'Annapolis', 'MD', 'active', '2024-03-01'),
('James', 'Davis', '2011-07-18', 'male', 'MA321654987', 'Frederick', 'MD', 'inactive', '2023-12-01');

SELECT 'Test data inserted!' as status;