<?php
session_start();
require_once '../simple_auth_helper.php';
require_once '../config_sqlite.php';

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type'])) {
    http_response_code(401);
    echo "Unauthorized";
    exit();
}

if (!in_array($_SESSION['user_type'], ['admin', 'billing'])) {
    http_response_code(403);
    echo "Access denied";
    exit();
}

// Get payment ID
$payment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$payment_id) {
    echo "Invalid payment ID";
    exit();
}

// Fetch payment details
$payment_query = "
    SELECT pp.*, 
           c.claim_number, c.service_date, c.billed_amount,
           p.first_name, p.last_name, p.medicaid_id,
           u1.username as posted_by_username,
           u2.username as voided_by_username,
           bd.deposit_number, bd.deposit_date
    FROM payment_postings pp
    LEFT JOIN claims c ON pp.claim_id = c.id
    LEFT JOIN patients p ON pp.patient_id = p.id
    LEFT JOIN users u1 ON pp.posted_by = u1.id
    LEFT JOIN users u2 ON pp.voided_by = u2.id
    LEFT JOIN batch_deposits bd ON pp.batch_deposit_id = bd.id
    WHERE pp.id = ?
";

$stmt = $db->prepare($payment_query);
$stmt->execute([$payment_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    echo "Payment not found";
    exit();
}

// Get claim payment history if applicable
$payment_history = [];
if ($payment['claim_id']) {
    $history_query = "
        SELECT pp.*, u.username as posted_by_username
        FROM payment_postings pp
        LEFT JOIN users u ON pp.posted_by = u.id
        WHERE pp.claim_id = ?
        ORDER BY pp.created_at DESC
    ";
    $history_stmt = $db->prepare($history_query);
    $history_stmt->execute([$payment['claim_id']]);
    $payment_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="payment-details">
    <div class="row">
        <div class="col-md-6">
            <h6 class="text-primary mb-3">Payment Information</h6>
            <table class="table table-sm">
                <tr>
                    <th width="40%">Payment ID:</th>
                    <td>#<?php echo $payment['id']; ?></td>
                </tr>
                <tr>
                    <th>Status:</th>
                    <td>
                        <?php if ($payment['status'] == 'posted'): ?>
                            <span class="badge bg-success">Posted</span>
                        <?php elseif ($payment['status'] == 'voided'): ?>
                            <span class="badge bg-danger">Voided</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?php echo ucfirst($payment['status']); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Payment Date:</th>
                    <td><?php echo date('m/d/Y', strtotime($payment['payment_date'])); ?></td>
                </tr>
                <tr>
                    <th>Payment Type:</th>
                    <td><?php echo ucfirst($payment['payment_type']); ?></td>
                </tr>
                <tr>
                    <th>Amount:</th>
                    <td class="<?php echo $payment['payment_amount'] < 0 ? 'text-danger' : 'text-success'; ?>">
                        <strong>$<?php echo number_format(abs($payment['payment_amount']), 2); ?></strong>
                        <?php if ($payment['payment_amount'] < 0): ?>
                            (Adjustment/Reversal)
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($payment['check_number']): ?>
                <tr>
                    <th>Check Number:</th>
                    <td><?php echo htmlspecialchars($payment['check_number']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($payment['reference_number']): ?>
                <tr>
                    <th>Reference Number:</th>
                    <td><?php echo htmlspecialchars($payment['reference_number']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($payment['era_number']): ?>
                <tr>
                    <th>ERA Number:</th>
                    <td><?php echo htmlspecialchars($payment['era_number']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($payment['payer_name']): ?>
                <tr>
                    <th>Payer:</th>
                    <td><?php echo htmlspecialchars($payment['payer_name']); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($payment['payment_method']): ?>
                <tr>
                    <th>Payment Method:</th>
                    <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($payment['deposit_number']): ?>
                <tr>
                    <th>Batch Deposit:</th>
                    <td>
                        <?php echo htmlspecialchars($payment['deposit_number']); ?> 
                        (<?php echo date('m/d/Y', strtotime($payment['deposit_date'])); ?>)
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <div class="col-md-6">
            <?php if ($payment['claim_id']): ?>
            <h6 class="text-primary mb-3">Claim Information</h6>
            <table class="table table-sm">
                <tr>
                    <th width="40%">Claim Number:</th>
                    <td><?php echo htmlspecialchars($payment['claim_number']); ?></td>
                </tr>
                <tr>
                    <th>Patient:</th>
                    <td><?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?></td>
                </tr>
                <tr>
                    <th>Medicaid ID:</th>
                    <td><?php echo htmlspecialchars($payment['medicaid_id']); ?></td>
                </tr>
                <tr>
                    <th>Service Date:</th>
                    <td><?php echo date('m/d/Y', strtotime($payment['service_date'])); ?></td>
                </tr>
                <tr>
                    <th>Billed Amount:</th>
                    <td>$<?php echo number_format($payment['billed_amount'], 2); ?></td>
                </tr>
            </table>
            <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> This is an unapplied payment
            </div>
            <?php endif; ?>
            
            <h6 class="text-primary mb-3 mt-4">Posting Information</h6>
            <table class="table table-sm">
                <tr>
                    <th width="40%">Posted By:</th>
                    <td><?php echo htmlspecialchars($payment['posted_by_username']); ?></td>
                </tr>
                <tr>
                    <th>Posted On:</th>
                    <td><?php echo date('m/d/Y g:i A', strtotime($payment['created_at'])); ?></td>
                </tr>
                <?php if ($payment['status'] == 'voided'): ?>
                <tr>
                    <th>Voided By:</th>
                    <td><?php echo htmlspecialchars($payment['voided_by_username']); ?></td>
                </tr>
                <tr>
                    <th>Void Date:</th>
                    <td><?php echo date('m/d/Y g:i A', strtotime($payment['void_date'])); ?></td>
                </tr>
                <tr>
                    <th>Void Reason:</th>
                    <td class="text-danger"><?php echo htmlspecialchars($payment['void_reason']); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
    
    <?php if ($payment['notes']): ?>
    <div class="mt-3">
        <h6 class="text-primary">Notes</h6>
        <div class="bg-light p-2 rounded">
            <?php echo nl2br(htmlspecialchars($payment['notes'])); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($payment['adjustment_type']): ?>
    <div class="mt-3">
        <h6 class="text-primary">Adjustment Details</h6>
        <table class="table table-sm">
            <tr>
                <th width="30%">Adjustment Type:</th>
                <td><?php echo ucfirst(str_replace('_', ' ', $payment['adjustment_type'])); ?></td>
            </tr>
            <?php if ($payment['adjustment_reason']): ?>
            <tr>
                <th>Reason:</th>
                <td><?php echo htmlspecialchars($payment['adjustment_reason']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($payment_history) && count($payment_history) > 1): ?>
    <div class="mt-4">
        <h6 class="text-primary">Claim Payment History</h6>
        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Check/Ref#</th>
                        <th>Status</th>
                        <th>Posted By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payment_history as $history): ?>
                    <tr <?php echo $history['id'] == $payment_id ? 'class="table-primary"' : ''; ?>>
                        <td><?php echo date('m/d/Y', strtotime($history['payment_date'])); ?></td>
                        <td><?php echo ucfirst($history['payment_type']); ?></td>
                        <td class="<?php echo $history['payment_amount'] < 0 ? 'text-danger' : ''; ?>">
                            $<?php echo number_format(abs($history['payment_amount']), 2); ?>
                        </td>
                        <td><?php echo htmlspecialchars($history['check_number'] ?: $history['reference_number'] ?: '-'); ?></td>
                        <td>
                            <?php if ($history['status'] == 'posted'): ?>
                                <span class="badge bg-success">Posted</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Voided</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($history['posted_by_username']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-secondary">
                        <th colspan="2">Total Posted:</th>
                        <th>
                            <?php 
                            $total_posted = array_sum(array_map(function($h) {
                                return $h['status'] == 'posted' ? $h['payment_amount'] : 0;
                            }, $payment_history));
                            echo '$' . number_format($total_posted, 2);
                            ?>
                        </th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.payment-details table th {
    font-weight: 600;
    color: #495057;
}
.payment-details table td {
    color: #212529;
}
</style>