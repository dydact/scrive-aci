<?php
/**
 * EDI Management Interface
 * 
 * Web interface for managing EDI 837 claim submissions
 */

session_start();
require_once __DIR__ . '/auth_helper.php';

// Check authentication
checkAuthentication();

// Check for billing permissions
if (!hasPermission('billing_view')) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Get recent EDI transactions
$stmt = $db->prepare("
    SELECT 
        t.id,
        t.transaction_type,
        t.filename,
        t.claim_count,
        t.total_amount,
        t.status,
        t.created_at,
        t.transmission_date,
        u.username as created_by_name
    FROM edi_transactions t
    JOIN users u ON t.created_by = u.id
    WHERE t.transaction_set = '837'
    ORDER BY t.created_at DESC
    LIMIT 50
");
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending claims count
$stmt = $db->prepare("
    SELECT COUNT(*) as pending_count
    FROM billing_claims
    WHERE claim_status = 'ready'
    AND edi_status = 'pending'
");
$stmt->execute();
$pendingCount = $stmt->fetch(PDO::FETCH_ASSOC)['pending_count'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDI Management - Autism Care Institute</title>
    <link rel="stylesheet" href="/public/assets/bootstrap/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/public/assets/@fortawesome/fontawesome-free/css/all.min.css">
    <style>
        .status-badge {
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
        }
        .status-pending { background-color: #ffc107; color: #000; }
        .status-transmitted { background-color: #17a2b8; color: #fff; }
        .status-accepted { background-color: #28a745; color: #fff; }
        .status-rejected { background-color: #dc3545; color: #fff; }
        .status-error { background-color: #6c757d; color: #fff; }
        
        .edi-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #495057;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navigation.php'; ?>
    
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h1>EDI Management</h1>
                <p class="text-muted">Manage EDI 837 Professional claim submissions</p>
            </div>
            <div class="col-auto">
                <?php if ($pendingCount > 0 && hasPermission('billing_submit')): ?>
                <button class="btn btn-primary" onclick="processPendingClaims()">
                    <i class="fas fa-paper-plane"></i> Process <?php echo $pendingCount; ?> Pending Claims
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="edi-stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo $pendingCount; ?></div>
                <div class="stat-label">Pending Claims</div>
            </div>
            
            <?php
            // Get today's statistics
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as file_count,
                    SUM(claim_count) as total_claims,
                    SUM(total_amount) as total_amount
                FROM edi_transactions
                WHERE transaction_set = '837'
                AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute();
            $todayStats = $stmt->fetch(PDO::FETCH_ASSOC);
            ?>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $todayStats['file_count'] ?? 0; ?></div>
                <div class="stat-label">Files Today</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $todayStats['total_claims'] ?? 0; ?></div>
                <div class="stat-label">Claims Today</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value">$<?php echo number_format($todayStats['total_amount'] ?? 0, 2); ?></div>
                <div class="stat-label">Total Amount Today</div>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent EDI Transactions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Filename</th>
                                <th>Claims</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Transmitted</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $trans): ?>
                            <tr>
                                <td><?php echo $trans['id']; ?></td>
                                <td><?php echo htmlspecialchars($trans['filename']); ?></td>
                                <td><?php echo $trans['claim_count']; ?></td>
                                <td>$<?php echo number_format($trans['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge status-badge status-<?php echo $trans['status']; ?>">
                                        <?php echo ucfirst($trans['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('m/d/Y H:i', strtotime($trans['created_at'])); ?></td>
                                <td>
                                    <?php echo $trans['transmission_date'] ? 
                                        date('m/d/Y H:i', strtotime($trans['transmission_date'])) : 
                                        '-'; ?>
                                </td>
                                <td><?php echo htmlspecialchars($trans['created_by_name']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button class="btn btn-outline-primary" onclick="viewTransaction(<?php echo $trans['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-outline-secondary" onclick="downloadFile(<?php echo $trans['id']; ?>)">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <?php if ($trans['status'] == 'pending' && hasPermission('billing_submit')): ?>
                                        <button class="btn btn-outline-success" onclick="transmitFile(<?php echo $trans['id']; ?>)">
                                            <i class="fas fa-upload"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Transaction Details Modal -->
    <div class="modal fade" id="transactionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transaction Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="transactionDetails">
                    <!-- Transaction details will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <script src="/public/assets/jquery/dist/jquery.min.js"></script>
    <script src="/public/assets/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function processPendingClaims() {
            if (!confirm('Process all pending claims for EDI submission?')) {
                return;
            }
            
            $.ajax({
                url: 'edi/process_claims.php',
                method: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Successfully generated EDI file:\n' +
                              'File ID: ' + response.file_id + '\n' +
                              'Claims processed: ' + response.claims_processed + '\n' +
                              'Total amount: $' + response.total_amount.toFixed(2));
                        
                        if (response.errors && response.errors.length > 0) {
                            console.log('Errors:', response.errors);
                        }
                        
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                        if (response.errors) {
                            console.log('Errors:', response.errors);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error processing claims: ' + error);
                }
            });
        }
        
        function viewTransaction(id) {
            $.get('edi/view_transaction.php?id=' + id, function(data) {
                $('#transactionDetails').html(data);
                $('#transactionModal').modal('show');
            });
        }
        
        function downloadFile(id) {
            window.location.href = 'edi/download.php?id=' + id;
        }
        
        function transmitFile(id) {
            if (!confirm('Transmit this EDI file to Maryland Medicaid?')) {
                return;
            }
            
            $.post('edi/transmit.php', { id: id }, function(response) {
                if (response.success) {
                    alert('File transmitted successfully');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }, 'json');
        }
    </script>
</body>
</html>