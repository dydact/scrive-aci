-- iris-emr Database Customization Script for American Caregivers Incorporated
-- This script customizes the OpenEMR database for American Caregivers Incorporated

-- Update global settings with client information
UPDATE `globals` SET `gl_value` = 'American Caregivers Incorporated' WHERE `gl_name` = 'practice_name';
UPDATE `globals` SET `gl_value` = '2301 Broadbirch Drive, Ste 135' WHERE `gl_name` = 'practice_address';
UPDATE `globals` SET `gl_value` = 'Silver Spring' WHERE `gl_name` = 'practice_city';
UPDATE `globals` SET `gl_value` = 'MD' WHERE `gl_name` = 'practice_state';
UPDATE `globals` SET `gl_value` = '20904' WHERE `gl_name` = 'practice_zip';
UPDATE `globals` SET `gl_value` = '(240) 555-1234' WHERE `gl_name` = 'practice_phone';
UPDATE `globals` SET `gl_value` = 'info@americancaregivers.example.com' WHERE `gl_name` = 'practice_email';
UPDATE `globals` SET `gl_value` = 'www.americancaregivers.example.com' WHERE `gl_name` = 'practice_website';

-- Update branding
UPDATE `globals` SET `gl_value` = 'iris' WHERE `gl_name` = 'openemr_name';
UPDATE `globals` SET `gl_value` = 'Powered by dydact LLMs' WHERE `gl_name` = 'login_tagline_text';

-- Add a facility record for the American Caregivers location
INSERT INTO `facility` (
    `name`,
    `phone`,
    `fax`,
    `street`,
    `city`,
    `state`,
    `postal_code`,
    `country_code`,
    `federal_ein`,
    `service_location`,
    `billing_location`,
    `accepts_assignment`,
    `pos_code`,
    `x12_sender_id`,
    `attn`,
    `domain_identifier`,
    `facility_npi`,
    `tax_id_type`,
    `color`,
    `primary_business_entity`,
    `facility_code`,
    `extra_validation`,
    `mail_street`,
    `mail_street2`,
    `mail_city`,
    `mail_state`,
    `mail_zip`,
    `oid`,
    `iban`,
    `info`,
    `inactive`
) VALUES (
    'American Caregivers Incorporated',
    '(240) 555-1234',
    '(240) 555-5678',
    '2301 Broadbirch Drive, Ste 135',
    'Silver Spring',
    'MD',
    '20904',
    'USA',
    '',
    1,
    1,
    1,
    11,
    '',
    '',
    '',
    '',
    '',
    '#94D6E7',
    1,
    '',
    1,
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    '',
    0
); 