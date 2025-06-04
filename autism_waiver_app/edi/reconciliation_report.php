<?php
/**
 * EDI 835 Reconciliation Report
 * 
 * Displays detailed reconciliation report for payment batches
 * 
 * @package     ScriverACI
 * @subpackage  EDI
 */

require_once dirname(dirname(__DIR__)) . '/autism_waiver_app/auth_helper.php';

// Check authentication
requireLogin();
requireRole(['admin', 'billing_manager', 'billing_staff']);

// Database connection
$db_path = dirname(__DIR__) . '/autism_waiver_test.db';
$db = new PDO('sqlite:' . $db_path);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$batchId = isset($_GET['batch_id']) ? intval($_GET['batch_id']) : 0;

if (!$batchId) {
    header('Location: process_remittance.php');
    exit;
}

// Get batch information
$stmt = $db->prepare("
    SELECT 
        pb.*,
        u.username as created_by_name,
        et.filename as edi_filename
    FROM payment_batches pb
    LEFT JOIN users u ON pb.created_by = u.id
    LEFT JOIN edi_transactions et ON pb.id = et.batch_id
    WHERE pb.id = :batch_id
");
$stmt->execute([':batch_id' => $batchId]);
$batch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$batch) {
    die("Batch not found");
}

// Get payment details
$stmt = $db->prepare("
    SELECT 
        cp.*,
        c.claim_number,
        c.service_date,
        c.total_amount as claim_amount,
        cl.first_name,
        cl.last_name,
        cl.medicaid_id
    FROM claim_payments cp
    JOIN billing_claims c ON cp.claim_id = c.id
    JOIN clients cl ON c.client_id = cl.id
    WHERE cp.batch_id = :batch_id
    ORDER BY c.claim_number
");
$stmt->execute([':batch_id' => $batchId]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get adjustments summary
$stmt = $db->prepare("
    SELECT 
        pa.group_code,
        pa.reason_code,
        COUNT(DISTINCT cp.claim_id) as claim_count,
        SUM(pa.amount) as total_amount
    FROM payment_adjustments pa
    JOIN claim_payments cp ON pa.payment_id = cp.id
    WHERE cp.batch_id = :batch_id
    GROUP BY pa.group_code, pa.reason_code
    ORDER BY total_amount DESC
");
$stmt->execute([':batch_id' => $batchId]);
$adjustmentSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get provider adjustments
$stmt = $db->prepare("
    SELECT 
        pa.*,
        GROUP_CONCAT(
            pad.reason_code || ':$' || printf('%.2f', pad.amount),
            ', '
        ) as adjustments
    FROM provider_adjustments pa
    LEFT JOIN provider_adjustment_details pad ON pa.id = pad.provider_adjustment_id
    WHERE pa.batch_id = :batch_id
    GROUP BY pa.id
");
$stmt->execute([':batch_id' => $batchId]);
$providerAdjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$totalClaims = count($payments);
$totalCharged = array_sum(array_column($payments, 'claim_amount'));
$totalPaid = array_sum(array_column($payments, 'payment_amount'));
$totalPatientResp = array_sum(array_column($payments, 'patient_responsibility'));
$totalAdjustments = $totalCharged - $totalPaid - $totalPatientResp;

// Group payments by status
$paidClaims = array_filter($payments, function($p) { return $p['payment_amount'] > 0; });
$deniedClaims = array_filter($payments, function($p) { return $p['payment_amount'] == 0; });

// Adjustment reason codes
$adjustmentReasonCodes = [
    '1' => 'Deductible Amount',
    '2' => 'Coinsurance Amount',
    '3' => 'Co-payment Amount',
    '45' => 'Charges exceed contracted fee',
    '50' => 'Not medically necessary',
    '96' => 'Non-covered charges',
    '97' => 'Not paid separately',
    '119' => 'Benefit maximum reached',
    '136' => 'No prior authorization',
    '197' => 'Precertification absent'
];

$adjustmentGroupCodes = [
    'CO' => 'Contractual Obligations',
    'PR' => 'Patient Responsibility',
    'OA' => 'Other Adjustments',
    'PI' => 'Payer Initiated',
    'CR' => 'Corrections/Reversals'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reconciliation Report - Batch #<?= htmlspecialchars($batch['id']) ?> - Scriver ACI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .page-break { page-break-after: always; }
        }
        .summary-card { border-left: 4px solid #0d6efd; }
        .adjustment-table td { font-size: 0.9em; }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/navigation.php'; ?>
    
    <div class="container-fluid mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-1">EDI 835 Reconciliation Report</h1>
                        <p class="text-muted mb-0">Batch #<?= htmlspecialchars($batch['id']) ?> - <?= htmlspecialchars($batch['payer_name']) ?></p>
                    </div>
                    <div class="no-print">
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print Report
                        </button>
                        <a href="process_remittance.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Batch Summary -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Batch Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Check/EFT Number:</strong></td>
                                <td><?= htmlspecialchars($batch['check_number'] ?: 'N/A') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Payment Date:</strong></td>
                                <td><?= date('m/d/Y', strtotime($batch['payment_date'])) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Payer:</strong></td>
                                <td><?= htmlspecialchars($batch['payer_name']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>EDI File:</strong></td>
                                <td><?= htmlspecialchars($batch['edi_filename'] ?: 'N/A') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Processed:</strong></td>
                                <td><?= date('m/d/Y g:i A', strtotime($batch['created_at'])) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Processed By:</strong></td>
                                <td><?= htmlspecialchars($batch['created_by_name']) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card summary-card">
                    <div class="card-body">
                        <h5 class="card-title">Payment Summary</h5>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Total Claims:</strong></td>
                                <td><?= number_format($totalClaims) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Claims Paid:</strong></td>
                                <td><?= number_format(count($paidClaims)) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Claims Denied:</strong></td>
                                <td><?= number_format(count($deniedClaims)) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Charged:</strong></td>
                                <td>$<?= number_format($totalCharged, 2) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Paid:</strong></td>
                                <td>$<?= number_format($totalPaid, 2) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Patient Responsibility:</strong></td>
                                <td>$<?= number_format($totalPatientResp, 2) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Adjustments:</strong></td>
                                <td>$<?= number_format($totalAdjustments, 2) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Adjustment Summary -->
        <?php if (!empty($adjustmentSummary)): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Adjustment Summary</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm adjustment-table">
                            <thead>
                                <tr>
                                    <th>Group</th>
                                    <th>Reason Code</th>
                                    <th>Description</th>
                                    <th>Claims</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($adjustmentSummary as $adj): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= htmlspecialchars($adj['group_code']) ?>
                                        </span>
                                        <?= htmlspecialchars($adjustmentGroupCodes[$adj['group_code']] ?? '') ?>
                                    </td>
                                    <td><?= htmlspecialchars($adj['reason_code']) ?></td>
                                    <td><?= htmlspecialchars($adjustmentReasonCodes[$adj['reason_code']] ?? 'Unknown') ?></td>
                                    <td><?= number_format($adj['claim_count']) ?></td>
                                    <td class="text-end">$<?= number_format($adj['total_amount'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Claim Details -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Claim Payment Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Claim #</th>
                                        <th>Patient</th>
                                        <th>Medicaid ID</th>
                                        <th>Service Date</th>
                                        <th>Status</th>
                                        <th class="text-end">Charged</th>
                                        <th class="text-end">Paid</th>
                                        <th class="text-end">Patient</th>
                                        <th class="text-end">Adjustment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): 
                                        $adjustment = $payment['claim_amount'] - $payment['payment_amount'] - $payment['patient_responsibility'];
                                        $statusClass = $payment['payment_amount'] > 0 ? 'success' : 'danger';
                                        $statusText = $payment['payment_amount'] > 0 ? 'Paid' : 'Denied';
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($payment['claim_number']) ?></td>
                                        <td><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></td>
                                        <td><?= htmlspecialchars($payment['medicaid_id']) ?></td>
                                        <td><?= date('m/d/Y', strtotime($payment['service_date'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $statusClass ?>">
                                                <?= $statusText ?>
                                            </span>
                                        </td>
                                        <td class="text-end">$<?= number_format($payment['claim_amount'], 2) ?></td>
                                        <td class="text-end">$<?= number_format($payment['payment_amount'], 2) ?></td>
                                        <td class="text-end">$<?= number_format($payment['patient_responsibility'], 2) ?></td>
                                        <td class="text-end">$<?= number_format($adjustment, 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="fw-bold">
                                        <td colspan="5">Totals</td>
                                        <td class="text-end">$<?= number_format($totalCharged, 2) ?></td>
                                        <td class="text-end">$<?= number_format($totalPaid, 2) ?></td>
                                        <td class="text-end">$<?= number_format($totalPatientResp, 2) ?></td>
                                        <td class="text-end">$<?= number_format($totalAdjustments, 2) ?></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Provider Level Adjustments -->
        <?php if (!empty($providerAdjustments)): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Provider Level Adjustments</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Provider ID</th>
                                    <th>Fiscal Period</th>
                                    <th>Adjustments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($providerAdjustments as $plb): ?>
                                <tr>
                                    <td><?= htmlspecialchars($plb['provider_id']) ?></td>
                                    <td><?= date('m/d/Y', strtotime($plb['fiscal_period_date'])) ?></td>
                                    <td><?= htmlspecialchars($plb['adjustments']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Footer -->
        <div class="row mt-5 mb-3">
            <div class="col-md-12 text-center text-muted">
                <small>
                    Generated on <?= date('m/d/Y g:i A') ?> by <?= htmlspecialchars($_SESSION['username']) ?>
                    <br>
                    Scriver ACI - EDI 835 Reconciliation Report
                </small>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>