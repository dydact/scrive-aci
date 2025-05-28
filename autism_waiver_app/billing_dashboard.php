<?php

/**
 * Billing Dashboard - Scrive AI-Powered ERM
 * 
 * @package   Scrive
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
 * @license   MIT License
 */

// Include API integration
require_once 'api.php';

// Simple authentication check
if (!isset($_SESSION['authUser']) || empty($_SESSION['authUser'])) {
    header('Location: ../interface/login/login.php?site=default');
    exit;
}

$api = new OpenEMRAPI();
$error = null;
$success = null;
$currentUser = null;
$billingStats = [];
$recentEntries = [];
$monthlyTotals = [];
$employeeRates = [];

// Get filter parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$endDate = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month
$employeeFilter = $_GET['employee_id'] ?? '';
$statusFilter = $_GET['status'] ?? '';

try {
    $currentUser = $api->getCurrentUser();
    $employee = $api->getCurrentEmployee();
    
    // Check if billing tables exist
    $dbSetup = $api->checkDatabaseSetup();
    if (!$dbSetup['tables_exist']) {
        throw new Exception("Billing system not yet set up. Please run the comprehensive database setup first.");
    }
    
    // Get billing statistics
    $billingStats = getBillingStatistics($startDate, $endDate, $employeeFilter);
    
    // Get recent billing entries
    $recentEntries = getRecentBillingEntries($startDate, $endDate, $employeeFilter, $statusFilter);
    
    // Get monthly totals for chart
    $monthlyTotals = getMonthlyBillingTotals();
    
    // Get employee rates for rate management
    $employeeRates = getEmployeeRates();
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'approve_entry') {
            $entryId = $_POST['entry_id'];
            $sql = "UPDATE autism_billing_entries SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE entry_id = ?";
            sqlStatement($sql, [$_SESSION['authUserID'], $entryId]);
            $success = "Billing entry approved successfully!";
            
        } elseif ($action === 'update_rate') {
            $rateId = $_POST['rate_id'];
            $newRate = $_POST['new_rate'];
            $sql = "UPDATE autism_billing_rates SET hourly_rate = ?, updated_at = NOW() WHERE rate_id = ?";
            sqlStatement($sql, [$newRate, $rateId]);
            $success = "Rate updated successfully!";
            
        } elseif ($action === 'generate_invoice') {
            // Placeholder for invoice generation
            $success = "Invoice generation will be implemented in the next phase!";
        }
        
        // Refresh data
        $billingStats = getBillingStatistics($startDate, $endDate, $employeeFilter);
        $recentEntries = getRecentBillingEntries($startDate, $endDate, $employeeFilter, $statusFilter);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

