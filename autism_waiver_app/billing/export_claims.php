<?php
session_start();
require_once '../auth_helper.php';
require_once '../billing_integration.php';

checkAuthorization(['admin', 'billing_specialist']);

$conn = getConnection();

// Get parameters
$format = $_GET['format'] ?? 'csv';
$status = $_GET['status'] ?? '';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Build query
$query = "SELECT bc.*, c.name as client_name, c.medicaid_id, c.date_of_birth,
          c.address, c.city, c.state, c.zip,
          s.name as staff_name, s.npi, s.employee_id
          FROM billing_claims bc
          INNER JOIN clients c ON bc.client_id = c.id
          INNER JOIN staff s ON bc.staff_id = s.id
          WHERE bc.created_at BETWEEN ? AND ?";

$params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
$types = "ss";

if ($status) {
    $query .= " AND bc.status = ?";
    $params[] = $status;
    $types .= "s";
}

$query .= " ORDER BY bc.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($format == 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="claims_export_' . date('Ymd_His') . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write header row
    $headers = [
        'Claim Number',
        'Client Name',
        'Medicaid ID',
        'Service Date',
        'Service Code',
        'Units',
        'Rate',
        'Total Amount',
        'Authorization Number',
        'Provider Name',
        'Provider NPI',
        'Status',
        'Submission Date',
        'Payment Date',
        'Payment Amount',
        'Denial Reason',
        'Created Date'
    ];
    fputcsv($output, $headers);
    
    // Write data rows
    while ($row = $result->fetch_assoc()) {
        $data = [
            $row['claim_number'],
            $row['client_name'],
            $row['medicaid_id'],
            $row['service_date'],
            $row['service_code'],
            $row['units'],
            $row['rate'],
            $row['total_amount'],
            $row['authorization_number'] ?? '',
            $row['staff_name'],
            $row['npi'],
            $row['status'],
            $row['submission_date'] ?? '',
            $row['payment_date'] ?? '',
            $row['payment_amount'] ?? '',
            $row['denial_reason'] ?? '',
            $row['created_at']
        ];
        fputcsv($output, $data);
    }
    
    fclose($output);
    
} elseif ($format == 'excel') {
    // For Excel export, we would use a library like PhpSpreadsheet
    // For now, redirect to CSV
    header('Location: export_claims.php?format=csv&' . http_build_query($_GET));
    
} elseif ($format == '837') {
    // Export as 837 file for clearinghouse submission
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="837P_' . date('Ymd_His') . '.txt"');
    
    $edi_content = '';
    
    // Build 837 batch file
    // ISA header
    $isa = [
        'ISA', '00', '          ', '00', '          ',
        'ZZ', 'YOURORGID      ', 'ZZ', 'MDMEDICAID     ',
        date('ymd'), date('Hi'), '^', '00501',
        str_pad(time(), 9, '0', STR_PAD_LEFT), '0', 'P', ':'
    ];
    $edi_content .= implode('*', $isa) . '~' . "\n";
    
    // Add claims
    $claim_count = 0;
    while ($claim = $result->fetch_assoc()) {
        if ($claim['status'] == 'pending' && $claim['validated']) {
            // Add claim segments (simplified)
            $claim_count++;
        }
    }
    
    // IEA trailer
    $edi_content .= 'IEA*1*' . str_pad(time(), 9, '0', STR_PAD_LEFT) . '~' . "\n";
    
    echo $edi_content;
}

exit;
?>