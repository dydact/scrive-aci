<?php

/**
 * Patient Detail - Autism Waiver Application
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
$patient = null;
$plans = [];
$recentSessions = [];
$currentUser = null;

// Get patient UUID from URL
$patientUuid = $_GET['uuid'] ?? null;
if (!$patientUuid) {
    header('Location: patients.php');
    exit;
}

try {
    $currentUser = $api->getCurrentUser();
    $patient = $api->getPatient($patientUuid);
    $plans = $api->getAutismPlans($patient['id']);
    $recentSessions = $api->getRecentSessions($patient['id'], 5);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($patient['full_name'] ?? 'Patient'); ?> - Autism Waiver System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: 600;
        }
        .patient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .patient-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 2rem;
            border: 3px solid rgba(255,255,255,0.3);
        }
        .stat-card {
            text-align: center;
            padding: 1rem;
            border-radius: 0.5rem;
            background: rgba(255,255,255,0.1);
            margin: 0.5rem;
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
        }
        .card-hover {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
                <a class="btn btn-outline-light btn-sm me-2" href="patients.php">
                    <i class="fas fa-arrow-left me-1"></i>
                    Back to Patients
                </a>
                <a class="btn btn-outline-light btn-sm" href="index.php">
                    <i class="fas fa-home me-1"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </nav>

    <?php if ($error): ?>
        <div class="container mt-4">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php elseif ($patient): ?>
        
        <!-- Patient Header -->
        <div class="patient-header py-4">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="patient-avatar">
                            <?php echo strtoupper(substr($patient['fname'], 0, 1) . substr($patient['lname'], 0, 1)); ?>
                        </div>
                    </div>
                    <div class="col">
                        <h2 class="mb-1"><?php echo htmlspecialchars($patient['full_name']); ?></h2>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1">
                                    <i class="fas fa-birthday-cake me-2"></i>
                                    Age: <?php echo $patient['age']; ?> years old
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-venus-mars me-2"></i>
                                    <?php echo ucfirst($patient['sex'] ?? 'Unknown'); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <?php if ($patient['phone_home']): ?>
                                    <p class="mb-1">
                                        <i class="fas fa-phone me-2"></i>
                                        <?php echo htmlspecialchars($patient['phone_home']); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if ($patient['email']): ?>
                                    <p class="mb-1">
                                        <i class="fas fa-envelope me-2"></i>
                                        <?php echo htmlspecialchars($patient['email']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="row">
                            <div class="col">
                                <div class="stat-card">
                                    <div class="h4 mb-1"><?php echo count($plans); ?></div>
                                    <div class="small">Treatment Plans</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="stat-card">
                                    <div class="h4 mb-1"><?php echo count($recentSessions); ?></div>
                                    <div class="small">Recent Sessions</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container my-4">
            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-md-3 mb-2">
                    <a href="new_session.php?patient_uuid=<?php echo urlencode($patient['uuid']); ?>" class="btn btn-primary w-100">
                        <i class="fas fa-edit me-2"></i>
                        New Progress Note
                    </a>
                </div>
                <div class="col-md-3 mb-2">
                    <a href="new_plan.php?patient_uuid=<?php echo urlencode($patient['uuid']); ?>" class="btn btn-success w-100">
                        <i class="fas fa-clipboard-list me-2"></i>
                        New Treatment Plan
                    </a>
                </div>
                <div class="col-md-3 mb-2">
                    <button class="btn btn-info w-100" onclick="alert('Coming soon!')">
                        <i class="fas fa-chart-line me-2"></i>
                        View Progress
                    </button>
                </div>
                <div class="col-md-3 mb-2">
                    <button class="btn btn-outline-primary w-100" onclick="alert('Coming soon!')">
                        <i class="fas fa-file-export me-2"></i>
                        Generate Report
                    </button>
                </div>
            </div>

            <div class="row">
                <!-- Treatment Plans -->
                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-clipboard-list me-2"></i>
                                Treatment Plans
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($plans)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-clipboard-list" style="font-size: 3rem;"></i>
                                    <p class="mt-2">No treatment plans yet</p>
                                    <a href="new_plan.php?patient_uuid=<?php echo urlencode($patient['uuid']); ?>" class="btn btn-primary btn-sm">
                                        Create First Plan
                                    </a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($plans as $plan): ?>
                                    <div class="border-bottom pb-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php echo ucfirst($plan['plan_type']); ?> Plan
                                                    <span class="badge bg-<?php echo $plan['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($plan['status']); ?>
                                                    </span>
                                                </h6>
                                                <p class="text-muted small mb-1">
                                                    Services: <?php echo htmlspecialchars($plan['service_types'] ?? 'Not specified'); ?>
                                                </p>
                                                <p class="text-muted small mb-0">
                                                    Created: <?php echo date('M j, Y', strtotime($plan['created_at'])); ?>
                                                    by <?php echo htmlspecialchars($plan['created_by_name']); ?>
                                                </p>
                                            </div>
                                            <button class="btn btn-outline-primary btn-sm" onclick="alert('View plan details coming soon!')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Sessions -->
                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>
                                Recent Sessions
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentSessions)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-edit" style="font-size: 3rem;"></i>
                                    <p class="mt-2">No sessions documented yet</p>
                                    <a href="new_session.php?patient_uuid=<?php echo urlencode($patient['uuid']); ?>" class="btn btn-success btn-sm">
                                        Document First Session
                                    </a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recentSessions as $session): ?>
                                    <div class="border-bottom pb-3 mb-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php echo htmlspecialchars($session['service_type']); ?> Session
                                                </h6>
                                                <p class="text-muted small mb-1">
                                                    <?php echo date('M j, Y', strtotime($session['date'])); ?> 
                                                    (<?php echo $session['duration_minutes']; ?> minutes)
                                                </p>
                                                <p class="text-muted small mb-0">
                                                    By: <?php echo htmlspecialchars($session['author_name']); ?>
                                                </p>
                                                <?php if ($session['narrative_note']): ?>
                                                    <p class="small mt-2 mb-0">
                                                        <?php echo htmlspecialchars(substr($session['narrative_note'], 0, 100)); ?>
                                                        <?php if (strlen($session['narrative_note']) > 100): ?>...<?php endif; ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <button class="btn btn-outline-success btn-sm" onclick="alert('View session details coming soon!')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <div class="text-center">
                                    <a href="#" class="btn btn-outline-success btn-sm" onclick="alert('View all sessions coming soon!')">
                                        View All Sessions
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Patient Information -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2"></i>
                                Patient Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Personal Information</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Full Name:</strong></td>
                                            <td><?php echo htmlspecialchars($patient['full_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Date of Birth:</strong></td>
                                            <td><?php echo date('M j, Y', strtotime($patient['DOB'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Gender:</strong></td>
                                            <td><?php echo ucfirst($patient['sex'] ?? 'Unknown'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Patient ID:</strong></td>
                                            <td><?php echo htmlspecialchars($patient['id']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6>Contact Information</h6>
                                    <table class="table table-sm">
                                        <tr>
                                            <td><strong>Phone:</strong></td>
                                            <td><?php echo htmlspecialchars($patient['phone_home'] ?? 'Not provided'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Email:</strong></td>
                                            <td><?php echo htmlspecialchars($patient['email'] ?? 'Not provided'); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Address:</strong></td>
                                            <td>
                                                <?php 
                                                $address = trim(($patient['street'] ?? '') . ' ' . ($patient['city'] ?? '') . ', ' . ($patient['state'] ?? '') . ' ' . ($patient['postal_code'] ?? ''));
                                                echo htmlspecialchars($address ?: 'Not provided');
                                                ?>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 