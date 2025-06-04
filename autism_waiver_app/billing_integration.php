<?php
require_once '../src/init.php';
requireAuth(3); // Case Manager+ access

try {
    $pdo = getDatabase();
    
    // Get dashboard statistics (simplified to work with current tables)
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as claims_this_month,
            COALESCE(SUM(total_amount), 0) as revenue_this_month,
            COUNT(CASE WHEN status IN ('draft', 'generated', 'submitted') THEN 1 END) as pending_claims,
            COALESCE(SUM(CASE WHEN status IN ('draft', 'generated', 'submitted') THEN total_amount ELSE 0 END), 0) as pending_amount
        FROM autism_claims 
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
          AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'claims_this_month' => 0,
        'revenue_this_month' => 0,
        'pending_claims' => 0,
        'pending_amount' => 0
    ];
    
    // Get recent claims
    $stmt = $pdo->query("
        SELECT c.*, cl.first_name, cl.last_name, cl.ma_number
        FROM autism_claims c
        LEFT JOIN autism_clients cl ON c.client_id = cl.id
        ORDER BY c.created_at DESC
        LIMIT 10
    ");
    $recentClaims = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get clients for unbilled sessions (simplified)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM autism_clients");
    $clientCount = $stmt->fetch(PDO::FETCH_ASSOC);
    $unbilledSessions = []; // Simplified for now
    $serviceStats = [];
    
    // Handle form submissions
    $message = '';
    $error = '';
    
    // Form submissions are now handled on the claims page
    
} catch (Exception $e) {
    $error = "Error loading billing data: " . $e->getMessage();
    $stats = [
        'claims_this_month' => 0,
        'revenue_this_month' => 0,
        'pending_claims' => 0,
        'pending_amount' => 0,
        'paid_this_month' => 0,
        'collected_this_month' => 0,
        'outstanding_receivables' => 0
    ];
    $recentClaims = [];
    $unbilledSessions = [];
    $serviceStats = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing Integration - ACI</title>
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
        }
        
        .header {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .logo-text .a { color: #1e40af; }
        .logo-text .c { color: #dc2626; }
        .logo-text .i { color: #16a34a; }
        
        .nav-links {
            display: flex;
            gap: 1rem;
        }
        
        .nav-links a {
            padding: 0.5rem 1rem;
            text-decoration: none;
            color: #64748b;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .nav-links a:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .nav-links a.active {
            background: #059669;
            color: white;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            color: #1e293b;
        }
        
        .actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #059669;
            color: white;
        }
        
        .btn-primary:hover {
            background: #047857;
        }
        
        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }
        
        .btn-secondary:hover {
            background: #d1d5db;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .stat-card h3 {
            color: #059669;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .stat-card.highlight {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
        }
        
        .stat-card.highlight h3,
        .stat-card.highlight p {
            color: white;
        }
        
        .section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
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
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-draft { background: #e5e7eb; color: #374151; }
        .status-generated { background: #fef3c7; color: #92400e; }
        .status-submitted { background: #dbeafe; color: #1e40af; }
        .status-accepted { background: #d1fae5; color: #065f46; }
        .status-paid { background: #a7f3d0; color: #047857; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #64748b;
        }
        
        .amount {
            font-weight: 600;
            color: #059669;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <div class="logo-text">
                <span class="a">A</span><span class="c">C</span><span class="i">I</span>
            </div>
            <span style="color: #64748b;">Billing Integration</span>
        </div>
        <div class="nav-links">
            <a href="/dashboard">Dashboard</a>
            <a href="/clients">Clients</a>
            <a href="/billing" class="active">Billing</a>
            <a href="/schedule">Schedule</a>
            <a href="/reports">Reports</a>
            <?php if ($_SESSION['access_level'] >= 5): ?>
                <a href="/admin">Admin</a>
            <?php endif; ?>
            <a href="/logout">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Medicaid Billing Integration</h1>
            <div class="actions">
                <a href="<?= UrlManager::url('billing_claims') ?>" class="btn btn-primary">Manage Claims</a>
                <a href="<?= UrlManager::url('billing_edi') ?>" class="btn btn-secondary">EDI Processing</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= $stats['claims_this_month'] ?></h3>
                <p>Claims This Month</p>
                <div class="amount">$<?= number_format($stats['revenue_this_month'], 2) ?></div>
            </div>
            <div class="stat-card">
                <h3><?= $stats['pending_claims'] ?></h3>
                <p>Pending Claims</p>
                <div class="amount">$<?= number_format($stats['pending_amount'], 2) ?></div>
            </div>
            <div class="stat-card">
                <h3><?= $stats['paid_this_month'] ?? 0 ?></h3>
                <p>Paid This Month</p>
                <div class="amount">$<?= number_format($stats['collected_this_month'] ?? 0, 2) ?></div>
            </div>
            <div class="stat-card highlight">
                <h3>$<?= number_format($stats['outstanding_receivables'] ?? 0, 2) ?></h3>
                <p>Outstanding Receivables</p>
            </div>
        </div>
        
        <div class="grid-2">
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Unbilled Sessions</h2>
                    <a href="<?= UrlManager::url('billing_claims') ?>" class="btn btn-primary">Create Claims</a>
                </div>
                <?php if (empty($unbilledSessions)): ?>
                    <div class="empty-state">
                        <p>No unbilled sessions found.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>MA Number</th>
                                <th>Sessions</th>
                                <th>Hours</th>
                                <th>Period</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unbilledSessions as $session): ?>
                                <tr>
                                    <td><?= htmlspecialchars($session['last_name'] . ', ' . $session['first_name']) ?></td>
                                    <td><?= htmlspecialchars($session['ma_number'] ?? 'N/A') ?></td>
                                    <td><?= $session['session_count'] ?></td>
                                    <td><?= number_format($session['total_hours'], 1) ?></td>
                                    <td>
                                        <?= date('m/d', strtotime($session['date_from'])) ?> - 
                                        <?= date('m/d', strtotime($session['date_to'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <div class="section-header">
                    <h2 class="section-title">Service Type Summary</h2>
                </div>
                <?php if (empty($serviceStats)): ?>
                    <div class="empty-state">
                        <p>No services provided this month.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Service</th>
                                <th>Code</th>
                                <th>Sessions</th>
                                <th>Hours</th>
                                <th>Clients</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($serviceStats as $stat): ?>
                                <tr>
                                    <td><?= htmlspecialchars($stat['service_name']) ?></td>
                                    <td><?= htmlspecialchars($stat['service_code']) ?></td>
                                    <td><?= $stat['session_count'] ?></td>
                                    <td><?= number_format($stat['total_hours'], 1) ?></td>
                                    <td><?= $stat['unique_clients'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">Recent Claims</h2>
                <a href="<?= UrlManager::url('billing_claims') ?>" class="btn btn-secondary">View All</a>
            </div>
            <?php if (empty($recentClaims)): ?>
                <div class="empty-state">
                    <p>No claims found. Create your first claim above.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Claim #</th>
                            <th>Client</th>
                            <th>Service Period</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentClaims as $claim): ?>
                            <tr>
                                <td><?= htmlspecialchars($claim['claim_number']) ?></td>
                                <td><?= htmlspecialchars($claim['last_name'] . ', ' . $claim['first_name']) ?></td>
                                <td>
                                    <?= date('m/d/Y', strtotime($claim['service_date_from'])) ?> - 
                                    <?= date('m/d/Y', strtotime($claim['service_date_to'])) ?>
                                </td>
                                <td class="amount">$<?= number_format($claim['total_amount'], 2) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $claim['status'] ?>">
                                        <?= ucfirst($claim['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('m/d/Y', strtotime($claim['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // All navigation now handled by proper UrlManager links
        console.log('Billing Integration page loaded');
    </script>
</body>
</html>