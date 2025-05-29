<?php
session_start();
require_once 'config.php';
require_once 'openemr_integration.php';

// Check if user is logged in and is administrator
if (!isset($_SESSION['user_id']) || $_SESSION['access_level'] < 5) {
    header('Location: login.php');
    exit;
}

$pdo = getDatabase();
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get organization settings from database or create defaults
        $stmt = $pdo->query("SELECT * FROM autism_organization_settings LIMIT 1");
        $settings = $stmt->fetch();
        
        $org_name = $_POST['org_name'];
        $org_address = $_POST['org_address'];
        $org_city = $_POST['org_city'];
        $org_state = $_POST['org_state'];
        $org_zip = $_POST['org_zip'];
        $org_phone = $_POST['org_phone'];
        $org_email = $_POST['org_email'];
        $org_website = $_POST['org_website'];
        $tax_id = $_POST['tax_id'];
        $npi_number = $_POST['npi_number'];
        $medicaid_provider_id = $_POST['medicaid_provider_id'];
        $billing_contact_name = $_POST['billing_contact_name'];
        $billing_contact_email = $_POST['billing_contact_email'];
        $billing_contact_phone = $_POST['billing_contact_phone'];
        
        if ($settings) {
            // Update existing settings
            $stmt = $pdo->prepare("
                UPDATE autism_organization_settings 
                SET org_name = ?, org_address = ?, org_city = ?, org_state = ?, org_zip = ?,
                    org_phone = ?, org_email = ?, org_website = ?, tax_id = ?, npi_number = ?,
                    medicaid_provider_id = ?, billing_contact_name = ?, billing_contact_email = ?,
                    billing_contact_phone = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                $org_name, $org_address, $org_city, $org_state, $org_zip,
                $org_phone, $org_email, $org_website, $tax_id, $npi_number,
                $medicaid_provider_id, $billing_contact_name, $billing_contact_email,
                $billing_contact_phone, $settings['id']
            ]);
        } else {
            // Insert new settings
            $stmt = $pdo->prepare("
                INSERT INTO autism_organization_settings 
                (org_name, org_address, org_city, org_state, org_zip, org_phone, org_email,
                 org_website, tax_id, npi_number, medicaid_provider_id, billing_contact_name,
                 billing_contact_email, billing_contact_phone, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                $org_name, $org_address, $org_city, $org_state, $org_zip,
                $org_phone, $org_email, $org_website, $tax_id, $npi_number,
                $medicaid_provider_id, $billing_contact_name, $billing_contact_email,
                $billing_contact_phone
            ]);
        }
        
        $message = "Organization settings updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Get current organization settings
$stmt = $pdo->query("SELECT * FROM autism_organization_settings LIMIT 1");
$org = $stmt->fetch();

