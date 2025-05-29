<?php
session_start();
require_once 'auth_helper.php';
require_once 'config.php';

// Check if user is logged in and has appropriate access
if (!isLoggedIn() || $_SESSION['access_level'] < 4) { // Supervisor or Admin only
    header('Location: login.php');
    exit;
}

// Database connection
$database = getenv('MARIADB_DATABASE') ?: 'iris';
$username = getenv('MARIADB_USER') ?: 'iris_user';
$password = getenv('MARIADB_PASSWORD') ?: '';
$host = getenv('MARIADB_HOST') ?: 'localhost';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get date range from request or default to current pay period
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('last sunday - 6 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('last saturday'));

// Get payroll data combining time clock and billing entries
$payrollData = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            e.id as employee_id,
            e.first_name,
            e.last_name,
            e.hourly_rate,
            -- Time Clock Data
            COALESCE(tc.total_clock_hours, 0) as clock_hours,
            COALESCE(tc.billable_clock_hours, 0) as billable_clock_hours,
            COALESCE(tc.non_billable_clock_hours, 0) as non_billable_clock_hours,
            -- Billing Entry Data
            COALESCE(be.session_hours, 0) as session_hours,
            COALESCE(be.billed_amount, 0) as billed_amount,
            COALESCE(be.approved_amount, 0) as approved_amount,
            -- Combined Totals
            GREATEST(COALESCE(tc.total_clock_hours, 0), COALESCE(be.session_hours, 0)) as total_hours,
            -- Calculate overtime (over 40 hours/week)
            CASE 
                WHEN GREATEST(COALESCE(tc.total_clock_hours, 0), COALESCE(be.session_hours, 0)) > 40 
                THEN GREATEST(COALESCE(tc.total_clock_hours, 0), COALESCE(be.session_hours, 0)) - 40
                ELSE 0
            END as overtime_hours
        FROM autism_staff_members e
        LEFT JOIN (
            -- Aggregate time clock data
            SELECT 
                employee_id,
                SUM(total_hours) as total_clock_hours,
                SUM(CASE WHEN is_billable = 1 THEN total_hours ELSE 0 END) as billable_clock_hours,
                SUM(CASE WHEN is_billable = 0 THEN total_hours ELSE 0 END) as non_billable_clock_hours
            FROM autism_time_clock
            WHERE clock_in BETWEEN :start1 AND DATE_ADD(:end1, INTERVAL 1 DAY)
            AND clock_out IS NOT NULL
            GROUP BY employee_id
        ) tc ON e.id = tc.employee_id
        LEFT JOIN (
            -- Aggregate billing entry data
            SELECT 
                employee_id,
                SUM(billable_minutes) / 60 as session_hours,
                SUM(total_amount) as billed_amount,
                SUM(CASE WHEN status IN ('approved', 'billed', 'paid') THEN total_amount ELSE 0 END) as approved_amount
            FROM autism_billing_entries
            WHERE billing_date BETWEEN :start2 AND :end2
            GROUP BY employee_id
        ) be ON e.id = be.employee_id
        WHERE e.status = 'active'
        AND (tc.total_clock_hours > 0 OR be.session_hours > 0)
        ORDER BY e.last_name, e.first_name
    ");
    
    $stmt->execute([
        'start1' => $startDate,
        'end1' => $endDate,
        'start2' => $startDate,
        'end2' => $endDate
    ]);
    
    $payrollData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Payroll query error: " . $e->getMessage());
}

// Calculate totals
$totals = [
    'total_hours' => 0,
    'overtime_hours' => 0,
    'regular_pay' => 0,
    'overtime_pay' => 0,
    'gross_pay' => 0,
    'billed_amount' => 0
];

