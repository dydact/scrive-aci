<?php

/**
 * Program & Service Type Management - Scrive AI-Powered ERM
 * 
 * @package   Scrive  
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
 * @license   MIT License
 */

// Include Scrive authentication and API integration
require_once 'auth.php';
require_once 'api.php';

// Initialize authentication
initScriveAuth();

$api = new OpenEMRAPI();
$error = null;
$success = null;
$currentUser = null;
$programs = [];
$serviceTypes = [];

try {
    $currentUser = getCurrentScriveUser();
    
    // Check if database is set up
    $dbSetup = $api->checkDatabaseSetup();
    if (!$dbSetup['tables_exist']) {
        throw new Exception("Database not yet set up. Please run the comprehensive database setup first.");
    }
    
    // Get all programs
    $programs = getPrograms();
    
    // Get all service types with program info
    $serviceTypes = getServiceTypesWithPrograms();
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add_program') {
            $name = trim($_POST['name']);
            $abbreviation = trim($_POST['abbreviation']);
            $description = trim($_POST['description']);
            $maNumber = trim($_POST['ma_number']);
            
            if (empty($name) || empty($abbreviation) || empty($maNumber)) {
                throw new Exception("Name, abbreviation, and MA number are required");
            }
            
            $sql = "INSERT INTO autism_programs (name, abbreviation, description, ma_number, created_by) VALUES (?, ?, ?, ?, ?)";
            sqlStatement($sql, [$name, $abbreviation, $description, $maNumber, $currentUser['id']]);
            
            $success = "Program '$name' added successfully!";
            
        } elseif ($action === 'add_service_type') {
            $programId = $_POST['program_id'];
            $name = trim($_POST['name']);
            $abbreviation = trim($_POST['abbreviation']);
            $description = trim($_POST['description']);
            $billingCode = trim($_POST['billing_code']);
            $defaultRatePerUnit = $_POST['default_rate_per_unit'] ?: null;
            $maxDailyUnits = $_POST['max_daily_units'] ?: null;
            $requiresAuth = isset($_POST['requires_authorization']) ? 1 : 0;
            
            if (empty($name) || empty($abbreviation) || empty($billingCode) || empty($programId)) {
                throw new Exception("Program, name, abbreviation, and billing code are required");
            }
            
            $sql = "INSERT INTO autism_service_types 
                    (program_id, name, abbreviation, description, billing_code, default_rate_per_unit, 
                     max_daily_units, requires_authorization, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            sqlStatement($sql, [
                $programId, $name, $abbreviation, $description, $billingCode,
                $defaultRatePerUnit, $maxDailyUnits, $requiresAuth, $currentUser['id']
            ]);
            
            $success = "Service type '$name' added successfully!";
            
        } elseif ($action === 'update_service_type') {
            $serviceTypeId = $_POST['service_type_id'];
            $name = trim($_POST['name']);
            $abbreviation = trim($_POST['abbreviation']);
            $description = trim($_POST['description']);
            $billingCode = trim($_POST['billing_code']);
            $defaultRatePerUnit = $_POST['default_rate_per_unit'] ?: null;
            $maxDailyUnits = $_POST['max_daily_units'] ?: null;
            $requiresAuth = isset($_POST['requires_authorization']) ? 1 : 0;
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            $sql = "UPDATE autism_service_types SET 
                    name = ?, abbreviation = ?, description = ?, billing_code = ?,
                    default_rate_per_unit = ?, max_daily_units = ?, requires_authorization = ?, 
                    is_active = ?, updated_at = NOW()
                    WHERE service_type_id = ?";
            
            sqlStatement($sql, [
                $name, $abbreviation, $description, $billingCode,
                $defaultRatePerUnit, $maxDailyUnits, $requiresAuth, $isActive, $serviceTypeId
            ]);
            
            $success = "Service type updated successfully!";
        }
        
        // Refresh data
        $programs = getPrograms();
        $serviceTypes = getServiceTypesWithPrograms();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

function getPrograms() {
    $sql = "SELECT p.*, 
               (SELECT COUNT(*) FROM autism_service_types st WHERE st.program_id = p.program_id AND st.is_active = 1) as active_services,
               (SELECT COUNT(*) FROM autism_client_enrollments ce WHERE ce.program_id = p.program_id AND ce.status = 'active') as enrolled_clients
            FROM autism_programs p 
            WHERE p.is_active = 1 
            ORDER BY p.name";
    
    $result = sqlStatement($sql);
    $programs = [];
    while ($row = sqlFetchArray($result)) {
        $programs[] = $row;
    }
    return $programs;
}

function getServiceTypesWithPrograms() {
    $sql = "SELECT st.*, p.name as program_name, p.abbreviation as program_abbr, p.ma_number,
               0 as session_count
            FROM autism_service_types st
            JOIN autism_programs p ON st.program_id = p.program_id
            ORDER BY p.name, st.name";
    
    $result = sqlStatement($sql);
    $serviceTypes = [];
    while ($row = sqlFetchArray($result)) {
        $serviceTypes[] = $row;
    }
    return $serviceTypes;
}

function formatCurrency($amount) {
    return $amount ? '$' . number_format($amount, 2) : 'Not set';
}

function getServiceTypeBadgeClass($programAbbr) {
    return match($programAbbr) {
        'AW' => 'bg-primary',
        'DDA' => 'bg-success', 
        'CFC' => 'bg-info',
        'CO' => 'bg-warning',
        'CPAS' => 'bg-secondary',
        'CS' => 'bg-dark',
        default => 'bg-light text-dark'
    };
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Program & Service Management - Scrive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .service-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .program-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .program-card:hover {
            transform: translateY(-2px);
        }
        .service-card {
            border-left: 4px solid #667eea;
            transition: all 0.2s ease;
        }
        .service-card:hover {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .ma-number {
            font-family: monospace;
            background: rgba(255,255,255,0.9);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85em;
        }
        .billing-code {
            font-family: monospace;
            font-weight: bold;
        }
        .unit-display {
            background: linear-gradient(45deg, #e3f2fd, #f3e5f5);
            border-radius: 8px;
            padding: 8px;
            font-size: 0.9em;
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
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($currentUser['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <span class="dropdown-item-text">
                                <small class="text-muted">Logged in as:</small><br>
                                <strong><?php echo htmlspecialchars($currentUser['username']); ?></strong>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
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
    <div class="service-header py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="mb-1">
                        <i class="fas fa-cogs me-2"></i>
                        Program & Service Type Management
                    </h2>
                    <p class="mb-0">Manage programs with MA numbers and unit-based service types</p>
                </div>
                <div class="col-auto">
                    <button class="btn btn-light me-2" data-bs-toggle="modal" data-bs-target="#addProgramModal">
                        <i class="fas fa-plus me-2"></i>
                        Add Program
                    </button>
                    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addServiceTypeModal">
                        <i class="fas fa-plus me-2"></i>
                        Add Service Type
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

        <!-- Programs Overview -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3">Programs with MA Numbers</h4>
                <div class="row">
                    <?php foreach ($programs as $program): ?>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card program-card h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="card-title mb-0">
                                            <span class="badge <?php echo getServiceTypeBadgeClass($program['abbreviation']); ?>">
                                                <?php echo htmlspecialchars($program['abbreviation']); ?>
                                            </span>
                                            <?php echo htmlspecialchars($program['name']); ?>
                                        </h6>
                                    </div>
                                    
                                    <div class="ma-number mb-2">
                                        <strong>MA:</strong> <?php echo htmlspecialchars($program['ma_number']); ?>
                                    </div>
                                    
                                    <?php if ($program['description']): ?>
                                        <p class="card-text text-muted small mb-2">
                                            <?php echo htmlspecialchars($program['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted">Services</small>
                                            <div class="fw-bold"><?php echo $program['active_services']; ?></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Enrolled</small>
                                            <div class="fw-bold"><?php echo $program['enrolled_clients']; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Service Types by Program -->
        <div class="row">
            <div class="col-12">
                <h4 class="mb-3">Service Types (Unit-Based Billing)</h4>
                
                <?php if (empty($serviceTypes)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-cogs" style="font-size: 3rem;"></i>
                        <p class="mt-3">No service types configured yet</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceTypeModal">
                            <i class="fas fa-plus me-2"></i>
                            Add First Service Type
                        </button>
                    </div>
                <?php else: ?>
                    <?php 
                    $groupedServices = [];
                    foreach ($serviceTypes as $service) {
                        $groupedServices[$service['program_name']][] = $service;
                    }
                    ?>
                    
                    <?php foreach ($groupedServices as $programName => $services): ?>
                        <div class="mb-4">
                            <h5 class="text-primary border-bottom pb-2">
                                <i class="fas fa-folder me-2"></i>
                                <?php echo htmlspecialchars($programName); ?> Services
                            </h5>
                            
                            <div class="row">
                                <?php foreach ($services as $service): ?>
                                    <div class="col-lg-6 col-md-12 mb-3">
                                        <div class="card service-card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h6 class="card-title mb-1">
                                                            <span class="badge <?php echo getServiceTypeBadgeClass($service['program_abbr']); ?>">
                                                                <?php echo htmlspecialchars($service['abbreviation']); ?>
                                                            </span>
                                                            <?php echo htmlspecialchars($service['name']); ?>
                                                        </h6>
                                                        <div class="billing-code text-muted">
                                                            <i class="fas fa-barcode me-1"></i>
                                                            <?php echo htmlspecialchars($service['billing_code']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        <?php if (!$service['is_active']): ?>
                                                            <span class="badge bg-secondary">Inactive</span>
                                                        <?php endif; ?>
                                                        <button class="btn btn-outline-primary btn-sm ms-1" 
                                                                onclick="editServiceType(<?php echo htmlspecialchars(json_encode($service)); ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <?php if ($service['description']): ?>
                                                    <p class="text-muted small mb-2">
                                                        <?php echo htmlspecialchars($service['description']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <div class="unit-display">
                                                    <div class="row text-center">
                                                        <div class="col-4">
                                                            <small class="text-muted">Rate/Unit</small>
                                                            <div class="fw-bold">
                                                                <?php echo formatCurrency($service['default_rate_per_unit']); ?>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <small class="text-muted">Max Daily</small>
                                                            <div class="fw-bold">
                                                                <?php echo $service['max_daily_units'] ? $service['max_daily_units'] . ' units' : 'No limit'; ?>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <small class="text-muted">Sessions</small>
                                                            <div class="fw-bold"><?php echo $service['session_count']; ?></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php if ($service['requires_authorization']): ?>
                                                    <div class="mt-2">
                                                        <span class="badge bg-warning text-dark">
                                                            <i class="fas fa-shield-alt me-1"></i>
                                                            Requires Authorization
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Unit Billing Info -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Unit-Based Billing Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Billing Units</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-clock text-primary me-2"></i>1 Unit = 15 minutes</li>
                                    <li><i class="fas fa-clock text-primary me-2"></i>4 Units = 1 hour</li>
                                    <li><i class="fas fa-arrow-up text-success me-2"></i>8-10 minutes rounds up to 1 unit</li>
                                    <li><i class="fas fa-arrow-down text-warning me-2"></i>Less than 5 minutes rounds down</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Program MA Numbers</h6>
                                <ul class="list-unstyled">
                                    <?php foreach ($programs as $program): ?>
                                        <li>
                                            <span class="badge <?php echo getServiceTypeBadgeClass($program['abbreviation']); ?> me-2">
                                                <?php echo htmlspecialchars($program['abbreviation']); ?>
                                            </span>
                                            <?php echo htmlspecialchars($program['ma_number']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Program Modal -->
    <div class="modal fade" id="addProgramModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Program</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_program">
                        
                        <div class="mb-3">
                            <label for="program_name" class="form-label">Program Name *</label>
                            <input type="text" class="form-control" id="program_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="program_abbreviation" class="form-label">Abbreviation *</label>
                            <input type="text" class="form-control" id="program_abbreviation" name="abbreviation" maxlength="10" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="program_ma_number" class="form-label">MA Number *</label>
                            <input type="text" class="form-control" id="program_ma_number" name="ma_number" placeholder="e.g., 410608300" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="program_description" class="form-label">Description</label>
                            <textarea class="form-control" id="program_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            Add Program
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Service Type Modal -->
    <div class="modal fade" id="addServiceTypeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Service Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_service_type">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="service_program_id" class="form-label">Program *</label>
                                    <select class="form-select" id="service_program_id" name="program_id" required>
                                        <option value="">Select Program</option>
                                        <?php foreach ($programs as $program): ?>
                                            <option value="<?php echo $program['program_id']; ?>">
                                                <?php echo htmlspecialchars($program['abbreviation'] . ' - ' . $program['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="service_abbreviation" class="form-label">Abbreviation *</label>
                                    <input type="text" class="form-control" id="service_abbreviation" name="abbreviation" maxlength="10" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="service_name" class="form-label">Service Name *</label>
                            <input type="text" class="form-control" id="service_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="service_description" class="form-label">Description</label>
                            <textarea class="form-control" id="service_description" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="service_billing_code" class="form-label">Billing Code *</label>
                                    <input type="text" class="form-control" id="service_billing_code" name="billing_code" placeholder="e.g., W9306" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="service_rate" class="form-label">Default Rate per Unit ($)</label>
                                    <input type="number" class="form-control" id="service_rate" name="default_rate_per_unit" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="service_max_units" class="form-label">Max Daily Units</label>
                                    <input type="number" class="form-control" id="service_max_units" name="max_daily_units" min="1">
                                    <div class="form-text">Leave blank for no limit</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="requires_authorization" id="service_requires_auth" checked>
                                        <label class="form-check-label" for="service_requires_auth">
                                            Requires Prior Authorization
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            Add Service Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Service Type Modal -->
    <div class="modal fade" id="editServiceTypeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Service Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editServiceTypeForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_service_type">
                        <input type="hidden" name="service_type_id" id="edit_service_type_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_service_name" class="form-label">Service Name *</label>
                                    <input type="text" class="form-control" id="edit_service_name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_service_abbreviation" class="form-label">Abbreviation *</label>
                                    <input type="text" class="form-control" id="edit_service_abbreviation" name="abbreviation" maxlength="10" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_service_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_service_description" name="description" rows="2"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_service_billing_code" class="form-label">Billing Code *</label>
                                    <input type="text" class="form-control" id="edit_service_billing_code" name="billing_code" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_service_rate" class="form-label">Default Rate per Unit ($)</label>
                                    <input type="number" class="form-control" id="edit_service_rate" name="default_rate_per_unit" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="edit_service_max_units" class="form-label">Max Daily Units</label>
                                    <input type="number" class="form-control" id="edit_service_max_units" name="max_daily_units" min="1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="requires_authorization" id="edit_service_requires_auth">
                                        <label class="form-check-label" for="edit_service_requires_auth">
                                            Requires Authorization
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="is_active" id="edit_service_is_active">
                                        <label class="form-check-label" for="edit_service_is_active">
                                            Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Update Service Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editServiceType(service) {
            document.getElementById('edit_service_type_id').value = service.service_type_id;
            document.getElementById('edit_service_name').value = service.name;
            document.getElementById('edit_service_abbreviation').value = service.abbreviation;
            document.getElementById('edit_service_description').value = service.description || '';
            document.getElementById('edit_service_billing_code').value = service.billing_code;
            document.getElementById('edit_service_rate').value = service.default_rate_per_unit || '';
            document.getElementById('edit_service_max_units').value = service.max_daily_units || '';
            document.getElementById('edit_service_requires_auth').checked = service.requires_authorization == 1;
            document.getElementById('edit_service_is_active').checked = service.is_active == 1;
            
            new bootstrap.Modal(document.getElementById('editServiceTypeModal')).show();
        }
    </script>
</body>
</html> 