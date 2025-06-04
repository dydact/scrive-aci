<?php
session_start();
require_once '../auth_helper.php';
require_once '../billing_integration.php';

checkAuthorization(['admin', 'billing_specialist']);

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Get claim statistics
$stats = [
    'pending' => 0,
    'submitted' => 0,
    'paid' => 0,
    'denied' => 0,
    'total_amount' => 0
];

$stats_query = "SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
    COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted,
    COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid,
    COUNT(CASE WHEN status = 'denied' THEN 1 END) as denied,
    SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END) as total_paid
    FROM billing_claims";
$result = $conn->query($stats_query);
if ($result && $row = $result->fetch_assoc()) {
    $stats = array_merge($stats, $row);
}

// Get recent claims
$recent_claims = [];
$claims_query = "SELECT bc.*, c.name as client_name, s.name as staff_name 
    FROM billing_claims bc
    LEFT JOIN clients c ON bc.client_id = c.id
    LEFT JOIN staff s ON bc.staff_id = s.id
    ORDER BY bc.created_at DESC
    LIMIT 20";
$result = $conn->query($claims_query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_claims[] = $row;
    }
}

// Get unbilled sessions count
$unbilled_query = "SELECT COUNT(*) as count FROM client_sessions 
    WHERE billing_status = 'unbilled' 
    AND status = 'completed'
    AND service_date <= CURDATE()";
