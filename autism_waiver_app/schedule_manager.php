<?php
session_start();
require_once '../src/config.php';
require_once '../src/openemr_integration.php';
require_once '../src/UrlManager.php';

// Strip .php extension if present
UrlManager::stripPhpExtension();

// Check authentication and permissions
if (!isset($_SESSION['user_id']) || $_SESSION['access_level'] < 3) {
    UrlManager::redirect('login');
}

try {
    $pdo = getDatabase();
    
    // Handle schedule creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_schedule'])) {
        $stmt = $pdo->prepare("
            INSERT INTO autism_schedules (staff_id, client_id, service_type_id, scheduled_date, start_time, end_time, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['staff_id'],
            $_POST['client_id'],
            $_POST['service_type_id'] ?: null,
            $_POST['scheduled_date'],
            $_POST['start_time'],
            $_POST['end_time'],
            $_POST['notes']
        ]);
        $success = "Schedule created successfully!";
    }
    
    // Get current week's schedules
    $week_start = $_GET['week'] ?? date('Y-m-d', strtotime('monday this week'));
    $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
    
    $stmt = $pdo->prepare("
        SELECT s.*, 
               sm.full_name as staff_name,
               c.first_name as client_first_name, c.last_name as client_last_name,
               st.service_name, st.service_code
        FROM autism_schedules s
        LEFT JOIN autism_staff_members sm ON s.staff_id = sm.id
        LEFT JOIN autism_clients c ON s.client_id = c.id
        LEFT JOIN autism_service_types st ON s.service_type_id = st.id
        WHERE s.scheduled_date BETWEEN ? AND ?
        ORDER BY s.scheduled_date, s.start_time
    ");
    $stmt->execute([$week_start, $week_end]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get staff members
    $stmt = $pdo->query("SELECT * FROM autism_staff_members ORDER BY full_name");
    $staff_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get clients
    $stmt = $pdo->query("SELECT * FROM autism_clients ORDER BY last_name, first_name");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get service types
    $stmt = $pdo->query("SELECT * FROM autism_service_types WHERE is_active = 1 ORDER BY service_name");
    $service_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize schedules by day
    $week_schedules = [];
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime($week_start . " +$i days"));
        $week_schedules[$date] = [];
    }
    
    foreach ($schedules as $schedule) {
        $week_schedules[$schedule['scheduled_date']][] = $schedule;
    }
    
} catch (Exception $e) {
    error_log("Schedule manager error: " . $e->getMessage());
    $schedules = [];
    $staff_members = [];
    $clients = [];
    $service_types = [];
    $week_schedules = [];
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Manager - ACI</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; }
        .header { background: white; padding: 1rem 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { color: #059669; }
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .section { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .btn { padding: 0.75rem 1.5rem; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .btn-primary { background: #059669; color: white; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        .week-nav { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .week-display { font-size: 1.25rem; font-weight: 600; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1rem; }
        .day-column { border: 1px solid #e5e7eb; border-radius: 8px; min-height: 400px; }
        .day-header { background: #f8fafc; padding: 1rem; text-align: center; font-weight: 600; border-bottom: 1px solid #e5e7eb; }
        .day-content { padding: 0.5rem; }
        .schedule-item { background: #e0f2fe; padding: 0.5rem; margin-bottom: 0.5rem; border-radius: 6px; font-size: 0.875rem; }
        .schedule-time { font-weight: 600; color: #059669; }
        .schedule-client { color: #1e293b; }
        .schedule-staff { color: #64748b; font-size: 0.75rem; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; margin: 5% auto; padding: 2rem; border-radius: 12px; max-width: 500px; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 8px; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .stats-row { display: flex; gap: 1rem; margin-bottom: 1rem; }
        .stat-card { background: #f8fafc; padding: 1rem; border-radius: 8px; text-align: center; flex: 1; }
        .stat-value { font-size: 1.5rem; font-weight: 700; color: #059669; }
        .stat-label { font-size: 0.875rem; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Schedule Manager</h1>
        <a href="<?= UrlManager::url('dashboard') ?>" style="color: #059669; text-decoration: none;">← Back to Dashboard</a>
    </div>
    
    <div class="container">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- Week Statistics -->
        <div class="section">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-value"><?= count($schedules) ?></div>
                    <div class="stat-label">Scheduled Sessions</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= count(array_unique(array_column($schedules, 'staff_id'))) ?></div>
                    <div class="stat-label">Staff Scheduled</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= count(array_unique(array_column($schedules, 'client_id'))) ?></div>
                    <div class="stat-label">Clients Scheduled</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?= array_sum(array_map(function($s) { 
                        return (strtotime($s['end_time']) - strtotime($s['start_time'])) / 3600; 
                    }, $schedules)) ?></div>
                    <div class="stat-label">Total Hours</div>
                </div>
            </div>
        </div>
        
        <!-- Week Navigation -->
        <div class="week-nav">
            <a href="?week=<?= date('Y-m-d', strtotime($week_start . ' -7 days')) ?>" class="btn btn-secondary">← Previous Week</a>
            <div class="week-display">
                <?= date('M j', strtotime($week_start)) ?> - <?= date('M j, Y', strtotime($week_end)) ?>
            </div>
            <div>
                <button class="btn btn-primary" onclick="showCreateModal()">+ New Schedule</button>
                <a href="?week=<?= date('Y-m-d', strtotime($week_start . ' +7 days')) ?>" class="btn btn-secondary">Next Week →</a>
            </div>
        </div>
        
        <!-- Weekly Calendar -->
        <div class="section">
            <div class="calendar-grid">
                <?php 
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                for ($i = 0; $i < 7; $i++): 
                    $date = date('Y-m-d', strtotime($week_start . " +$i days"));
                    $day_schedules = $week_schedules[$date] ?? [];
                ?>
                    <div class="day-column">
                        <div class="day-header">
                            <div><?= $days[$i] ?></div>
                            <div style="font-size: 0.875rem; color: #64748b;"><?= date('M j', strtotime($date)) ?></div>
                        </div>
                        <div class="day-content">
                            <?php if (empty($day_schedules)): ?>
                                <div style="text-align: center; color: #64748b; padding: 2rem; font-size: 0.875rem;">
                                    No sessions scheduled
                                </div>
                            <?php else: ?>
                                <?php foreach ($day_schedules as $schedule): ?>
                                    <div class="schedule-item">
                                        <div class="schedule-time">
                                            <?= date('g:i A', strtotime($schedule['start_time'])) ?> - 
                                            <?= date('g:i A', strtotime($schedule['end_time'])) ?>
                                        </div>
                                        <div class="schedule-client">
                                            <?= htmlspecialchars($schedule['client_first_name'] . ' ' . $schedule['client_last_name']) ?>
                                        </div>
                                        <div class="schedule-staff">
                                            <?= htmlspecialchars($schedule['staff_name'] ?? 'Unassigned') ?>
                                        </div>
                                        <?php if ($schedule['service_name']): ?>
                                            <div style="font-size: 0.75rem; color: #059669;">
                                                <?= htmlspecialchars($schedule['service_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    
    <!-- Create Schedule Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <h3>Create New Schedule</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Staff Member</label>
                    <select name="staff_id" required>
                        <option value="">Select Staff</option>
                        <?php foreach ($staff_members as $staff): ?>
                            <option value="<?= $staff['id'] ?>">
                                <?= htmlspecialchars($staff['full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Client</label>
                    <select name="client_id" required>
                        <option value="">Select Client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['id'] ?>">
                                <?= htmlspecialchars($client['last_name'] . ', ' . $client['first_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Service Type</label>
                    <select name="service_type_id">
                        <option value="">Select Service</option>
                        <?php foreach ($service_types as $service): ?>
                            <option value="<?= $service['id'] ?>">
                                <?= htmlspecialchars($service['service_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="scheduled_date" value="<?= $week_start ?>" required>
                </div>
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time" required>
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="end_time" required>
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="3" placeholder="Additional notes..."></textarea>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="hideCreateModal()">Cancel</button>
                    <button type="submit" name="create_schedule" class="btn btn-primary">Create Schedule</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showCreateModal() {
            document.getElementById('createModal').style.display = 'block';
        }
        
        function hideCreateModal() {
            document.getElementById('createModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('createModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>