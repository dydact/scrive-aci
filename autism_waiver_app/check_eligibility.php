<?php
/**
 * Real-time eligibility verification through Claim.MD
 */
session_start();
require_once '../src/init.php';
require_once '../config/claimmd.php';
require_once 'integrations/claim_md_api.php';

requireAuth(3); // Case Manager and above

$error = null;
$eligibilityResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDatabase();
        $claimMD = new ClaimMDAPI();
        
        // Get client information
        $clientId = $_POST['client_id'] ?? '';
        $serviceDate = $_POST['service_date'] ?? date('Y-m-d');
        $payerId = $_POST['payer_id'] ?? 'MDMCD';
        
        if (empty($clientId)) {
            throw new Exception("Please select a client");
        }
        
        // Get client details
        $stmt = $pdo->prepare("
            SELECT first_name, last_name, date_of_birth, ma_number, gender
            FROM autism_clients
            WHERE id = ?
        ");
        $stmt->execute([$clientId]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$client) {
            throw new Exception("Client not found");
        }
        
        // Prepare eligibility request
        $eligibilityData = [
            'ins_name_f' => $client['first_name'],
            'ins_name_l' => $client['last_name'],
            'ins_dob' => date('Ymd', strtotime($client['date_of_birth'])),
            'ins_sex' => strtoupper(substr($client['gender'], 0, 1)),
            'ins_number' => $client['ma_number'],
            'payerid' => $payerId
        ];
        
        // Check eligibility
        $eligibilityResult = $claimMD->checkEligibility($eligibilityData, $serviceDate);
        
        // Log eligibility check
        $auditStmt = $pdo->prepare("
            INSERT INTO autism_audit_log (user_id, action, entity_type, entity_id, details)
            VALUES (?, 'eligibility_check', 'client', ?, ?)
        ");
        $auditStmt->execute([
            $_SESSION['user_id'],
            $clientId,
            json_encode([
                'payer_id' => $payerId,
                'service_date' => $serviceDate,
                'result' => $eligibilityResult['result']['elig'][0]['benefit'][0]['benefit_coverage_description'] ?? 'Unknown'
            ])
        ]);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get active clients
$clientsStmt = $pdo->query("
    SELECT id, CONCAT(last_name, ', ', first_name) as name, ma_number
    FROM autism_clients
    ORDER BY last_name, first_name
");
$clients = $clientsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eligibility Verification - ACI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-group select, .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-primary {
            background: #059669;
            color: white;
        }
        .btn-primary:hover {
            background: #047857;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        .eligibility-result {
            margin-top: 2rem;
        }
        .eligibility-result h3 {
            color: #059669;
            margin-bottom: 1rem;
        }
        .benefit-item {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }
        .benefit-active {
            border-left: 4px solid #059669;
        }
        .benefit-inactive {
            border-left: 4px solid #dc2626;
        }
        .benefit-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }
        .benefit-details strong {
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Eligibility Verification</h1>
        <a href="/dashboard" style="color: #059669; text-decoration: none;">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Check Client Eligibility</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="client_id">Select Client</label>
                    <select name="client_id" id="client_id" required>
                        <option value="">-- Select Client --</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>" <?= ($_POST['client_id'] ?? '') == $client['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($client['name']) ?> - MA: <?= htmlspecialchars($client['ma_number']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="service_date">Service Date</label>
                    <input type="date" name="service_date" id="service_date" 
                           value="<?= htmlspecialchars($_POST['service_date'] ?? date('Y-m-d')) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="payer_id">Payer</label>
                    <select name="payer_id" id="payer_id" required>
                        <?php foreach (MD_MEDICAID_PAYER_IDS as $id => $name): ?>
                            <option value="<?= $id ?>" <?= ($_POST['payer_id'] ?? 'MDMCD') == $id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Check Eligibility</button>
            </form>
        </div>
        
        <?php if ($eligibilityResult && !empty($eligibilityResult['result']['elig'])): ?>
            <div class="card eligibility-result">
                <h3>Eligibility Results</h3>
                
                <?php 
                $elig = $eligibilityResult['result']['elig'][0];
                $isActive = false;
                
                // Check for active coverage
                foreach ($elig['benefit'] ?? [] as $benefit) {
                    if ($benefit['benefit_coverage_code'] == '1' && $benefit['benefit_coverage_description'] == 'Active Coverage') {
                        $isActive = true;
                        break;
                    }
                }
                ?>
                
                <div class="alert <?= $isActive ? 'alert-success' : 'alert-error' ?>">
                    <?= $isActive ? '✓ Client has active coverage' : '✗ Client does not have active coverage' ?>
                </div>
                
                <div class="benefit-details">
                    <div>
                        <strong>Patient:</strong> <?= htmlspecialchars($elig['ins_name_f'] . ' ' . $elig['ins_name_l']) ?>
                    </div>
                    <div>
                        <strong>Member ID:</strong> <?= htmlspecialchars($elig['ins_number']) ?>
                    </div>
                    <div>
                        <strong>Group:</strong> <?= htmlspecialchars($elig['group_number'] ?? 'N/A') ?>
                    </div>
                    <div>
                        <strong>Plan:</strong> <?= htmlspecialchars($elig['insurance_plan'] ?? 'N/A') ?>
                    </div>
                </div>
                
                <h4 style="margin-top: 2rem; margin-bottom: 1rem;">Coverage Details</h4>
                
                <?php foreach ($elig['benefit'] ?? [] as $benefit): ?>
                    <div class="benefit-item <?= $benefit['benefit_coverage_code'] == '1' ? 'benefit-active' : 'benefit-inactive' ?>">
                        <strong><?= htmlspecialchars($benefit['benefit_description']) ?></strong>
                        - <?= htmlspecialchars($benefit['benefit_coverage_description']) ?>
                        
                        <?php if (!empty($benefit['benefit_notes'])): ?>
                            <div style="margin-top: 0.5rem; font-size: 0.875rem; color: #64748b;">
                                Note: <?= htmlspecialchars($benefit['benefit_notes']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($benefit['benefit_amount']) || !empty($benefit['benefit_percent'])): ?>
                            <div class="benefit-details">
                                <?php if (!empty($benefit['benefit_amount'])): ?>
                                    <div><strong>Amount:</strong> $<?= number_format($benefit['benefit_amount'], 2) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($benefit['benefit_percent'])): ?>
                                    <div><strong>Coinsurance:</strong> <?= $benefit['benefit_percent'] ?>%</div>
                                <?php endif; ?>
                                <?php if (!empty($benefit['benefit_period_description'])): ?>
                                    <div><strong>Period:</strong> <?= htmlspecialchars($benefit['benefit_period_description']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <p style="margin-top: 2rem; font-size: 0.875rem; color: #64748b;">
                    Eligibility checked on <?= date('M j, Y g:i A') ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>