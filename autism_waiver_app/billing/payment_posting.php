<?php
session_start();
require_once '../simple_auth_helper.php';
require_once '../config_sqlite.php';

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_type'])) {
    header("Location: ../simple_login.php");
    exit();
}

// Check user permissions - only admin and billing staff can access
if (!in_array($_SESSION['user_type'], ['admin', 'billing'])) {
    $_SESSION['error'] = "Access denied. You don't have permission to access payment posting.";
    header("Location: ../simple_dashboard.php");
    exit();
}

// Get filter parameters
$filter_date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$filter_date_to = $_GET['date_to'] ?? date('Y-m-d');
$filter_payment_type = $_GET['payment_type'] ?? '';
$filter_status = $_GET['status'] ?? '';
$search_term = $_GET['search'] ?? '';

// Fetch pending claims for payment application
$pending_claims_query = "
    SELECT c.*, p.first_name, p.last_name, p.medicaid_id,
           c.billed_amount - COALESCE(paid.total_paid, 0) as balance_due
    FROM claims c
    JOIN patients p ON c.patient_id = p.id
    LEFT JOIN (
        SELECT claim_id, SUM(payment_amount) as total_paid
        FROM payment_postings
        WHERE status = 'posted'
        GROUP BY claim_id
    ) paid ON c.id = paid.claim_id
    WHERE c.status IN ('submitted', 'partially_paid')
    AND c.billed_amount > COALESCE(paid.total_paid, 0)
    ORDER BY c.service_date DESC
";
$pending_claims = $db->query($pending_claims_query)->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent payments
$payments_query = "
    SELECT pp.*, c.claim_number, p.first_name, p.last_name,
           u.username as posted_by_username
    FROM payment_postings pp
    LEFT JOIN claims c ON pp.claim_id = c.id
    LEFT JOIN patients p ON pp.patient_id = p.id
    LEFT JOIN users u ON pp.posted_by = u.id
    WHERE 1=1
";

$params = [];
if ($filter_date_from) {
    $payments_query .= " AND DATE(pp.payment_date) >= ?";
    $params[] = $filter_date_from;
}
if ($filter_date_to) {
    $payments_query .= " AND DATE(pp.payment_date) <= ?";
    $params[] = $filter_date_to;
}
if ($filter_payment_type) {
    $payments_query .= " AND pp.payment_type = ?";
    $params[] = $filter_payment_type;
}
if ($filter_status) {
    $payments_query .= " AND pp.status = ?";
    $params[] = $filter_status;
}
if ($search_term) {
    $payments_query .= " AND (pp.check_number LIKE ? OR pp.era_number LIKE ? OR c.claim_number LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
}

$payments_query .= " ORDER BY pp.created_at DESC LIMIT 100";

$stmt = $db->prepare($payments_query);
$stmt->execute($params);
$recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unapplied payments
$unapplied_query = "
    SELECT pp.*, u.username as posted_by_username
    FROM payment_postings pp
    LEFT JOIN users u ON pp.posted_by = u.id
    WHERE pp.claim_id IS NULL
    AND pp.status = 'posted'
    ORDER BY pp.payment_date DESC
";
$unapplied_payments = $db->query($unapplied_query)->fetchAll(PDO::FETCH_ASSOC);

// Fetch batch deposits
$deposits_query = "
    SELECT bd.*, COUNT(pp.id) as payment_count, 
           SUM(pp.payment_amount) as total_amount,
           u.username as created_by_username
    FROM batch_deposits bd
    LEFT JOIN payment_postings pp ON bd.id = pp.batch_deposit_id
    LEFT JOIN users u ON bd.created_by = u.id
    WHERE bd.status = 'open'
    GROUP BY bd.id
    ORDER BY bd.deposit_date DESC
