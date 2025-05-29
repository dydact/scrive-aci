<?php
session_start();
require_once 'auth_helper.php';
require_once 'auth.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get user information from session
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? 'employee';
$userName = $_SESSION['first_name'] ?? 'User';
$userFullName = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$userInitials = substr($_SESSION['first_name'] ?? '', 0, 1) . substr($_SESSION['last_name'] ?? '', 0, 1);

// Database connection using environment variables or config
$database = getenv('MARIADB_DATABASE') ?: 'iris';
$username = getenv('MARIADB_USER') ?: 'iris_user';
$password = getenv('MARIADB_PASSWORD') ?: '';
$host = getenv('MARIADB_HOST') ?: 'localhost';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error. Please contact support.");
}

// Get employee's assigned clients for today
$todayClients = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.first_name,
            c.last_name,
            c.date_of_birth,
            c.ma_number,
            c.program_type,
            s.service_type,
            s.scheduled_time,
            tp.goal_summary
        FROM autism_clients c
        INNER JOIN autism_staff_assignments sa ON c.id = sa.client_id
        LEFT JOIN autism_schedules s ON c.id = s.client_id AND s.date = CURDATE()
        LEFT JOIN autism_treatment_plans tp ON c.id = tp.client_id AND tp.status = 'active'
        WHERE sa.staff_id = :staff_id
        AND sa.status = 'active'
        ORDER BY s.scheduled_time ASC
    ");
    $stmt->execute(['staff_id' => $userId]);
    $todayClients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to fetch clients: " . $e->getMessage());
}

