<?php

/**
 * Patient List - Autism Waiver Application
 * 
 * @package   Autism Waiver App
 * @author    American Caregivers Inc
 * @copyright Copyright (c) 2025 American Caregivers Inc
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
$patients = [];
$currentUser = null;

try {
    $currentUser = $api->getCurrentUser();
    
    // Handle search
    $search = [];
    if (!empty($_GET['name'])) {
        $search['name'] = $_GET['name'];
    }
    if (!empty($_GET['dob'])) {
        $search['dob'] = $_GET['dob'];
    }
    
    $clients = $api->getClients($search);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient List - Autism Waiver System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: 600;
        }
        .patient-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }
        .patient-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .patient-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .search-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-puzzle-piece me-2"></i>
                Autism Waiver System
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

    <div class="container my-4">
        <!-- Search Section -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header search-card">
                <h5 class="mb-0">
                    <i class="fas fa-search me-2"></i>
                    Search Patients
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="name" class="form-label">Patient Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($_GET['name'] ?? ''); ?>"
                               placeholder="First or Last Name">
                    </div>
                    <div class="col-md-3">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dob" name="dob" 
                               value="<?php echo htmlspecialchars($_GET['dob'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-1"></i>
                            Search
                        </button>
                        <a href="patients.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>
                            Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Error Display -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Patient List -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>
                <i class="fas fa-users text-primary me-2"></i>
                Patients 
                <span class="badge bg-primary"><?php echo count($clients); ?></span>
            </h4>
            <div>
                <a href="new_patient.php" class="btn btn-success">
                    <i class="fas fa-user-plus me-1"></i>
                    Add Patient
                </a>
            </div>
        </div>

        <?php if (empty($clients) && !$error): ?>
            <div class="text-center py-5">
                <i class="fas fa-users text-muted" style="font-size: 4rem;"></i>
                <h4 class="text-muted mt-3">No Patients Found</h4>
                <p class="text-muted">Try adjusting your search criteria or add a new patient.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($clients as $client): ?>
                    <div class="col-lg-6 col-xl-4 mb-3">
                        <div class="card patient-card h-100 border-0 shadow-sm" 
                             onclick="window.location.href='client_detail.php?uuid=<?php echo urlencode($client['uuid']); ?>'">
                            <div class="card-body">
                                <div class="d-flex align-items-start">
                                    <div class="patient-avatar me-3">
                                        <?php echo strtoupper(substr($client['fname'], 0, 1) . substr($client['lname'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold">
                                            <?php echo htmlspecialchars($client['full_name']); ?>
                                        </h6>
                                        <div class="text-muted small mb-2">
                                            <div class="row">
                                                <div class="col-6">
                                                    <i class="fas fa-birthday-cake me-1"></i>
                                                    Age: <?php echo $client['age'] ?? 'Unknown'; ?>
                                                </div>
                                                <div class="col-6">
                                                    <i class="fas fa-venus-mars me-1"></i>
                                                    <?php echo ucfirst($client['sex'] ?? 'Unknown'); ?>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <?php if ($client['phone_home']): ?>
                                            <div class="text-muted small mb-1">
                                                <i class="fas fa-phone me-1"></i>
                                                <?php echo htmlspecialchars($client['phone_home']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($client['city'] && $client['state']): ?>
                                            <div class="text-muted small">
                                                <i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($client['city'] . ', ' . $client['state']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="mt-3 pt-3 border-top">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="text-primary fw-bold"><?php echo $client['active_plans'] ?? 0; ?></div>
                                            <div class="text-muted small">Plans</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-success fw-bold"><?php echo $client['recent_sessions'] ?? 0; ?></div>
                                            <div class="text-muted small">Sessions</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-warning fw-bold">Active</div>
                                            <div class="text-muted small">Status</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 