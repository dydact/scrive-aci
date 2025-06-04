<?php
session_start();
require_once '../src/config.php';
require_once '../src/openemr_integration.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

$error = '';
$success = '';
$session = null;

try {
    $pdo = getDatabase();
    
    // Get session ID
    $session_id = $_GET['id'] ?? 0;
    
    if (!$session_id) {
        throw new Exception("Session ID required");
    }
    
    // Get session details with client and service info
    $stmt = $pdo->prepare("
        SELECT s.*, 
               c.first_name, c.last_name, c.ma_number,
               st.service_name, st.service_code,
               sm.full_name as staff_name
        FROM autism_sessions s
        JOIN autism_clients c ON s.client_id = c.id
        LEFT JOIN autism_service_types st ON s.service_type_id = st.id
        LEFT JOIN autism_staff_members sm ON s.staff_id = sm.id
        WHERE s.id = ?
    ");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$session) {
        throw new Exception("Session not found");
    }
    
    // Check permissions - staff can only edit their own sessions, supervisors can edit any
    if ($_SESSION['access_level'] < 4 && $session['created_by'] != $_SESSION['user_id']) {
        throw new Exception("You don't have permission to edit this session");
    }
    
    // Check if session is locked (older than 48 hours)
    $session_date = new DateTime($session['session_date'] . ' ' . $session['end_time']);
    $now = new DateTime();
    $diff = $now->diff($session_date);
    $hours_passed = ($diff->days * 24) + $diff->h;
    
    if ($hours_passed > 48 && $_SESSION['access_level'] < 4) {
        throw new Exception("Sessions older than 48 hours cannot be edited without supervisor approval");
    }
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate required fields
        if (empty($_POST['session_date']) || empty($_POST['start_time']) || empty($_POST['end_time'])) {
            throw new Exception("Session date and times are required");
        }
        
        // Calculate duration
        $start = new DateTime($_POST['session_date'] . ' ' . $_POST['start_time']);
        $end = new DateTime($_POST['session_date'] . ' ' . $_POST['end_time']);
        $duration = $start->diff($end);
        $duration_hours = $duration->h + ($duration->i / 60);
        
        // Update session
        $stmt = $pdo->prepare("
            UPDATE autism_sessions SET
                session_date = ?,
                start_time = ?,
                end_time = ?,
                duration_hours = ?,
                session_type = ?,
                location = ?,
                goals_addressed = ?,
                interventions = ?,
                client_response = ?,
                notes = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['session_date'],
            $_POST['start_time'],
            $_POST['end_time'],
            $duration_hours,
            $_POST['session_type'] ?? null,
            $_POST['location'] ?? null,
            $_POST['goals_addressed'] ?? null,
            $_POST['interventions'] ?? null,
            $_POST['client_response'] ?? null,
            $_POST['notes'] ?? null,
            $_POST['status'] ?? 'completed',
            $session_id
        ]);
        
        // Log the edit
        $stmt = $pdo->prepare("
            INSERT INTO autism_audit_log (user_id, action, table_name, record_id, details)
            VALUES (?, 'UPDATE', 'autism_sessions', ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $session_id,
            "Session edited for {$session['first_name']} {$session['last_name']} on {$_POST['session_date']}"
        ]);
        
        $success = "Session updated successfully!";
        
        // Refresh session data
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   c.first_name, c.last_name, c.ma_number,
                   st.service_name, st.service_code,
                   sm.full_name as staff_name
            FROM autism_sessions s
            JOIN autism_clients c ON s.client_id = c.id
            LEFT JOIN autism_service_types st ON s.service_type_id = st.id
            LEFT JOIN autism_staff_members sm ON s.staff_id = sm.id
            WHERE s.id = ?
        ");
        $stmt->execute([$session_id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get service types for dropdown
    $stmt = $pdo->query("SELECT * FROM autism_service_types WHERE is_active = 1 ORDER BY service_name");
    $service_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Session - ACI</title>
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
            margin-bottom: 2rem;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.5rem;
            color: #1e40af;
        }
        
        .nav-links {
            display: flex;
            gap: 1rem;
        }
        
        .nav-links a {
            color: #64748b;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .nav-links a:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .alert-warning {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        
        .session-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .client-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .info-item {
            padding: 1rem;
            background: #f8fafc;
            border-radius: 6px;
        }
        
        .info-label {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: 600;
            color: #1e293b;
        }
        
        .form-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .form-section:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 1rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #475569;
        }
        
        .required::after {
            content: " *";
            color: #dc2626;
        }
        
        input[type="text"],
        input[type="date"],
        input[type="time"],
        select,
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }
        
        .btn-secondary:hover {
            background: #cbd5e1;
        }
        
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b91c1c;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-completed {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .status-scheduled {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .status-no_show {
            background: #fef3c7;
            color: #d97706;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1>Edit Session</h1>
                <div class="nav-links">
                    <a href="/staff/dashboard">Dashboard</a>
                    <a href="/staff/notes">Session Notes</a>
                    <a href="/staff/hours">My Hours</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($session): ?>
            <?php if ($hours_passed > 24 && $_SESSION['access_level'] < 4): ?>
                <div class="alert alert-warning">
                    <strong>Warning:</strong> This session is older than 24 hours. Changes require supervisor approval.
                </div>
            <?php endif; ?>
            
            <div class="session-header">
                <h2>Session Details</h2>
                <div class="client-info">
                    <div class="info-item">
                        <div class="info-label">Client</div>
                        <div class="info-value"><?= htmlspecialchars($session['first_name'] . ' ' . $session['last_name']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">MA Number</div>
                        <div class="info-value"><?= htmlspecialchars($session['ma_number'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Staff</div>
                        <div class="info-value"><?= htmlspecialchars($session['staff_name'] ?? 'Unassigned') ?></div>
                    </div>
                </div>
            </div>
            
            <form method="POST" class="form-card">
                <div class="form-section">
                    <h3 class="section-title">Session Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="session_date" class="required">Session Date</label>
                            <input type="date" id="session_date" name="session_date" 
                                   value="<?= htmlspecialchars($session['session_date']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="start_time" class="required">Start Time</label>
                            <input type="time" id="start_time" name="start_time" 
                                   value="<?= htmlspecialchars($session['start_time']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="end_time" class="required">End Time</label>
                            <input type="time" id="end_time" name="end_time" 
                                   value="<?= htmlspecialchars($session['end_time']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="service_type_id">Service Type</label>
                            <select id="service_type_id" name="service_type_id">
                                <option value="">Select Service</option>
                                <?php foreach ($service_types as $st): ?>
                                    <option value="<?= $st['id'] ?>" 
                                            <?= $st['id'] == $session['service_type_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($st['service_name'] . ' (' . $st['service_code'] . ')') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="session_type">Session Type</label>
                            <select id="session_type" name="session_type">
                                <option value="">Select Type</option>
                                <option value="in-person" <?= $session['session_type'] == 'in-person' ? 'selected' : '' ?>>In-Person</option>
                                <option value="telehealth" <?= $session['session_type'] == 'telehealth' ? 'selected' : '' ?>>Telehealth</option>
                                <option value="community" <?= $session['session_type'] == 'community' ? 'selected' : '' ?>>Community</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="location">Location</label>
                            <input type="text" id="location" name="location" 
                                   value="<?= htmlspecialchars($session['location'] ?? '') ?>" 
                                   placeholder="Home, School, Community, etc.">
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="scheduled" <?= $session['status'] == 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                                <option value="completed" <?= $session['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $session['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="no_show" <?= $session['status'] == 'no_show' ? 'selected' : '' ?>>No Show</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title">Clinical Documentation</h3>
                    <div class="form-group">
                        <label for="goals_addressed">Goals Addressed</label>
                        <textarea id="goals_addressed" name="goals_addressed" rows="3"><?= htmlspecialchars($session['goals_addressed'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="interventions">Interventions Provided</label>
                        <textarea id="interventions" name="interventions" rows="3"><?= htmlspecialchars($session['interventions'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="client_response">Client Response</label>
                        <textarea id="client_response" name="client_response" rows="3"><?= htmlspecialchars($session['client_response'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="notes">Additional Notes</label>
                        <textarea id="notes" name="notes" rows="4"><?= htmlspecialchars($session['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Session</button>
                    <a href="/staff/notes" class="btn btn-secondary">Cancel</a>
                    <?php if ($_SESSION['access_level'] >= 4): ?>
                        <button type="button" class="btn btn-danger" onclick="if(confirm('Are you sure you want to delete this session?')) { window.location.href='delete_session.php?id=<?= $session_id ?>'; }">
                            Delete Session
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>