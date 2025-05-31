<?php
require_once '../src/init.php';
requireAuth(3); // Case Manager+ access

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDatabase();
        
        // Validate required fields
        if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['date_of_birth'])) {
            throw new Exception("First name, last name, and date of birth are required.");
        }
        
        // Insert client into database
        $stmt = $pdo->prepare("
            INSERT INTO autism_clients (
                first_name, last_name, date_of_birth, ma_number,
                phone, email, address, emergency_contact, emergency_phone,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $result = $stmt->execute([
            trim($_POST['first_name']),
            trim($_POST['last_name']),
            $_POST['date_of_birth'],
            trim($_POST['ma_number'] ?? ''),
            trim($_POST['phone'] ?? ''),
            trim($_POST['email'] ?? ''),
            trim($_POST['address'] ?? ''),
            trim($_POST['emergency_contact_name'] ?? ''),
            trim($_POST['emergency_contact_phone'] ?? '')
        ]);
        
        if ($result) {
            UrlManager::redirectWithSuccess('clients', "Client added successfully!");
        } else {
            $error = "Failed to add client. Please try again.";
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Client - ACI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; }
        .header { background: white; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { color: #059669; }
        .container { max-width: 800px; margin: 0 auto; padding: 2rem; }
        .form-section { background: white; border-radius: 12px; padding: 2rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #374151; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 1rem; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #059669; }
        .required { color: #dc2626; }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #059669; color: white; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .btn-group { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .section-title { font-size: 1.25rem; font-weight: 600; color: #1e293b; margin-bottom: 1.5rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem; }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>Add New Client</h1>
        <a href="<?= UrlManager::url('clients') ?>" style="color: #059669; text-decoration: none;">← Back to Clients</a>
    </div>
    
    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <!-- Personal Information -->
            <div class="form-section">
                <h2 class="section-title">Personal Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" required 
                               value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" required 
                               value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth <span class="required">*</span></label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required 
                               value="<?= htmlspecialchars($_POST['date_of_birth'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="other" <?= ($_POST['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Prefer not to specify</option>
                            <option value="male" <?= ($_POST['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= ($_POST['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ma_number">Medicaid Number</label>
                        <input type="text" id="ma_number" name="ma_number" 
                               value="<?= htmlspecialchars($_POST['ma_number'] ?? '') ?>"
                               placeholder="MA123456789">
                    </div>
                    <div class="form-group">
                        <label for="enrollment_date">Enrollment Date</label>
                        <input type="date" id="enrollment_date" name="enrollment_date" 
                               value="<?= htmlspecialchars($_POST['enrollment_date'] ?? date('Y-m-d')) ?>">
                    </div>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="form-section">
                <h2 class="section-title">Contact Information</h2>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" 
                               value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" 
                               value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="state">State</label>
                        <select id="state" name="state">
                            <option value="MD" <?= ($_POST['state'] ?? 'MD') === 'MD' ? 'selected' : '' ?>>Maryland</option>
                            <option value="DC" <?= ($_POST['state'] ?? '') === 'DC' ? 'selected' : '' ?>>Washington DC</option>
                            <option value="VA" <?= ($_POST['state'] ?? '') === 'VA' ? 'selected' : '' ?>>Virginia</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="zip">ZIP Code</label>
                        <input type="text" id="zip" name="zip" 
                               value="<?= htmlspecialchars($_POST['zip'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                               placeholder="(410) 555-0123">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                </div>
            </div>
            
            <!-- Emergency Contact -->
            <div class="form-section">
                <h2 class="section-title">Emergency Contact</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="emergency_contact_name">Contact Name</label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name" 
                               value="<?= htmlspecialchars($_POST['emergency_contact_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="emergency_contact_phone">Contact Phone</label>
                        <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" 
                               value="<?= htmlspecialchars($_POST['emergency_contact_phone'] ?? '') ?>">
                    </div>
                    <div class="form-group full-width">
                        <label for="emergency_contact_relationship">Relationship</label>
                        <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" 
                               value="<?= htmlspecialchars($_POST['emergency_contact_relationship'] ?? '') ?>"
                               placeholder="Parent, Guardian, Sibling, etc.">
                    </div>
                </div>
            </div>
            
            <div class="btn-group">
                <a href="<?= UrlManager::url('clients') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Add Client</button>
            </div>
        </form>
    </div>
</body>
</html>