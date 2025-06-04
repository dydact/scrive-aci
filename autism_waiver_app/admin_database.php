<?php
session_start();
require_once '../src/config.php';
require_once '../src/openemr_integration.php';

// Admin only access
if (!isset($_SESSION['user_id']) || $_SESSION['access_level'] < 5) {
    header('Location: /login');
    exit;
}

$message = '';
$error = '';

try {
    $pdo = getDatabase();
    
    // Get all autism tables
    $stmt = $pdo->query("SHOW TABLES LIKE 'autism_%'");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Define all required tables
    $required_tables = [
        'autism_users' => "CREATE TABLE IF NOT EXISTS autism_users (
            id INT(11) NOT NULL AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            full_name VARCHAR(100),
            access_level INT(1) DEFAULT 1,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_username (username),
            KEY idx_access_level (access_level)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'autism_clients' => "CREATE TABLE IF NOT EXISTS autism_clients (
            id INT(11) NOT NULL AUTO_INCREMENT,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            date_of_birth DATE,
            ma_number VARCHAR(20),
            waiver_type_id INT(11),
            phone VARCHAR(20),
            email VARCHAR(100),
            address TEXT,
            emergency_contact VARCHAR(100),
            emergency_phone VARCHAR(20),
            status ENUM('active','inactive','discharged') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_name (last_name, first_name),
            KEY idx_ma_number (ma_number),
            KEY idx_status (status),
            KEY idx_waiver_type (waiver_type_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'autism_schedules' => "CREATE TABLE IF NOT EXISTS autism_schedules (
            id INT(11) NOT NULL AUTO_INCREMENT,
            staff_id INT(11),
            client_id INT(11) NOT NULL,
            service_type_id INT(11),
            scheduled_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            notes TEXT,
            status ENUM('scheduled','confirmed','completed','cancelled','no_show') DEFAULT 'scheduled',
            created_by INT(11),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_date_time (scheduled_date, start_time),
            KEY idx_client (client_id),
            KEY idx_staff (staff_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'autism_organization_settings' => "CREATE TABLE IF NOT EXISTS autism_organization_settings (
            id INT(11) NOT NULL AUTO_INCREMENT,
            organization_name VARCHAR(255) NOT NULL,
            address VARCHAR(255),
            city VARCHAR(100),
            state VARCHAR(2),
            zip VARCHAR(10),
            phone VARCHAR(20),
            email VARCHAR(255),
            tax_id VARCHAR(20),
            npi VARCHAR(20),
            medicaid_provider_id VARCHAR(50),
            website VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'autism_claims' => "CREATE TABLE IF NOT EXISTS autism_claims (
            id INT(11) NOT NULL AUTO_INCREMENT,
            claim_number VARCHAR(50) UNIQUE,
            client_id INT(11) NOT NULL,
            service_date_from DATE NOT NULL,
            service_date_to DATE NOT NULL,
            total_amount DECIMAL(10,2),
            status ENUM('draft','generated','submitted','paid','denied') DEFAULT 'draft',
            payment_amount DECIMAL(10,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_client (client_id),
            KEY idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'autism_staff_members' => "CREATE TABLE IF NOT EXISTS autism_staff_members (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'autism_service_types' => "CREATE TABLE IF NOT EXISTS autism_service_types (
            id INT(11) NOT NULL AUTO_INCREMENT,
            service_code VARCHAR(20) NOT NULL,
            service_name VARCHAR(100) NOT NULL,
            description TEXT,
            rate DECIMAL(10,2),
            unit_type ENUM('hour','unit','day','session') DEFAULT 'hour',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_service_code (service_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'autism_waiver_types' => "CREATE TABLE IF NOT EXISTS autism_waiver_types (
            id INT(11) NOT NULL AUTO_INCREMENT,
            waiver_code VARCHAR(20) NOT NULL,
            waiver_name VARCHAR(100) NOT NULL,
            description TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_waiver_code (waiver_code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'autism_client_authorizations' => "CREATE TABLE IF NOT EXISTS autism_client_authorizations (
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
            KEY idx_dates (start_date, end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'autism_sessions' => "CREATE TABLE IF NOT EXISTS autism_sessions (
            id INT(11) NOT NULL AUTO_INCREMENT,
            client_id INT(11) NOT NULL,
            staff_id INT(11),
            service_type_id INT(11),
            session_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            duration_hours DECIMAL(5,2),
            session_type VARCHAR(50),
            location VARCHAR(100),
            goals_addressed TEXT,
            interventions TEXT,
            client_response TEXT,
            notes TEXT,
            status ENUM('scheduled','completed','cancelled','no_show') DEFAULT 'scheduled',
            billing_status ENUM('unbilled','billed','paid','denied') DEFAULT 'unbilled',
            created_by INT(11),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_client (client_id),
            KEY idx_staff (staff_id),
            KEY idx_service (service_type_id),
            KEY idx_date (session_date),
            KEY idx_status (status),
            KEY idx_billing (billing_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    // Handle actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['create_all_tables'])) {
            $created = 0;
            foreach ($required_tables as $table_name => $create_sql) {
                try {
                    $pdo->exec($create_sql);
                    $created++;
                } catch (Exception $e) {
                    $error .= "Error creating $table_name: " . $e->getMessage() . "<br>";
                }
            }
            $message = "Created/verified $created tables.";
        }
        
        if (isset($_POST['create_table']) && isset($_POST['table_name'])) {
            $table_name = $_POST['table_name'];
            if (isset($required_tables[$table_name])) {
                try {
                    $pdo->exec($required_tables[$table_name]);
                    $message = "Table $table_name created successfully.";
                } catch (Exception $e) {
                    $error = "Error creating $table_name: " . $e->getMessage();
                }
            }
        }
        
        if (isset($_POST['run_fix_script'])) {
            // Run the comprehensive fix script
            ob_start();
            include '../pages/utilities/fix_database.php';
            $output = ob_get_clean();
            $message = "Fix script executed. Output:<br><pre>" . htmlspecialchars($output) . "</pre>";
        }
        
        if (isset($_POST['insert_sample_data'])) {
            try {
                // Insert waiver types
                $pdo->exec("INSERT IGNORE INTO autism_waiver_types (waiver_code, waiver_name) VALUES 
                    ('AW', 'Autism Waiver'),
                    ('CFC', 'Community First Choice'),
                    ('CP', 'Community Pathways')");
                
                // Insert service types
                $pdo->exec("INSERT IGNORE INTO autism_service_types (service_code, service_name, rate) VALUES 
                    ('IISS', 'Individual Intensive Support Services', 35.00),
                    ('TI', 'Therapeutic Integration', 40.00),
                    ('RESPITE', 'Respite Care', 30.00)");
                
                // Insert default admin
                $pdo->exec("INSERT IGNORE INTO autism_users (username, password_hash, email, full_name, access_level) 
                    VALUES ('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
                    'admin@americancaregivers.com', 'System Administrator', 5)");
                
                $message = "Sample data inserted successfully.";
            } catch (Exception $e) {
                $error = "Error inserting sample data: " . $e->getMessage();
            }
        }
    }
    
} catch (Exception $e) {
    $error = "Database connection error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        
        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .header h1 {
            font-size: 1.5rem;
            color: #dc2626;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .card h2 {
            font-size: 1.25rem;
            color: #1e40af;
            margin-bottom: 1rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            text-align: left;
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background: #f9fafb;
            font-weight: 600;
        }
        
        .status-exists {
            color: #16a34a;
        }
        
        .status-missing {
            color: #dc2626;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b91c1c;
        }
        
        .btn-success {
            background: #16a34a;
            color: white;
        }
        
        .btn-success:hover {
            background: #15803d;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        pre {
            background: #f3f4f6;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 0.875rem;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 1rem;
            color: #3b82f6;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Database Management - Admin Only</h1>
    </div>
    
    <div class="container">
        <a href="/admin" class="back-link">← Back to Admin Dashboard</a>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="grid">
            <div class="card">
                <h2>Table Status</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Table Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($required_tables as $table_name => $sql): ?>
                            <tr>
                                <td><?= $table_name ?></td>
                                <td>
                                    <?php if (in_array($table_name, $existing_tables)): ?>
                                        <span class="status-exists">✓ Exists</span>
                                    <?php else: ?>
                                        <span class="status-missing">✗ Missing</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!in_array($table_name, $existing_tables)): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="table_name" value="<?= $table_name ?>">
                                            <button type="submit" name="create_table" class="btn btn-primary">Create</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <form method="POST">
                        <button type="submit" name="create_all_tables" class="btn btn-primary">
                            Create All Missing Tables
                        </button>
                    </form>
                    
                    <form method="POST">
                        <button type="submit" name="run_fix_script" class="btn btn-success">
                            Run Comprehensive Fix Script
                        </button>
                    </form>
                    
                    <form method="POST">
                        <button type="submit" name="insert_sample_data" class="btn btn-primary">
                            Insert Sample Data
                        </button>
                    </form>
                </div>
                
                <h3 style="margin-top: 2rem; margin-bottom: 1rem;">Database Info</h3>
                <ul style="color: #64748b; line-height: 1.8;">
                    <li>Database: <?= DB_NAME ?></li>
                    <li>Host: <?= DB_HOST ?></li>
                    <li>Total Autism Tables: <?= count($existing_tables) ?> / <?= count($required_tables) ?></li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <h2>Manual SQL Execution</h2>
            <form method="POST">
                <textarea name="custom_sql" rows="10" style="width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; font-family: monospace;" placeholder="Enter SQL query here..."></textarea>
                <div style="margin-top: 1rem;">
                    <button type="submit" name="execute_sql" class="btn btn-danger">Execute SQL (Use with caution)</button>
                </div>
            </form>
            
            <?php
            if (isset($_POST['execute_sql']) && !empty($_POST['custom_sql'])) {
                try {
                    $stmt = $pdo->query($_POST['custom_sql']);
                    if ($stmt) {
                        echo '<div class="alert alert-success" style="margin-top: 1rem;">SQL executed successfully. Affected rows: ' . $stmt->rowCount() . '</div>';
                        
                        // If it's a SELECT query, show results
                        if (stripos(trim($_POST['custom_sql']), 'SELECT') === 0) {
                            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if ($results) {
                                echo '<pre>' . htmlspecialchars(print_r($results, true)) . '</pre>';
                            }
                        }
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-error" style="margin-top: 1rem;">SQL Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
            ?>
        </div>
    </div>
</body>
</html>