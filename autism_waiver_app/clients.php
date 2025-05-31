<?php
require_once '../src/init.php';
requireAuth(2); // DSP+ access

try {
    $pdo = getDatabase();
    $currentUser = getCurrentUser();
    
    // Get search parameters
    $search_term = $_GET['search'] ?? '';
    $status_filter = $_GET['status'] ?? 'active';
    
    // Get clients based on user role
    if ($currentUser['access_level'] >= 4) {
        // Supervisor and Admin see all clients
        if ($search_term) {
            $stmt = $pdo->prepare("
                SELECT * FROM autism_clients 
                WHERE (first_name LIKE ? OR last_name LIKE ? OR ma_number LIKE ?)
                ORDER BY last_name, first_name
            ");
            $searchPattern = "%{$search_term}%";
            $stmt->execute([$searchPattern, $searchPattern, $searchPattern]);
        } else {
            $stmt = $pdo->query("SELECT * FROM autism_clients ORDER BY last_name, first_name");
        }
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // DSP and Case Managers see all clients for now (simplified)
        $stmt = $pdo->query("SELECT * FROM autism_clients ORDER BY last_name, first_name");
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get statistics (simplified)
    $stmt = $pdo->query("SELECT COUNT(*) as total_active FROM autism_clients");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats = [
        'total_active' => $result['total_active'] ?? 0,
        'sessions_this_month' => 0, // Simplified for now
        'new_this_month' => 0 // Simplified for now
    ];
    
} catch (Exception $e) {
    $error = "Error loading clients: " . $e->getMessage();
    $clients = [];
    $stats = ['total_active' => 0, 'sessions_this_month' => 0, 'new_this_month' => 0];
}

// Check for messages
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? $error ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management - ACI</title>
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
        
        .stats-row {
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
        
        .search-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            align-items: end;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #059669;
        }
        
        .clients-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .client-name {
            font-weight: 600;
            color: #1e293b;
        }
        
        .client-ma {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-discharged {
            background: #e5e7eb;
            color: #374151;
        }
        
        .action-links {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-links a {
            padding: 0.5rem 1rem;
            background: #f3f4f6;
            color: #374151;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.3s;
        }
        
        .action-links a:hover {
            background: #e5e7eb;
        }
        
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
            padding: 3rem;
            color: #64748b;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <div class="logo-text">
                <span class="a">A</span><span class="c">C</span><span class="i">I</span>
            </div>
            <span style="color: #64748b;">Client Management</span>
        </div>
        <div class="nav-links">
            <a href="/dashboard">Dashboard</a>
            <a href="/clients" class="active">Clients</a>
            <a href="/billing">Billing</a>
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
            <h1 class="page-title">Client Management</h1>
            <div class="actions">
                <?php if ($_SESSION['access_level'] >= 3): ?>
                    <a href="/clients/add" class="btn btn-primary">+ Add New Client</a>
                <?php endif; ?>
                <a href="/reports/clients" class="btn btn-secondary">Export Report</a>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="stats-row">
            <div class="stat-card">
                <h3><?= $stats['total_active'] ?></h3>
                <p>Active Clients</p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['sessions_this_month'] ?></h3>
                <p>Clients with Sessions This Month</p>
            </div>
            <div class="stat-card">
                <h3><?= $stats['new_this_month'] ?></h3>
                <p>New Enrollments This Month</p>
            </div>
        </div>
        
        <div class="search-bar">
            <form method="GET" action="" class="search-form">
                <div class="form-group">
                    <label for="search">Search Clients</label>
                    <input type="text" id="search" name="search" 
                           value="<?= htmlspecialchars($search_term) ?>"
                           placeholder="Name or MA number...">
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="discharged" <?= $status_filter === 'discharged' ? 'selected' : '' ?>>Discharged</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="/clients" class="btn btn-secondary">Clear</a>
            </form>
        </div>
        
        <div class="clients-table">
            <?php if (empty($clients)): ?>
                <div class="empty-state">
                    <h3>No clients found</h3>
                    <p>Try adjusting your search criteria or add a new client.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th>MA Number</th>
                            <th>Date of Birth</th>
                            <th>Status</th>
                            <th>Assigned Staff</th>
                            <th>Sessions</th>
                            <th>Last Session</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td>
                                    <div class="client-name">
                                        <?= htmlspecialchars($client['last_name'] . ', ' . $client['first_name']) ?>
                                    </div>
                                    <?php if (!empty($client['email'])): ?>
                                        <div class="client-ma"><?= htmlspecialchars($client['email']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($client['ma_number'] ?? 'N/A') ?></td>
                                <td><?= !empty($client['date_of_birth']) ? date('m/d/Y', strtotime($client['date_of_birth'])) : 'N/A' ?></td>
                                <td>
                                    <span class="status-badge status-<?= $client['status'] ?? 'active' ?>">
                                        <?= ucfirst($client['status'] ?? 'active') ?>
                                    </span>
                                </td>
                                <td><?= $client['assigned_staff'] ?? 0 ?></td>
                                <td><?= $client['total_sessions'] ?? 0 ?></td>
                                <td>
                                    <?= $client['last_session_date'] 
                                        ? date('m/d/Y', strtotime($client['last_session_date'])) 
                                        : 'Never' ?>
                                </td>
                                <td>
                                    <div class="action-links">
                                        <a href="/client/<?= $client['id'] ?>">View</a>
                                        <?php if ($_SESSION['access_level'] >= 3): ?>
                                            <a href="/client/<?= $client['id'] ?>/edit">Edit</a>
                                        <?php endif; ?>
                                        <a href="/staff/notes?client_id=<?= $client['id'] ?>">Add Note</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>