<?php
require_once '../src/init.php';
requireAuth(3); // Case Manager+ access

try {
    $pdo = getDatabase();
    
    // Handle claim creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_claim'])) {
        $client_id = $_POST['client_id'];
        $date_from = $_POST['date_from'];
        $date_to = $_POST['date_to'];
        $total_amount = $_POST['total_amount'] ?? 500.00; // Default amount
        
        // Generate claim number
        $claim_number = 'CLM' . date('Ymd') . str_pad($client_id, 3, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("
            INSERT INTO autism_claims (claim_number, client_id, service_date_from, service_date_to, total_amount, status)
            VALUES (?, ?, ?, ?, ?, 'draft')
        ");
        
        if ($stmt->execute([$claim_number, $client_id, $date_from, $date_to, $total_amount])) {
            $success = "Claim {$claim_number} created successfully!";
            // Refresh claims list
            $stmt = $pdo->query("
                SELECT c.*, cl.first_name, cl.last_name, cl.ma_number
                FROM autism_claims c
                JOIN autism_clients cl ON c.client_id = cl.id
                ORDER BY c.created_at DESC
            ");
            $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "Failed to create claim.";
        }
    }
    
    // Get all claims
    $stmt = $pdo->query("
        SELECT c.*, cl.first_name, cl.last_name, cl.ma_number
        FROM autism_claims c
        JOIN autism_clients cl ON c.client_id = cl.id
        ORDER BY c.created_at DESC
    ");
    $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get clients for claim creation (simplified approach)
    $stmt = $pdo->query("SELECT id, first_name, last_name, ma_number FROM autism_clients ORDER BY last_name, first_name");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Billing claims error: " . $e->getMessage());
    $claims = [];
    $clients = [];
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claims Management - ACI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; }
        .header { background: white; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { color: #059669; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .section { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .btn-primary { background: #059669; color: white; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background: #f8fafc; font-weight: 600; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .status-draft { background: #e5e7eb; color: #374151; }
        .status-submitted { background: #dbeafe; color: #1e40af; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; margin: 5% auto; padding: 2rem; border-radius: 12px; max-width: 500px; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Claims Management</h1>
        <a href="<?= UrlManager::url('billing') ?>" style="color: #059669; text-decoration: none;">← Back to Billing</a>
    </div>
    
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2>Medicaid Claims</h2>
                <button class="btn btn-primary" onclick="showCreateModal()">Create New Claim</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Claim Number</th>
                        <th>Client</th>
                        <th>MA Number</th>
                        <th>Service Period</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($claims)): ?>
                        <tr><td colspan="8" style="text-align: center; color: #64748b;">No claims found</td></tr>
                    <?php else: ?>
                        <?php foreach ($claims as $claim): ?>
                            <tr>
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
                                <td>
                                    <a href="<?= UrlManager::url('billing') ?>/claim/<?= $claim['id'] ?>" style="color: #059669;">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Create Claim Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <h3>Create New Claim</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Client</label>
                    <select name="client_id" required>
                        <option value="">Select Client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>">
                                <?= htmlspecialchars($client['last_name'] . ', ' . $client['first_name']) ?>
                                (MA: <?= $client['ma_number'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Service Date From</label>
                    <input type="date" name="date_from" required>
                </div>
                <div class="form-group">
                    <label>Service Date To</label>
                    <input type="date" name="date_to" required>
                </div>
                <div class="form-group">
                    <label>Total Amount ($)</label>
                    <input type="number" name="total_amount" step="0.01" min="0" value="500.00" required>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="hideCreateModal()" style="background: #e5e7eb;">Cancel</button>
                    <button type="submit" name="create_claim" class="btn btn-primary">Create Claim</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showCreateModal() {
            document.getElementById('createModal').style.display = 'block';
        }
        
        function hideCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('createModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>