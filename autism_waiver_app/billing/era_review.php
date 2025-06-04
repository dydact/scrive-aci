<?php
session_start();
require_once '../simple_auth_helper.php';
require_once '../config_sqlite.php';

// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type'])) {
    header("Location: ../simple_login.php");
    exit();
}

if (!in_array($_SESSION['user_type'], ['admin', 'billing'])) {
    $_SESSION['error'] = "Access denied. You don't have permission to access ERA review.";
    header("Location: ../simple_dashboard.php");
    exit();
}

$era_import_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$era_import_id) {
    $_SESSION['error'] = "Invalid ERA import ID";
    header("Location: payment_posting.php");
    exit();
}

// Fetch ERA import details
$era_stmt = $db->prepare("
    SELECT ei.*, u.username as imported_by_username
    FROM era_imports ei
    LEFT JOIN users u ON ei.imported_by = u.id
    WHERE ei.id = ?
");
$era_stmt->execute([$era_import_id]);
$era_import = $era_stmt->fetch(PDO::FETCH_ASSOC);

if (!$era_import) {
    $_SESSION['error'] = "ERA import not found";
    header("Location: payment_posting.php");
    exit();
}

// Fetch ERA payment details
$details_stmt = $db->prepare("
    SELECT epd.*, c.id as matched_claim_id, c.status as claim_status,
           p.first_name, p.last_name
    FROM era_payment_details epd
    LEFT JOIN claims c ON epd.claim_number = c.claim_number
    LEFT JOIN patients p ON c.patient_id = p.id
    WHERE epd.era_import_id = ?
    ORDER BY epd.id
");
$details_stmt->execute([$era_import_id]);
$payment_details = $details_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERA Review - <?php echo htmlspecialchars($era_import['era_number']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .era-header {
            background-color: #e7f3ff;
            border: 1px solid #0066cc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .payment-row.matched {
            background-color: #d4edda;
        }
        .payment-row.unmatched {
            background-color: #f8d7da;
        }
        .payment-row.partial {
            background-color: #fff3cd;
        }
        .action-buttons {
            position: sticky;
            bottom: 0;
            background-color: white;
            border-top: 2px solid #dee2e6;
            padding: 15px;
            box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="../simple_dashboard.php">
                <i class="bi bi-heart-pulse"></i> Autism Waiver System
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="bi bi-file-earmark-text"></i> ERA/835 Review</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../simple_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="../billing_dashboard.php">Billing</a></li>
                        <li class="breadcrumb-item"><a href="payment_posting.php">Payment Posting</a></li>
                        <li class="breadcrumb-item active">ERA Review</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- ERA Header Information -->
        <div class="era-header">
            <div class="row">
                <div class="col-md-3">
                    <h6>ERA Number</h6>
                    <p class="mb-0"><strong><?php echo htmlspecialchars($era_import['era_number']); ?></strong></p>
                </div>
                <div class="col-md-3">
                    <h6>Payer</h6>
                    <p class="mb-0"><?php echo htmlspecialchars($era_import['payer_name']); ?></p>
                </div>
                <div class="col-md-3">
                    <h6>Check Number</h6>
                    <p class="mb-0"><?php echo htmlspecialchars($era_import['check_number'] ?: 'N/A'); ?></p>
                </div>
                <div class="col-md-3">
                    <h6>Total Amount</h6>
                    <p class="mb-0 text-success"><strong>$<?php echo number_format($era_import['total_amount'], 2); ?></strong></p>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3">
                    <h6>Check Date</h6>
                    <p class="mb-0"><?php echo date('m/d/Y', strtotime($era_import['check_date'])); ?></p>
                </div>
                <div class="col-md-3">
                    <h6>Import Date</h6>
                    <p class="mb-0"><?php echo date('m/d/Y', strtotime($era_import['import_date'])); ?></p>
                </div>
                <div class="col-md-3">
                    <h6>Payment Count</h6>
                    <p class="mb-0"><?php echo $era_import['payment_count']; ?> payments</p>
                </div>
                <div class="col-md-3">
                    <h6>Status</h6>
                    <p class="mb-0">
                        <?php if ($era_import['status'] == 'pending'): ?>
                            <span class="badge bg-warning">Pending Review</span>
                        <?php elseif ($era_import['status'] == 'processed'): ?>
                            <span class="badge bg-success">Processed</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?php echo ucfirst($era_import['status']); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Payment Details</h5>
            </div>
            <div class="card-body p-0">
                <form id="eraReviewForm">
                    <input type="hidden" name="era_import_id" value="<?php echo $era_import_id; ?>">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>Patient</th>
                                    <th>Claim #</th>
                                    <th>Service Date</th>
                                    <th>Billed</th>
                                    <th>Allowed</th>
                                    <th>Paid</th>
                                    <th>Adjustment</th>
                                    <th>Reason</th>
                                    <th>Match Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payment_details as $detail): ?>
                                <?php
                                $match_class = '';
                                $match_status = 'Unmatched';
                                if ($detail['matched_claim_id']) {
                                    $match_class = 'matched';
                                    $match_status = 'Matched';
                                } elseif ($detail['claim_number']) {
                                    $match_class = 'partial';
                                    $match_status = 'Partial Match';
                                } else {
                                    $match_class = 'unmatched';
                                }
                                ?>
                                <tr class="payment-row <?php echo $match_class; ?>">
                                    <td>
                                        <input type="checkbox" name="selected_payments[]" 
                                               value="<?php echo $detail['id']; ?>" 
                                               class="form-check-input payment-checkbox"
                                               <?php echo $detail['status'] == 'processed' ? 'disabled' : ''; ?>>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($detail['first_name']) {
                                            echo htmlspecialchars($detail['first_name'] . ' ' . $detail['last_name']);
                                        } else {
                                            echo htmlspecialchars($detail['patient_name']);
                                        }
                                        ?>
                                        <?php if ($detail['patient_id']): ?>
                                            <br><small class="text-muted">ID: <?php echo htmlspecialchars($detail['patient_id']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($detail['claim_number'] ?: 'N/A'); ?></td>
                                    <td><?php echo $detail['service_date'] ? date('m/d/Y', strtotime($detail['service_date'])) : 'N/A'; ?></td>
                                    <td>$<?php echo number_format($detail['billed_amount'], 2); ?></td>
                                    <td>$<?php echo number_format($detail['allowed_amount'], 2); ?></td>
                                    <td class="text-success">
                                        <strong>$<?php echo number_format($detail['paid_amount'], 2); ?></strong>
                                    </td>
                                    <td class="text-danger">
                                        $<?php echo number_format($detail['adjustment_amount'], 2); ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($detail['adjustment_reason'] ?: '-'); ?>
                                    </td>
                                    <td>
                                        <?php if ($detail['status'] == 'processed'): ?>
                                            <span class="badge bg-success">Posted</span>
                                        <?php else: ?>
                                            <span class="badge bg-<?php echo $match_class == 'matched' ? 'primary' : ($match_class == 'partial' ? 'warning' : 'danger'); ?>">
                                                <?php echo $match_status; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($detail['status'] != 'processed'): ?>
                                            <?php if (!$detail['matched_claim_id']): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="findClaim(<?php echo $detail['id']; ?>)">
                                                    <i class="bi bi-search"></i> Find
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" disabled>
                                                    <i class="bi bi-check"></i> Ready
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-secondary">
                                    <th colspan="4">Totals:</th>
                                    <th>$<?php echo number_format(array_sum(array_column($payment_details, 'billed_amount')), 2); ?></th>
                                    <th>$<?php echo number_format(array_sum(array_column($payment_details, 'allowed_amount')), 2); ?></th>
                                    <th class="text-success">
                                        <strong>$<?php echo number_format(array_sum(array_column($payment_details, 'paid_amount')), 2); ?></strong>
                                    </th>
                                    <th class="text-danger">
                                        $<?php echo number_format(array_sum(array_column($payment_details, 'adjustment_amount')), 2); ?>
                                    </th>
                                    <th colspan="3"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </form>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span id="selectedCount">0</span> payments selected
                </div>
                <div>
                    <button type="button" class="btn btn-secondary" onclick="history.back()">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn btn-primary" onclick="postSelectedPayments()" id="postPaymentsBtn" disabled>
                        <i class="bi bi-check-circle"></i> Post Selected Payments
                    </button>
                    <button type="button" class="btn btn-success" onclick="postAllPayments()">
                        <i class="bi bi-check-all"></i> Post All Matched Payments
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Find Claim Modal -->
    <div class="modal fade" id="findClaimModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Find Matching Claim</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="era_detail_id">
                    <div class="mb-3">
                        <input type="text" class="form-control" id="claimSearch" 
                               placeholder="Search by patient name, claim number, or date...">
                    </div>
                    <div id="claimSearchResults">
                        <!-- Results will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Select all checkbox
            $('#selectAll').change(function() {
                $('.payment-checkbox:not(:disabled)').prop('checked', $(this).is(':checked'));
                updateSelectedCount();
            });
            
            // Individual checkbox change
            $('.payment-checkbox').change(function() {
                updateSelectedCount();
            });
            
            // Claim search
            $('#claimSearch').on('input', function() {
                searchClaims();
            });
        });
        
        function updateSelectedCount() {
            const count = $('.payment-checkbox:checked').length;
            $('#selectedCount').text(count);
            $('#postPaymentsBtn').prop('disabled', count === 0);
        }
        
        function findClaim(detailId) {
            $('#era_detail_id').val(detailId);
            $('#claimSearch').val('');
            $('#claimSearchResults').html('');
            $('#findClaimModal').modal('show');
            searchClaims();
        }
        
        function searchClaims() {
            const searchTerm = $('#claimSearch').val();
            const detailId = $('#era_detail_id').val();
            
            $.ajax({
                url: 'search_claims_for_era.php',
                method: 'GET',
                data: {
                    search: searchTerm,
                    era_detail_id: detailId
                },
                success: function(response) {
                    $('#claimSearchResults').html(response);
                },
                error: function() {
                    $('#claimSearchResults').html('<div class="alert alert-danger">Error searching claims</div>');
                }
            });
        }
        
        function selectClaim(claimId) {
            const detailId = $('#era_detail_id').val();
            
            $.ajax({
                url: 'match_era_claim.php',
                method: 'POST',
                data: {
                    era_detail_id: detailId,
                    claim_id: claimId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#findClaimModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error matching claim');
                }
            });
        }
        
        function postSelectedPayments() {
            const formData = $('#eraReviewForm').serialize();
            
            if (!confirm('Are you sure you want to post the selected payments?')) {
                return;
            }
            
            $.ajax({
                url: 'process_era_payments.php',
                method: 'POST',
                data: formData + '&action=selected',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Successfully posted ' + response.posted_count + ' payments');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error processing payments');
                }
            });
        }
        
        function postAllPayments() {
            if (!confirm('Are you sure you want to post all matched payments?')) {
                return;
            }
            
            $.ajax({
                url: 'process_era_payments.php',
                method: 'POST',
                data: {
                    era_import_id: <?php echo $era_import_id; ?>,
                    action: 'all_matched'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Successfully posted ' + response.posted_count + ' payments');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error processing payments');
                }
            });
        }
    </script>
</body>
</html>