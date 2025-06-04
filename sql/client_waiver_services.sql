-- Waiver types available in Maryland
CREATE TABLE IF NOT EXISTS autism_waiver_types (
    id INT(11) NOT NULL AUTO_INCREMENT,
    waiver_code VARCHAR(20) NOT NULL,
    waiver_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY idx_waiver_code (waiver_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Maryland waiver types
INSERT IGNORE INTO autism_waiver_types (waiver_code, waiver_name, description) VALUES 
('AW', 'Autism Waiver', 'Maryland Autism Waiver Program for children and adults with Autism Spectrum Disorder'),
('CFC', 'Community First Choice', 'Community-based services and supports for individuals with disabilities'),
('CP', 'Community Pathways', 'Supports for individuals with developmental disabilities in community settings'),
('FCP', 'Family Supports', 'Family-centered supports for individuals with developmental disabilities'),
('TBI', 'Brain Injury Waiver', 'Services for individuals with traumatic brain injuries'),
('MDH', 'Medical Day Care', 'Health services in a day program setting'),
('ICS', 'Increased Community Services', 'Enhanced community services for individuals transitioning from institutions');

-- Service authorizations for clients
CREATE TABLE IF NOT EXISTS autism_client_authorizations (
    id INT(11) NOT NULL AUTO_INCREMENT,
    client_id INT(11) NOT NULL,
    waiver_type_id INT(11) NOT NULL,
    service_type_id INT(11) NOT NULL,
    fiscal_year INT(4) NOT NULL,
    fiscal_year_start DATE NOT NULL,
    fiscal_year_end DATE NOT NULL,
    weekly_hours DECIMAL(5,2),
    yearly_hours DECIMAL(7,2),
    used_hours DECIMAL(7,2) DEFAULT 0,
    remaining_hours DECIMAL(7,2),
    authorization_number VARCHAR(50),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('active','expired','suspended','terminated') DEFAULT 'active',
    notes TEXT,
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_client (client_id),
    KEY idx_waiver (waiver_type_id),
    KEY idx_service (service_type_id),
    KEY idx_fiscal_year (fiscal_year),
    KEY idx_dates (start_date, end_date),
    CONSTRAINT fk_auth_client FOREIGN KEY (client_id) REFERENCES autism_clients(id),
    CONSTRAINT fk_auth_waiver FOREIGN KEY (waiver_type_id) REFERENCES autism_waiver_types(id),
    CONSTRAINT fk_auth_service FOREIGN KEY (service_type_id) REFERENCES autism_service_types(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add waiver_type_id to clients table
ALTER TABLE autism_clients 
ADD COLUMN waiver_type_id INT(11) AFTER ma_number,
ADD KEY idx_waiver_type (waiver_type_id);

-- Update service types with waiver associations
ALTER TABLE autism_service_types
ADD COLUMN waiver_type_id INT(11) AFTER service_code,
ADD KEY idx_waiver_type (waiver_type_id);

-- Update service types for Autism Waiver
UPDATE autism_service_types 
SET waiver_type_id = (SELECT id FROM autism_waiver_types WHERE waiver_code = 'AW')
WHERE service_code IN ('IISS', 'TI', 'RESPITE', 'FAMILY');

-- Add additional service types for different waivers
INSERT IGNORE INTO autism_service_types (service_code, service_name, description, rate, unit_type, waiver_type_id) 
SELECT 'PC', 'Personal Care', 'Assistance with activities of daily living', 25.00, 'hour', id 
FROM autism_waiver_types WHERE waiver_code = 'CFC'
UNION ALL
SELECT 'DS', 'Day Services', 'Structured day program services', 45.00, 'day', id 
FROM autism_waiver_types WHERE waiver_code = 'CP'
UNION ALL
SELECT 'SE', 'Supported Employment', 'Job coaching and employment support', 35.00, 'hour', id 
FROM autism_waiver_types WHERE waiver_code = 'CP'
UNION ALL
SELECT 'RH', 'Residential Habilitation', '24/7 residential support services', 150.00, 'day', id 
FROM autism_waiver_types WHERE waiver_code = 'CP';

-- Billing tracking view
CREATE OR REPLACE VIEW autism_authorization_summary AS
SELECT 
    ca.id,
    ca.client_id,
    CONCAT(c.first_name, ' ', c.last_name) as client_name,
    c.ma_number,
    wt.waiver_name,
    st.service_name,
    st.service_code,
    ca.fiscal_year,
    ca.weekly_hours,
    ca.yearly_hours,
    ca.used_hours,
    ca.remaining_hours,
    ROUND((ca.used_hours / ca.yearly_hours) * 100, 2) as usage_percentage,
    ca.start_date,
    ca.end_date,
    ca.status,
    CASE 
        WHEN ca.end_date < CURDATE() THEN 'Expired'
        WHEN ca.remaining_hours <= 0 THEN 'Exhausted'
        WHEN ca.remaining_hours < (ca.weekly_hours * 4) THEN 'Low Hours'
        ELSE 'Available'
    END as availability_status
FROM autism_client_authorizations ca
JOIN autism_clients c ON ca.client_id = c.id
JOIN autism_waiver_types wt ON ca.waiver_type_id = wt.id
JOIN autism_service_types st ON ca.service_type_id = st.id;

-- Function to calculate fiscal year
DELIMITER $$
CREATE FUNCTION IF NOT EXISTS get_fiscal_year(check_date DATE)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE fiscal_year INT;
    IF MONTH(check_date) >= 7 THEN
        SET fiscal_year = YEAR(check_date) + 1;
    ELSE
        SET fiscal_year = YEAR(check_date);
    END IF;
    RETURN fiscal_year;
END$$
DELIMITER ;

-- Trigger to update remaining hours
DELIMITER $$
CREATE TRIGGER IF NOT EXISTS update_remaining_hours
BEFORE UPDATE ON autism_client_authorizations
FOR EACH ROW
BEGIN
    SET NEW.remaining_hours = NEW.yearly_hours - NEW.used_hours;
END$$
DELIMITER ;