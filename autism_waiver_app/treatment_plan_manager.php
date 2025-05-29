<?php
session_start();
require_once 'auth_helper.php';
require_once 'config.php';

// Check if user is logged in and has appropriate access (Case Manager or higher)
if (!isLoggedIn() || $_SESSION['access_level'] < 3) {
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
$planId = $_GET['plan_id'] ?? null;

// Load client information
$client = null;
if ($clientId) {
    $stmt = $pdo->prepare("
        SELECT c.*, 
               TIMESTAMPDIFF(YEAR, c.date_of_birth, CURDATE()) as age,
               ce.program_type
        FROM autism_clients c
        LEFT JOIN autism_client_enrollments ce ON c.id = ce.client_id AND ce.status = 'active'
        WHERE c.id = ?
    ");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Load existing plan if editing
$plan = null;
$goals = [];
if ($planId) {
    $stmt = $pdo->prepare("SELECT * FROM autism_treatment_plans WHERE id = ?");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Load goals
    $stmt = $pdo->prepare("
        SELECT g.*, 
               COUNT(gp.id) as progress_entries,
               AVG(gp.progress_rating) as avg_rating,
               MAX(gp.progress_date) as last_progress_date
        FROM autism_treatment_goals g
        LEFT JOIN autism_goal_progress gp ON g.id = gp.goal_id
        WHERE g.plan_id = ?
        GROUP BY g.id
        ORDER BY g.goal_category, g.id
    ");
    $stmt->execute([$planId]);
    $goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all clients for dropdown
$clients = [];
if ($_SESSION['access_level'] >= 3) {
    $stmt = $pdo->query("
        SELECT id, first_name, last_name, ma_number 
        FROM autism_clients 
        WHERE status = 'active'
        ORDER BY last_name, first_name
    ");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        if ($planId) {
            // Update existing plan
            $stmt = $pdo->prepare("
                UPDATE autism_treatment_plans 
                SET plan_name = ?, plan_type = ?, start_date = ?, end_date = ?,
                    status = ?, notes = ?, updated_by = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['plan_name'],
                $_POST['plan_type'],
                $_POST['start_date'],
                $_POST['end_date'],
                $_POST['status'],
                $_POST['notes'],
                $_SESSION['user_id'],
                $planId
            ]);
        } else {
            // Create new plan
            $stmt = $pdo->prepare("
                INSERT INTO autism_treatment_plans (
                    client_id, plan_name, plan_type, start_date, end_date,
                    status, notes, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $_POST['client_id'],
                $_POST['plan_name'],
                $_POST['plan_type'],
                $_POST['start_date'],
                $_POST['end_date'],
                $_POST['status'],
                $_POST['notes'],
                $_SESSION['user_id']
            ]);
            $planId = $pdo->lastInsertId();
        }
        
        // Handle goals
        if (isset($_POST['goals']) && is_array($_POST['goals'])) {
            // Update existing goals
            foreach ($_POST['goals'] as $goalId => $goalData) {
                if (is_numeric($goalId)) {
                    $stmt = $pdo->prepare("
                        UPDATE autism_treatment_goals 
                        SET goal_category = ?, goal_text = ?, objective = ?,
                            baseline = ?, target_date = ?, target_criteria = ?,
                            measurement_method = ?, frequency = ?, status = ?
                        WHERE id = ? AND plan_id = ?
                    ");
                    $stmt->execute([
                        $goalData['category'],
                        $goalData['text'],
                        $goalData['objective'],
                        $goalData['baseline'],
                        $goalData['target_date'],
                        $goalData['target_criteria'],
                        $goalData['measurement_method'],
                        $goalData['frequency'],
                        $goalData['status'],
                        $goalId,
                        $planId
                    ]);
                }
            }
        }
        
        // Add new goals
        if (isset($_POST['new_goals']) && is_array($_POST['new_goals'])) {
            $stmt = $pdo->prepare("
                INSERT INTO autism_treatment_goals (
                    plan_id, goal_category, goal_text, objective, baseline,
                    target_date, target_criteria, measurement_method, frequency,
                    status, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($_POST['new_goals'] as $newGoal) {
                if (!empty($newGoal['text'])) {
                    $stmt->execute([
                        $planId,
                        $newGoal['category'],
                        $newGoal['text'],
                        $newGoal['objective'],
                        $newGoal['baseline'],
                        $newGoal['target_date'],
                        $newGoal['target_criteria'],
                        $newGoal['measurement_method'],
                        $newGoal['frequency'],
                        'active',
                        $_SESSION['user_id']
                    ]);
                }
            }
        }
        
        $pdo->commit();
        
        $_SESSION['success_message'] = "Treatment plan saved successfully!";
        header("Location: treatment_plan_manager.php?plan_id=" . $planId);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error saving treatment plan: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treatment Plan Manager - Scrive ACI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css">
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem 2rem;
        }
        
        .plan-header {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .client-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 6px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: 600;
            color: #1f2937;
        }
        
        .tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .tab {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            color: #6b7280;
            font-weight: 500;
            cursor: pointer;
            position: relative;
            transition: color 0.3s;
        }
        
        .tab.active {
            color: #059669;
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #059669;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        
        .goal-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .goal-category-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: #dbeafe;
            color: #1e40af;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .goal-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            padding: 0.5rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-icon:hover {
            background: #f3f4f6;
        }
        
        .progress-chart {
            height: 200px;
            margin-top: 1rem;
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
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border: 1px solid #a7f3d0;
        }
        
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 style="color: #059669;">📋 Treatment Plan Manager</h1>
            <a href="/src/dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Client Selection or Info -->
        <?php if ($client): ?>
            <div class="plan-header">
                <h2>Treatment Plan for <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?></h2>
                <div class="client-info">
                    <div class="info-item">
                        <span class="info-label">MA Number</span>
                        <span class="info-value"><?php echo htmlspecialchars($client['ma_number']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Age</span>
                        <span class="info-value"><?php echo $client['age']; ?> years</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Program</span>
                        <span class="info-value"><?php echo htmlspecialchars($client['program_type'] ?? 'Not enrolled'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Plan Status</span>
                        <span class="info-value"><?php echo $plan ? ucfirst($plan['status']) : 'No active plan'; ?></span>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="form-card">
                <h2>Select a Client</h2>
                <form method="GET">
                    <div class="form-group">
                        <label for="client_id">Client <span class="required">*</span></label>
                        <select name="client_id" id="client_id" class="form-control" required onchange="this.form.submit()">
                            <option value="">Choose a client...</option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?>
                                    (MA: <?php echo substr($c['ma_number'], -4); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <?php if ($client): ?>
            <!-- Tabs -->
            <div class="tabs">
                <button class="tab active" onclick="showTab('plan-info')">Plan Information</button>
                <button class="tab" onclick="showTab('goals')">Goals & Objectives</button>
                <?php if ($plan): ?>
                    <button class="tab" onclick="showTab('progress')">Progress Tracking</button>
                <?php endif; ?>
            </div>
            
            <form method="POST">
                <input type="hidden" name="client_id" value="<?php echo $clientId; ?>">
                
                <!-- Plan Information Tab -->
                <div class="tab-content active" id="plan-info-tab">
                    <div class="form-card">
                        <h3>Plan Details</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="plan_name">Plan Name <span class="required">*</span></label>
                                <input type="text" name="plan_name" id="plan_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($plan['plan_name'] ?? 'Autism Waiver Treatment Plan'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="plan_type">Plan Type <span class="required">*</span></label>
                                <select name="plan_type" id="plan_type" class="form-control" required>
                                    <option value="initial" <?php echo ($plan['plan_type'] ?? '') == 'initial' ? 'selected' : ''; ?>>Initial Plan</option>
                                    <option value="annual" <?php echo ($plan['plan_type'] ?? '') == 'annual' ? 'selected' : ''; ?>>Annual Review</option>
                                    <option value="revision" <?php echo ($plan['plan_type'] ?? '') == 'revision' ? 'selected' : ''; ?>>Plan Revision</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status <span class="required">*</span></label>
                                <select name="status" id="status" class="form-control" required>
                                    <option value="draft" <?php echo ($plan['status'] ?? 'draft') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="active" <?php echo ($plan['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="review" <?php echo ($plan['status'] ?? '') == 'review' ? 'selected' : ''; ?>>Under Review</option>
                                    <option value="expired" <?php echo ($plan['status'] ?? '') == 'expired' ? 'selected' : ''; ?>>Expired</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date">Start Date <span class="required">*</span></label>
                                <input type="date" name="start_date" id="start_date" class="form-control" required
                                       value="<?php echo htmlspecialchars($plan['start_date'] ?? date('Y-m-d')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="end_date">End Date <span class="required">*</span></label>
                                <input type="date" name="end_date" id="end_date" class="form-control" required
                                       value="<?php echo htmlspecialchars($plan['end_date'] ?? date('Y-m-d', strtotime('+1 year'))); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Plan Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="4"><?php echo htmlspecialchars($plan['notes'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Goals Tab -->
                <div class="tab-content" id="goals-tab">
                    <div class="form-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h3>Treatment Goals</h3>
                            <button type="button" class="btn btn-primary" onclick="addNewGoal()">+ Add Goal</button>
                        </div>
                        
                        <div id="existing-goals">
                            <?php foreach ($goals as $goal): ?>
                                <div class="goal-card">
                                    <div class="goal-header">
                                        <span class="goal-category-badge">
                                            <?php echo ucfirst(str_replace('_', ' ', $goal['goal_category'])); ?>
                                        </span>
                                        <div class="goal-actions">
                                            <?php if ($goal['progress_entries'] > 0): ?>
                                                <span style="color: #059669; font-size: 0.875rem;">
                                                    📊 <?php echo $goal['progress_entries']; ?> progress entries
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group" style="grid-column: 1 / -1;">
                                            <label>Goal Text</label>
                                            <textarea name="goals[<?php echo $goal['id']; ?>][text]" class="form-control" required><?php echo htmlspecialchars($goal['goal_text']); ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Category</label>
                                            <select name="goals[<?php echo $goal['id']; ?>][category]" class="form-control">
                                                <option value="communication" <?php echo $goal['goal_category'] == 'communication' ? 'selected' : ''; ?>>Communication</option>
                                                <option value="social_skills" <?php echo $goal['goal_category'] == 'social_skills' ? 'selected' : ''; ?>>Social Skills</option>
                                                <option value="daily_living" <?php echo $goal['goal_category'] == 'daily_living' ? 'selected' : ''; ?>>Daily Living</option>
                                                <option value="behavior" <?php echo $goal['goal_category'] == 'behavior' ? 'selected' : ''; ?>>Behavior</option>
                                                <option value="academic" <?php echo $goal['goal_category'] == 'academic' ? 'selected' : ''; ?>>Academic</option>
                                                <option value="recreation" <?php echo $goal['goal_category'] == 'recreation' ? 'selected' : ''; ?>>Recreation</option>
                                                <option value="adaptive" <?php echo $goal['goal_category'] == 'adaptive' ? 'selected' : ''; ?>>Adaptive</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Target Date</label>
                                            <input type="date" name="goals[<?php echo $goal['id']; ?>][target_date]" class="form-control" 
                                                   value="<?php echo htmlspecialchars($goal['target_date']); ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Status</label>
                                            <select name="goals[<?php echo $goal['id']; ?>][status]" class="form-control">
                                                <option value="active" <?php echo $goal['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="achieved" <?php echo $goal['status'] == 'achieved' ? 'selected' : ''; ?>>Achieved</option>
                                                <option value="discontinued" <?php echo $goal['status'] == 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                                                <option value="modified" <?php echo $goal['status'] == 'modified' ? 'selected' : ''; ?>>Modified</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Measurable Objective</label>
                                            <textarea name="goals[<?php echo $goal['id']; ?>][objective]" class="form-control" rows="2"><?php echo htmlspecialchars($goal['objective']); ?></textarea>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Current Baseline</label>
                                            <textarea name="goals[<?php echo $goal['id']; ?>][baseline]" class="form-control" rows="2"><?php echo htmlspecialchars($goal['baseline']); ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Target Criteria</label>
                                            <input type="text" name="goals[<?php echo $goal['id']; ?>][target_criteria]" class="form-control" 
                                                   value="<?php echo htmlspecialchars($goal['target_criteria']); ?>"
                                                   placeholder="e.g., 80% accuracy over 3 sessions">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Measurement Method</label>
                                            <input type="text" name="goals[<?php echo $goal['id']; ?>][measurement_method]" class="form-control" 
                                                   value="<?php echo htmlspecialchars($goal['measurement_method']); ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Frequency</label>
                                            <input type="text" name="goals[<?php echo $goal['id']; ?>][frequency]" class="form-control" 
                                                   value="<?php echo htmlspecialchars($goal['frequency']); ?>"
                                                   placeholder="e.g., Daily, 3x per week">
                                        </div>
                                    </div>
                                    
                                    <?php if ($goal['avg_rating']): ?>
                                        <div style="margin-top: 1rem; padding: 1rem; background: #f0fdf4; border-radius: 6px;">
                                            <strong>Progress:</strong> Average rating <?php echo number_format($goal['avg_rating'], 1); ?>/5
                                            (Last updated: <?php echo date('m/d/Y', strtotime($goal['last_progress_date'])); ?>)
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div id="new-goals"></div>
                    </div>
                </div>
                
                <!-- Progress Tracking Tab -->
                <?php if ($plan): ?>
                    <div class="tab-content" id="progress-tab">
                        <div class="form-card">
                            <h3>Goal Progress Overview</h3>
                            <canvas id="progressChart" class="progress-chart"></canvas>
                        </div>
                        
                        <div class="form-card">
                            <h3>Recent Progress Entries</h3>
                            <?php
                            // Load recent progress entries
                            $stmt = $pdo->prepare("
                                SELECT gp.*, g.goal_text, g.goal_category,
                                       CONCAT(u.first_name, ' ', u.last_name) as recorded_by_name
                                FROM autism_goal_progress gp
                                JOIN autism_treatment_goals g ON gp.goal_id = g.id
                                JOIN autism_users u ON gp.recorded_by = u.id
                                WHERE g.plan_id = ?
                                ORDER BY gp.progress_date DESC
                                LIMIT 10
                            ");
                            $stmt->execute([$planId]);
                            $progressEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            
                            <?php if (empty($progressEntries)): ?>
                                <p style="color: #6b7280;">No progress entries yet. Progress will be tracked through IISS session notes.</p>
                            <?php else: ?>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr style="border-bottom: 2px solid #e5e7eb;">
                                            <th style="padding: 0.75rem; text-align: left;">Date</th>
                                            <th style="padding: 0.75rem; text-align: left;">Goal</th>
                                            <th style="padding: 0.75rem; text-align: center;">Rating</th>
                                            <th style="padding: 0.75rem; text-align: center;">Trials</th>
                                            <th style="padding: 0.75rem; text-align: left;">Recorded By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($progressEntries as $entry): ?>
                                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                                <td style="padding: 0.75rem;"><?php echo date('m/d/Y', strtotime($entry['progress_date'])); ?></td>
                                                <td style="padding: 0.75rem;">
                                                    <span class="goal-category-badge" style="font-size: 0.75rem;">
                                                        <?php echo ucfirst(str_replace('_', ' ', $entry['goal_category'])); ?>
                                                    </span><br>
                                                    <?php echo htmlspecialchars(substr($entry['goal_text'], 0, 50)) . '...'; ?>
                                                </td>
                                                <td style="padding: 0.75rem; text-align: center;">
                                                    <strong><?php echo $entry['progress_rating']; ?>/5</strong>
                                                </td>
                                                <td style="padding: 0.75rem; text-align: center;">
                                                    <?php if ($entry['trials_total']): ?>
                                                        <?php echo $entry['trials_correct']; ?>/<?php echo $entry['trials_total']; ?>
                                                        (<?php echo number_format($entry['percentage'], 0); ?>%)
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 0.75rem;"><?php echo htmlspecialchars($entry['recorded_by_name']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Treatment Plan</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        let newGoalCount = 0;
        
        function addNewGoal() {
            newGoalCount++;
            const container = document.getElementById('new-goals');
            
            const goalHtml = `
                <div class="goal-card" id="new-goal-${newGoalCount}">
                    <div class="goal-header">
                        <span class="goal-category-badge">New Goal</span>
                        <button type="button" class="btn-icon" onclick="removeNewGoal(${newGoalCount})">✕</button>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Goal Text <span class="required">*</span></label>
                            <textarea name="new_goals[${newGoalCount}][text]" class="form-control" required></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="new_goals[${newGoalCount}][category]" class="form-control">
                                <option value="communication">Communication</option>
                                <option value="social_skills">Social Skills</option>
                                <option value="daily_living">Daily Living</option>
                                <option value="behavior">Behavior</option>
                                <option value="academic">Academic</option>
                                <option value="recreation">Recreation</option>
                                <option value="adaptive">Adaptive</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Target Date</label>
                            <input type="date" name="new_goals[${newGoalCount}][target_date]" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Measurable Objective</label>
                            <textarea name="new_goals[${newGoalCount}][objective]" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Current Baseline</label>
                            <textarea name="new_goals[${newGoalCount}][baseline]" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Target Criteria</label>
                            <input type="text" name="new_goals[${newGoalCount}][target_criteria]" class="form-control" 
                                   placeholder="e.g., 80% accuracy over 3 sessions">
                        </div>
                        
                        <div class="form-group">
                            <label>Measurement Method</label>
                            <input type="text" name="new_goals[${newGoalCount}][measurement_method]" class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Frequency</label>
                            <input type="text" name="new_goals[${newGoalCount}][frequency]" class="form-control" 
                                   placeholder="e.g., Daily, 3x per week">
                        </div>
                    </div>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', goalHtml);
        }
        
        function removeNewGoal(id) {
            document.getElementById('new-goal-' + id).remove();
        }
        
        <?php if ($plan && !empty($goals)): ?>
        // Progress Chart
        const ctx = document.getElementById('progressChart');
        if (ctx) {
            // Prepare data for chart
            const goalLabels = <?php echo json_encode(array_map(function($g) { 
                return substr($g['goal_text'], 0, 30) . '...'; 
            }, $goals)); ?>;
            
            const avgRatings = <?php echo json_encode(array_map(function($g) { 
                return $g['avg_rating'] ?: 0; 
            }, $goals)); ?>;
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: goalLabels,
                    datasets: [{
                        label: 'Average Progress Rating',
                        data: avgRatings,
                        backgroundColor: 'rgba(5, 150, 105, 0.6)',
                        borderColor: 'rgba(5, 150, 105, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 5,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>