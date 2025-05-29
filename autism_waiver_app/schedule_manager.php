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

// Get current week
$weekStart = $_GET['week'] ?? date('Y-m-d', strtotime('monday this week'));
$weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));

// Get staff member (for staff view) or all staff (for supervisor/admin)
$staffId = $_SESSION['access_level'] >= 4 ? ($_GET['staff_id'] ?? null) : $_SESSION['user_id'];

// Load appointments
$appointments = [];
$query = "
    SELECT 
        a.*,
        c.first_name as client_first,
        c.last_name as client_last,
        s.first_name as staff_first,
        s.last_name as staff_last,
        st.service_name,
        st.billing_code
    FROM autism_appointments a
    JOIN autism_clients c ON a.client_id = c.id
    LEFT JOIN autism_staff_members s ON a.staff_id = s.id
    JOIN autism_service_types st ON a.service_type_id = st.id
    WHERE a.appointment_date BETWEEN :start AND :end
";

$params = ['start' => $weekStart, 'end' => $weekEnd];

if ($staffId) {
    $query .= " AND a.staff_id = :staff_id";
    $params['staff_id'] = $staffId;
}

$query .= " ORDER BY a.appointment_date, a.start_time";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all staff for filter (supervisors and admins only)
$allStaff = [];
if ($_SESSION['access_level'] >= 4) {
    $stmt = $pdo->query("
        SELECT id, first_name, last_name 
        FROM autism_staff_members 
        WHERE status = 'active'
        ORDER BY last_name, first_name
    ");
    $allStaff = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get service types
$serviceTypes = [];
$stmt = $pdo->query("SELECT * FROM autism_service_types WHERE is_active = 1 ORDER BY service_name");
$serviceTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get assigned clients
$clients = [];
if ($staffId) {
    $stmt = $pdo->prepare("
        SELECT c.id, c.first_name, c.last_name, c.ma_number
        FROM autism_clients c
        JOIN autism_staff_assignments sa ON c.id = sa.client_id
        WHERE sa.staff_id = :staff_id AND sa.status = 'active'
        ORDER BY c.last_name, c.first_name
    ");
    $stmt->execute(['staff_id' => $staffId]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle appointment creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $stmt = $pdo->prepare("
                        INSERT INTO autism_appointments (
                            client_id, staff_id, service_type_id, appointment_date,
                            start_time, end_time, location, status, appointment_type,
                            notes, created_by
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['client_id'],
                        $_POST['staff_id'] ?? $staffId,
                        $_POST['service_type_id'],
                        $_POST['appointment_date'],
                        $_POST['start_time'],
                        $_POST['end_time'],
                        $_POST['location'],
                        'scheduled',
                        $_POST['appointment_type'] ?? 'regular',
                        $_POST['notes'],
                        $_SESSION['user_id']
                    ]);
                    break;
                    
                case 'update_status':
                    $stmt = $pdo->prepare("
                        UPDATE autism_appointments 
                        SET status = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$_POST['status'], $_POST['appointment_id']]);
                    break;
                    
                case 'cancel':
                    $stmt = $pdo->prepare("
                        UPDATE autism_appointments 
                        SET status = 'cancelled', 
                            cancellation_reason = ?,
                            cancelled_by = ?,
                            cancelled_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['reason'],
                        $_SESSION['user_id'],
                        $_POST['appointment_id']
                    ]);
                    break;
            }
        }
        
        header("Location: schedule_manager.php?week=" . $weekStart);
        exit;
        
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Manager - Scrive ACI</title>
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
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem 2rem;
        }
        
        .schedule-controls {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .week-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .week-nav button {
            padding: 0.5rem 1rem;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .week-nav button:hover {
            background: #e5e7eb;
        }
        
        .current-week {
            font-weight: 600;
            color: #1f2937;
        }
        
        .schedule-grid {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .calendar-header {
            display: grid;
            grid-template-columns: 120px repeat(7, 1fr);
            background: #f8fafc;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .time-column {
            background: #f8fafc;
            border-right: 1px solid #e5e7eb;
            padding: 1rem;
            font-weight: 500;
            text-align: center;
        }
        
        .day-header {
            padding: 1rem;
            text-align: center;
            border-right: 1px solid #e5e7eb;
        }
        
        .day-header:last-child {
            border-right: none;
        }
        
        .day-name {
            font-weight: 600;
            color: #1f2937;
        }
        
        .day-date {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .today {
            background: #ecfdf5;
        }
        
        .calendar-body {
            display: grid;
            grid-template-columns: 120px repeat(7, 1fr);
            position: relative;
        }
        
        .time-slot {
            border-right: 1px solid #e5e7eb;
            border-bottom: 1px solid #f3f4f6;
            padding: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
            text-align: center;
            height: 60px;
        }
        
        .day-column {
            border-right: 1px solid #e5e7eb;
            position: relative;
            min-height: 720px; /* 12 hours * 60px */
        }
        
        .day-column:last-child {
            border-right: none;
        }
        
        .appointment {
            position: absolute;
            left: 4px;
            right: 4px;
            background: #dbeafe;
            border: 1px solid #60a5fa;
            border-radius: 4px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .appointment:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            z-index: 10;
        }
        
        .appointment.completed {
            background: #d1fae5;
            border-color: #10b981;
        }
        
        .appointment.cancelled {
            background: #fee2e2;
            border-color: #ef4444;
            opacity: 0.6;
        }
        
        .appointment.no-show {
            background: #fef3c7;
            border-color: #f59e0b;
        }
        
        .appointment-time {
            font-weight: 600;
            color: #1f2937;
        }
        
        .appointment-client {
            color: #374151;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .appointment-service {
            color: #6b7280;
            font-size: 0.7rem;
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
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            position: relative;
            background: white;
            margin: 5% auto;
            padding: 2rem;
            width: 90%;
            max-width: 600px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6b7280;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #059669;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #059669;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 style="color: #059669;">📅 Schedule Manager</h1>
            <a href="/src/dashboard.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($error)): ?>
            <div style="background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Schedule Controls -->
        <div class="schedule-controls">
            <div class="week-nav">
                <button onclick="changeWeek(-1)">← Previous</button>
                <span class="current-week">
                    <?php echo date('M j', strtotime($weekStart)); ?> - <?php echo date('M j, Y', strtotime($weekEnd)); ?>
                </span>
                <button onclick="changeWeek(1)">Next →</button>
                <button onclick="goToToday()">Today</button>
            </div>
            
            <div style="display: flex; gap: 1rem; align-items: center;">
                <?php if ($_SESSION['access_level'] >= 4 && !empty($allStaff)): ?>
                    <select class="form-control" style="width: auto;" onchange="filterByStaff(this.value)">
                        <option value="">All Staff</option>
                        <?php foreach ($allStaff as $staff): ?>
                            <option value="<?php echo $staff['id']; ?>" <?php echo $staffId == $staff['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                
                <button class="btn btn-primary" onclick="showNewAppointmentModal()">+ New Appointment</button>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-cards">
            <?php
            $totalAppointments = count($appointments);
            $completedCount = count(array_filter($appointments, fn($a) => $a['status'] === 'completed'));
            $scheduledCount = count(array_filter($appointments, fn($a) => $a['status'] === 'scheduled'));
            $cancelledCount = count(array_filter($appointments, fn($a) => $a['status'] === 'cancelled'));
            ?>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalAppointments; ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $scheduledCount; ?></div>
                <div class="stat-label">Scheduled</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $completedCount; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value"><?php echo $cancelledCount; ?></div>
                <div class="stat-label">Cancelled</div>
            </div>
        </div>
        
        <!-- Calendar Grid -->
        <div class="schedule-grid">
            <div class="calendar-header">
                <div class="time-column">Time</div>
                <?php
                $currentDate = new DateTime($weekStart);
                $today = date('Y-m-d');
                for ($i = 0; $i < 7; $i++):
                    $dateStr = $currentDate->format('Y-m-d');
                    $isToday = $dateStr === $today;
                ?>
                    <div class="day-header <?php echo $isToday ? 'today' : ''; ?>">
                        <div class="day-name"><?php echo $currentDate->format('l'); ?></div>
                        <div class="day-date"><?php echo $currentDate->format('M j'); ?></div>
                    </div>
                <?php 
                    $currentDate->modify('+1 day');
                endfor; 
                ?>
            </div>
            
            <div class="calendar-body">
                <!-- Time slots -->
                <?php for ($hour = 8; $hour < 20; $hour++): ?>
                    <div class="time-slot"><?php echo date('g:i A', strtotime("$hour:00")); ?></div>
                <?php endfor; ?>
                
                <!-- Day columns -->
                <?php
                $currentDate = new DateTime($weekStart);
                for ($day = 0; $day < 7; $day++):
                    $dateStr = $currentDate->format('Y-m-d');
                    $dayAppointments = array_filter($appointments, fn($a) => $a['appointment_date'] === $dateStr);
                ?>
                    <div class="day-column" data-date="<?php echo $dateStr; ?>">
                        <?php foreach ($dayAppointments as $appt): 
                            $startTime = new DateTime($appt['start_time']);
                            $endTime = new DateTime($appt['end_time']);
                            $startHour = (int)$startTime->format('H');
                            $startMinute = (int)$startTime->format('i');
                            $duration = ($endTime->getTimestamp() - $startTime->getTimestamp()) / 60;
                            
                            $top = (($startHour - 8) * 60 + $startMinute) . 'px';
                            $height = $duration . 'px';
                        ?>
                            <div class="appointment <?php echo $appt['status']; ?>" 
                                 style="top: <?php echo $top; ?>; height: <?php echo $height; ?>;"
                                 onclick="showAppointmentDetails(<?php echo $appt['id']; ?>)"
                                 data-appointment='<?php echo json_encode($appt); ?>'>
                                <div class="appointment-time">
                                    <?php echo $startTime->format('g:i A'); ?>
                                </div>
                                <div class="appointment-client">
                                    <?php echo htmlspecialchars($appt['client_first'] . ' ' . $appt['client_last']); ?>
                                </div>
                                <div class="appointment-service">
                                    <?php echo htmlspecialchars($appt['service_name']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php 
                    $currentDate->modify('+1 day');
                endfor; 
                ?>
            </div>
        </div>
    </div>
    
    <!-- New Appointment Modal -->
    <div id="newAppointmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>New Appointment</h2>
                <button class="modal-close" onclick="closeModal('newAppointmentModal')">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="client_id">Client</label>
                    <select name="client_id" id="client_id" class="form-control" required>
                        <option value="">Select a client...</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>">
                                <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                                (MA: <?php echo substr($client['ma_number'], -4); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($_SESSION['access_level'] >= 4): ?>
                    <div class="form-group">
                        <label for="staff_id">Staff Member</label>
                        <select name="staff_id" id="staff_id" class="form-control" required>
                            <option value="">Select staff...</option>
                            <?php foreach ($allStaff as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>">
                                    <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="service_type_id">Service Type</label>
                    <select name="service_type_id" id="service_type_id" class="form-control" required>
                        <option value="">Select service...</option>
                        <?php foreach ($serviceTypes as $service): ?>
                            <option value="<?php echo $service['id']; ?>">
                                <?php echo htmlspecialchars($service['service_name']); ?>
                                (<?php echo htmlspecialchars($service['billing_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="appointment_date">Date</label>
                    <input type="date" name="appointment_date" id="appointment_date" 
                           class="form-control" required 
                           min="<?php echo $weekStart; ?>" 
                           max="<?php echo $weekEnd; ?>"
                           value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time">Start Time</label>
                        <input type="time" name="start_time" id="start_time" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" name="end_time" id="end_time" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="location">Location</label>
                    <select name="location" id="location" class="form-control" required>
                        <option value="Home">Client's Home</option>
                        <option value="Center">ACI Center</option>
                        <option value="Community">Community</option>
                        <option value="School">School</option>
                        <option value="Virtual">Virtual/Telehealth</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="appointment_type">Appointment Type</label>
                    <select name="appointment_type" id="appointment_type" class="form-control">
                        <option value="regular">Regular Session</option>
                        <option value="make_up">Make-up Session</option>
                        <option value="evaluation">Evaluation</option>
                        <option value="crisis">Crisis Intervention</option>
                        <option value="group">Group Session</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn" style="background: #6b7280; color: white;" 
                            onclick="closeModal('newAppointmentModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Appointment</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Appointment Details Modal -->
    <div id="appointmentDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Appointment Details</h2>
                <button class="modal-close" onclick="closeModal('appointmentDetailsModal')">&times;</button>
            </div>
            
            <div id="appointmentDetails"></div>
        </div>
    </div>
    
    <script>
        function changeWeek(direction) {
            const currentWeek = '<?php echo $weekStart; ?>';
            const date = new Date(currentWeek);
            date.setDate(date.getDate() + (direction * 7));
            const newWeek = date.toISOString().split('T')[0];
            window.location.href = '?week=' + newWeek + '<?php echo $staffId ? "&staff_id=$staffId" : ""; ?>';
        }
        
        function goToToday() {
            const today = new Date();
            const monday = new Date(today);
            monday.setDate(today.getDate() - today.getDay() + 1);
            const weekStart = monday.toISOString().split('T')[0];
            window.location.href = '?week=' + weekStart + '<?php echo $staffId ? "&staff_id=$staffId" : ""; ?>';
        }
        
        function filterByStaff(staffId) {
            const currentWeek = '<?php echo $weekStart; ?>';
            if (staffId) {
                window.location.href = '?week=' + currentWeek + '&staff_id=' + staffId;
            } else {
                window.location.href = '?week=' + currentWeek;
            }
        }
        
        function showNewAppointmentModal() {
            document.getElementById('newAppointmentModal').style.display = 'block';
        }
        
        function showAppointmentDetails(appointmentId) {
            const appointment = document.querySelector(`[data-appointment*='"id":${appointmentId}'`).dataset.appointment;
            const appt = JSON.parse(appointment);
            
            const detailsHtml = `
                <div style="display: grid; gap: 1rem;">
                    <div>
                        <strong>Client:</strong> ${appt.client_first} ${appt.client_last}
                    </div>
                    <div>
                        <strong>Staff:</strong> ${appt.staff_first} ${appt.staff_last}
                    </div>
                    <div>
                        <strong>Service:</strong> ${appt.service_name} (${appt.billing_code})
                    </div>
                    <div>
                        <strong>Date:</strong> ${new Date(appt.appointment_date).toLocaleDateString()}
                    </div>
                    <div>
                        <strong>Time:</strong> ${formatTime(appt.start_time)} - ${formatTime(appt.end_time)}
                    </div>
                    <div>
                        <strong>Location:</strong> ${appt.location}
                    </div>
                    <div>
                        <strong>Status:</strong> ${appt.status.charAt(0).toUpperCase() + appt.status.slice(1)}
                    </div>
                    ${appt.notes ? `<div><strong>Notes:</strong> ${appt.notes}</div>` : ''}
                </div>
                
                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    ${appt.status === 'scheduled' ? `
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="appointment_id" value="${appt.id}">
                            <input type="hidden" name="status" value="completed">
                            <button type="submit" class="btn btn-primary">Mark Completed</button>
                        </form>
                        
                        <button class="btn" style="background: #dc2626; color: white;" 
                                onclick="cancelAppointment(${appt.id})">Cancel</button>
                    ` : ''}
                    
                    ${appt.status === 'scheduled' ? `
                        <button class="btn" style="background: #f59e0b; color: white;" 
                                onclick="markNoShow(${appt.id})">Mark No-Show</button>
                    ` : ''}
                </div>
            `;
            
            document.getElementById('appointmentDetails').innerHTML = detailsHtml;
            document.getElementById('appointmentDetailsModal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function formatTime(timeStr) {
            const time = new Date('2000-01-01 ' + timeStr);
            return time.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        }
        
        function cancelAppointment(appointmentId) {
            const reason = prompt('Cancellation reason:');
            if (reason) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="appointment_id" value="${appointmentId}">
                    <input type="hidden" name="reason" value="${reason}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function markNoShow(appointmentId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="appointment_id" value="${appointmentId}">
                <input type="hidden" name="status" value="no_show">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>