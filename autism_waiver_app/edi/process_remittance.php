<?php
/**
 * Process EDI 835 Remittance Advice
 * 
 * This script processes inbound EDI 835 remittance files from payers
 * and automatically posts payments to claims
 * 
 * @package     ScriverACI
 * @subpackage  EDI
 */

require_once dirname(dirname(__DIR__)) . '/autism_waiver_app/auth_helper.php';
require_once __DIR__ . '/EDI835Parser.php';

use ScriverACI\EDI\EDI835Parser;

// Check authentication
requireLogin();
requireRole(['admin', 'billing_manager']);

// Database connection
$db_path = dirname(__DIR__) . '/autism_waiver_test.db';
$db = new PDO('sqlite:' . $db_path);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

try {
    // Handle file upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['edi_file'])) {
        
        // Validate file upload
        if ($_FILES['edi_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed');
        }
        
        // Read file content
        $ediContent = file_get_contents($_FILES['edi_file']['tmp_name']);
        
        if (empty($ediContent)) {
            throw new Exception('Empty file uploaded');
        }
        
        // Save uploaded file
        $uploadDir = dirname(dirname(__DIR__)) . '/edi_files/inbound';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $filename = '835_' . date('Ymd_His') . '_' . basename($_FILES['edi_file']['name']);
        $filepath = $uploadDir . '/' . $filename;
        
        if (!move_uploaded_file($_FILES['edi_file']['tmp_name'], $filepath)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        // Save EDI transaction record
        $stmt = $db->prepare("
            INSERT INTO edi_transactions (
                transaction_type,
                transaction_set,
                filename,
                file_path,
                status,
                created_by,
                created_at
            ) VALUES (
                'inbound',
                '835',
                :filename,
                :filepath,
                'received',
                :user_id,
                datetime('now')
            )
        ");
        
        $stmt->execute([
            ':filename' => $filename,
            ':filepath' => $filepath,
            ':user_id' => $_SESSION['user_id']
        ]);
        
        $transactionId = $db->lastInsertId();
        
        // Initialize parser
        $parser = new EDI835Parser($db);
        
        // Parse the EDI file
        if (!$parser->parse($ediContent)) {
            $errors = $parser->getErrors();
            throw new Exception('Failed to parse EDI 835: ' . implode(', ', $errors));
        }
        
        // Validate Maryland Medicaid format
        $validation = $parser->validateMarylandMedicaidFormat();
        if (!$validation['valid']) {
            throw new Exception('Maryland Medicaid validation failed: ' . implode(', ', $validation['errors']));
        }
        
        // Process remittance (post payments)
        $processResult = $parser->processRemittance();
        
        if (!$processResult['success']) {
            throw new Exception('Failed to process remittance: ' . $processResult['error']);
        }
        
        // Generate reconciliation report
        $report = $parser->generateReconciliationReport($processResult['batch_id']);
        
        // Update EDI transaction status
        $stmt = $db->prepare("
            UPDATE edi_transactions 
            SET status = 'processed',
                batch_id = :batch_id,
                claim_count = :claim_count,
                total_amount = :total_amount,
                processed_at = datetime('now')
            WHERE id = :transaction_id
        ");
        
        $stmt->execute([
            ':transaction_id' => $transactionId,
            ':batch_id' => $processResult['batch_id'],
            ':claim_count' => $processResult['claims_processed'],
            ':total_amount' => $processResult['total_posted']
        ]);
        
        // Log successful processing
        logActivity($db, 'edi_835_processed', [
            'transaction_id' => $transactionId,
            'batch_id' => $processResult['batch_id'],
            'claims_processed' => $processResult['claims_processed'],
            'total_posted' => $processResult['total_posted']
        ]);
        
        $response = [
            'success' => true,
            'message' => sprintf(
                'Successfully processed %d claims. Total posted: $%.2f',
                $processResult['claims_processed'],
                $processResult['total_posted']
            ),
            'data' => [
                'transaction_id' => $transactionId,
                'batch_id' => $processResult['batch_id'],
                'report' => $report,
                'warnings' => $parser->getWarnings()
            ]
        ];
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        
        switch ($_GET['action']) {
            case 'list':
                // List recent EDI 835 transactions
                $stmt = $db->prepare("
                    SELECT 
                        t.*,
                        u.username as created_by_name,
                        b.check_number,
                        b.payer_name
                    FROM edi_transactions t
                    LEFT JOIN users u ON t.created_by = u.id
                    LEFT JOIN payment_batches b ON t.batch_id = b.id
                    WHERE t.transaction_set = '835'
                    ORDER BY t.created_at DESC
                    LIMIT 50
                ");
                $stmt->execute();
                $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response = [
                    'success' => true,
                    'data' => $transactions
                ];
                break;
                
            case 'report':
                // Get reconciliation report for a batch
                if (!isset($_GET['batch_id'])) {
                    throw new Exception('Batch ID required');
                }
                
                $batchId = intval($_GET['batch_id']);
                
                // Get batch details
                $stmt = $db->prepare("
                    SELECT * FROM payment_batches WHERE id = :batch_id
                ");
                $stmt->execute([':batch_id' => $batchId]);
                $batch = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$batch) {
                    throw new Exception('Batch not found');
                }
                
                // Get payments
                $stmt = $db->prepare("
                    SELECT 
                        p.*,
                        c.claim_number,
                        c.client_id,
                        cl.first_name,
                        cl.last_name
                    FROM claim_payments p
                    JOIN billing_claims c ON p.claim_id = c.id
                    JOIN clients cl ON c.client_id = cl.id
                    WHERE p.batch_id = :batch_id
                ");
                $stmt->execute([':batch_id' => $batchId]);
                $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Get adjustments
                $stmt = $db->prepare("
                    SELECT 
                        a.*,
                        p.claim_id
                    FROM payment_adjustments a
                    JOIN claim_payments p ON a.payment_id = p.id
                    WHERE p.batch_id = :batch_id
                ");
                $stmt->execute([':batch_id' => $batchId]);
                $adjustments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response = [
                    'success' => true,
                    'data' => [
                        'batch' => $batch,
                        'payments' => $payments,
                        'adjustments' => $adjustments
                    ]
                ];
                break;
                
            default:
                throw new Exception('Invalid action');
        }
        
    } else {
        throw new Exception('Invalid request');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    // Log error
    logActivity($db, 'edi_835_error', [
        'error' => $e->getMessage(),
        'file' => $_FILES['edi_file']['name'] ?? 'N/A'
    ]);
}

// Return JSON response for AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Otherwise, display the upload interface
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process EDI 835 Remittance - Scriver ACI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/navigation.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1 class="h3 mb-4">Process EDI 835 Remittance Advice</h1>
                
                <!-- Upload Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Upload EDI 835 File</h5>
                    </div>
                    <div class="card-body">
                        <form id="uploadForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="edi_file" class="form-label">Select EDI 835 File</label>
                                <input type="file" class="form-control" id="edi_file" name="edi_file" accept=".txt,.edi,.835" required>
                                <div class="form-text">Upload an EDI 835 remittance advice file from Maryland Medicaid</div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Upload and Process
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Processing Results -->
                <div id="results" class="d-none">
                    <div class="alert" id="resultAlert"></div>
                    
                    <!-- Summary Card -->
                    <div class="card mb-4" id="summaryCard" style="display: none;">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Processing Summary</h5>
                        </div>
                        <div class="card-body" id="summaryContent"></div>
                    </div>
                    
                    <!-- Claims Detail -->
                    <div class="card" id="claimsCard" style="display: none;">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Claim Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm" id="claimsTable">
                                    <thead>
                                        <tr>
                                            <th>Claim #</th>
                                            <th>Patient</th>
                                            <th>Status</th>
                                            <th>Charge</th>
                                            <th>Paid</th>
                                            <th>Adjustments</th>
                                            <th>Patient Resp.</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Transactions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent EDI 835 Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="transactionsTable">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>File</th>
                                        <th>Payer</th>
                                        <th>Check #</th>
                                        <th>Claims</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Load recent transactions
        loadTransactions();
        
        // Handle form submission
        $('#uploadForm').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            var $btn = $(this).find('button[type="submit"]');
            
            $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');
            $('#results').addClass('d-none');
            
            $.ajax({
                url: 'process_remittance.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        displayResults(response.data);
                        loadTransactions();
                        $('#uploadForm')[0].reset();
                    } else {
                        showAlert('danger', 'Error: ' + response.message);
                    }
                },
                error: function() {
                    showAlert('danger', 'An error occurred while processing the file');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="bi bi-upload"></i> Upload and Process');
                }
            });
        });
        
        function showAlert(type, message) {
            $('#results').removeClass('d-none');
            $('#resultAlert')
                .removeClass('alert-success alert-danger alert-warning')
                .addClass('alert-' + type)
                .html(message);
        }
        
        function displayResults(data) {
            if (data.warnings && data.warnings.length > 0) {
                var warningHtml = '<div class="alert alert-warning mt-3">';
                warningHtml += '<h6>Warnings:</h6><ul class="mb-0">';
                data.warnings.forEach(function(warning) {
                    warningHtml += '<li>' + warning + '</li>';
                });
                warningHtml += '</ul></div>';
                $('#resultAlert').after(warningHtml);
            }
            
            if (data.report) {
                displaySummary(data.report);
                displayClaims(data.report.claims);
            }
        }
        
        function displaySummary(report) {
            var html = '<div class="row">';
            html += '<div class="col-md-3"><strong>Total Payment:</strong> $' + 
                    report.totals.total_payment.toFixed(2) + '</div>';
            html += '<div class="col-md-3"><strong>Total Adjustments:</strong> $' + 
                    report.totals.total_adjustments.toFixed(2) + '</div>';
            html += '<div class="col-md-3"><strong>Claims Processed:</strong> ' + 
                    report.totals.claim_count + '</div>';
            html += '<div class="col-md-3"><strong>Paid/Denied:</strong> ' + 
                    report.totals.paid_claims + '/' + report.totals.denied_claims + '</div>';
            html += '</div>';
            
            if (report.payer) {
                html += '<div class="mt-3"><strong>Payer:</strong> ' + 
                        report.payer.name + ' (' + report.payer.id + ')</div>';
            }
            
            $('#summaryContent').html(html);
            $('#summaryCard').show();
        }
        
        function displayClaims(claims) {
            var tbody = $('#claimsTable tbody');
            tbody.empty();
            
            claims.forEach(function(claim) {
                var row = '<tr>';
                row += '<td>' + claim.claim_number + '</td>';
                row += '<td>' + claim.patient_name + '</td>';
                row += '<td><span class="badge bg-' + getStatusColor(claim.status) + '">' + 
                       claim.status_description + '</span></td>';
                row += '<td>$' + claim.total_charge.toFixed(2) + '</td>';
                row += '<td>$' + claim.payment_amount.toFixed(2) + '</td>';
                row += '<td>' + claim.adjustments.length + '</td>';
                row += '<td>$' + claim.patient_responsibility.toFixed(2) + '</td>';
                row += '</tr>';
                
                tbody.append(row);
            });
            
            $('#claimsCard').show();
        }
        
        function getStatusColor(status) {
            switch(status) {
                case '1':
                case '2':
                case '3':
                    return 'success';
                case '4':
                    return 'danger';
                case '22':
                    return 'warning';
                default:
                    return 'secondary';
            }
        }
        
        function loadTransactions() {
            $.get('process_remittance.php?action=list', function(response) {
                if (response.success) {
                    var tbody = $('#transactionsTable tbody');
                    tbody.empty();
                    
                    response.data.forEach(function(trans) {
                        var row = '<tr>';
                        row += '<td>' + new Date(trans.created_at).toLocaleString() + '</td>';
                        row += '<td>' + trans.filename + '</td>';
                        row += '<td>' + (trans.payer_name || 'N/A') + '</td>';
                        row += '<td>' + (trans.check_number || 'N/A') + '</td>';
                        row += '<td>' + (trans.claim_count || 0) + '</td>';
                        row += '<td>$' + (trans.total_amount || 0).toFixed(2) + '</td>';
                        row += '<td><span class="badge bg-' + 
                               (trans.status === 'processed' ? 'success' : 'warning') + '">' + 
                               trans.status + '</span></td>';
                        row += '<td>';
                        if (trans.batch_id) {
                            row += '<a href="reconciliation_report.php?batch_id=' + trans.batch_id + 
                                   '" class="btn btn-sm btn-info">View Report</a>';
                        }
                        row += '</td>';
                        row += '</tr>';
                        
                        tbody.append(row);
                    });
                }
            });
        }
    });
    </script>
</body>
</html>