foreach ($payrollData as &$employee) {
    $regularHours = min($employee['total_hours'], 40);
    $overtimeHours = max(0, $employee['total_hours'] - 40);
    
    $employee['regular_hours'] = $regularHours;
    $employee['overtime_hours'] = $overtimeHours;
    $employee['regular_pay'] = $regularHours * $employee['hourly_rate'];
    $employee['overtime_pay'] = $overtimeHours * $employee['hourly_rate'] * 1.5;
    $employee['gross_pay'] = $employee['regular_pay'] + $employee['overtime_pay'];
    
    // Add to totals
    $totals['total_hours'] += $employee['total_hours'];
    $totals['overtime_hours'] += $overtimeHours;
    $totals['regular_pay'] += $employee['regular_pay'];
    $totals['overtime_pay'] += $employee['overtime_pay'];
    $totals['gross_pay'] += $employee['gross_pay'];
    $totals['billed_amount'] += $employee['billed_amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Report - Scrive ACI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }
        
        .header {
            background: white;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .header h1 {
            font-size: 1.5rem;
            color: #059669;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .date-filter {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .date-filter form {
            display: flex;
            gap: 1rem;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        
        .form-group input {
            padding: 0.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            background: #059669;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn:hover {
            background: #047857;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .summary-card h3 {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        
        .summary-card .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }
        
        .payroll-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .table-header h2 {
            font-size: 1.25rem;
            color: #1e293b;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8fafc;
            padding: 0.75rem;
            text-align: left;
            font-size: 0.875rem;
            color: #64748b;
            font-weight: 500;
            border-bottom: 1px solid #e5e7eb;
        }
        
        td {
            padding: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .text-right {
            text-align: right;
        }
        
        .totals-row {
            font-weight: 700;
            background: #f8fafc;
        }
        
        .discrepancy {
            color: #dc2626;
            font-size: 0.875rem;
        }
        
        .export-buttons {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .btn-secondary {
            background: #6366f1;
        }
        
        .btn-secondary:hover {
            background: #4f46e5;
        }
        
        @media print {
            .header, .date-filter, .export-buttons {
                display: none;
            }
            
            body {
                background: white;
            }
            
            .summary-cards {
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>📊 Payroll Report</h1>
        </div>
    </div>
    
    <div class="container">
        <!-- Date Filter -->
        <div class="date-filter">
            <form method="GET">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" required>
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" required>
                </div>
                <button type="submit" class="btn">Update Report</button>
                <button type="button" class="btn btn-secondary" onclick="setCurrentWeek()">Current Week</button>
                <button type="button" class="btn btn-secondary" onclick="setLastWeek()">Last Week</button>
            </form>
        </div>
        
        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card">
                <h3>Total Employees</h3>
                <div class="value"><?php echo count($payrollData); ?></div>
            </div>
            <div class="summary-card">
                <h3>Total Hours</h3>
                <div class="value"><?php echo number_format($totals['total_hours'], 2); ?></div>
            </div>
            <div class="summary-card">
                <h3>Overtime Hours</h3>
                <div class="value"><?php echo number_format($totals['overtime_hours'], 2); ?></div>
            </div>
            <div class="summary-card">
                <h3>Gross Payroll</h3>
                <div class="value">$<?php echo number_format($totals['gross_pay'], 2); ?></div>
            </div>
            <div class="summary-card">
                <h3>Billed Amount</h3>
                <div class="value">$<?php echo number_format($totals['billed_amount'], 2); ?></div>
            </div>
        </div>
        
        <!-- Payroll Table -->
        <div class="payroll-table">
            <div class="table-header">
                <h2>Employee Hours & Payroll</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th class="text-right">Clock Hours</th>
                        <th class="text-right">Session Hours</th>
                        <th class="text-right">Total Hours</th>
                        <th class="text-right">Regular (≤40)</th>
                        <th class="text-right">Overtime</th>
                        <th class="text-right">Rate/Hr</th>
                        <th class="text-right">Regular Pay</th>
                        <th class="text-right">OT Pay</th>
                        <th class="text-right">Gross Pay</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payrollData as $employee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                            <td class="text-right"><?php echo number_format($employee['clock_hours'], 2); ?></td>
                            <td class="text-right"><?php echo number_format($employee['session_hours'], 2); ?></td>
                            <td class="text-right">
                                <?php echo number_format($employee['total_hours'], 2); ?>
                                <?php if (abs($employee['clock_hours'] - $employee['session_hours']) > 0.5): ?>
                                    <div class="discrepancy">⚠️ Discrepancy</div>
                                <?php endif; ?>
                            </td>
                            <td class="text-right"><?php echo number_format($employee['regular_hours'], 2); ?></td>
                            <td class="text-right"><?php echo number_format($employee['overtime_hours'], 2); ?></td>
                            <td class="text-right">$<?php echo number_format($employee['hourly_rate'], 2); ?></td>
                            <td class="text-right">$<?php echo number_format($employee['regular_pay'], 2); ?></td>
                            <td class="text-right">$<?php echo number_format($employee['overtime_pay'], 2); ?></td>
                            <td class="text-right"><strong>$<?php echo number_format($employee['gross_pay'], 2); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td>TOTALS</td>
                        <td class="text-right">-</td>
                        <td class="text-right">-</td>
                        <td class="text-right"><?php echo number_format($totals['total_hours'], 2); ?></td>
                        <td class="text-right">-</td>
                        <td class="text-right"><?php echo number_format($totals['overtime_hours'], 2); ?></td>
                        <td class="text-right">-</td>
                        <td class="text-right">$<?php echo number_format($totals['regular_pay'], 2); ?></td>
                        <td class="text-right">$<?php echo number_format($totals['overtime_pay'], 2); ?></td>
                        <td class="text-right">$<?php echo number_format($totals['gross_pay'], 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <!-- Export Buttons -->
        <div class="export-buttons">
            <button class="btn" onclick="window.print()">🖨️ Print</button>
            <button class="btn btn-secondary" onclick="exportToCSV()">📊 Export to CSV</button>
            <button class="btn btn-secondary" onclick="generateBillingEntries()">💰 Generate Billing</button>
        </div>
    </div>
    
    <script>
        function setCurrentWeek() {
            const today = new Date();
            const dayOfWeek = today.getDay();
            const startOfWeek = new Date(today);
            startOfWeek.setDate(today.getDate() - dayOfWeek);
            const endOfWeek = new Date(startOfWeek);
            endOfWeek.setDate(startOfWeek.getDate() + 6);
            
            document.getElementById('start_date').value = startOfWeek.toISOString().split('T')[0];
            document.getElementById('end_date').value = endOfWeek.toISOString().split('T')[0];
        }
        
        function setLastWeek() {
            const today = new Date();
            const dayOfWeek = today.getDay();
            const startOfLastWeek = new Date(today);
            startOfLastWeek.setDate(today.getDate() - dayOfWeek - 7);
            const endOfLastWeek = new Date(startOfLastWeek);
            endOfLastWeek.setDate(startOfLastWeek.getDate() + 6);
            
            document.getElementById('start_date').value = startOfLastWeek.toISOString().split('T')[0];
            document.getElementById('end_date').value = endOfLastWeek.toISOString().split('T')[0];
        }
        
        function exportToCSV() {
            // Get current URL parameters
            const params = new URLSearchParams(window.location.search);
            params.append('export', 'csv');
            
            // Redirect to same page with export parameter
            window.location.href = '?' + params.toString();
        }
        
        function generateBillingEntries() {
            if (confirm('Generate billing entries for this period? This will create pending billing records from completed sessions.')) {
                const params = new URLSearchParams(window.location.search);
                params.append('action', 'generate_billing');
                
                fetch('?' + params.toString(), {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message || 'Billing entries generated');
                    location.reload();
                })
                .catch(error => {
                    alert('Error generating billing entries');
                });
            }
        }
    </script>
</body>
</html>

<?php
// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="payroll_report_' . $startDate . '_to_' . $endDate . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, [
        'Employee Name',
        'Clock Hours',
        'Session Hours', 
        'Total Hours',
        'Regular Hours',
        'Overtime Hours',
        'Hourly Rate',
        'Regular Pay',
        'Overtime Pay',
        'Gross Pay'
    ]);
    
    // Data rows
    foreach ($payrollData as $employee) {
        fputcsv($output, [
            $employee['first_name'] . ' ' . $employee['last_name'],
            $employee['clock_hours'],
            $employee['session_hours'],
            $employee['total_hours'],
            $employee['regular_hours'],
            $employee['overtime_hours'],
            $employee['hourly_rate'],
            $employee['regular_pay'],
            $employee['overtime_pay'],
            $employee['gross_pay']
        ]);
    }
    
    // Totals row
    fputcsv($output, [
        'TOTALS',
        '',
        '',
        $totals['total_hours'],
        '',
        $totals['overtime_hours'],
        '',
        $totals['regular_pay'],
        $totals['overtime_pay'],
        $totals['gross_pay']
    ]);
    
    fclose($output);
    exit;
}

// Handle billing generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'generate_billing') {
    header('Content-Type: application/json');
    
    try {
        $stmt = $pdo->prepare("CALL sp_generate_billing_entries(:start_date, :end_date)");
        $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'message' => $result['result'] ?? 'Billing entries generated']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}
?>