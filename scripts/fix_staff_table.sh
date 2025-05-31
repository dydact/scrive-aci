#!/bin/bash
# Quick fix for missing autism_staff_members table

echo "Creating autism_staff_members table..."

docker exec scrive-aci-mysql-1 mysql -u openemr -p'openemr' openemr -e "
CREATE TABLE IF NOT EXISTS autism_staff_members (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11),
    employee_id VARCHAR(50),
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    role VARCHAR(50),
    hire_date DATE,
    status ENUM('active','inactive','on_leave') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_id (user_id),
    KEY idx_employee_id (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample staff members
INSERT IGNORE INTO autism_staff_members (user_id, employee_id, full_name, email, phone, role, hire_date, status) VALUES 
(1, 'EMP001', 'System Administrator', 'admin@americancaregivers.com', '(240) 555-0001', 'Administrator', '2020-01-01', 'active'),
(NULL, 'EMP002', 'Jane Manager', 'jane.manager@americancaregivers.com', '(240) 555-0002', 'Case Manager', '2021-03-15', 'active'),
(NULL, 'EMP003', 'John Support', 'john.support@americancaregivers.com', '(240) 555-0003', 'DSP', '2022-06-01', 'active');
"

echo "Done! Checking tables..."
docker exec scrive-aci-mysql-1 mysql -u openemr -p'openemr' openemr -e "SHOW TABLES LIKE 'autism_%';"