<?php
require_once 'init.php';
requireAuth(5); // Admin only

try {
    $pdo = getDatabase();
    
    // Handle organization settings update
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch ($_POST['action']) {
            case 'update_organization':
                // Create or update organization settings
                $stmt = $pdo->prepare("
                    INSERT INTO autism_organization_settings 
                    (organization_name, address, city, state, zip, phone, email, tax_id, npi, 
                     medicaid_provider_id, website, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE
                    organization_name=VALUES(organization_name),
                    address=VALUES(address),
                    city=VALUES(city), 
                    state=VALUES(state),
                    zip=VALUES(zip),
                    phone=VALUES(phone),
                    email=VALUES(email),
                    tax_id=VALUES(tax_id),
                    npi=VALUES(npi),
                    medicaid_provider_id=VALUES(medicaid_provider_id),
                    website=VALUES(website),
                    updated_at=NOW()
                ");
                $stmt->execute([
                    $_POST['organization_name'],
                    $_POST['address'],
                    $_POST['city'],
                    $_POST['state'],
                    $_POST['zip'],
                    $_POST['phone'], 
                    $_POST['email'],
                    $_POST['tax_id'],
                    $_POST['npi'],
                    $_POST['medicaid_provider_id'],
                    $_POST['website']
                ]);
                
                UrlManager::redirectWithSuccess('admin_organization', 'Organization settings updated successfully!');
                break;
        }
    }
    
    // Get current organization settings
    $stmt = $pdo->query("SELECT * FROM autism_organization_settings LIMIT 1");
    $org_settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If no settings exist, create default
    if (!$org_settings) {
        $org_settings = [
            'organization_name' => 'American Caregivers Inc',
            'address' => '',
            'city' => '',
            'state' => 'MD',
            'zip' => '',
            'phone' => '',
            'email' => '',
            'tax_id' => '',
            'npi' => '',
            'medicaid_provider_id' => '',
            'website' => ''
        ];
    }
    
} catch (Exception $e) {
    error_log("Admin organization error: " . $e->getMessage());
    $org_settings = [];
}

$tab = $_GET['tab'] ?? 'organization';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Settings - ACI Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; }
        .header { background: white; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { color: #1e40af; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .section { background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .tabs { display: flex; border-bottom: 2px solid #e5e7eb; margin-bottom: 2rem; }
        .tab { padding: 1rem 2rem; background: none; border: none; font-weight: 600; cursor: pointer; color: #64748b; }
        .tab.active { color: #1e40af; border-bottom: 2px solid #1e40af; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .btn-primary { background: #1e40af; color: white; }
        .btn-success { background: #059669; color: white; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-info { background: #dbeafe; color: #1e40af; }
        .info-box { background: #f0f9ff; border: 1px solid #0ea5e9; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
        .setting-card { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; }
        .setting-card h4 { color: #1e293b; margin-bottom: 0.5rem; }
        .setting-card p { color: #64748b; font-size: 0.875rem; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Organization Settings</h1>
        <a href="<?= UrlManager::url('admin') ?>" style="color: #1e40af; text-decoration: none;">← Back to Admin Dashboard</a>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab <?= $tab === 'organization' ? 'active' : '' ?>" onclick="showTab('organization')">Organization Profile</button>
            <button class="tab <?= $tab === 'billing' ? 'active' : '' ?>" onclick="showTab('billing')">Billing Settings</button>
            <button class="tab <?= $tab === 'system' ? 'active' : '' ?>" onclick="showTab('system')">System Settings</button>
        </div>
        
        <!-- Organization Profile Tab -->
        <div id="organization" class="tab-content <?= $tab === 'organization' ? 'active' : '' ?>">
            <div class="section">
                <h2>Organization Profile</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_organization">
                    
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label>Organization Name</label>
                            <input type="text" name="organization_name" required 
                                   value="<?= htmlspecialchars($org_settings['organization_name'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group full-width">
                            <label>Street Address</label>
                            <input type="text" name="address" 
                                   value="<?= htmlspecialchars($org_settings['address'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>City</label>
                            <input type="text" name="city" 
                                   value="<?= htmlspecialchars($org_settings['city'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>State</label>
                            <select name="state">
                                <option value="MD" <?= ($org_settings['state'] ?? '') === 'MD' ? 'selected' : '' ?>>Maryland</option>
                                <option value="DC" <?= ($org_settings['state'] ?? '') === 'DC' ? 'selected' : '' ?>>Washington DC</option>
                                <option value="VA" <?= ($org_settings['state'] ?? '') === 'VA' ? 'selected' : '' ?>>Virginia</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>ZIP Code</label>
                            <input type="text" name="zip" 
                                   value="<?= htmlspecialchars($org_settings['zip'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" 
                                   value="<?= htmlspecialchars($org_settings['phone'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" 
                                   value="<?= htmlspecialchars($org_settings['email'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Website</label>
                            <input type="url" name="website" 
                                   value="<?= htmlspecialchars($org_settings['website'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Tax ID (EIN)</label>
                            <input type="text" name="tax_id" 
                                   value="<?= htmlspecialchars($org_settings['tax_id'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>NPI Number</label>
                            <input type="text" name="npi" 
                                   value="<?= htmlspecialchars($org_settings['npi'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Medicaid Provider ID</label>
                            <input type="text" name="medicaid_provider_id" 
                                   value="<?= htmlspecialchars($org_settings['medicaid_provider_id'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div style="margin-top: 2rem;">
                        <button type="submit" class="btn btn-success">Save Organization Settings</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Billing Settings Tab -->
        <div id="billing" class="tab-content <?= $tab === 'billing' ? 'active' : '' ?>">
            <div class="section">
                <h2>Billing Configuration</h2>
                
                <div class="setting-card">
                    <h4>🏥 Maryland Medicaid Settings</h4>
                    <p>Configure Maryland Medicaid billing parameters and EDI submission settings.</p>
                    <div style="margin-top: 1rem;">
                        <a href="<?= UrlManager::url('billing_edi') ?>" class="btn btn-primary">Configure EDI Settings</a>
                    </div>
                </div>
                
                <div class="setting-card">
                    <h4>💳 Payment Processing</h4>
                    <p>Set up payment gateways and processing options for private pay clients.</p>
                    <div style="margin-top: 1rem;">
                        <button class="btn btn-secondary" disabled>Coming Soon</button>
                    </div>
                </div>
                
                <div class="setting-card">
                    <h4>📊 Billing Reports</h4>
                    <p>Configure automated billing reports and revenue tracking.</p>
                    <div style="margin-top: 1rem;">
                        <a href="<?= UrlManager::url('reports', ['tab' => 'billing']) ?>" class="btn btn-primary">View Billing Reports</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Settings Tab -->
        <div id="system" class="tab-content <?= $tab === 'system' ? 'active' : '' ?>">
            <div class="section">
                <h2>System Configuration</h2>
                
                <div class="setting-card">
                    <h4>🔧 Database Management</h4>
                    <p>Backup, restore, and maintain your system database.</p>
                    <div style="margin-top: 1rem;">
                        <button class="btn btn-secondary" disabled>Database Tools (Coming Soon)</button>
                    </div>
                </div>
                
                <div class="setting-card">
                    <h4>🔐 Security Settings</h4>
                    <p>Configure password policies, session timeouts, and access controls.</p>
                    <div style="margin-top: 1rem;">
                        <button class="btn btn-secondary" disabled>Security Settings (Coming Soon)</button>
                    </div>
                </div>
                
                <div class="setting-card">
                    <h4>📧 Email Configuration</h4>
                    <p>Set up SMTP settings for system notifications and alerts.</p>
                    <div style="margin-top: 1rem;">
                        <button class="btn btn-secondary" disabled>Email Settings (Coming Soon)</button>
                    </div>
                </div>
                
                <div class="setting-card">
                    <h4>🔄 Integration Settings</h4>
                    <p>Configure integrations with external systems and APIs.</p>
                    <div style="margin-top: 1rem;">
                        <button class="btn btn-secondary" disabled>Integrations (Coming Soon)</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            // Update URL parameter
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        }
    </script>
</body>
</html>