function getBillingStatistics($startDate, $endDate, $employeeFilter = null) {
    $whereConditions = ["be.billing_date BETWEEN ? AND ?"];
    $params = [$startDate, $endDate];
    
    if ($employeeFilter) {
        $whereConditions[] = "be.employee_id = ?";
        $params[] = $employeeFilter;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "SELECT 
                COUNT(*) as total_entries,
                SUM(be.total_minutes) as total_minutes,
                SUM(be.billable_minutes) as billable_minutes,
                SUM(be.total_amount) as total_amount,
                AVG(be.hourly_rate) as avg_rate,
                COUNT(DISTINCT be.client_id) as unique_clients,
                COUNT(DISTINCT be.employee_id) as unique_employees,
                SUM(CASE WHEN be.status = 'pending' THEN be.total_amount ELSE 0 END) as pending_amount,
                SUM(CASE WHEN be.status = 'approved' THEN be.total_amount ELSE 0 END) as approved_amount,
                SUM(CASE WHEN be.status = 'billed' THEN be.total_amount ELSE 0 END) as billed_amount,
                SUM(CASE WHEN be.status = 'paid' THEN be.total_amount ELSE 0 END) as paid_amount
            FROM autism_billing_entries be
            WHERE $whereClause";
    
    $result = sqlQuery($sql, $params);
    return $result ?: [];
}

function getRecentBillingEntries($startDate, $endDate, $employeeFilter = null, $statusFilter = null) {
    $whereConditions = ["be.billing_date BETWEEN ? AND ?"];
    $params = [$startDate, $endDate];
    
    if ($employeeFilter) {
        $whereConditions[] = "be.employee_id = ?";
        $params[] = $employeeFilter;
    }
    
    if ($statusFilter) {
        $whereConditions[] = "be.status = ?";
        $params[] = $statusFilter;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "SELECT 
                be.*,
                CONCAT(pd.fname, ' ', pd.lname) as client_name,
                CONCAT(ae.first_name, ' ', ae.last_name) as employee_name,
                st.name as service_type_name,
                st.abbreviation as service_abbr
            FROM autism_billing_entries be
            LEFT JOIN patient_data pd ON be.client_id = pd.id
            LEFT JOIN autism_employees ae ON be.employee_id = ae.employee_id
            LEFT JOIN autism_service_types st ON be.service_type_id = st.service_type_id
            WHERE $whereClause
            ORDER BY be.billing_date DESC, be.created_at DESC
            LIMIT 50";
    
    $result = sqlStatement($sql, $params);
    $entries = [];
    while ($row = sqlFetchArray($result)) {
        $entries[] = $row;
    }
    return $entries;
}

function getMonthlyBillingTotals() {
    $sql = "SELECT 
                DATE_FORMAT(be.billing_date, '%Y-%m') as month,
                SUM(be.total_amount) as total_amount,
                COUNT(*) as entry_count
            FROM autism_billing_entries be
            WHERE be.billing_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(be.billing_date, '%Y-%m')
            ORDER BY month";
    
    $result = sqlStatement($sql);
    $totals = [];
    while ($row = sqlFetchArray($result)) {
        $totals[] = $row;
    }
    return $totals;
}

function getEmployeeRates() {
    $sql = "SELECT 
                br.*,
                st.name as service_name,
                st.abbreviation as service_abbr,
                CONCAT(ae.first_name, ' ', ae.last_name) as employee_name
            FROM autism_billing_rates br
            LEFT JOIN autism_service_types st ON br.service_type_id = st.service_type_id
            LEFT JOIN autism_employees ae ON br.employee_role = ae.role
            WHERE br.is_active = 1 AND (br.end_date IS NULL OR br.end_date >= CURDATE())
            ORDER BY st.name, br.employee_role, br.rate_type";
    
    $result = sqlStatement($sql);
    $rates = [];
    while ($row = sqlFetchArray($result)) {
        $rates[] = $row;
    }
    return $rates;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Dashboard - Scrive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .navbar-brand {
            font-weight: 600;
        }
        .billing-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.7;
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-brain me-2"></i>
                Scrive
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>
                    <?php echo htmlspecialchars($currentUser['fname'] . ' ' . $currentUser['lname'] ?? 'User'); ?>
                </span>
                <a class="btn btn-outline-light btn-sm" href="index.php">
                    <i class="fas fa-home me-1"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="billing-header py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="mb-1">
                        <i class="fas fa-dollar-sign me-2"></i>
                        Billing & Time Tracking Dashboard
                    </h2>
                    <p class="mb-0">Comprehensive billing management and financial overview</p>
                </div>
                <div class="col-auto">
                    <button class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="fas fa-file-export me-2"></i>
                        Export Data
                    </button>
                    <button class="btn btn-light" onclick="generateInvoice()">
                        <i class="fas fa-file-invoice me-2"></i>
                        Generate Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-4">
        <!-- Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filter Controls -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" 
                               value="<?php echo htmlspecialchars($startDate); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" 
                               value="<?php echo htmlspecialchars($endDate); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status Filter</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="billed" <?php echo $statusFilter === 'billed' ? 'selected' : ''; ?>>Billed</option>
                            <option value="paid" <?php echo $statusFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <?php if (!empty($billingStats)): ?>
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="stat-value">$<?php echo number_format($billingStats['total_amount'] ?? 0, 2); ?></div>
                                <div>Total Revenue</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="stat-value"><?php echo number_format(($billingStats['billable_minutes'] ?? 0) / 60, 1); ?>h</div>
                                <div>Billable Hours</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="stat-value"><?php echo $billingStats['unique_clients'] ?? 0; ?></div>
                                <div>Active Clients</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card stat-card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="stat-value">$<?php echo number_format($billingStats['pending_amount'] ?? 0, 2); ?></div>
                                <div>Pending</div>
                            </div>
                            <div class="stat-icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Recent Billing Entries -->
            <div class="col-lg-8 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Recent Billing Entries
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentEntries)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-file-invoice" style="font-size: 3rem;"></i>
                                <p class="mt-3">No billing entries found for the selected period</p>
                                <a href="clients.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>
                                    Create Session to Generate Billing
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Client</th>
                                            <th>Employee</th>
                                            <th>Service</th>
                                            <th>Hours</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentEntries as $entry): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($entry['billing_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($entry['client_name']); ?></td>
                                                <td><?php echo htmlspecialchars($entry['employee_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($entry['service_abbr']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($entry['billable_minutes'] / 60, 2); ?>h</td>
                                                <td>$<?php echo number_format($entry['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge status-badge bg-<?php 
                                                        echo match($entry['status']) {
                                                            'draft' => 'secondary',
                                                            'pending' => 'warning',
                                                            'approved' => 'info',
                                                            'billed' => 'primary',
                                                            'paid' => 'success',
                                                            'disputed' => 'danger',
                                                            default => 'secondary'
                                                        }; ?>">
                                                        <?php echo ucfirst($entry['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <?php if ($entry['status'] === 'pending'): ?>
                                                            <button class="btn btn-outline-success" onclick="approveEntry(<?php echo $entry['entry_id']; ?>)">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="btn btn-outline-primary" onclick="viewEntry(<?php echo $entry['entry_id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Charts -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>
                            Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" onclick="generateReport()">
                                <i class="fas fa-chart-bar me-2"></i>
                                Generate Report
                            </button>
                            <button class="btn btn-success" onclick="approveAll()">
                                <i class="fas fa-check-double me-2"></i>
                                Approve All Pending
                            </button>
                            <button class="btn btn-info" onclick="exportToCsv()">
                                <i class="fas fa-file-csv me-2"></i>
                                Export to CSV
                            </button>
                            <button class="btn btn-warning" onclick="manageBillingRates()">
                                <i class="fas fa-dollar-sign me-2"></i>
                                Manage Rates
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <?php if (!empty($monthlyTotals)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-area me-2"></i>
                            Monthly Revenue Trend
                        </h6>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="200"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Billing Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="exportForm">
                        <div class="mb-3">
                            <label class="form-label">Export Format</label>
                            <select class="form-select" name="format">
                                <option value="csv">CSV (Excel Compatible)</option>
                                <option value="pdf">PDF Report</option>
                                <option value="quickbooks">QuickBooks Format</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="date" class="form-control" name="export_start" value="<?php echo $startDate; ?>">
                                </div>
                                <div class="col-6">
                                    <input type="date" class="form-control" name="export_end" value="<?php echo $endDate; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include_details" checked>
                            <label class="form-check-label">Include session details</label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="performExport()">
                        <i class="fas fa-download me-2"></i>
                        Export Data
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart
        <?php if (!empty($monthlyTotals)): ?>
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php echo "'" . implode("','", array_column($monthlyTotals, 'month')) . "'"; ?>],
                datasets: [{
                    label: 'Monthly Revenue',
                    data: [<?php echo implode(',', array_column($monthlyTotals, 'total_amount')); ?>],
                    borderColor: 'rgb(102, 126, 234)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        function approveEntry(entryId) {
            if (confirm('Are you sure you want to approve this billing entry?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="approve_entry">
                    <input type="hidden" name="entry_id" value="${entryId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function viewEntry(entryId) {
            alert('Entry details view coming soon! This will show comprehensive billing entry details.');
        }

        function generateInvoice() {
            alert('Invoice generation coming soon! This will create professional invoices for Medicaid billing.');
        }

        function generateReport() {
            alert('Advanced reporting coming soon! This will generate comprehensive financial reports.');
        }

        function approveAll() {
            if (confirm('Are you sure you want to approve all pending billing entries?')) {
                alert('Bulk approval feature coming soon!');
            }
        }

        function exportToCsv() {
            alert('CSV export feature coming soon! This will export billing data for external analysis.');
        }

        function manageBillingRates() {
            alert('Rate management interface coming soon! This will allow you to configure service rates by employee role.');
        }

        function performExport() {
            alert('Export functionality coming soon! This will support multiple formats including QuickBooks integration.');
        }
    </script>
</body>
</html> 