<?php
require_once '../src/init.php';
requireAuth(3); // Case Manager+ access

try {
    $pdo = getDatabase();
    
    // Get claims ready for EDI submission
    $stmt = $pdo->query("
        SELECT c.*, cl.first_name, cl.last_name, cl.ma_number
        FROM autism_claims c
        JOIN autism_clients cl ON c.client_id = cl.id
        WHERE c.status IN ('draft', 'generated')
        ORDER BY c.created_at DESC
    ");
    $ediClaims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle EDI generation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_edi'])) {
        $claim_ids = $_POST['claim_ids'] ?? [];
        if (!empty($claim_ids)) {
            // Generate EDI 837 file
            $edi_content = generateEDI837($claim_ids, $pdo);
            $filename = 'EDI_837_' . date('Ymd_His') . '.txt';
            
            // In production, you would save to a secure directory
            // For now, we'll just mark claims as generated
            $placeholders = str_repeat('?,', count($claim_ids) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE autism_claims SET status = 'generated' WHERE id IN ($placeholders)");
            $stmt->execute($claim_ids);
            
            $success = "EDI file generated for " . count($claim_ids) . " claims";
        }
    }
    
} catch (Exception $e) {
    error_log("EDI processing error: " . $e->getMessage());
    $ediClaims = [];
    $error = "Database error: " . $e->getMessage();
}

function generateEDI837($claim_ids, $pdo) {
    // Simplified EDI 837 generation - in production, use proper EDI library
    $edi = "ISA*00*          *00*          *ZZ*SENDER         *ZZ*RECEIVER       *" . date('ymd') . "*" . date('Hi') . "*^*00501*000000001*0*P*:~\n";
    $edi .= "GS*HC*SENDER*RECEIVER*" . date('Ymd') . "*" . date('Hi') . "*1*X*005010X222A1~\n";
    
    // Add claim data here - this is a simplified example
    foreach ($claim_ids as $claim_id) {
        $edi .= "ST*837*0001*005010X222A1~\n";
        // Add actual claim segments here
        $edi .= "SE*10*0001~\n";
    }
    
    $edi .= "GE*1*1~\n";
    $edi .= "IEA*1*000000001~\n";
    
    return $edi;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDI Processing - ACI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; }
        .header { background: white; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { color: #059669; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .section { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .btn-primary { background: #059669; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f8fafc; font-weight: 600; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-draft { background: #e5e7eb; color: #374151; }
        .status-generated { background: #fef3c7; color: #92400e; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .info-box { background: #f0f9ff; border: 1px solid #0ea5e9; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
        .checkbox { margin-right: 0.5rem; }
    </style>
</head>
<body>
    <div class="header">
        <h1>EDI Processing</h1>
        <a href="<?= UrlManager::url('billing') ?>" style="color: #059669; text-decoration: none;">← Back to Billing</a>
    </div>
    
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3 style="color: #0c4a6e; margin-bottom: 0.5rem;">📄 EDI 837 Processing</h3>
            <p style="color: #0369a1;">Generate electronic claims for Maryland Medicaid submission. Claims must be in 'draft' status to be included in EDI files.</p>
        </div>
        
        <div class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2>Claims Ready for EDI</h2>
                <button class="btn btn-primary" onclick="generateSelectedEDI()">Generate EDI File</button>
            </div>
            
            <form method="POST" id="ediForm">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" onclick="toggleAll(this)"></th>
                            <th>Claim Number</th>
                            <th>Client</th>
                            <th>MA Number</th>
                            <th>Service Period</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ediClaims)): ?>
                            <tr><td colspan="8" style="text-align: center; color: #64748b;">No claims ready for EDI processing</td></tr>
                        <?php else: ?>
                            <?php foreach ($ediClaims as $claim): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="claim_ids[]" value="<?= $claim['id'] ?>" class="claim-checkbox">
                                    </td>
                                    <td><?= htmlspecialchars($claim['claim_number']) ?></td>
                                    <td><?= htmlspecialchars($claim['last_name'] . ', ' . $claim['first_name']) ?></td>
                                    <td><?= htmlspecialchars($claim['ma_number']) ?></td>
                                    <td>
                                        <?= date('m/d/Y', strtotime($claim['service_date_from'])) ?> - 
                                        <?= date('m/d/Y', strtotime($claim['service_date_to'])) ?>
                                    </td>
                                    <td>$<?= number_format($claim['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $claim['status'] ?>">
                                            <?= ucfirst($claim['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('m/d/Y', strtotime($claim['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
        
        <div class="section">
            <h2>EDI File History</h2>
            <table>
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Claims Count</th>
                        <th>Total Amount</th>
                        <th>Generated</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="6" style="text-align: center; color: #64748b;">No EDI files generated yet</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>Maryland Medicaid Requirements</h2>
            <ul style="color: #374151; line-height: 1.6;">
                <li>EDI 837 files must be submitted within 365 days of service date</li>
                <li>Claims require valid NPI and taxonomy codes</li>
                <li>Prior authorization numbers must be included for applicable services</li>
                <li>All client eligibility must be verified before submission</li>
                <li>Claims are processed in batches - typically 24-48 hour turnaround</li>
            </ul>
        </div>
    </div>
    
    <script>
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('.claim-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = source.checked);
        }
        
        function generateSelectedEDI() {
            const checked = document.querySelectorAll('.claim-checkbox:checked');
            if (checked.length === 0) {
                alert('Please select at least one claim to process');
                return;
            }
            
            if (confirm(`Generate EDI file for ${checked.length} selected claims?`)) {
                document.getElementById('ediForm').innerHTML += '<input type="hidden" name="generate_edi" value="1">';
                document.getElementById('ediForm').submit();
            }
        }
    </script>
</body>
</html>