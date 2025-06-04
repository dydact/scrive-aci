# Production Server Update Steps

Since you're on the deployment server, here are the exact steps to update the production system:

## Step 1: Update Database Authentication

First, let's update the authentication to use the database instead of hardcoded credentials.

### 1.1 Create/Update the OpenEMR Integration File
Make sure `/src/openemr_integration.php` has this authenticateOpenEMRUser function:

```php
function authenticateOpenEMRUser($username, $password) {
    try {
        $pdo = getDatabase();
        
        // Try email first, then username
        $stmt = $pdo->prepare("
            SELECT id as user_id, username, email, password_hash, 
                   access_level, full_name, role
            FROM autism_users 
            WHERE email = ? OR username = ?
            LIMIT 1
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Parse full name
            $nameParts = explode(' ', $user['full_name']);
            $user['first_name'] = $nameParts[0] ?? '';
            $user['last_name'] = $nameParts[count($nameParts)-1] ?? '';
            $user['title'] = $user['role'] ?? 'User';
            
            return $user;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Auth error: " . $e->getMessage());
        return false;
    }
}
```

## Step 2: Run Database Updates

### 2.1 Add role column to users table (if not exists)
```sql
ALTER TABLE autism_users ADD COLUMN IF NOT EXISTS role VARCHAR(100) DEFAULT NULL;
```

### 2.2 Create user permissions table
```sql
CREATE TABLE IF NOT EXISTS autism_user_permissions (
    user_id INT,
    permission VARCHAR(100),
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES autism_users(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, permission)
);
```

### 2.3 Insert the new user accounts
```sql
-- Delete old test users
DELETE FROM autism_users WHERE email LIKE '%@aci.com';

-- Insert real users with pre-hashed passwords
INSERT INTO autism_users (username, email, password_hash, access_level, full_name, role, created_at) VALUES
('frank', 'frank@acgcares.com', '$2y$10$4KXNqkYBqFn3TqzKpDXXhOw8mWJQgPKwSQFGwKqDqHcwPGzC3y.Hi', 6, 'Frank (Supreme Admin)', 'Supreme Administrator', NOW()),
('mary.emah', 'mary.emah@acgcares.com', '$2y$10$Eq/8TqKHfXBM7LQx8cNpHu7E3qVRqNFGpGKQXxHuCGz6Ql5Wa8J6S', 5, 'Mary Emah', 'Chief Executive Officer', NOW()),
('drukpeh', 'drukpeh@duck.com', '$2y$10$a9GQXr8VgGZmPVV6HfKmWOKjL4X2yTPQKPO6SZDqTlMa5jNqgKwgK', 5, 'Dr. Ukpeh', 'Executive', NOW()),
('amanda.georgi', 'amanda.georgi@acgcares.com', '$2y$10$RyALKzML5OYS9CBd5cNbL.xweGJn.0n7IyYHfxQXdtLgYZ5x2XCCO', 4, 'Amanda Georgi', 'Human Resources Officer', NOW()),
('edwin.recto', 'edwin.recto@acgcares.com', '$2y$10$0K5DbYm6Qaq0xNckNNNkG.cY3YNGsczGfmFPHEVKvJMvbsZKsxjmG', 4, 'Edwin Recto', 'Site Supervisor / Clinical Lead', NOW()),
('pam.pastor', 'pam.pastor@acgcares.com', '$2y$10$YtXKUJMzj8m5LzKpV7kHXuQ7yxG9mX5bKC7VEXb5TRLMQzJ7fQBGy', 4, 'Pam Pastor', 'Billing Administrator', NOW()),
('yanika.crosse', 'yanika.crosse@acgcares.com', '$2y$10$YtXKUJMzj8m5LzKpV7kHXuQ7yxG9mX5bKC7VEXb5TRLMQzJ7fQBGy', 4, 'Yanika Crosse', 'Billing Administrator', NOW()),
('alvin.ukpeh', 'alvin.ukpeh@acgcares.com', '$2y$10$BzxOZ6mMwLQK8FGCnYb0S.TzL7E2gBJPHgZqJ5SZQXHfqKT/cDOaO', 5, 'Alvin Ukpeh', 'System Administrator', NOW())
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), access_level=VALUES(access_level), role=VALUES(role);
```

