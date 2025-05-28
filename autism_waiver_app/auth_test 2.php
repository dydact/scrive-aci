<?php

/**
 * Authentication Test - Autism Waiver Application
 * Simple test page to verify OpenEMR integration
 */

// Include API integration
require_once 'api.php';

$status = [];

try {
    // Test database connection
    $status['database'] = 'Connected';
    
    // Test authentication
    if (isset($_SESSION['authUser']) && !empty($_SESSION['authUser'])) {
        $status['auth'] = 'Authenticated as: ' . $_SESSION['authUser'];
        
        try {
            $api = new OpenEMRAPI();
            $user = $api->getCurrentUser();
            $status['user_info'] = $user['fname'] . ' ' . $user['lname'] . ' (ID: ' . $user['id'] . ')';
            
            // Test patient count
            $patients = $api->getPatients();
            $status['patient_count'] = count($patients);
            
            // Test database setup
            $dbStatus = $api->checkDatabaseSetup();
            $status['autism_tables'] = $dbStatus['existing_tables'] . ' of ' . $dbStatus['total_tables'] . ' tables exist';
            
        } catch (Exception $e) {
            $status['api_error'] = $e->getMessage();
        }
        
    } else {
        $status['auth'] = 'Not authenticated - redirecting to login';
        header('Location: ../interface/login/login.php?site=default');
        exit;
    }
    
} catch (Exception $e) {
    $status['error'] = $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Test - Autism Waiver System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Autism Waiver App - Integration Test</h4>
                    </div>
                    <div class="card-body">
                        <h5>Status Check</h5>
                        <table class="table">
                            <?php foreach ($status as $key => $value): ?>
                                <tr>
                                    <td><strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong></td>
                                    <td>
                                        <?php if (str_contains($key, 'error')): ?>
                                            <span class="text-danger"><?php echo htmlspecialchars($value); ?></span>
                                        <?php else: ?>
                                            <span class="text-success"><?php echo htmlspecialchars($value); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-primary">Go to Dashboard</a>
                            <a href="patients.php" class="btn btn-success">View Patients</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 