// Get employee's hours for the week
$weekHours = 0;
try {
    $stmt = $pdo->prepare("
        SELECT SUM(TIMESTAMPDIFF(MINUTE, clock_in, IFNULL(clock_out, NOW()))) / 60 as hours
        FROM autism_time_clock
        WHERE employee_id = :employee_id
        AND YEARWEEK(clock_in) = YEARWEEK(CURDATE())
    ");
    $stmt->execute(['employee_id' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $weekHours = round($result['hours'] ?? 0, 1);
} catch (PDOException $e) {
    error_log("Failed to fetch hours: " . $e->getMessage());
}

// Get completed notes count for the week
$notesCount = 0;
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM autism_session_notes
        WHERE created_by = :employee_id
        AND YEARWEEK(created_at) = YEARWEEK(CURDATE())
    ");
    $stmt->execute(['employee_id' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $notesCount = $result['count'] ?? 0;
} catch (PDOException $e) {
    error_log("Failed to fetch notes count: " . $e->getMessage());
}

// Check current clock status
$clockedIn = false;
$clockInTime = null;
try {
    $stmt = $pdo->prepare("
        SELECT clock_in, clock_out
        FROM autism_time_clock
        WHERE employee_id = :employee_id
        ORDER BY clock_in DESC
        LIMIT 1
    ");
    $stmt->execute(['employee_id' => $userId]);
    $lastEntry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastEntry && !$lastEntry['clock_out']) {
        $clockedIn = true;
        $clockInTime = $lastEntry['clock_in'];
    }
} catch (PDOException $e) {
    error_log("Failed to check clock status: " . $e->getMessage());
}

// Calculate age from date of birth
function calculateAge($dob) {
    if (!$dob) return 'N/A';
    $birthDate = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($birthDate)->y;
    return $age;
}

// Mask MA number based on role
function maskMANumber($maNumber, $role) {
    if (!$maNumber) return 'N/A';
    
    // Only show last 4 digits for non-admin roles
    if ($role !== 'admin' && $role !== 'supervisor') {
        return 'XXX-XXX-' . substr($maNumber, -4);
    }
    return $maNumber;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>ACI Mobile Portal - <?php echo htmlspecialchars($userFullName); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            padding-bottom: 70px;
            overflow-x: hidden;
        }
        
        /* Mobile Header */
        .mobile-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 1rem;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info-mobile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: #065f46;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .user-details h3 {
            font-size: 1rem;
            color: #1f2937;
            font-weight: 600;
        }
        
        .user-details p {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .time-badge {
            background: #ecfdf5;
            color: #065f46;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        /* Stats Bar */
        .stats-bar {
            background: white;
            padding: 1rem;
            display: flex;
            justify-content: space-around;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1f2937;
        }
        
        .stat-label {
            display: block;
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
        }
        
        /* Main Content */
        .main-content {
            padding: 1rem;
        }
        
        /* Time Clock Widget */
        .time-clock-mobile {
            background: linear-gradient(135deg, #065f46 0%, #059669 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 1rem;
            text-align: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .clock-time {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-variant-numeric: tabular-nums;
        }
        
        .clock-status {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }
        
        .clock-btn {
            background: white;
            color: #065f46;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* Quick Actions */
        .quick-actions-mobile {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .action-btn-mobile {
            background: white;
            padding: 1.25rem;
            border-radius: 0.75rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .action-btn-mobile:active {
            transform: scale(0.98);
        }
        
        .action-icon {
            display: block;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .action-text {
            display: block;
            font-size: 0.875rem;
            color: #4b5563;
            font-weight: 500;
        }
        
        /* Mobile Card */
        .mobile-card {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .card-title {
            font-size: 1.125rem;
            color: #1f2937;
            font-weight: 600;
        }
        
        /* Client List Mobile */
        .client-list-mobile {
            padding: 0.5rem;
        }
        
        .client-item-mobile {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .client-item-mobile:active {
            background: #f3f4f6;
        }
        
        .client-header-mobile {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .client-name-mobile {
            font-weight: 600;
            color: #1f2937;
        }
        
        .next-session {
            font-size: 0.875rem;
            color: #059669;
            font-weight: 500;
        }
        
        .client-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.75rem;
        }
        
        .progress-summary {
            margin-top: 0.75rem;
        }
        
        .progress-item {
            margin-bottom: 0.5rem;
        }
        
        .progress-label {
            font-size: 0.75rem;
            color: #6b7280;
            display: block;
            margin-bottom: 0.25rem;
        }
        
        .progress-bar-mobile {
            background: #e5e7eb;
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill-mobile {
            background: #059669;
            height: 100%;
            transition: width 0.3s ease;
        }
        
        /* No Data Message */
        .no-data {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }
        
        .no-data-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        /* Loading Spinner */
        .loading {
            text-align: center;
            padding: 2rem;
            color: #6b7280;
        }
        
        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid #e5e7eb;
            border-radius: 50%;
            border-top-color: #059669;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 375px) {
            .quick-actions-mobile {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="mobile-container">
        <!-- Mobile Header -->
        <div class="mobile-header">
            <div class="header-content">
                <div class="user-info-mobile">
                    <div class="user-avatar"><?php echo htmlspecialchars($userInitials); ?></div>
                    <div class="user-details">
                        <h3><?php echo htmlspecialchars($userFullName); ?></h3>
                        <p><?php echo htmlspecialchars(ucfirst($userRole)); ?></p>
                    </div>
                </div>
                <div class="time-badge" id="currentTime"></div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-number"><?php echo count($todayClients); ?></span>
                <span class="stat-label">Today's Clients</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $weekHours; ?></span>
                <span class="stat-label">Week Hours</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo $notesCount; ?></span>
                <span class="stat-label">Notes Done</span>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Time Clock Widget -->
            <div class="time-clock-mobile">
                <div class="clock-time" id="clockTime"></div>
                <div class="clock-status">
                    <?php echo $clockedIn ? 'Currently Clocked In' : 'Currently Clocked Out'; ?>
                </div>
                <button class="clock-btn" onclick="toggleClock()">
                    <?php echo $clockedIn ? 'Clock Out' : 'Clock In'; ?>
                </button>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions-mobile">
                <div class="action-btn-mobile" onclick="window.location.href='new_session.php'">
                    <span class="action-icon">📝</span>
                    <span class="action-text">Quick Note</span>
                </div>
                <div class="action-btn-mobile" onclick="window.location.href='calendar.php'">
                    <span class="action-icon">📅</span>
                    <span class="action-text">Schedule</span>
                </div>
                <div class="action-btn-mobile" onclick="window.location.href='reports.php?type=payroll'">
                    <span class="action-icon">💰</span>
                    <span class="action-text">Payroll</span>
                </div>
                <div class="action-btn-mobile" onclick="window.location.href='training.php'">
                    <span class="action-icon">🎓</span>
                    <span class="action-text">Training</span>
                </div>
            </div>

            <!-- Today's Clients -->
            <div class="mobile-card">
                <div class="card-header">
                    <h2 class="card-title">📋 Today's Clients</h2>
                </div>
                <div class="client-list-mobile">
                    <?php if (empty($todayClients)): ?>
                        <div class="no-data">
                            <div class="no-data-icon">📅</div>
                            <p>No clients scheduled for today</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($todayClients as $client): ?>
                            <div class="client-item-mobile" onclick="window.location.href='client_detail.php?id=<?php echo $client['id']; ?>'">
                                <div class="client-header-mobile">
                                    <div class="client-name-mobile">
                                        <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                                    </div>
                                    <div class="next-session">
                                        <?php echo $client['scheduled_time'] ? date('g:i A', strtotime($client['scheduled_time'])) : 'No time set'; ?>
                                    </div>
                                </div>
                                <div class="client-meta">
                                    <span>Age: <?php echo calculateAge($client['date_of_birth']); ?> • <?php echo htmlspecialchars($client['service_type'] ?? 'N/A'); ?></span>
                                    <span><?php echo htmlspecialchars($client['program_type'] ?? 'N/A'); ?> Program</span>
                                </div>
                                <?php if ($client['goal_summary']): ?>
                                    <div class="progress-summary">
                                        <div class="progress-item">
                                            <span class="progress-label">Treatment Progress</span>
                                            <div class="progress-bar-mobile">
                                                <div class="progress-fill-mobile" style="width: 70%"></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update time displays
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit', 
                hour12: true 
            });
            document.getElementById('currentTime').textContent = timeString;
            
            const clockTimeString = now.toLocaleTimeString('en-US', { 
                hour: 'numeric', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: true 
            });
            document.getElementById('clockTime').textContent = clockTimeString;
        }
        
        // Update time immediately and then every second
        updateTime();
        setInterval(updateTime, 1000);
        
        // Clock in/out functionality
        function toggleClock() {
            const clockedIn = <?php echo $clockedIn ? 'true' : 'false'; ?>;
            const action = clockedIn ? 'clock_out' : 'clock_in';
            
            if (confirm(`Are you sure you want to ${action.replace('_', ' ')}?`)) {
                // Get location if available
                let locationPromise = Promise.resolve(null);
                if (navigator.geolocation) {
                    locationPromise = new Promise((resolve) => {
                        navigator.geolocation.getCurrentPosition(
                            (position) => resolve(`${position.coords.latitude},${position.coords.longitude}`),
                            () => resolve(null),
                            { timeout: 5000 }
                        );
                    });
                }
                
                locationPromise.then(location => {
                    return fetch('api_time_clock.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: action,
                            employee_id: <?php echo $userId; ?>,
                            location: location
                        })
                    });
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.total_hours) {
                            alert(`Clocked out successfully. Total hours: ${data.total_hours}`);
                        }
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    alert('Network error. Please try again.');
                    console.error('Error:', error);
                });
            }
        }
        
        // Add pull-to-refresh functionality
        let startY = 0;
        let isPulling = false;
        
        document.addEventListener('touchstart', (e) => {
            startY = e.touches[0].pageY;
        });
        
        document.addEventListener('touchmove', (e) => {
            const currentY = e.touches[0].pageY;
            const diff = currentY - startY;
            
            if (diff > 100 && window.scrollY === 0 && !isPulling) {
                isPulling = true;
                location.reload();
            }
        });
        
        document.addEventListener('touchend', () => {
            isPulling = false;
        });
    </script>
</body>
</html>