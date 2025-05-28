<?php

/**
 * Enhanced New Session/Progress Note - Scrive AI-Powered ERM
 * 
 * @package   Scrive
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
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
$success = null;
$client = null;
$currentUser = null;
$serviceTypes = [];
$goals = [];
$objectives = [];

// Get client UUID from URL
$clientUuid = $_GET['client_uuid'] ?? null;
if (!$clientUuid) {
    header('Location: clients.php');
    exit;
}

try {
    $currentUser = $api->getCurrentUser();
    $client = $api->getClient($clientUuid);
    $serviceTypes = $api->getServiceTypes();
    
    // Get client's active treatment plans and objectives
    $plans = $api->getAutismPlans($client['id']);
    foreach ($plans as $plan) {
        if ($plan['status'] === 'active') {
            $planGoals = sqlStatement("SELECT * FROM autism_goal WHERE plan_id = ? ORDER BY sequence", [$plan['plan_id']]);
            while ($goal = sqlFetchArray($planGoals)) {
                $goals[] = $goal;
                $goalObjectives = sqlStatement("SELECT * FROM autism_objective WHERE goal_id = ?", [$goal['goal_id']]);
                while ($objective = sqlFetchArray($goalObjectives)) {
                    $objectives[] = $objective;
                }
            }
        }
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get employee info for current user
        $employee = $api->getCurrentEmployee();
        if (!$employee) {
            throw new Exception("Employee record not found. Please contact your administrator to set up your employee profile.");
        }
        
        // Validate required fields
        $required = ['service_type_id', 'date', 'time_start', 'time_end', 'narrative_note'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields");
            }
        }
        
        // Calculate duration
        $startTime = new DateTime($_POST['date'] . ' ' . $_POST['time_start']);
        $endTime = new DateTime($_POST['date'] . ' ' . $_POST['time_end']);
        $duration = $endTime->diff($startTime);
        $durationMinutes = ($duration->h * 60) + $duration->i;
        
        if ($durationMinutes <= 0) {
            throw new Exception("End time must be after start time");
        }
        
        // Create session record
        $sql = "INSERT INTO autism_session 
                (client_id, employee_id, service_type_id, date, time_start, time_end, 
                 duration_minutes, location, narrative_note, participation_level, 
                 goal_achievement, interventions_used, recommendations, incidents, 
                 parent_contact, clock_in_time, clock_out_time, actual_duration_minutes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)";
        
        $params = [
            $client['id'],
            $employee['employee_id'],
            $_POST['service_type_id'],
            $_POST['date'],
            $_POST['time_start'],
            $_POST['time_end'],
            $durationMinutes,
            $_POST['location'] ?: null,
            $_POST['narrative_note'],
            $_POST['participation_level'] ?: null,
            $_POST['goal_achievement'] ?: null,
            $_POST['interventions_used'] ?: null,
            $_POST['recommendations'] ?: null,
            $_POST['incidents'] ?: null,
            $_POST['parent_contact'] ?: null,
            $durationMinutes
        ];
        
        $sessionId = sqlInsert($sql, $params);
        
        if ($sessionId) {
            // Create activity records for each objective worked on
            if (!empty($_POST['objectives'])) {
                foreach ($_POST['objectives'] as $objId => $data) {
                    if (!empty($data['worked_on'])) {
                        $activitySql = "INSERT INTO autism_session_activity 
                                       (session_id, objective_id, activity_desc, outcome, measurement_value, activity_note)
                                       VALUES (?, ?, ?, ?, ?, ?)";
                        
                        $activityParams = [
                            $sessionId,
                            $objId,
                            $data['activity_desc'] ?: null,
                            $data['outcome'] ?: null,
                            $data['measurement_value'] ?: null,
                            $data['activity_note'] ?: null
                        ];
                        
                        sqlStatement($activitySql, $activityParams);
                    }
                }
            }
            
            // Create incident records if any
            if (!empty($_POST['incident_occurred'])) {
                $incidentSql = "INSERT INTO autism_session_incident 
                               (session_id, incident_time, incident_type, severity, description, action_taken, follow_up_required)
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $incidentParams = [
                    $sessionId,
                    $_POST['incident_time'] ?: null,
                    $_POST['incident_type'],
                    $_POST['incident_severity'],
                    $_POST['incident_description'],
                    $_POST['incident_action'] ?: null,
                    isset($_POST['incident_follow_up']) ? 1 : 0
                ];
                
                sqlStatement($incidentSql, $incidentParams);
            }
            
            // Auto-create billing entry
            $api->createBillingEntry($sessionId, $_POST);
            
            $success = "Progress note created successfully!";
            
            // Clear form or redirect
            if (isset($_POST['save_and_new'])) {
                $_POST = []; // Clear form for new entry
            } else {
                header("Location: client_detail.php?uuid=" . urlencode($clientUuid) . "&success=session_created");
                exit;
            }
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Progress Note - Scrive</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: 600;
        }
        .session-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
        }
        .objective-card {
            border-left: 4px solid #667eea;
            background: #f8f9fa;
        }
        .ai-assistant {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
        }
        .form-section {
            border: 1px solid #e3e6f0;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            background: white;
        }
        .section-title {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e3e6f0;
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
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>
                    <?php echo htmlspecialchars($currentUser['fname'] . ' ' . $currentUser['lname'] ?? 'User'); ?>
                </span>
                <a class="btn btn-outline-light btn-sm me-2" href="client_detail.php?uuid=<?php echo urlencode($clientUuid); ?>">
                    <i class="fas fa-arrow-left me-1"></i>
                    Back to Client
                </a>
                <a class="btn btn-outline-light btn-sm" href="index.php">
                    <i class="fas fa-home me-1"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="session-header py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="mb-1">
                        <i class="fas fa-edit me-2"></i>
                        New Progress Note
                    </h2>
                    <p class="mb-0">
                        Client: <strong><?php echo htmlspecialchars($client['full_name'] ?? 'Unknown'); ?></strong>
                        <?php if ($client): ?>
                            | Age: <?php echo $client['age']; ?> | ID: <?php echo $client['id']; ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-auto">
                    <button class="btn btn-light" onclick="showAIAssistant()">
                        <i class="fas fa-robot me-2"></i>
                        AI Assistant
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

        <form method="POST" id="sessionForm">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Basic Session Information -->
                    <div class="form-section">
                        <h5 class="section-title">
                            <i class="fas fa-info-circle me-2"></i>
                            Session Information
                        </h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="service_type_id" class="form-label">Service Type *</label>
                                <select class="form-select" id="service_type_id" name="service_type_id" required onchange="updateServiceDetails()">
                                    <option value="">Select Service Type</option>
                                    <?php foreach ($serviceTypes as $service): ?>
                                        <option value="<?php echo $service['service_type_id']; ?>" 
                                                data-rate="<?php echo $service['default_rate']; ?>"
                                                data-max-hours="<?php echo $service['max_daily_hours']; ?>"
                                                data-category="<?php echo $service['category']; ?>">
                                            <?php echo htmlspecialchars($service['name']); ?> (<?php echo $service['abbreviation']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted" id="serviceDetails"></small>
                            </div>
                            <div class="col-md-6">
                                <label for="date" class="form-label">Session Date *</label>
                                <input type="date" class="form-control" id="date" name="date" 
                                       value="<?php echo $_POST['date'] ?? date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <label for="time_start" class="form-label">Start Time *</label>
                                <input type="time" class="form-control" id="time_start" name="time_start" 
                                       value="<?php echo $_POST['time_start'] ?? ''; ?>" required onchange="calculateDuration()">
                            </div>
                            <div class="col-md-4">
                                <label for="time_end" class="form-label">End Time *</label>
                                <input type="time" class="form-control" id="time_end" name="time_end" 
                                       value="<?php echo $_POST['time_end'] ?? ''; ?>" required onchange="calculateDuration()">
                            </div>
                            <div class="col-md-4">
                                <label for="duration_display" class="form-label">Duration</label>
                                <input type="text" class="form-control" id="duration_display" readonly 
                                       placeholder="Auto-calculated">
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo $_POST['location'] ?? ''; ?>" 
                                       placeholder="e.g., Client's home, Community center">
                            </div>
                            <div class="col-md-6">
                                <label for="participation_level" class="form-label">Client Participation Level</label>
                                <select class="form-select" id="participation_level" name="participation_level">
                                    <option value="">Select Level</option>
                                    <option value="high" <?php echo ($_POST['participation_level'] ?? '') === 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="medium" <?php echo ($_POST['participation_level'] ?? '') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                    <option value="low" <?php echo ($_POST['participation_level'] ?? '') === 'low' ? 'selected' : ''; ?>>Low</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Objectives and Activities -->
                    <?php if (!empty($objectives)): ?>
                    <div class="form-section">
                        <h5 class="section-title">
                            <i class="fas fa-bullseye me-2"></i>
                            Objectives Worked On
                        </h5>
                        
                        <?php foreach ($objectives as $objective): ?>
                            <div class="objective-card p-3 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           id="obj_<?php echo $objective['obj_id']; ?>" 
                                           name="objectives[<?php echo $objective['obj_id']; ?>][worked_on]" 
                                           value="1" onchange="toggleObjectiveDetails(<?php echo $objective['obj_id']; ?>)">
                                    <label class="form-check-label fw-bold" for="obj_<?php echo $objective['obj_id']; ?>">
                                        <?php echo htmlspecialchars($objective['objective_text']); ?>
                                    </label>
                                </div>
                                
                                <div id="obj_details_<?php echo $objective['obj_id']; ?>" class="mt-3" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Activity Description</label>
                                            <textarea class="form-control" rows="2" 
                                                    name="objectives[<?php echo $objective['obj_id']; ?>][activity_desc]" 
                                                    placeholder="Describe what was done to work on this objective"></textarea>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Outcome</label>
                                            <select class="form-select" name="objectives[<?php echo $objective['obj_id']; ?>][outcome]">
                                                <option value="">Select</option>
                                                <option value="success">Success</option>
                                                <option value="partial">Partial</option>
                                                <option value="failure">Needs Work</option>
                                                <option value="n/a">N/A</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Measurement (%)</label>
                                            <input type="number" class="form-control" min="0" max="100" 
                                                   name="objectives[<?php echo $objective['obj_id']; ?>][measurement_value]" 
                                                   placeholder="0-100">
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label class="form-label">Additional Notes</label>
                                        <textarea class="form-control" rows="2" 
                                                name="objectives[<?php echo $objective['obj_id']; ?>][activity_note]" 
                                                placeholder="Any additional observations or notes"></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Session Narrative -->
                    <div class="form-section">
                        <h5 class="section-title">
                            <i class="fas fa-file-text me-2"></i>
                            Session Narrative *
                        </h5>
                        
                        <div class="mb-3">
                            <label for="narrative_note" class="form-label">Detailed Session Notes</label>
                            <textarea class="form-control" id="narrative_note" name="narrative_note" rows="6" 
                                      required placeholder="Provide a comprehensive narrative of the session including client's behavior, activities completed, progress observed, challenges encountered, and any notable events..."><?php echo htmlspecialchars($_POST['narrative_note'] ?? ''); ?></textarea>
                            <div class="form-text">
                                <small>
                                    <i class="fas fa-robot me-1"></i>
                                    Click "AI Assistant" to help generate or improve your narrative
                                </small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="interventions_used" class="form-label">Interventions Used</label>
                                <textarea class="form-control" id="interventions_used" name="interventions_used" rows="3" 
                                          placeholder="List specific interventions, techniques, or strategies used"><?php echo htmlspecialchars($_POST['interventions_used'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="recommendations" class="form-label">Recommendations</label>
                                <textarea class="form-control" id="recommendations" name="recommendations" rows="3" 
                                          placeholder="Recommendations for future sessions or home practice"><?php echo htmlspecialchars($_POST['recommendations'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Incidents (Optional) -->
                    <div class="form-section">
                        <h5 class="section-title">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Incidents (If Any)
                        </h5>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="incident_occurred" 
                                   name="incident_occurred" value="1" onchange="toggleIncidentDetails()">
                            <label class="form-check-label" for="incident_occurred">
                                An incident occurred during this session
                            </label>
                        </div>
                        
                        <div id="incident_details" style="display: none;">
                            <div class="row">
                                <div class="col-md-4">
                                    <label for="incident_time" class="form-label">Incident Time</label>
                                    <input type="time" class="form-control" id="incident_time" name="incident_time">
                                </div>
                                <div class="col-md-4">
                                    <label for="incident_type" class="form-label">Incident Type</label>
                                    <select class="form-select" id="incident_type" name="incident_type">
                                        <option value="">Select Type</option>
                                        <option value="behavioral">Behavioral</option>
                                        <option value="safety">Safety</option>
                                        <option value="medical">Medical</option>
                                        <option value="communication">Communication</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="incident_severity" class="form-label">Severity</label>
                                    <select class="form-select" id="incident_severity" name="incident_severity">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <label for="incident_description" class="form-label">Incident Description</label>
                                <textarea class="form-control" id="incident_description" name="incident_description" rows="3" 
                                          placeholder="Detailed description of what happened"></textarea>
                            </div>
                            
                            <div class="mt-3">
                                <label for="incident_action" class="form-label">Action Taken</label>
                                <textarea class="form-control" id="incident_action" name="incident_action" rows="2" 
                                          placeholder="Describe the immediate action taken to address the incident"></textarea>
                            </div>
                            
                            <div class="mt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="incident_follow_up" 
                                           name="incident_follow_up" value="1">
                                    <label class="form-check-label" for="incident_follow_up">
                                        Follow-up required
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- AI Assistant Panel -->
                    <div class="ai-assistant p-3 mb-4" id="aiAssistantPanel" style="display: none;">
                        <h6 class="mb-3">
                            <i class="fas fa-robot me-2"></i>
                            AI Writing Assistant
                        </h6>
                        <p class="small mb-3">
                            Get help improving your session narrative with AI-powered suggestions
                        </p>
                        <button type="button" class="btn btn-light btn-sm w-100 mb-2" onclick="improveNarrative()">
                            <i class="fas fa-magic me-1"></i>
                            Improve Narrative
                        </button>
                        <button type="button" class="btn btn-outline-light btn-sm w-100 mb-2" onclick="suggestInterventions()">
                            <i class="fas fa-lightbulb me-1"></i>
                            Suggest Interventions
                        </button>
                        <button type="button" class="btn btn-outline-light btn-sm w-100" onclick="generateRecommendations()">
                            <i class="fas fa-clipboard-list me-1"></i>
                            Generate Recommendations
                        </button>
                    </div>

                    <!-- Session Summary -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-pie me-2"></i>
                                Session Summary
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Goal Achievement</label>
                                <select class="form-select" name="goal_achievement">
                                    <option value="">Select Overall Achievement</option>
                                    <option value="exceeded">Exceeded Expectations</option>
                                    <option value="met">Met Expectations</option>
                                    <option value="partial">Partially Met</option>
                                    <option value="not_met">Not Met</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="parent_contact" class="form-label">Parent/Guardian Contact</label>
                                <textarea class="form-control" id="parent_contact" name="parent_contact" rows="3" 
                                          placeholder="Any communication with parent/guardian during or after session"><?php echo htmlspecialchars($_POST['parent_contact'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-save me-2"></i>
                                Save & Return to Client
                            </button>
                            
                            <button type="submit" name="save_and_new" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-plus me-2"></i>
                                Save & Create Another
                            </button>
                            
                            <a href="client_detail.php?uuid=<?php echo urlencode($clientUuid); ?>" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times me-2"></i>
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateServiceDetails() {
            const select = document.getElementById('service_type_id');
            const details = document.getElementById('serviceDetails');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                const rate = option.getAttribute('data-rate');
                const maxHours = option.getAttribute('data-max-hours');
                const category = option.getAttribute('data-category');
                
                details.innerHTML = `Rate: $${rate}/hour | Category: ${category}${maxHours ? ` | Max Daily: ${maxHours}h` : ''}`;
            } else {
                details.innerHTML = '';
            }
        }
        
        function calculateDuration() {
            const startTime = document.getElementById('time_start').value;
            const endTime = document.getElementById('time_end').value;
            const display = document.getElementById('duration_display');
            
            if (startTime && endTime) {
                const start = new Date('2000-01-01 ' + startTime);
                const end = new Date('2000-01-01 ' + endTime);
                const diff = end - start;
                
                if (diff > 0) {
                    const hours = Math.floor(diff / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    display.value = `${hours}h ${minutes}m`;
                } else {
                    display.value = 'Invalid time range';
                }
            } else {
                display.value = '';
            }
        }
        
        function toggleObjectiveDetails(objId) {
            const checkbox = document.getElementById('obj_' + objId);
            const details = document.getElementById('obj_details_' + objId);
            
            if (checkbox.checked) {
                details.style.display = 'block';
            } else {
                details.style.display = 'none';
            }
        }
        
        function toggleIncidentDetails() {
            const checkbox = document.getElementById('incident_occurred');
            const details = document.getElementById('incident_details');
            
            if (checkbox.checked) {
                details.style.display = 'block';
            } else {
                details.style.display = 'none';
            }
        }
        
        function showAIAssistant() {
            const panel = document.getElementById('aiAssistantPanel');
            if (panel.style.display === 'none') {
                panel.style.display = 'block';
            } else {
                panel.style.display = 'none';
            }
        }
        
        function improveNarrative() {
            const narrative = document.getElementById('narrative_note');
            const currentText = narrative.value;
            
            if (!currentText.trim()) {
                alert('Please write some initial notes first, then AI can help improve them.');
                return;
            }
            
            // Placeholder for AI integration
            alert('AI narrative improvement coming soon! This will analyze your notes and suggest improvements for clarity, completeness, and clinical quality.');
        }
        
        function suggestInterventions() {
            alert('AI intervention suggestions coming soon! This will suggest evidence-based interventions based on the client\'s goals and session context.');
        }
        
        function generateRecommendations() {
            alert('AI recommendation generator coming soon! This will suggest next steps and home practice activities based on session outcomes.');
        }
    </script>
</body>
</html> 