-- Update autism_service_types table with current Maryland Medicaid rates and limits

-- First, clear existing service types
TRUNCATE TABLE autism_service_types;

-- Insert Maryland Autism Waiver service types with 2024 rates
INSERT INTO autism_service_types (service_code, service_name, description, rate, unit_type, is_active) VALUES
('W9307', 'Regular Therapeutic Integration', 'Support services for individuals with autism in community settings (80 units/week limit)', 9.28, 'unit', 1),
('W9308', 'Intensive Therapeutic Integration', 'Enhanced support services requiring higher staff qualifications (60 units/week limit)', 11.60, 'unit', 1),
('W9306', 'Intensive Individual Support Services (IISS)', 'One-on-one intensive support for individuals with complex needs (160 units/week limit)', 12.80, 'unit', 1),
('W9314', 'Respite Care', 'Temporary relief for primary caregivers (96 units/day, 1344 units/year limit)', 9.07, 'unit', 1),
('W9315', 'Family Consultation', 'Training and support for family members (24 units/day, 160 units/year limit)', 38.10, 'unit', 1);

-- Update any existing sessions to use the new service codes (if applicable)
-- This is a safety measure - adjust based on your current data
UPDATE autism_sessions 
SET service_type_id = (SELECT id FROM autism_service_types WHERE service_code = 'W9306' LIMIT 1)
WHERE service_type_id IS NULL OR service_type_id = 0;

-- Create a view for easy service lookup with formatted information
CREATE OR REPLACE VIEW v_autism_services AS
SELECT 
    id,
    service_code,
    service_name,
    CONCAT('$', FORMAT(rate, 2)) as formatted_rate,
    unit_type,
    description,
    CASE is_active 
        WHEN 1 THEN 'Active' 
        ELSE 'Inactive' 
    END as status
FROM autism_service_types
ORDER BY service_code;