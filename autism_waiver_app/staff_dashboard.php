<?php
require_once '../src/init.php';
requireAuth(1); // Technician+ access

$currentUser = getCurrentUser();

// This portal is for DSP/Technicians (level 1-2)
if ($currentUser['access_level'] > 2) {
    header('Location: ' . UrlManager::url('staff'));
    exit;
}

try {
    $pdo = getDatabase();
    
    $staff_id = $currentUser['id'];
    
    // Get all clients (simplified - no staff assignment table yet)
    $stmt = $pdo->query("SELECT * FROM autism_clients ORDER BY last_name, first_name");
    $myClients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get today's schedule
    $stmt = $pdo->prepare("
        SELECT s.*, c.first_name, c.last_name, st.service_name
        FROM autism_schedules s
        JOIN autism_clients c ON s.client_id = c.id
        LEFT JOIN autism_service_types st ON s.service_type_id = st.id
        WHERE s.staff_id = ? AND s.scheduled_date = CURDATE()
        ORDER BY s.start_time
    ");
    $stmt->execute([$staff_id]);
    $todaySchedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get time clock status
    $stmt = $pdo->prepare("
        SELECT * FROM autism_time_clock 
        WHERE employee_id = ? AND DATE(clock_in) = CURDATE() 
        ORDER BY clock_in DESC LIMIT 1
    ");
    $stmt->execute([$staff_id]);
    $currentClock = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent session notes
    $stmt = $pdo->prepare("
        SELECT sn.*, c.first_name, c.last_name
        FROM autism_session_notes sn
        JOIN autism_clients c ON sn.client_id = c.id
        WHERE sn.staff_id = ? 
        ORDER BY sn.session_date DESC, sn.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$staff_id]);
    $recentNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get hours this week
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT DATE(clock_in)) as days_worked,
            SUM(TIME_TO_SEC(TIMEDIFF(clock_out, clock_in))/3600) as total_hours
        FROM autism_time_clock
        WHERE employee_id = ? 
        AND WEEK(clock_in) = WEEK(CURRENT_DATE())
        AND clock_out IS NOT NULL
    ");
    $stmt->execute([$staff_id]);
    $weekStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Staff dashboard error: " . $e->getMessage());
    $myClients = [];
    $todaySchedule = [];
    $currentClock = null;
    $recentNotes = [];
    $weekStats = ['days_worked' => 0, 'total_hours' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal - ACI</title>
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
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logo-text {
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .logo-text .a { color: #1e40af; }
        .logo-text .c { color: #dc2626; }
        .logo-text .i { color: #16a34a; }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-name {
            font-weight: 500;
            color: #374151;
        }
        
        .logout-btn {
            padding: 0.5rem 1rem;
            background: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.875rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem;
        }
        
        .clock-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .clock-status {
            font-size: 1.125rem;
            margin-bottom: 1rem;
        }
        
        .clock-status.clocked-in {
            color: #059669;
        }
        
        .clock-status.clocked-out {
            color: #dc2626;
        }
        
        .clock-time {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
        }
        
        .clock-btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1.125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .clock-btn.clock-in {
            background: #059669;
            color: white;
        }
        
        .clock-btn.clock-out {
            background: #dc2626;
            color: white;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .action-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            text-decoration: none;
            color: #1e293b;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }
        
        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .action-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .action-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .action-desc {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1e293b;
        }
        
        .schedule-item {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .schedule-item:last-child {
            border-bottom: none;
        }
        
        .schedule-time {
            font-weight: 600;
            color: #059669;
            margin-bottom: 0.25rem;
        }
        
        .schedule-client {
            font-weight: 500;
            color: #1e293b;
        }
        
        .schedule-service {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .schedule-action {
            padding: 0.5rem 1rem;
            background: #059669;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.875rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-card {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #059669;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #64748b;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
            font-weight: 600;
            color: #374151;
        }
        
        td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-draft { background: #e5e7eb; color: #374151; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-approved { background: #a7f3d0; color: #047857; }
        
        @media (max-width: 768px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-text">
                    <span class="a">A</span><span class="c">C</span><span class="i">I</span>
                </div>
                <span style="color: #64748b;">Staff Portal</span>
            </div>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?></span>
                <a href="/logout" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Time Clock Section -->
        <div class="clock-section">
            <div id="current-time" class="clock-time"></div>
            <?php if ($currentClock && !$currentClock['clock_out']): ?>
                <div class="clock-status clocked-in">
                    Clocked in since <?= date('g:i A', strtotime($currentClock['clock_in'])) ?>
                </div>
                <button class="clock-btn clock-out" onclick="clockOut()">Clock Out</button>
            <?php else: ?>
                <div class="clock-status clocked-out">
                    Not clocked in
                </div>
                <button class="clock-btn clock-in" onclick="clockIn()">Clock In</button>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="/staff/notes" class="action-card">
                <div class="action-icon">📝</div>
                <div class="action-title">Session Notes</div>
                <div class="action-desc">Enter client session notes</div>
            </a>
            <a href="/staff/schedule" class="action-card">
                <div class="action-icon">📅</div>
                <div class="action-title">My Schedule</div>
                <div class="action-desc">View your assignments</div>
            </a>
            <a href="/staff/clients" class="action-card">
                <div class="action-icon">👥</div>
                <div class="action-title">My Clients</div>
                <div class="action-desc"><?= count($myClients) ?> assigned</div>
            </a>
            <a href="/staff/hours" class="action-card">
                <div class="action-icon">⏱️</div>
                <div class="action-title">My Hours</div>
                <div class="action-desc">Timesheet & payroll</div>
            </a>
        </div>
        
        <!-- Week Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= $weekStats['days_worked'] ?></div>
                <div class="stat-label">Days This Week</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($weekStats['total_hours'], 1) ?></div>
                <div class="stat-label">Hours This Week</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($todaySchedule) ?></div>
                <div class="stat-label">Sessions Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($myClients) ?></div>
                <div class="stat-label">Active Clients</div>
            </div>
        </div>
        
        <!-- Today's Schedule -->
        <div class="section">
            <h2 class="section-title">Today's Schedule</h2>
            <?php if (empty($todaySchedule)): ?>
                <div class="empty-state">
                    <p>No sessions scheduled for today</p>
                </div>
            <?php else: ?>
                <?php foreach ($todaySchedule as $session): ?>
                    <div class="schedule-item">
                        <div>
                            <div class="schedule-time">
                                <?= date('g:i A', strtotime($session['start_time'])) ?> - 
                                <?= date('g:i A', strtotime($session['end_time'])) ?>
                            </div>
                            <div class="schedule-client">
                                <?= htmlspecialchars($session['first_name'] . ' ' . $session['last_name']) ?>
                            </div>
                            <div class="schedule-service">
                                <?= htmlspecialchars($session['service_name'] ?? 'Service') ?>
                            </div>
                        </div>
                        <a href="/staff/notes?client_id=<?= $session['client_id'] ?>&schedule_id=<?= $session['id'] ?>" 
                           class="schedule-action">Add Note</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Recent Session Notes -->
        <div class="section">
            <h2 class="section-title">Recent Session Notes</h2>
            <?php if (empty($recentNotes)): ?>
                <div class="empty-state">
                    <p>No session notes yet</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Client</th>
                            <th>Session Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentNotes as $note): ?>
                            <tr>
                                <td><?= date('m/d/Y', strtotime($note['session_date'])) ?></td>
                                <td><?= htmlspecialchars($note['last_name'] . ', ' . $note['first_name']) ?></td>
                                <td><?= htmlspecialchars($note['session_type'] ?? 'Session') ?></td>
                                <td>
                                    <span class="status-badge status-<?= $note['status'] ?>">
                                        <?= ucfirst($note['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/staff/notes/edit/<?= $note['id'] ?>" style="color: #059669;">
                                        <?= $note['status'] === 'draft' ? 'Complete' : 'View' ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('current-time').textContent = timeStr;
        }
        
        updateTime();
        setInterval(updateTime, 1000);
        
        // Clock in/out functions
        function clockIn() {
            fetch('/api/time-clock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'clock_in' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to clock in: ' + data.message);
                }
            });
        }
        
        function clockOut() {
            fetch('/api/time-clock', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'clock_out' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to clock out: ' + data.message);
                }
            });
        }
    </script>
</body>
</html>