<?php
require_once '../src/init.php';
requireAuth(3); // Case Manager+ access

$success = '';
$error = '';

try {
    $pdo = getDatabase();
    
    // Get waiver types
    $stmt = $pdo->query("SELECT * FROM autism_waiver_types WHERE is_active = 1 ORDER BY waiver_name");
    $waiver_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get service types
    $stmt = $pdo->query("SELECT * FROM autism_service_types WHERE is_active = 1 ORDER BY service_name");
    $service_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Failed to load form data: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Validate required fields
        if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['date_of_birth'])) {
            throw new Exception("First name, last name, and date of birth are required.");
        }
        
        // Insert client
        $stmt = $pdo->prepare("
            INSERT INTO autism_clients (
                first_name, last_name, date_of_birth, ma_number, waiver_type_id,
                phone, email, address, emergency_contact, emergency_phone,
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $stmt->execute([
            trim($_POST['first_name']),
            trim($_POST['last_name']),
            $_POST['date_of_birth'],
            trim($_POST['ma_number'] ?? ''),
            $_POST['waiver_type_id'] ?: null,
            trim($_POST['phone'] ?? ''),
            trim($_POST['email'] ?? ''),
            trim($_POST['address'] ?? ''),
            trim($_POST['emergency_contact_name'] ?? ''),
            trim($_POST['emergency_contact_phone'] ?? '')
        ]);
        
        $client_id = $pdo->lastInsertId();
        
        // Insert service authorizations if provided
        if (!empty($_POST['services']) && is_array($_POST['services'])) {
            $auth_stmt = $pdo->prepare("
                INSERT INTO autism_client_authorizations (
                    client_id, waiver_type_id, service_type_id, fiscal_year,
                    fiscal_year_start, fiscal_year_end, weekly_hours, yearly_hours,
                    remaining_hours, authorization_number, start_date, end_date,
                    status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
            ");
            
            foreach ($_POST['services'] as $service) {
                if (!empty($service['service_type_id']) && !empty($service['yearly_hours'])) {
                    // Calculate fiscal year
                    $current_date = new DateTime();
                    $current_month = (int)$current_date->format('n');
                    $current_year = (int)$current_date->format('Y');
                    
                    if ($current_month >= 7) {
                        $fiscal_year = $current_year + 1;
                        $fiscal_start = "$current_year-07-01";
                        $fiscal_end = ($current_year + 1) . "-06-30";
                    } else {
                        $fiscal_year = $current_year;
                        $fiscal_start = ($current_year - 1) . "-07-01";
                        $fiscal_end = "$current_year-06-30";
                    }
                    
                    $auth_stmt->execute([
                        $client_id,
                        $_POST['waiver_type_id'],
                        $service['service_type_id'],
                        $fiscal_year,
                        $fiscal_start,
                        $fiscal_end,
                        $service['weekly_hours'] ?? null,
                        $service['yearly_hours'],
                        $service['yearly_hours'], // Initially, remaining = yearly
                        $service['authorization_number'] ?? null,
                        $service['start_date'] ?? $fiscal_start,
                        $service['end_date'] ?? $fiscal_end,
                        $_SESSION['user_id']
                    ]);
                }
            }
        }
        
        $pdo->commit();
        header("Location: clients.php?success=" . urlencode("Client added successfully with service authorizations!"));
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
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
            color: #1e40af;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
        }
        
        .form-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 1rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #475569;
        }
        
        .required::after {
            content: " *";
            color: #dc2626;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .service-authorization {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
        }
        
        .service-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 0.5rem;
            align-items: end;
        }
        
        .add-service-btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        
        .add-service-btn:hover {
            background: #059669;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #cbd5e1;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .fiscal-year-info {
            background: #fef3c7;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #92400e;
        }
        
        .remove-service {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .remove-service:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Add New Client</h1>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="form-card">
            <!-- Basic Information -->
            <div class="form-section">
                <h2 class="section-title">Basic Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name" class="required">First Name</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name" class="required">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth" class="required">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required>
                    </div>
                    <div class="form-group">
                        <label for="ma_number">MA Number</label>
                        <input type="text" id="ma_number" name="ma_number" placeholder="MD123456789">
                    </div>
                </div>
            </div>
            
            <!-- Waiver Information -->
            <div class="form-section">
                <h2 class="section-title">Waiver Information</h2>
                <div class="form-group">
                    <label for="waiver_type_id" class="required">Waiver Type</label>
                    <select id="waiver_type_id" name="waiver_type_id" required onchange="updateServiceTypes()">
                        <option value="">Select Waiver Type</option>
                        <?php foreach ($waiver_types as $waiver): ?>
                            <option value="<?= $waiver['id'] ?>" data-code="<?= $waiver['waiver_code'] ?>">
                                <?= htmlspecialchars($waiver['waiver_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Service Authorizations -->
            <div class="form-section">
                <h2 class="section-title">Service Authorizations</h2>
                <div class="fiscal-year-info">
                    <strong>Note:</strong> Maryland fiscal year runs from July 1 to June 30. 
                    Current FY<?= date('n') >= 7 ? date('Y') + 1 : date('Y') ?> 
                    (<?= date('n') >= 7 ? date('Y') : date('Y') - 1 ?>-07-01 to 
                    <?= date('n') >= 7 ? date('Y') + 1 : date('Y') ?>-06-30)
                </div>
                
                <div id="service-authorizations">
                    <!-- Services will be added dynamically -->
                </div>
                
                <button type="button" class="add-service-btn" onclick="addServiceAuthorization()">
                    + Add Service Authorization
                </button>
            </div>
            
            <!-- Contact Information -->
            <div class="form-section">
                <h2 class="section-title">Contact Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email">
                    </div>
                    <div class="form-group full-width">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="2"></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Emergency Contact -->
            <div class="form-section">
                <h2 class="section-title">Emergency Contact</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="emergency_contact_name">Contact Name</label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name">
                    </div>
                    <div class="form-group">
                        <label for="emergency_contact_phone">Contact Phone</label>
                        <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone">
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Client</button>
                <a href="clients.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <script>
        let serviceCount = 0;
        const serviceTypes = <?= json_encode($service_types) ?>;
        
        function updateServiceTypes() {
            const waiverSelect = document.getElementById('waiver_type_id');
            const waiverCode = waiverSelect.options[waiverSelect.selectedIndex].getAttribute('data-code');
            
            // Clear existing services
            document.getElementById('service-authorizations').innerHTML = '';
            serviceCount = 0;
            
            // Add one service authorization by default
            if (waiverSelect.value) {
                addServiceAuthorization();
            }
        }
        
        function addServiceAuthorization() {
            const container = document.getElementById('service-authorizations');
            const serviceId = 'service_' + serviceCount;
            
            const serviceDiv = document.createElement('div');
            serviceDiv.className = 'service-authorization';
            serviceDiv.id = serviceId;
            
            serviceDiv.innerHTML = `
                <div class="service-grid">
                    <div class="form-group">
                        <label>Service Type</label>
                        <select name="services[${serviceCount}][service_type_id]" required>
                            <option value="">Select Service</option>
                            ${serviceTypes.map(st => 
                                `<option value="${st.id}">${st.service_name} (${st.service_code})</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Weekly Hours</label>
                        <input type="number" name="services[${serviceCount}][weekly_hours]" 
                               step="0.25" min="0" placeholder="40">
                    </div>
                    <div class="form-group">
                        <label>Yearly Hours</label>
                        <input type="number" name="services[${serviceCount}][yearly_hours]" 
                               step="0.25" min="0" required placeholder="2080">
                    </div>
                    <div class="form-group">
                        <button type="button" class="remove-service" onclick="removeService('${serviceId}')">
                            Remove
                        </button>
                    </div>
                </div>
                <div class="form-grid" style="margin-top: 0.5rem;">
                    <div class="form-group">
                        <label>Authorization #</label>
                        <input type="text" name="services[${serviceCount}][authorization_number]" 
                               placeholder="AUTH-12345">
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="services[${serviceCount}][start_date]">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="services[${serviceCount}][end_date]">
                    </div>
                </div>
            `;
            
            container.appendChild(serviceDiv);
            serviceCount++;
        }
        
        function removeService(serviceId) {
            const element = document.getElementById(serviceId);
            if (element) {
                element.remove();
            }
        }
    </script>
</body>
</html>