";
$open_deposits = $db->query($deposits_query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Posting - Autism Waiver System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <style>
        .payment-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .payment-type-btn {
            min-width: 150px;
            margin: 5px;
        }
        .claim-row:hover {
            background-color: #e9ecef;
            cursor: pointer;
        }
        .selected-claim {
            background-color: #d1ecf1 !important;
        }
        .payment-amount {
            font-size: 1.2em;
            font-weight: bold;
        }
        .adjustment-section {
            border-left: 3px solid #dc3545;
            padding-left: 15px;
        }
        .unapplied-payment {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
        }
        .batch-deposit-card {
            border: 2px solid #198754;
            background-color: #f8f9fa;
        }
        .void-payment {
            background-color: #f8d7da;
            text-decoration: line-through;
        }
        .era-section {
            background-color: #e7f3ff;
            border: 1px solid #0066cc;
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
                <h2><i class="bi bi-cash-stack"></i> Payment Posting</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../simple_dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="../billing_dashboard.php">Billing</a></li>
                        <li class="breadcrumb-item active">Payment Posting</li>
                    </ol>
                </nav>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Today's Payments</h5>
                        <p class="card-text display-6">$<?php 
                            $today_total = $db->query("
                                SELECT COALESCE(SUM(payment_amount), 0) as total 
                                FROM payment_postings 
                                WHERE DATE(payment_date) = DATE('now') 
                                AND status = 'posted'
                            ")->fetchColumn();
                            echo number_format($today_total, 2);
                        ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Unapplied Payments</h5>
                        <p class="card-text display-6"><?php echo count($unapplied_payments); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Open Deposits</h5>
                        <p class="card-text display-6"><?php echo count($open_deposits); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Month to Date</h5>
                        <p class="card-text display-6">$<?php 
                            $mtd_total = $db->query("
                                SELECT COALESCE(SUM(payment_amount), 0) as total 
                                FROM payment_postings 
                                WHERE strftime('%Y-%m', payment_date) = strftime('%Y-%m', 'now') 
                                AND status = 'posted'
                            ")->fetchColumn();
                            echo number_format($mtd_total, 2);
                        ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Type Selection -->
        <div class="payment-section">
            <h4><i class="bi bi-plus-circle"></i> New Payment</h4>
            <div class="text-center">
                <button class="btn btn-primary payment-type-btn" onclick="showPaymentForm('insurance')">
                    <i class="bi bi-building"></i> Insurance Payment
                </button>
                <button class="btn btn-success payment-type-btn" onclick="showPaymentForm('patient')">
                    <i class="bi bi-person"></i> Patient Payment
                </button>
                <button class="btn btn-info payment-type-btn" onclick="showERAForm()">
                    <i class="bi bi-file-earmark-text"></i> ERA/835 Import
                </button>
                <button class="btn btn-warning payment-type-btn" onclick="showAdjustmentForm()">
                    <i class="bi bi-pencil-square"></i> Adjustment/Write-off
                </button>
            </div>
        </div>

        <!-- Payment Form (Initially Hidden) -->
        <div id="paymentForm" class="payment-section" style="display: none;">
            <h4 id="paymentFormTitle">Post Payment</h4>
            <form id="postPaymentForm">
                <input type="hidden" id="payment_type" name="payment_type">
                <input type="hidden" id="selected_claim_id" name="claim_id">
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Payment Date</label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Payment Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="payment_amount" name="payment_amount" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row" id="insuranceFields" style="display: none;">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Check Number</label>
                            <input type="text" class="form-control" id="check_number" name="check_number">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Payer Name</label>
                            <input type="text" class="form-control" id="payer_name" name="payer_name">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Batch Deposit</label>
                            <select class="form-select" id="batch_deposit_id" name="batch_deposit_id">
                                <option value="">-- Select Deposit --</option>
                                <?php foreach ($open_deposits as $deposit): ?>
                                    <option value="<?php echo $deposit['id']; ?>">
                                        <?php echo htmlspecialchars($deposit['deposit_number']); ?> - 
                                        <?php echo date('m/d/Y', strtotime($deposit['deposit_date'])); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="new">+ Create New Deposit</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row" id="patientFields" style="display: none;">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method">
                                <option value="cash">Cash</option>
                                <option value="check">Check</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" id="reference_number" name="reference_number">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" id="payment_notes" name="notes" rows="2"></textarea>
                </div>

                <!-- Claim Selection -->
                <div class="mb-3">
                    <label class="form-label">Apply to Claim</label>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Select</th>
                                    <th>Claim #</th>
                                    <th>Patient</th>
                                    <th>Service Date</th>
                                    <th>Billed</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody id="claimsList">
                                <?php foreach ($pending_claims as $claim): ?>
                                <tr class="claim-row" data-claim-id="<?php echo $claim['id']; ?>" 
                                    data-balance="<?php echo $claim['balance_due']; ?>">
                                    <td>
                                        <input type="radio" name="claim_selection" 
                                               value="<?php echo $claim['id']; ?>"
                                               onchange="selectClaim(<?php echo $claim['id']; ?>, <?php echo $claim['balance_due']; ?>)">
                                    </td>
                                    <td><?php echo htmlspecialchars($claim['claim_number']); ?></td>
                                    <td><?php echo htmlspecialchars($claim['first_name'] . ' ' . $claim['last_name']); ?></td>
                                    <td><?php echo date('m/d/Y', strtotime($claim['service_date'])); ?></td>
                                    <td>$<?php echo number_format($claim['billed_amount'], 2); ?></td>
                                    <td>$<?php echo number_format($claim['balance_due'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="unapplied_payment" name="unapplied_payment">
                        <label class="form-check-label" for="unapplied_payment">
                            Post as unapplied payment (no claim selected)
                        </label>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" onclick="hidePaymentForm()">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Post Payment
                    </button>
                </div>
            </form>
        </div>

        <!-- ERA Import Form -->
        <div id="eraForm" class="payment-section era-section" style="display: none;">
            <h4><i class="bi bi-file-earmark-text"></i> ERA/835 Import</h4>
            <form id="eraImportForm" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Select ERA File</label>
                    <input type="file" class="form-control" id="era_file" name="era_file" accept=".835,.txt" required>
                    <div class="form-text">Upload 835 remittance file for automatic payment posting</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deposit Date</label>
                    <input type="date" class="form-control" id="era_deposit_date" name="deposit_date" 
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" onclick="hideERAForm()">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Import & Review
                    </button>
                </div>
            </form>
        </div>

        <!-- Adjustment Form -->
        <div id="adjustmentForm" class="payment-section adjustment-section" style="display: none;">
            <h4><i class="bi bi-pencil-square"></i> Post Adjustment/Write-off</h4>
            <form id="postAdjustmentForm">
                <input type="hidden" name="adjustment" value="1">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Adjustment Type</label>
                            <select class="form-select" id="adjustment_type" name="adjustment_type" required>
                                <option value="">-- Select Type --</option>
                                <option value="contractual">Contractual Adjustment</option>
                                <option value="write_off">Write-off</option>
                                <option value="bad_debt">Bad Debt</option>
                                <option value="charity">Charity Care</option>
                                <option value="other">Other Adjustment</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Adjustment Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="adjustment_amount" name="adjustment_amount" 
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <textarea class="form-control" id="adjustment_reason" name="reason" rows="2" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select Claim</label>
                    <select class="form-select" id="adjustment_claim_id" name="claim_id" required>
                        <option value="">-- Select Claim --</option>
                        <?php foreach ($pending_claims as $claim): ?>
                            <option value="<?php echo $claim['id']; ?>">
                                <?php echo htmlspecialchars($claim['claim_number']); ?> - 
                                <?php echo htmlspecialchars($claim['first_name'] . ' ' . $claim['last_name']); ?> - 
                                Balance: $<?php echo number_format($claim['balance_due'], 2); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" onclick="hideAdjustmentForm()">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle"></i> Post Adjustment
                    </button>
                </div>
            </form>
        </div>

        <!-- Unapplied Payments -->
        <?php if (!empty($unapplied_payments)): ?>
        <div class="payment-section unapplied-payment">
            <h4><i class="bi bi-exclamation-triangle"></i> Unapplied Payments</h4>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Check/Ref#</th>
                            <th>Posted By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($unapplied_payments as $payment): ?>
                        <tr>
                            <td><?php echo date('m/d/Y', strtotime($payment['payment_date'])); ?></td>
                            <td class="payment-amount">$<?php echo number_format($payment['payment_amount'], 2); ?></td>
                            <td><?php echo ucfirst($payment['payment_type']); ?></td>
                            <td><?php echo htmlspecialchars($payment['check_number'] ?: $payment['reference_number'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($payment['posted_by_username']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="applyPayment(<?php echo $payment['id']; ?>)">
                                    <i class="bi bi-link"></i> Apply
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Payments -->
        <div class="payment-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="bi bi-clock-history"></i> Recent Payments</h4>
                <button class="btn btn-sm btn-outline-primary" onclick="toggleFilters()">
                    <i class="bi bi-funnel"></i> Filters
                </button>
            </div>

            <!-- Filters (Initially Hidden) -->
            <div id="filterSection" style="display: none;" class="mb-3">
                <form method="GET" class="row g-2">
                    <div class="col-md-2">
                        <input type="date" class="form-control form-control-sm" name="date_from" 
                               value="<?php echo $filter_date_from; ?>" placeholder="From Date">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control form-control-sm" name="date_to" 
                               value="<?php echo $filter_date_to; ?>" placeholder="To Date">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="payment_type">
                            <option value="">All Types</option>
                            <option value="insurance" <?php echo $filter_payment_type == 'insurance' ? 'selected' : ''; ?>>Insurance</option>
                            <option value="patient" <?php echo $filter_payment_type == 'patient' ? 'selected' : ''; ?>>Patient</option>
                            <option value="adjustment" <?php echo $filter_payment_type == 'adjustment' ? 'selected' : ''; ?>>Adjustment</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select form-select-sm" name="status">
                            <option value="">All Status</option>
                            <option value="posted" <?php echo $filter_status == 'posted' ? 'selected' : ''; ?>>Posted</option>
                            <option value="voided" <?php echo $filter_status == 'voided' ? 'selected' : ''; ?>>Voided</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control form-control-sm" name="search" 
                               value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search...">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="payment_posting.php" class="btn btn-sm btn-secondary">Clear</a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover" id="paymentsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Claim #</th>
                            <th>Patient</th>
                            <th>Check/Ref#</th>
                            <th>Status</th>
                            <th>Posted By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_payments as $payment): ?>
                        <tr class="<?php echo $payment['status'] == 'voided' ? 'void-payment' : ''; ?>">
                            <td><?php echo $payment['id']; ?></td>
                            <td><?php echo date('m/d/Y', strtotime($payment['payment_date'])); ?></td>
                            <td>
                                <?php
                                $type_icon = '';
                                switch($payment['payment_type']) {
                                    case 'insurance': $type_icon = '<i class="bi bi-building"></i>'; break;
                                    case 'patient': $type_icon = '<i class="bi bi-person"></i>'; break;
                                    case 'adjustment': $type_icon = '<i class="bi bi-pencil-square"></i>'; break;
                                }
                                echo $type_icon . ' ' . ucfirst($payment['payment_type']);
                                ?>
                            </td>
                            <td class="payment-amount">
                                $<?php echo number_format($payment['payment_amount'], 2); ?>
                            </td>
                            <td><?php echo htmlspecialchars($payment['claim_number'] ?: 'Unapplied'); ?></td>
                            <td><?php echo $payment['first_name'] ? htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($payment['check_number'] ?: $payment['reference_number'] ?: '-'); ?></td>
                            <td>
                                <?php if ($payment['status'] == 'posted'): ?>
                                    <span class="badge bg-success">Posted</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Voided</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($payment['posted_by_username']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewPaymentDetails(<?php echo $payment['id']; ?>)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if ($payment['status'] == 'posted' && $_SESSION['user_type'] == 'admin'): ?>
                                    <button class="btn btn-sm btn-danger" onclick="voidPayment(<?php echo $payment['id']; ?>)">
                                        <i class="bi bi-x-octagon"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Details Modal -->
    <div class="modal fade" id="paymentDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Payment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="paymentDetailsContent">
                    <!-- Content loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- New Deposit Modal -->
    <div class="modal fade" id="newDepositModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Batch Deposit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="newDepositForm">
                        <div class="mb-3">
                            <label class="form-label">Deposit Number</label>
                            <input type="text" class="form-control" name="deposit_number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deposit Date</label>
                            <input type="date" class="form-control" name="deposit_date" 
                                   value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bank Account</label>
                            <input type="text" class="form-control" name="bank_account" placeholder="e.g., Chase ****1234">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="createDeposit()">Create Deposit</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#paymentsTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 25
            });

            // Handle batch deposit selection
            $('#batch_deposit_id').change(function() {
                if ($(this).val() === 'new') {
                    $('#newDepositModal').modal('show');
                }
            });

            // Handle unapplied payment checkbox
            $('#unapplied_payment').change(function() {
                if ($(this).is(':checked')) {
                    $('input[name="claim_selection"]').prop('checked', false);
                    $('input[name="claim_selection"]').prop('disabled', true);
                    $('#selected_claim_id').val('');
                } else {
                    $('input[name="claim_selection"]').prop('disabled', false);
                }
            });
        });

        function showPaymentForm(type) {
            $('#paymentForm').show();
            $('#eraForm').hide();
            $('#adjustmentForm').hide();
            $('#payment_type').val(type);
            
            if (type === 'insurance') {
                $('#paymentFormTitle').text('Post Insurance Payment');
                $('#insuranceFields').show();
                $('#patientFields').hide();
            } else {
                $('#paymentFormTitle').text('Post Patient Payment');
                $('#insuranceFields').hide();
                $('#patientFields').show();
            }
        }

        function hidePaymentForm() {
            $('#paymentForm').hide();
            $('#postPaymentForm')[0].reset();
        }

        function showERAForm() {
            $('#eraForm').show();
            $('#paymentForm').hide();
            $('#adjustmentForm').hide();
        }

        function hideERAForm() {
            $('#eraForm').hide();
            $('#eraImportForm')[0].reset();
        }

        function showAdjustmentForm() {
            $('#adjustmentForm').show();
            $('#paymentForm').hide();
            $('#eraForm').hide();
        }

        function hideAdjustmentForm() {
            $('#adjustmentForm').hide();
            $('#postAdjustmentForm')[0].reset();
        }

        function selectClaim(claimId, balance) {
            $('#selected_claim_id').val(claimId);
            $('.claim-row').removeClass('selected-claim');
            $(`tr[data-claim-id="${claimId}"]`).addClass('selected-claim');
            
            // Suggest payment amount as balance
            if (!$('#payment_amount').val()) {
                $('#payment_amount').val(balance.toFixed(2));
            }
        }

        function toggleFilters() {
            $('#filterSection').toggle();
        }

        // Post Payment Form
        $('#postPaymentForm').submit(function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize();
            
            $.ajax({
                url: 'post_payment.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Payment posted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while posting the payment.');
                }
            });
        });

        // Post Adjustment Form
        $('#postAdjustmentForm').submit(function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize();
            
            $.ajax({
                url: 'post_payment.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Adjustment posted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while posting the adjustment.');
                }
            });
        });

        // ERA Import Form
        $('#eraImportForm').submit(function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            $.ajax({
                url: 'import_era.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('ERA file imported successfully! ' + response.payment_count + ' payments ready for review.');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while importing the ERA file.');
                }
            });
        });

        function viewPaymentDetails(paymentId) {
            $.ajax({
                url: 'payment_details.php',
                method: 'GET',
                data: { id: paymentId },
                success: function(response) {
                    $('#paymentDetailsContent').html(response);
                    $('#paymentDetailsModal').modal('show');
                },
                error: function() {
                    alert('Error loading payment details.');
                }
            });
        }

        function voidPayment(paymentId) {
            if (!confirm('Are you sure you want to void this payment? This action cannot be undone.')) {
                return;
            }
            
            const reason = prompt('Please provide a reason for voiding this payment:');
            if (!reason) {
                return;
            }
            
            $.ajax({
                url: 'void_payment.php',
                method: 'POST',
                data: { 
                    payment_id: paymentId,
                    void_reason: reason
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Payment voided successfully.');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while voiding the payment.');
                }
            });
        }

        function applyPayment(paymentId) {
            // This would open a modal to select which claim to apply the payment to
            alert('Apply payment functionality would open here for payment ID: ' + paymentId);
            // TODO: Implement apply payment modal
        }

        function createDeposit() {
            const formData = $('#newDepositForm').serialize();
            
            $.ajax({
                url: 'create_deposit.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#newDepositModal').modal('hide');
                        // Add new deposit to select
                        $('#batch_deposit_id').append(
                            `<option value="${response.deposit_id}" selected>
                                ${response.deposit_number} - ${response.deposit_date}
                            </option>`
                        );
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('An error occurred while creating the deposit.');
                }
            });
        }
    </script>
</body>
</html>