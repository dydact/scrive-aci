<?php
session_start();
require_once '../src/config.php';
require_once '../src/openemr_integration.php';
require_once '../src/UrlManager.php';

// Strip .php extension if present
UrlManager::stripPhpExtension();

// Check authentication and permissions
if (!isset($_SESSION['user_id']) || $_SESSION['access_level'] < 3) {
    UrlManager::redirect('login');
}

// Get current user
$currentUser = [
    'full_name' => $_SESSION['full_name'] ?? 'User',
    'user_id' => $_SESSION['user_id'] ?? 0
];

try {
    $pdo = getDatabase();
    
    // Get report statistics
    $stats = [];
    
    // Client statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_clients,
            COUNT(*) as active_clients,
            COUNT(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_clients_30_days
        FROM autism_clients
    ");
    $stats['clients'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Session statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_sessions,
            COUNT(CASE WHEN session_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as sessions_30_days,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as approved_sessions,
            SUM(duration_hours) as total_hours
        FROM autism_sessions
    ");
    $stats['sessions'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Billing statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_claims,
            SUM(total_amount) as total_revenue,
            COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_claims,
            SUM(CASE WHEN status = 'paid' THEN payment_amount ELSE 0 END) as collected_revenue
        FROM autism_claims
    ");
    $stats['billing'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Staff statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_staff,
            COUNT(*) as active_staff
        FROM autism_staff_members
    ");
    $stats['staff'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Reports error: " . $e->getMessage());
    $stats = [
        'clients' => ['total_clients' => 0, 'active_clients' => 0, 'new_clients_30_days' => 0],
        'sessions' => ['total_sessions' => 0, 'sessions_30_days' => 0, 'approved_sessions' => 0, 'total_hours' => 0],
        'billing' => ['total_claims' => 0, 'total_revenue' => 0, 'paid_claims' => 0, 'collected_revenue' => 0],
        'staff' => ['total_staff' => 0, 'active_staff' => 0]
    ];
}

$pageTitle = "Reports & Export - Scrive";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .reports-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .report-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .report-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-brain me-2"></i>
                Scrive
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($currentUser['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item text-danger" href="login.php?action=logout">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <section class="reports-header py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="mb-0">
                        <i class="fas fa-file-export me-3"></i>
                        Reports & Export
                    </h1>
                    <p class="mb-0 mt-2">Generate comprehensive reports for billing, compliance, and analysis</p>
                </div>
                <div class="col-auto">
                    <a href="<?= UrlManager::url('dashboard') ?>" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Reports Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Billing Reports -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card report-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-dollar-sign text-success" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Billing Reports</h5>
                            <p class="card-text">Generate detailed billing reports by date range, client, or service type.</p>
                            <button class="btn btn-success" disabled>
                                <i class="fas fa-chart-line me-2"></i>
                                Generate Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Client Progress -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card report-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-chart-bar text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Client Progress</h5>
                            <p class="card-text">Track client progress and goal achievement across all programs.</p>
                            <button class="btn btn-primary" disabled>
                                <i class="fas fa-users me-2"></i>
                                View Progress
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Compliance Reports -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card report-card h-100">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-shield-alt text-info" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Compliance Reports</h5>
                            <p class="card-text">Ensure regulatory compliance with automated audit reports.</p>
                            <button class="btn btn-info" disabled>
                                <i class="fas fa-clipboard-check me-2"></i>
                                Compliance Check
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        <i class="fas fa-copyright me-1"></i>
                        2025 American Caregivers Incorporated - Scrive AI-Powered ERM v1.0.0
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        HIPAA Compliant | Maryland Approved
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 