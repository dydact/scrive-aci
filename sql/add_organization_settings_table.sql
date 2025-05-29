-- Create organization settings table
CREATE TABLE IF NOT EXISTS autism_organization_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    org_name VARCHAR(255) NOT NULL,
    org_address VARCHAR(255),
    org_city VARCHAR(100),
    org_state VARCHAR(2),
    org_zip VARCHAR(10),
    org_phone VARCHAR(20),
    org_email VARCHAR(255),
    org_website VARCHAR(255),
    tax_id VARCHAR(20),
    npi_number VARCHAR(20),
    medicaid_provider_id VARCHAR(50),
    billing_contact_name VARCHAR(255),
    billing_contact_email VARCHAR(255),
    billing_contact_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default organization settings
INSERT INTO autism_organization_settings (org_name, org_state)
VALUES ('American Caregivers Inc', 'NY')
ON DUPLICATE KEY UPDATE org_name = VALUES(org_name); 