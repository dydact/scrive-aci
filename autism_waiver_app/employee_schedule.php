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
$schedules = [];
$week_start = $_GET['week'] ?? date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));

try {
    $pdo = getDatabase();
    
    // Get current staff member ID
    $stmt = $pdo->prepare("SELECT id FROM autism_staff_members WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff) {
        throw new Exception("Staff member profile not found. Please contact administrator.");
    }
    
    $staff_id = $staff['id'];
    
    // Get schedules for the week
    $stmt = $pdo->prepare("
        SELECT s.*, 
               c.first_name as client_first_name, c.last_name as client_last_name,
               c.phone as client_phone, c.address as client_address,
               st.service_name, st.service_code,
               sess.id as session_id, sess.status as session_status
        FROM autism_schedules s
        JOIN autism_clients c ON s.client_id = c.id
        LEFT JOIN autism_service_types st ON s.service_type_id = st.id
        LEFT JOIN autism_sessions sess ON (
            sess.client_id = s.client_id 
            AND sess.staff_id = s.staff_id 
            AND sess.session_date = s.scheduled_date
            AND sess.start_time = s.start_time
        )
        WHERE s.staff_id = ? 
        AND s.scheduled_date BETWEEN ? AND ?
        ORDER BY s.scheduled_date, s.start_time
    ");
    $stmt->execute([$staff_id, $week_start, $week_end]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group schedules by date
    $schedules_by_date = [];
    foreach ($schedules as $schedule) {
        $date = $schedule['scheduled_date'];
        if (!isset($schedules_by_date[$date])) {
            $schedules_by_date[$date] = [];
        }
        $schedules_by_date[$date][] = $schedule;
    }
    
    // Calculate weekly statistics
    $total_hours = 0;
    $completed_sessions = 0;
    $pending_sessions = 0;
    
    foreach ($schedules as $schedule) {
        $start = new DateTime($schedule['start_time']);
        $end = new DateTime($schedule['end_time']);
        $duration = $start->diff($end);
        $hours = $duration->h + ($duration->i / 60);
        $total_hours += $hours;
        
        if ($schedule['session_status'] == 'completed') {
            $completed_sessions++;
        } else if ($schedule['scheduled_date'] >= date('Y-m-d')) {
            $pending_sessions++;
        }
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Generate calendar days for the week
$calendar_days = [];
$current_date = new DateTime($week_start);
for ($i = 0; $i < 7; $i++) {
    $calendar_days[] = $current_date->format('Y-m-d');
    $current_date->add(new DateInterval('P1D'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - ACI</title>
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
        
        .nav-links a:hover,
        .nav-links a.active {
            background: #f1f5f9;
            color: #1e293b;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .week-navigation {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .week-nav-btn {
            padding: 0.5rem 1rem;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .week-nav-btn:hover {
            background: #2563eb;
        }
        
        .week-label {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e40af;
        }
        
        .stat-label {
            color: #64748b;
            margin-top: 0.5rem;
        }
        
        .schedule-grid {
            display: grid;
            gap: 1rem;
        }
        
        .day-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .day-header {
            background: #1e40af;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .day-header.today {
            background: #059669;
        }
        
        .day-header.past {
            background: #64748b;
        }
        
        .day-date {
            font-size: 1.125rem;
            font-weight: 600;
        }
        
        .day-name {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .schedule-list {
            padding: 1rem;
        }
        
        .schedule-item {
            padding: 1rem;
            background: #f8fafc;
            border-radius: 6px;
            margin-bottom: 0.75rem;
            border-left: 4px solid #3b82f6;
            transition: all 0.3s;
        }
        
        .schedule-item:last-child {
            margin-bottom: 0;
        }
        
        .schedule-item:hover {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transform: translateX(4px);
        }
        
        .schedule-item.completed {
            border-left-color: #16a34a;
            opacity: 0.8;
        }
        
        .schedule-item.cancelled {
            border-left-color: #dc2626;
            opacity: 0.6;
        }
        
        .schedule-time {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .schedule-client {
            font-size: 1.125rem;
            color: #1e40af;
            margin-bottom: 0.25rem;
        }
        
        .schedule-service {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .schedule-address {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .schedule-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }
        
        .schedule-btn {
            padding: 0.375rem 0.75rem;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.875rem;
            transition: all 0.3s;
        }
        
        .schedule-btn:hover {
            background: #2563eb;
        }
        
        .schedule-btn.secondary {
            background: #e2e8f0;
            color: #475569;
        }
        
        .schedule-btn.secondary:hover {
            background: #cbd5e1;
        }
        
        .schedule-btn.success {
            background: #16a34a;
        }
        
        .schedule-btn.success:hover {
            background: #15803d;
        }
        
        .no-schedules {
            text-align: center;
            color: #64748b;
            padding: 2rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .mobile-view {
            display: none;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .week-navigation {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .desktop-view {
                display: none;
            }
            
            .mobile-view {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1>My Schedule</h1>
                <div class="nav-links">
                    <a href="/staff/dashboard">Dashboard</a>
                    <a href="/staff/schedule" class="active">Schedule</a>
                    <a href="/staff/clock">Time Clock</a>
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
        
        <div class="week-navigation">
            <a href="?week=<?= date('Y-m-d', strtotime($week_start . ' -7 days')) ?>" class="week-nav-btn">← Previous Week</a>
            <div class="week-label">
                Week of <?= date('F j, Y', strtotime($week_start)) ?>
            </div>
            <a href="?week=<?= date('Y-m-d', strtotime($week_start . ' +7 days')) ?>" class="week-nav-btn">Next Week →</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?= count($schedules) ?></div>
                <div class="stat-label">Total Sessions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= number_format($total_hours, 1) ?></div>
                <div class="stat-label">Scheduled Hours</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $completed_sessions ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        
        <div class="schedule-grid">
            <?php foreach ($calendar_days as $date): ?>
                <?php 
                    $day_schedules = $schedules_by_date[$date] ?? [];
                    $is_today = $date == date('Y-m-d');
                    $is_past = $date < date('Y-m-d');
                ?>
                <div class="day-card">
                    <div class="day-header <?= $is_today ? 'today' : ($is_past ? 'past' : '') ?>">
                        <div>
                            <div class="day-date"><?= date('l', strtotime($date)) ?></div>
                            <div class="day-name"><?= date('F j, Y', strtotime($date)) ?></div>
                        </div>
                        <div class="day-count"><?= count($day_schedules) ?> sessions</div>
                    </div>
                    <div class="schedule-list">
                        <?php if (empty($day_schedules)): ?>
                            <div class="no-schedules">No sessions scheduled</div>
                        <?php else: ?>
                            <?php foreach ($day_schedules as $schedule): ?>
                                <div class="schedule-item <?= $schedule['session_status'] ?? '' ?>">
                                    <div class="schedule-time">
                                        <?= date('g:i A', strtotime($schedule['start_time'])) ?> - 
                                        <?= date('g:i A', strtotime($schedule['end_time'])) ?>
                                    </div>
                                    <div class="schedule-client">
                                        <?= htmlspecialchars($schedule['client_first_name'] . ' ' . $schedule['client_last_name']) ?>
                                    </div>
                                    <?php if ($schedule['service_name']): ?>
                                        <div class="schedule-service">
                                            <?= htmlspecialchars($schedule['service_name']) ?> (<?= htmlspecialchars($schedule['service_code']) ?>)
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($schedule['client_address']): ?>
                                        <div class="schedule-address desktop-view">
                                            📍 <?= htmlspecialchars($schedule['client_address']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($schedule['client_phone']): ?>
                                        <div class="schedule-address">
                                            📞 <?= htmlspecialchars($schedule['client_phone']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="schedule-actions">
                                        <?php if ($schedule['session_id']): ?>
                                            <?php if ($schedule['session_status'] == 'completed'): ?>
                                                <a href="edit_session.php?id=<?= $schedule['session_id'] ?>" class="schedule-btn success">
                                                    ✓ Completed
                                                </a>
                                            <?php else: ?>
                                                <a href="edit_session.php?id=<?= $schedule['session_id'] ?>" class="schedule-btn secondary">
                                                    Edit Note
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if (!$is_past): ?>
                                                <a href="new_session.php?schedule_id=<?= $schedule['id'] ?>" class="schedule-btn">
                                                    Start Session
                                                </a>
                                            <?php else: ?>
                                                <a href="new_session.php?client_id=<?= $schedule['client_id'] ?>&date=<?= $date ?>" class="schedule-btn">
                                                    Add Note
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <a href="client_detail.php?id=<?= $schedule['client_id'] ?>" class="schedule-btn secondary">
                                            View Client
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>