-- Insert test data for American Caregivers Inc

-- Insert test clients
INSERT INTO autism_clients (first_name, last_name, date_of_birth, gender, ma_number, address, city, state, zip, phone, email, emergency_contact_name, emergency_contact_phone, status, enrollment_date) VALUES
('John', 'Smith', '2010-05-15', 'male', 'MA123456789', '123 Main St', 'Baltimore', 'MD', '21201', '410-555-0101', 'john.family@email.com', 'Jane Smith', '410-555-0102', 'active', '2024-01-15'),
('Emily', 'Johnson', '2012-08-22', 'female', 'MA987654321', '456 Oak Ave', 'Columbia', 'MD', '21044', '410-555-0201', 'emily.family@email.com', 'Robert Johnson', '410-555-0202', 'active', '2024-02-01'),
('Michael', 'Williams', '2008-03-10', 'male', 'MA456789123', '789 Pine Rd', 'Towson', 'MD', '21204', '410-555-0301', 'michael.family@email.com', 'Sarah Williams', '410-555-0302', 'active', '2024-01-20'),
('Sophie', 'Brown', '2015-11-30', 'female', 'MA789123456', '321 Elm St', 'Annapolis', 'MD', '21401', '410-555-0401', 'sophie.family@email.com', 'David Brown', '410-555-0402', 'active', '2024-03-01'),
('James', 'Davis', '2011-07-18', 'male', 'MA321654987', '654 Maple Dr', 'Frederick', 'MD', '21701', '301-555-0501', 'james.family@email.com', 'Linda Davis', '301-555-0502', 'inactive', '2023-12-01')
ON DUPLICATE KEY UPDATE id=id;

-- Assign staff to clients
INSERT INTO autism_staff_assignments (staff_id, client_id, assignment_type, start_date, is_active, status) VALUES
(1, 1, 'primary', '2024-01-15', TRUE, 'active'),
(2, 1, 'secondary', '2024-01-15', TRUE, 'active'),
(2, 2, 'primary', '2024-02-01', TRUE, 'active'),
(3, 3, 'case_manager', '2024-01-20', TRUE, 'active'),
(2, 3, 'primary', '2024-01-20', TRUE, 'active'),
(4, 4, 'case_manager', '2024-03-01', TRUE, 'active'),
(1, 4, 'primary', '2024-03-01', TRUE, 'active'),
(3, 5, 'case_manager', '2023-12-01', FALSE, 'inactive')
ON DUPLICATE KEY UPDATE id=id;

-- Insert some session notes
INSERT INTO autism_session_notes (client_id, staff_id, session_date, start_time, end_time, service_type_id, session_type, goals_addressed, activities, client_response, progress_notes, status, created_by) VALUES
(1, 2, CURDATE(), '09:00:00', '11:00:00', 1, 'IISS', 'Social skills, Communication', 'Practiced greeting peers, turn-taking games', 'Engaged well, showed improvement', 'Client demonstrated good progress in social interactions', 'completed', 2),
(1, 2, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '14:00:00', '16:00:00', 1, 'IISS', 'Daily living skills', 'Practiced hygiene routine, meal preparation', 'Required prompting but completed tasks', 'Continues to need verbal prompts for sequencing', 'completed', 2),
(2, 2, CURDATE(), '10:00:00', '12:00:00', 2, 'TI', 'Behavioral goals', 'Implemented behavior plan, practiced coping strategies', 'Used strategies independently twice', 'Excellent progress with self-regulation', 'completed', 2),
(3, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '13:00:00', '15:00:00', 1, 'IISS', 'Academic support', 'Homework assistance, organization skills', 'Completed all assignments', 'Maintaining good academic progress', 'completed', 2),
(4, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '09:30:00', '11:30:00', 1, 'IISS', 'Play skills, Social interaction', 'Structured play activities with peers', 'Participated with minimal support', 'Showing increased independence in play', 'approved', 1)
ON DUPLICATE KEY UPDATE id=id;

-- Update session notes to add created_by values
UPDATE autism_session_notes SET created_by = staff_id WHERE created_by IS NULL;

-- Success message
SELECT 'Test data inserted successfully!' as status;