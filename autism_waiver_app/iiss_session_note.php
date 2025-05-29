<?php
session_start();
require_once 'auth_helper.php';
require_once 'config.php';

// Check if user is logged in and has appropriate access
if (!isLoggedIn() || $_SESSION['access_level'] < 2) {
    header('Location: login.php');
    exit;
}

// Database connection
$database = getenv('MARIADB_DATABASE') ?: 'iris';
$username = getenv('MARIADB_USER') ?: 'iris_user';
$password = getenv('MARIADB_PASSWORD') ?: '';
$host = getenv('MARIADB_HOST') ?: 'localhost';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get client ID from request
$clientId = $_GET['client_id'] ?? null;
$noteId = $_GET['note_id'] ?? null;

// Load client information
$client = null;
if ($clientId) {
    $stmt = $pdo->prepare("
        SELECT c.*, tp.id as plan_id
        FROM autism_clients c
        LEFT JOIN autism_treatment_plans tp ON c.id = tp.client_id AND tp.status = 'active'
        WHERE c.id = ?
    ");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Load treatment goals
$goals = [];
if ($client && $client['plan_id']) {
    $stmt = $pdo->prepare("
        SELECT * FROM autism_treatment_goals 
        WHERE plan_id = ? AND status = 'active'
        ORDER BY goal_category, id
    ");
    $stmt->execute([$client['plan_id']]);
    $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get staff's assigned clients
$assignedClients = [];
$stmt = $pdo->prepare("
    SELECT c.id, c.first_name, c.last_name, c.ma_number
    FROM autism_clients c
    INNER JOIN autism_staff_assignments sa ON c.id = sa.client_id
    WHERE sa.staff_id = ? AND sa.status = 'active'
    ORDER BY c.last_name, c.first_name
");
$stmt->execute([$_SESSION['user_id']]);
$assignedClients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Insert IISS note
        $stmt = $pdo->prepare("
            INSERT INTO autism_iiss_notes (
                client_id, staff_id, session_date, start_time, end_time,
                total_minutes, location, session_type, goals_addressed,
                activities, client_response, progress_notes, behavior_incidents,
                parent_communication, next_session_plan, status
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted'
            )
        ");
        
        $totalMinutes = (strtotime($_POST['end_time']) - strtotime($_POST['start_time'])) / 60;
        $goalsAddressed = json_encode($_POST['goals'] ?? []);
        
        $stmt->execute([
            $_POST['client_id'],
            $_SESSION['user_id'],
            $_POST['session_date'],
            $_POST['start_time'],
            $_POST['end_time'],
            $totalMinutes,
            $_POST['location'],
            $_POST['session_type'],
            $goalsAddressed,
            $_POST['activities'],
            $_POST['client_response'],
            $_POST['progress_notes'],
            $_POST['behavior_incidents'],
            $_POST['parent_communication'],
            $_POST['next_session_plan']
        ]);
        
        $noteId = $pdo->lastInsertId();
        
        // Insert goal progress records
        if (isset($_POST['goal_progress']) && is_array($_POST['goal_progress'])) {
            $progressStmt = $pdo->prepare("
                INSERT INTO autism_goal_progress (
                    goal_id, iiss_note_id, progress_date, progress_rating,
                    trials_correct, trials_total, percentage, notes, recorded_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['goal_progress'] as $goalId => $progress) {
                if (!empty($progress['rating'])) {
                    $percentage = null;
                    if (!empty($progress['trials_total']) && $progress['trials_total'] > 0) {
                        $percentage = ($progress['trials_correct'] / $progress['trials_total']) * 100;
                    }
                    
                    $progressStmt->execute([
                        $goalId,
                        $noteId,
                        $_POST['session_date'],
                        $progress['rating'],
                        $progress['trials_correct'] ?? null,
                        $progress['trials_total'] ?? null,
                        $percentage,
                        $progress['notes'] ?? null,
                        $_SESSION['user_id']
                    ]);
                }
            }
        }
        
        // Create billing entry
        $stmt = $pdo->prepare("
            INSERT INTO autism_billing_entries (
                employee_id, client_id, session_note_id, billing_date,
                service_type_id, total_minutes, billable_minutes, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['client_id'],
            $noteId,
            $_POST['session_date'],
            1, // Default to IISS service type
            $totalMinutes,
            $totalMinutes
        ]);
        
        $pdo->commit();
        
        $_SESSION['success_message'] = "IISS session note saved successfully!";
        header("Location: client_detail.php?id=" . $_POST['client_id']);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error saving session note: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IISS Session Note - Scrive ACI</title>
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
            line-height: 1.6;
        }
        
        .header {
            background: white;
            padding: 1rem 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 1rem 2rem;
        }
        
        .form-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .form-title {
            font-size: 1.5rem;
            color: #059669;
            font-weight: 600;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        
        .required {
            color: #dc2626;
        }
        
        .form-control {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .goals-section {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        
        .goal-item {
            background: white;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border: 1px solid #e5e7eb;
        }
        
        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .goal-category {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .goal-text {
            color: #4b5563;
            margin-bottom: 0.5rem;
        }
        
        .progress-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .rating-scale {
            display: flex;
            gap: 0.5rem;
        }
        
        .rating-option {
            position: relative;
        }
        
        .rating-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .rating-option label {
            display: block;
            padding: 0.5rem 1rem;
            background: #f3f4f6;
            border: 2px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .rating-option input[type="radio"]:checked + label {
            background: #059669;
            color: white;
            border-color: #059669;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
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
            background: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .btn-outline {
            background: white;
            color: #059669;
            border: 1px solid #059669;
        }
        
        .btn-outline:hover {
            background: #f0fdf4;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .info-box {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            color: #0c4a6e;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .rating-scale {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 style="color: #059669;">📝 IISS Session Note</h1>
            <a href="/src/dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" id="sessionNoteForm">
            <!-- Session Information -->
            <div class="form-card">
                <div class="form-header">
                    <h2 class="form-title">Session Information</h2>
                    <span style="color: #6b7280; font-size: 0.875rem;">
                        <span class="required">*</span> Required fields
                    </span>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="client_id">Client <span class="required">*</span></label>
                        <select name="client_id" id="client_id" class="form-control" required 
                                onchange="if(this.value) window.location.href='?client_id='+this.value">
                            <option value="">Select a client...</option>
                            <?php foreach ($assignedClients as $assignedClient): ?>
                                <option value="<?php echo $assignedClient['id']; ?>" 
                                        <?php echo $clientId == $assignedClient['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($assignedClient['first_name'] . ' ' . $assignedClient['last_name']); ?>
                                    (MA: <?php echo substr($assignedClient['ma_number'], -4); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="session_date">Session Date <span class="required">*</span></label>
                        <input type="date" name="session_date" id="session_date" class="form-control" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="session_type">Session Type <span class="required">*</span></label>
                        <select name="session_type" id="session_type" class="form-control" required>
                            <option value="direct_service">Direct Service</option>
                            <option value="make_up">Make-up Session</option>
                            <option value="crisis_intervention">Crisis Intervention</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time">Start Time <span class="required">*</span></label>
                        <input type="time" name="start_time" id="start_time" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_time">End Time <span class="required">*</span></label>
                        <input type="time" name="end_time" id="end_time" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location <span class="required">*</span></label>
                        <select name="location" id="location" class="form-control" required>
                            <option value="">Select location...</option>
                            <option value="Home">Client's Home</option>
                            <option value="Community">Community Setting</option>
                            <option value="School">School</option>
                            <option value="Center">ACI Center</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Goals Addressed -->
            <?php if (!empty($goals)): ?>
                <div class="form-card">
                    <div class="form-header">
                        <h2 class="form-title">Goals Addressed</h2>
                    </div>
                    
                    <div class="info-box">
                        📊 Rate progress for each goal worked on during this session. Use the 1-5 scale where:
                        1 = No progress, 2 = Minimal, 3 = Moderate, 4 = Good, 5 = Goal met
                    </div>
                    
                    <div class="goals-section">
                        <?php foreach ($goals as $goal): ?>
                            <div class="goal-item">
                                <div class="goal-header">
                                    <div>
                                        <span class="goal-category">
                                            <?php echo ucfirst(str_replace('_', ' ', $goal['goal_category'])); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <input type="checkbox" name="goals[]" value="<?php echo $goal['id']; ?>" 
                                               id="goal_<?php echo $goal['id']; ?>"
                                               onchange="toggleGoalProgress(<?php echo $goal['id']; ?>)">
                                        <label for="goal_<?php echo $goal['id']; ?>">Worked on this goal</label>
                                    </div>
                                </div>
                                
                                <div class="goal-text">
                                    <strong>Goal:</strong> <?php echo htmlspecialchars($goal['goal_text']); ?>
                                </div>
                                
                                <div class="progress-inputs" id="progress_<?php echo $goal['id']; ?>" style="display: none;">
                                    <div class="form-group">
                                        <label>Progress Rating <span class="required">*</span></label>
                                        <div class="rating-scale">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <div class="rating-option">
                                                    <input type="radio" 
                                                           name="goal_progress[<?php echo $goal['id']; ?>][rating]" 
                                                           value="<?php echo $i; ?>" 
                                                           id="rating_<?php echo $goal['id']; ?>_<?php echo $i; ?>">
                                                    <label for="rating_<?php echo $goal['id']; ?>_<?php echo $i; ?>">
                                                        <?php echo $i; ?>
                                                    </label>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Trials Correct</label>
                                        <input type="number" 
                                               name="goal_progress[<?php echo $goal['id']; ?>][trials_correct]" 
                                               class="form-control" min="0">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Total Trials</label>
                                        <input type="number" 
                                               name="goal_progress[<?php echo $goal['id']; ?>][trials_total]" 
                                               class="form-control" min="0">
                                    </div>
                                    
                                    <div class="form-group" style="grid-column: 1 / -1;">
                                        <label>Progress Notes</label>
                                        <textarea name="goal_progress[<?php echo $goal['id']; ?>][notes]" 
                                                  class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Session Details -->
            <div class="form-card">
                <div class="form-header">
                    <h2 class="form-title">Session Details</h2>
                </div>
                
                <div class="form-group">
                    <label for="activities">Activities Performed <span class="required">*</span></label>
                    <textarea name="activities" id="activities" class="form-control" rows="4" required
                              placeholder="Describe the activities performed during the session..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="client_response">Client Response <span class="required">*</span></label>
                    <textarea name="client_response" id="client_response" class="form-control" rows="4" required
                              placeholder="How did the client respond to the activities and interventions?"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="progress_notes">Detailed Progress Notes</label>
                    <textarea name="progress_notes" id="progress_notes" class="form-control" rows="4"
                              placeholder="Additional observations about the client's progress..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="behavior_incidents">Behavior Incidents</label>
                    <textarea name="behavior_incidents" id="behavior_incidents" class="form-control" rows="3"
                              placeholder="Document any behavioral incidents, interventions used, and outcomes..."></textarea>
                </div>
            </div>
            
            <!-- Communication & Planning -->
            <div class="form-card">
                <div class="form-header">
                    <h2 class="form-title">Communication & Planning</h2>
                </div>
                
                <div class="form-group">
                    <label for="parent_communication">Parent/Caregiver Communication</label>
                    <textarea name="parent_communication" id="parent_communication" class="form-control" rows="3"
                              placeholder="Document any communication with parents or caregivers..."></textarea>
                </div>
                
                <div class="form-group">
                    <label for="next_session_plan">Next Session Planning</label>
                    <textarea name="next_session_plan" id="next_session_plan" class="form-control" rows="3"
                              placeholder="Plans and materials needed for the next session..."></textarea>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="button" class="btn btn-outline" onclick="saveDraft()">Save as Draft</button>
                <button type="submit" class="btn btn-primary">Submit Session Note</button>
            </div>
        </form>
    </div>
    
    <script>
        function toggleGoalProgress(goalId) {
            const checkbox = document.getElementById('goal_' + goalId);
            const progressDiv = document.getElementById('progress_' + goalId);
            
            if (checkbox.checked) {
                progressDiv.style.display = 'grid';
            } else {
                progressDiv.style.display = 'none';
                // Clear inputs when unchecked
                const inputs = progressDiv.querySelectorAll('input, textarea');
                inputs.forEach(input => {
                    if (input.type === 'radio') {
                        input.checked = false;
                    } else {
                        input.value = '';
                    }
                });
            }
        }
        
        function saveDraft() {
            // In a real implementation, this would save to draft status
            alert('Draft saving functionality would be implemented here');
        }
        
        // Calculate duration when times change
        document.getElementById('start_time').addEventListener('change', calculateDuration);
        document.getElementById('end_time').addEventListener('change', calculateDuration);
        
        function calculateDuration() {
            const start = document.getElementById('start_time').value;
            const end = document.getElementById('end_time').value;
            
            if (start && end) {
                const startTime = new Date('2000-01-01 ' + start);
                const endTime = new Date('2000-01-01 ' + end);
                const duration = (endTime - startTime) / 1000 / 60; // minutes
                
                if (duration > 0) {
                    console.log('Session duration:', duration, 'minutes');
                } else {
                    alert('End time must be after start time');
                }
            }
        }
        
        // Form validation
        document.getElementById('sessionNoteForm').addEventListener('submit', function(e) {
            // Check if at least one goal is selected
            const goalCheckboxes = document.querySelectorAll('input[name="goals[]"]:checked');
            if (goalCheckboxes.length === 0) {
                alert('Please select at least one goal worked on during this session');
                e.preventDefault();
                return;
            }
            
            // Validate that selected goals have ratings
            let missingRatings = false;
            goalCheckboxes.forEach(checkbox => {
                const goalId = checkbox.value;
                const ratingChecked = document.querySelector(`input[name="goal_progress[${goalId}][rating]"]:checked`);
                if (!ratingChecked) {
                    missingRatings = true;
                }
            });
            
            if (missingRatings) {
                alert('Please provide progress ratings for all selected goals');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html>