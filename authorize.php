<?php
/**
 * OpenEMR-compatible authorization handler
 * This file handles routing and authentication for OpenEMR integration
 */

require_once 'src/config.php';
require_once 'src/openemr_integration.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get the rewrite command from URL
$command = $_GET['_REWRITE_COMMAND'] ?? '';

// Parse the command to determine routing
$parts = explode('/', trim($command, '/'));
$module = $parts[0] ?? '';
$action = $parts[1] ?? '';

// Handle autism waiver app routing
if ($module === 'autism_waiver_app') {
    $file = 'autism_waiver_app/' . ($action ?: 'index.php');
    if (file_exists($file)) {
        include $file;
        exit;
    }
}

// Handle src files routing
if ($module === 'src') {
    // Check if user is authenticated for src files
    if (!isset($_SESSION['user_id'])) {
        header('Location: /src/login.php');
        exit;
    }
    
    $file = 'src/' . ($action ?: 'dashboard.php');
    if (file_exists($file)) {
        include $file;
        exit;
    }
}

// Handle direct file access
if (empty($command)) {
    // Redirect to homepage
    header('Location: /index.php');
    exit;
}

// Check if it's a direct file request
$possibleFile = $command;
if (!str_ends_with($possibleFile, '.php')) {
    $possibleFile .= '.php';
}

if (file_exists($possibleFile)) {
    include $possibleFile;
    exit;
}

// 404 handler
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            text-align: center; 
            padding: 50px;
            background: #f8fafc;
            color: #1e293b;
        }
        h1 { 
            color: #1e40af; 
            margin-bottom: 1rem;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .back-btn {
            display: inline-block;
            background: #1e40af;
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .debug-info {
            background: #f1f5f9;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.875rem;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏥 404 - Page Not Found</h1>
        <p>The page you're looking for doesn't exist in the American Caregivers Inc system.</p>
        
        <div class="debug-info">
            <strong>Requested:</strong> <?= htmlspecialchars($command) ?><br>
            <strong>Module:</strong> <?= htmlspecialchars($module) ?><br>
            <strong>Action:</strong> <?= htmlspecialchars($action) ?>
        </div>
        
        <a href="/index.php" class="back-btn">← Return to Homepage</a>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/src/dashboard.php" class="back-btn">📊 Go to Dashboard</a>
        <?php else: ?>
            <a href="/src/login.php" class="back-btn">🔑 Login</a>
        <?php endif; ?>
    </div>
</body>
</html><?php exit; ?> 