### 2.4 Update service types with Maryland rates
```sql
TRUNCATE TABLE autism_service_types;

INSERT INTO autism_service_types (service_code, service_name, description, rate, unit_type, is_active) VALUES
('W9307', 'Regular Therapeutic Integration', 'Support services for individuals with autism in community settings (80 units/week limit)', 9.28, 'unit', 1),
('W9308', 'Intensive Therapeutic Integration', 'Enhanced support services requiring higher staff qualifications (60 units/week limit)', 11.60, 'unit', 1),
('W9306', 'Intensive Individual Support Services (IISS)', 'One-on-one intensive support for individuals with complex needs (160 units/week limit)', 12.80, 'unit', 1),
('W9314', 'Respite Care', 'Temporary relief for primary caregivers (96 units/day, 1344 units/year limit)', 9.07, 'unit', 1),
('W9315', 'Family Consultation', 'Training and support for family members (24 units/day, 160 units/year limit)', 38.10, 'unit', 1);
```

## Step 3: Update Configuration Files

### 3.1 Update `/src/config.php`
Change these values:
```php
// Medicaid billing settings - American Caregivers Inc
define('MEDICAID_PROVIDER_ID', '1013104314');
define('ORGANIZATION_NPI', '1013104314');
define('ORGANIZATION_TAX_ID', '52-2305229');
define('TAXONOMY_CODE', '251C00000X');
```

### 3.2 Create `/config/claimmd.php`
This file contains the Claim.MD API credentials (see the file I created earlier).

## Step 4: Upload New Files

Upload these new files to the production server:
- `/autism_waiver_app/integrations/claim_md_api.php`
- `/autism_waiver_app/cron/submit_claims_to_claimmd.php`
- `/autism_waiver_app/check_eligibility.php`
- `/config/claimmd.php`

## Step 5: Test the Login

After completing these steps:
1. Clear your browser cache
2. Go to https://aci.dydact.io
3. Try logging in with: frank@acgcares.com / Supreme2024!

## Quick SQL to Run All At Once

```sql
-- Run all updates in one go
ALTER TABLE autism_users ADD COLUMN IF NOT EXISTS role VARCHAR(100) DEFAULT NULL;

CREATE TABLE IF NOT EXISTS autism_user_permissions (
    user_id INT,
    permission VARCHAR(100),
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES autism_users(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, permission)
);

DELETE FROM autism_users WHERE email LIKE '%@aci.com';

-- Insert users (passwords are already hashed)
INSERT INTO autism_users (username, email, password_hash, access_level, full_name, role) VALUES
('frank', 'frank@acgcares.com', '$2y$10$4KXNqkYBqFn3TqzKpDXXhOw8mWJQgPKwSQFGwKqDqHcwPGzC3y.Hi', 6, 'Frank (Supreme Admin)', 'Supreme Administrator'),
('mary.emah', 'mary.emah@acgcares.com', '$2y$10$Eq/8TqKHfXBM7LQx8cNpHu7E3qVRqNFGpGKQXxHuCGz6Ql5Wa8J6S', 5, 'Mary Emah', 'Chief Executive Officer'),
('drukpeh', 'drukpeh@duck.com', '$2y$10$a9GQXr8VgGZmPVV6HfKmWOKjL4X2yTPQKPO6SZDqTlMa5jNqgKwgK', 5, 'Dr. Ukpeh', 'Executive'),
('amanda.georgi', 'amanda.georgi@acgcares.com', '$2y$10$RyALKzML5OYS9CBd5cNbL.xweGJn.0n7IyYHfxQXdtLgYZ5x2XCCO', 4, 'Amanda Georgi', 'Human Resources Officer'),
('edwin.recto', 'edwin.recto@acgcares.com', '$2y$10$0K5DbYm6Qaq0xNckNNNkG.cY3YNGsczGfmFPHEVKvJMvbsZKsxjmG', 4, 'Edwin Recto', 'Site Supervisor / Clinical Lead'),
('pam.pastor', 'pam.pastor@acgcares.com', '$2y$10$YtXKUJMzj8m5LzKpV7kHXuQ7yxG9mX5bKC7VEXb5TRLMQzJ7fQBGy', 4, 'Pam Pastor', 'Billing Administrator'),
('yanika.crosse', 'yanika.crosse@acgcares.com', '$2y$10$YtXKUJMzj8m5LzKpV7kHXuQ7yxG9mX5bKC7VEXb5TRLMQzJ7fQBGy', 4, 'Yanika Crosse', 'Billing Administrator'),
('alvin.ukpeh', 'alvin.ukpeh@acgcares.com', '$2y$10$BzxOZ6mMwLQK8FGCnYb0S.TzL7E2gBJPHgZqJ5SZQXHfqKT/cDOaO', 5, 'Alvin Ukpeh', 'System Administrator')
ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), access_level=VALUES(access_level), role=VALUES(role);
```

## Need Help?

If you encounter any issues:
1. Check the error logs
2. Verify the database connection
3. Make sure the autism_users table exists
4. Ensure src/login.php is using authenticateOpenEMRUser() function