// Set defaults if no settings exist
if (!$org) {
    $org = [
        'org_name' => 'American Caregivers Inc',
        'org_address' => '',
        'org_city' => '',
        'org_state' => 'NY',
        'org_zip' => '',
        'org_phone' => '',
        'org_email' => '',
        'org_website' => '',
        'tax_id' => '',
        'npi_number' => '',
        'medicaid_provider_id' => '',
        'billing_contact_name' => '',
        'billing_contact_email' => '',
        'billing_contact_phone' => ''
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Settings - American Caregivers Inc</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-banner {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-text {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .logo-text .a { color: #1e40af; }
        .logo-text .c { color: #dc2626; }
        .logo-text .i { color: #16a34a; }
        
        .company-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #64748b;
            margin-bottom: 2rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .alert-success {
            background: #d1fae5;
            border: 1px solid #34d399;
            color: #065f46;
        }
        
        .alert-error {
            background: #fee2e2;
            border: 1px solid #f87171;
            color: #991b1b;
        }
        
        .settings-sections {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .settings-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .settings-card h3 {
            color: #1e40af;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1e40af;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #1e40af;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e3a8a;
        }
        
        .btn-secondary {
            background: #e5e7eb;
            color: #1e293b;
        }
        
        .btn-secondary:hover {
            background: #d1d5db;
        }
        
        .back-link {
            color: #1e40af;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .help-text {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-banner">
            <div class="logo-text">
                <span class="a">A</span><span class="c">C</span><span class="i">I</span>
            </div>
            <div class="company-name">Organization Settings</div>
        </div>
    </div>
    
    <div class="container">
        <a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>
        
        <h1 class="page-title">Organization Profile</h1>
        <p class="page-subtitle">Configure your organization's information and billing details</p>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="settings-sections">
                <div class="settings-card">
                    <h3>Basic Information</h3>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="org_name">Organization Name *</label>
                            <input type="text" id="org_name" name="org_name" 
                                   value="<?php echo htmlspecialchars($org['org_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="org_address">Street Address</label>
                            <input type="text" id="org_address" name="org_address" 
                                   value="<?php echo htmlspecialchars($org['org_address'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="org_city">City</label>
                            <input type="text" id="org_city" name="org_city" 
                                   value="<?php echo htmlspecialchars($org['org_city'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="org_state">State</label>
                            <select id="org_state" name="org_state">
                                <option value="">Select State</option>
                                <option value="NY" <?php echo ($org['org_state'] ?? '') === 'NY' ? 'selected' : ''; ?>>New York</option>
                                <option value="NJ" <?php echo ($org['org_state'] ?? '') === 'NJ' ? 'selected' : ''; ?>>New Jersey</option>
                                <option value="CT" <?php echo ($org['org_state'] ?? '') === 'CT' ? 'selected' : ''; ?>>Connecticut</option>
                                <option value="PA" <?php echo ($org['org_state'] ?? '') === 'PA' ? 'selected' : ''; ?>>Pennsylvania</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="org_zip">ZIP Code</label>
                            <input type="text" id="org_zip" name="org_zip" 
                                   value="<?php echo htmlspecialchars($org['org_zip'] ?? ''); ?>" 
                                   pattern="[0-9]{5}" maxlength="5">
                        </div>
                        
                        <div class="form-group">
                            <label for="org_phone">Phone Number</label>
                            <input type="tel" id="org_phone" name="org_phone" 
                                   value="<?php echo htmlspecialchars($org['org_phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="org_email">Email Address</label>
                            <input type="email" id="org_email" name="org_email" 
                                   value="<?php echo htmlspecialchars($org['org_email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="org_website">Website</label>
                            <input type="url" id="org_website" name="org_website" 
                                   value="<?php echo htmlspecialchars($org['org_website'] ?? ''); ?>" 
                                   placeholder="https://example.com">
                        </div>
                    </div>
                </div>
                
                <div class="settings-card">
                    <h3>Provider Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="tax_id">Tax ID (EIN)</label>
                            <input type="text" id="tax_id" name="tax_id" 
                                   value="<?php echo htmlspecialchars($org['tax_id'] ?? ''); ?>" 
                                   placeholder="XX-XXXXXXX">
                            <span class="help-text">Federal Employer Identification Number</span>
                        </div>
                        
                        <div class="form-group">
                            <label for="npi_number">NPI Number</label>
                            <input type="text" id="npi_number" name="npi_number" 
                                   value="<?php echo htmlspecialchars($org['npi_number'] ?? ''); ?>" 
                                   placeholder="XXXXXXXXXX">
                            <span class="help-text">10-digit National Provider Identifier</span>
                        </div>
                        
                        <div class="form-group">
                            <label for="medicaid_provider_id">Medicaid Provider ID</label>
                            <input type="text" id="medicaid_provider_id" name="medicaid_provider_id" 
                                   value="<?php echo htmlspecialchars($org['medicaid_provider_id'] ?? ''); ?>">
                            <span class="help-text">State-specific Medicaid provider number</span>
                        </div>
                    </div>
                </div>
                
                <div class="settings-card">
                    <h3>Billing Contact</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="billing_contact_name">Contact Name</label>
                            <input type="text" id="billing_contact_name" name="billing_contact_name" 
                                   value="<?php echo htmlspecialchars($org['billing_contact_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="billing_contact_email">Contact Email</label>
                            <input type="email" id="billing_contact_email" name="billing_contact_email" 
                                   value="<?php echo htmlspecialchars($org['billing_contact_email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="billing_contact_phone">Contact Phone</label>
                            <input type="tel" id="billing_contact_phone" name="billing_contact_phone" 
                                   value="<?php echo htmlspecialchars($org['billing_contact_phone'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Settings</button>
                <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <script src="/public/assets/js/interactive-help.js"></script>
</body>
</html> 