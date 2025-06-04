<?php
// Test script for billing reports
require_once '../src/init.php';

// Set session for testing
$_SESSION['user_id'] = 1;
$_SESSION['access_level'] = 5;
$_SESSION['full_name'] = 'Test Admin';

try {
    $pdo = getDatabase();
    
    echo "<h2>Testing Billing Reports System</h2>\n";
    
    // Check if required tables exist
    $tables = [
        'autism_claims',
        'autism_clients', 
        'autism_session_notes',
        'autism_service_types',
        'autism_billing_rates',
        'autism_prior_authorizations',
        'autism_claim_denials',
        'autism_payments'
    ];
    
    echo "<h3>Checking Tables:</h3>\n";
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = $stmt->rowCount() > 0;
        echo "- $table: " . ($exists ? "✓ EXISTS" : "✗ MISSING") . "\n";
    }
    
    // Check sample data
    echo "\n<h3>Data Statistics:</h3>\n";
    
    $stats = [];
    $stats['Clients'] = $pdo->query("SELECT COUNT(*) FROM autism_clients")->fetchColumn();
    $stats['Claims'] = $pdo->query("SELECT COUNT(*) FROM autism_claims")->fetchColumn();
    $stats['Sessions'] = $pdo->query("SELECT COUNT(*) FROM autism_session_notes")->fetchColumn();
    $stats['Service Types'] = $pdo->query("SELECT COUNT(*) FROM autism_service_types")->fetchColumn();
    
    foreach ($stats as $label => $count) {
        echo "- $label: $count records\n";
    }
    
    // Test report queries
    echo "\n<h3>Testing Report Queries:</h3>\n";
    
    // Revenue Summary
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as claim_count,
            SUM(total_amount) as total_revenue
        FROM autism_claims 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $revenue = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "- Revenue Summary (Last 30 days): {$revenue['claim_count']} claims, $" . number_format($revenue['total_revenue'] ?? 0, 2) . "\n";
    
    // Aging Report
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as unpaid_claims,
            SUM(total_amount) as unpaid_amount
        FROM autism_claims 
        WHERE status IN ('generated', 'submitted')
    ");
    $aging = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "- Unpaid Claims: {$aging['unpaid_claims']} claims, $" . number_format($aging['unpaid_amount'] ?? 0, 2) . "\n";
    
    echo "\n<h3>Available Reports:</h3>\n";
    $reports = [
        'revenue_summary' => 'Revenue Summary Report',
        'aging_report' => 'Accounts Receivable Aging',
        'denial_analysis' => 'Claim Denial Analysis',
        'collection_rates' => 'Collection Rates & Trends',
        'outstanding_balances' => 'Outstanding Balances by Client',
        'payer_mix' => 'Payer Mix Analysis',
        'service_profitability' => 'Service Profitability',
        'ma_billing_summary' => 'Maryland Medicaid Billing Summary',
        'authorization_analysis' => 'Authorization vs Billed Analysis',
        'timely_filing' => 'Timely Filing Compliance'
    ];
    
    foreach ($reports as $key => $name) {
        echo "- <a href='billing_reports.php?report=$key'>$name</a>\n";
    }
    
    echo "\n<p><a href='billing_reports.php'>Go to Billing Reports</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; white-space: pre-wrap; }
h2, h3 { color: #333; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style>