$unbilled_count = 0;
$result = $conn->query($unbilled_query);
if ($result && $row = $result->fetch_assoc()) {
    $unbilled_count = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Management - ACI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .stat-card.pending { background: #fff3cd; border-left: 4px solid #ffc107; }
        .stat-card.submitted { background: #cfe2ff; border-left: 4px solid #0d6efd; }
        .stat-card.paid { background: #d1e7dd; border-left: 4px solid #198754; }
        .stat-card.denied { background: #f8d7da; border-left: 4px solid #dc3545; }
        
        .claim-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .claim-row:hover {
            background-color: #f8f9fa;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .claim-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .claim-status.pending { background: #ffc107; color: #000; }
        .claim-status.submitted { background: #0d6efd; color: #fff; }
        .claim-status.paid { background: #198754; color: #fff; }
        .claim-status.denied { background: #dc3545; color: #fff; }
        
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .modal-lg {
            max-width: 90%;
        }
        
        .validation-error {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 5px;
        }
        
        .validation-success {
            color: #198754;
            font-size: 0.875rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include '../simple_nav.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="bi bi-file-earmark-medical"></i> Claim Management</h2>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card pending">
                    <h5>Pending Claims</h5>
                    <h2><?php echo number_format($stats['pending']); ?></h2>
                    <small>Ready for submission</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card submitted">
                    <h5>Submitted Claims</h5>
                    <h2><?php echo number_format($stats['submitted']); ?></h2>
                    <small>Awaiting response</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card paid">
                    <h5>Paid Claims</h5>
                    <h2><?php echo number_format($stats['paid']); ?></h2>
                    <small>$<?php echo number_format($stats['total_paid'], 2); ?> total</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card denied">
                    <h5>Denied Claims</h5>
                    <h2><?php echo number_format($stats['denied']); ?></h2>
                    <small>Require attention</small>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="showGenerateClaimsModal()">
                <i class="bi bi-plus-circle"></i> Generate New Claims
                <?php if ($unbilled_count > 0): ?>
                    <span class="badge bg-warning"><?php echo $unbilled_count; ?></span>
                <?php endif; ?>
            </button>
            <button class="btn btn-success" onclick="showBatchSubmitModal()">
                <i class="bi bi-send"></i> Batch Submit
            </button>
            <button class="btn btn-info" onclick="showValidationModal()">
                <i class="bi bi-check-circle"></i> Validate Claims
            </button>
            <button class="btn btn-secondary" onclick="exportClaims()">
                <i class="bi bi-download"></i> Export Claims
            </button>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row">
                <div class="col-md-3">
                    <label>Status Filter</label>
                    <select class="form-select" id="statusFilter" onchange="filterClaims()">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="submitted">Submitted</option>
                        <option value="paid">Paid</option>
                        <option value="denied">Denied</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Date Range</label>
                    <input type="date" class="form-control" id="startDate" onchange="filterClaims()">
                </div>
                <div class="col-md-3">
                    <label>&nbsp;</label>
                    <input type="date" class="form-control" id="endDate" onchange="filterClaims()">
                </div>
                <div class="col-md-3">
                    <label>Client</label>
                    <input type="text" class="form-control" id="clientSearch" placeholder="Search client..." onkeyup="filterClaims()">
                </div>
            </div>
        </div>

        <!-- Claims Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Claims</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="claimsTable">
                        <thead>
                            <tr>
                                <th>Claim #</th>
                                <th>Client</th>
                                <th>Service Date</th>
                                <th>Provider</th>
                                <th>Service</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_claims as $claim): ?>
                            <tr class="claim-row" data-claim-id="<?php echo $claim['id']; ?>">
                                <td><?php echo htmlspecialchars($claim['claim_number']); ?></td>
                                <td><?php echo htmlspecialchars($claim['client_name']); ?></td>
                                <td><?php echo date('m/d/Y', strtotime($claim['service_date'])); ?></td>
                                <td><?php echo htmlspecialchars($claim['staff_name']); ?></td>
                                <td><?php echo htmlspecialchars($claim['service_code']); ?></td>
                                <td>$<?php echo number_format($claim['total_amount'], 2); ?></td>
                                <td>
                                    <span class="claim-status <?php echo $claim['status']; ?>">
                                        <?php echo ucfirst($claim['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" onclick="viewClaim(<?php echo $claim['id']; ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if ($claim['status'] == 'pending'): ?>
                                        <button class="btn btn-outline-success" onclick="submitClaim(<?php echo $claim['id']; ?>)">
                                            <i class="bi bi-send"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($claim['status'] == 'denied'): ?>
                                        <button class="btn btn-outline-warning" onclick="resubmitClaim(<?php echo $claim['id']; ?>)">
                                            <i class="bi bi-arrow-repeat"></i>
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

    <!-- Generate Claims Modal -->
    <div class="modal fade" id="generateClaimsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate New Claims</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong><?php echo $unbilled_count; ?> unbilled sessions</strong> found ready for claim generation.
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Date Range</label>
                            <div class="input-group">
                                <input type="date" class="form-control" id="genStartDate" value="<?php echo date('Y-m-01'); ?>">
                                <span class="input-group-text">to</span>
                                <input type="date" class="form-control" id="genEndDate" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>Client Filter</label>
                            <select class="form-select" id="genClientFilter">
                                <option value="">All Clients</option>
                                <?php
                                $clients_query = "SELECT DISTINCT c.id, c.name 
                                    FROM clients c
                                    INNER JOIN client_sessions cs ON c.id = cs.client_id
                                    WHERE cs.billing_status = 'unbilled'
                                    ORDER BY c.name";
                                $result = $conn->query($clients_query);
                                while ($row = $result->fetch_assoc()) {
                                    echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="validateAuth" checked>
                        <label class="form-check-label" for="validateAuth">
                            Validate authorizations before generating
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="checkTimely" checked>
                        <label class="form-check-label" for="checkTimely">
                            Check timely filing limits (95 days for Maryland Medicaid)
                        </label>
                    </div>
                    
                    <div id="generationProgress" style="display: none;">
                        <div class="progress mb-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                        <div id="progressText" class="text-center text-muted"></div>
                    </div>
                    
                    <div id="generationResults" style="display: none;">
                        <h6>Generation Results:</h6>
                        <div id="resultsContent"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="generateClaims()">
                        <i class="bi bi-gear"></i> Generate Claims
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Claim Details Modal -->
    <div class="modal fade" id="claimDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Claim Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="claimDetailsContent">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveclaimChanges()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Batch Submit Modal -->
    <div class="modal fade" id="batchSubmitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Batch Submit Claims</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        This will submit all validated pending claims to the clearinghouse.
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="onlyValidated" checked>
                        <label class="form-check-label" for="onlyValidated">
                            Only submit validated claims
                        </label>
                    </div>
                    
                    <div id="submitProgress" style="display: none;">
                        <div class="progress mb-2">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                        <div id="submitProgressText" class="text-center text-muted"></div>
                    </div>
                    
                    <div id="submitResults" style="display: none;">
                        <h6>Submission Results:</h6>
                        <div id="submitResultsContent"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="batchSubmitClaims()">
                        <i class="bi bi-send"></i> Submit Claims
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'validation_modal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function showGenerateClaimsModal() {
            $('#generateClaimsModal').modal('show');
        }
        
        function showBatchSubmitModal() {
            $('#batchSubmitModal').modal('show');
        }
        
        // showValidationModal is now defined in validation_modal.php
        
        function generateClaims() {
            const startDate = $('#genStartDate').val();
            const endDate = $('#genEndDate').val();
            const clientId = $('#genClientFilter').val();
            const validateAuth = $('#validateAuth').is(':checked');
            const checkTimely = $('#checkTimely').is(':checked');
            
            $('#generationProgress').show();
            $('#generationResults').hide();
            
            $.ajax({
                url: 'generate_claims.php',
                method: 'POST',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    client_id: clientId,
                    validate_auth: validateAuth,
                    check_timely: checkTimely
                },
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = evt.loaded / evt.total * 100;
                            $('.progress-bar').css('width', percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    $('#generationProgress').hide();
                    $('#generationResults').show();
                    $('#resultsContent').html(response.message);
                    
                    if (response.success) {
                        setTimeout(() => {
                            location.reload();
                        }, 3000);
                    }
                },
                error: function(xhr, status, error) {
                    $('#generationProgress').hide();
                    alert('Error generating claims: ' + error);
                }
            });
        }
        
        function viewClaim(claimId) {
            $.ajax({
                url: 'get_claim_details.php',
                method: 'GET',
                data: { id: claimId },
                success: function(response) {
                    $('#claimDetailsContent').html(response);
                    $('#claimDetailsModal').modal('show');
                }
            });
        }
        
        function submitClaim(claimId) {
            if (!confirm('Submit this claim to the clearinghouse?')) return;
            
            $.ajax({
                url: 'submit_claims.php',
                method: 'POST',
                data: { claim_ids: [claimId] },
                success: function(response) {
                    if (response.success) {
                        alert('Claim submitted successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                }
            });
        }
        
        function resubmitClaim(claimId) {
            // Implementation for resubmitting denied claims
            viewClaim(claimId);
        }
        
        function batchSubmitClaims() {
            const onlyValidated = $('#onlyValidated').is(':checked');
            
            $('#submitProgress').show();
            $('#submitResults').hide();
            
            $.ajax({
                url: 'submit_claims.php',
                method: 'POST',
                data: {
                    batch: true,
                    only_validated: onlyValidated
                },
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = evt.loaded / evt.total * 100;
                            $('#submitProgress .progress-bar').css('width', percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    $('#submitProgress').hide();
                    $('#submitResults').show();
                    $('#submitResultsContent').html(response.message);
                    
                    if (response.success) {
                        setTimeout(() => {
                            location.reload();
                        }, 3000);
                    }
                }
            });
        }
        
        function filterClaims() {
            const status = $('#statusFilter').val();
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            const clientSearch = $('#clientSearch').val().toLowerCase();
            
            $('#claimsTable tbody tr').each(function() {
                let show = true;
                const row = $(this);
                
                if (status && !row.find('.claim-status').hasClass(status)) {
                    show = false;
                }
                
                if (clientSearch && !row.find('td:eq(1)').text().toLowerCase().includes(clientSearch)) {
                    show = false;
                }
                
                // Add date filtering logic here if needed
                
                row.toggle(show);
            });
        }
        
        function exportClaims() {
            window.location.href = 'export_claims.php?format=csv';
        }
        
        function saveClaimChanges() {
            // Implementation for saving claim edits
            alert('Save functionality coming soon');
        }
    </script>
</body>
</html>