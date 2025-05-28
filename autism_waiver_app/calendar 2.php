<?php

/**
 * Enhanced Calendar & Scheduling System - Scrive AI-Powered Autism Waiver ERM
 * 
 * @package   Scrive
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
 * @license   MIT License
 */

// Include Scrive authentication and API
require_once 'auth.php';
require_once 'api.php';

// Initialize authentication
initScriveAuth();

$api = new OpenEMRAPI();
$error = null;
$success = null;
$currentUser = null;
$appointments = [];
$clients = [];
$staff = [];

try {
    $currentUser = getCurrentScriveUser();
    
    // Get view parameters
    $view_date = $_GET['date'] ?? date('Y-m-d');
    $view_type = $_GET['view'] ?? 'week'; // week, month, day
    $selected_staff = $_GET['staff_id'] ?? '';
    $selected_client = $_GET['client_id'] ?? '';
    
    // Get success/error messages
    if (isset($_GET['success'])) {
        $success = $_GET['success'];
    }
    if (isset($_GET['error'])) {
        $error = $_GET['error'];
    }
    
    // Get appointments for the view
    $appointments = getAppointments($view_date, $view_type, $selected_staff, $selected_client);
    
    // Get clients for dropdown
    $clients = $api->getEnhancedClients();
    
    // Get available staff
    $staff = $api->getAvailableStaff();
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

/**
 * Get appointments from OpenEMR calendar system
 */
function getAppointments($date, $view_type, $staff_id = '', $client_id = '') {
    try {
        // Calculate date range based on view type
        $start_date = $date;
        $end_date = $date;
        
        switch ($view_type) {
            case 'week':
                $start_date = date('Y-m-d', strtotime('monday this week', strtotime($date)));
                $end_date = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
                break;
            case 'month':
                $start_date = date('Y-m-01', strtotime($date));
                $end_date = date('Y-m-t', strtotime($date));
                break;
            case 'day':
                $start_date = $end_date = $date;
                break;
        }
        
        // Build query
        $where_conditions = ["pc_eventDate BETWEEN ? AND ?"];
        $params = [$start_date, $end_date];
        
        if (!empty($staff_id)) {
            $where_conditions[] = "pc_aid = ?";
            $params[] = $staff_id;
        }
        
        if (!empty($client_id)) {
            $where_conditions[] = "pc_pid = ?";
            $params[] = $client_id;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT 
                    e.pc_eid as appointment_id,
                    e.pc_eventDate as date,
                    e.pc_startTime as start_time,
                    e.pc_endTime as end_time,
                    e.pc_duration as duration,
                    e.pc_title as title,
                    e.pc_hometext as notes,
                    e.pc_apptstatus as status,
                    e.pc_pid as client_id,
                    e.pc_aid as staff_id,
                    e.pc_catid as category_id,
                    e.pc_room as room,
                    CONCAT(p.fname, ' ', p.lname) as client_name,
                    p.phone_home as client_phone,
                    CONCAT(u.fname, ' ', u.lname) as staff_name,
                    u.username as staff_username,
                    c.pc_catname as category_name,
                    c.pc_catcolor as category_color
                FROM openemr_postcalendar_events e
                LEFT JOIN patient_data p ON e.pc_pid = p.pid
                LEFT JOIN users u ON e.pc_aid = u.id
                LEFT JOIN openemr_postcalendar_categories c ON e.pc_catid = c.pc_catid
                WHERE {$where_clause}
                ORDER BY e.pc_eventDate, e.pc_startTime";
        
        $result = sqlStatement($sql, $params);
        $appointments = [];
        
        while ($row = sqlFetchArray($result)) {
            // Add autism waiver specific data if available
            $row['service_type'] = getAppointmentServiceType($row['appointment_id']);
            $row['is_autism_service'] = !empty($row['service_type']);
            $appointments[] = $row;
        }
        
        return $appointments;
        
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get service type for autism waiver appointments
 */
function getAppointmentServiceType($appointment_id) {
    try {
        $table_check = sqlQuery("SHOW TABLES LIKE 'autism_schedules'");
        if (!$table_check) {
            return null;
        }
        
        $result = sqlQuery(
            "SELECT st.abbreviation, st.service_name 
             FROM autism_schedules s
             JOIN autism_service_types st ON s.service_type_id = st.service_type_id
             WHERE s.openemr_event_id = ?",
            [$appointment_id]
        );
        
        return $result;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get current week dates
 */
function getCurrentWeekDates($date) {
    $week_start = date('Y-m-d', strtotime('monday this week', strtotime($date)));
    $dates = [];
    for ($i = 0; $i < 7; $i++) {
        $dates[] = date('Y-m-d', strtotime($week_start . " + {$i} days"));
    }
    return $dates;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar & Scheduling - Scrive</title>
    
    <!-- Modern Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --dark-bg: #1a1d29;
            --card-bg: #ffffff;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Modern Navigation */
        .modern-nav {
            background: rgba(26, 29, 41, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: var(--transition);
        }

        .modern-nav .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .user-dropdown .dropdown-toggle {
            border: 2px solid transparent;
            border-radius: 50px;
            padding: 8px 16px;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.1);
        }

        .user-dropdown .dropdown-toggle:hover {
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.2);
        }

        /* Page Header */
        .page-header {
            background: var(--primary-gradient);
            color: white;
            padding: 3rem 0;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,1000 1000,800 1000,1000"/></svg>');
            background-size: cover;
        }

        .page-header-content {
            position: relative;
            z-index: 2;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .page-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0;
        }

        /* Calendar Controls */
        .calendar-controls {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 2rem;
            margin: -2rem 1rem 2rem;
            position: relative;
            z-index: 10;
        }

        .view-tabs {
            background: #f8fafc;
            border-radius: var(--border-radius);
            padding: 0.5rem;
            display: inline-flex;
            margin-bottom: 1.5rem;
        }

        .view-tab {
            padding: 0.75rem 1.5rem;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            font-weight: 500;
            border-radius: var(--border-radius);
            transition: var(--transition);
            cursor: pointer;
        }

        .view-tab.active {
            background: var(--primary-color);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .view-tab:hover:not(.active) {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary-color);
        }

        /* Calendar Grid */
        .calendar-container {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .calendar-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .calendar-title {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .calendar-nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            cursor: pointer;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        /* Week View */
        .week-view {
            display: grid;
            grid-template-columns: 80px repeat(7, 1fr);
            min-height: 600px;
        }

        .time-column {
            background: #f8fafc;
            border-right: 1px solid var(--border-color);
        }

        .time-slot {
            height: 60px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .day-column {
            border-right: 1px solid var(--border-color);
            position: relative;
        }

        .day-header {
            background: #f8fafc;
            height: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
        }

        .day-header.today {
            background: var(--primary-color);
            color: white;
        }

        .day-date {
            font-size: 1.2rem;
        }

        .day-name {
            font-size: 0.8rem;
            opacity: 0.7;
        }

        .hour-slot {
            height: 60px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.5);
            position: relative;
            cursor: pointer;
            transition: var(--transition);
        }

        .hour-slot:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        /* Appointments */
        .appointment {
            position: absolute;
            left: 2px;
            right: 2px;
            border-radius: 6px;
            padding: 0.5rem;
            font-size: 0.8rem;
            color: white;
            cursor: pointer;
            transition: var(--transition);
            z-index: 2;
        }

        .appointment:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .appointment.iiss { background: var(--primary-gradient); }
        .appointment.ti { background: var(--secondary-gradient); }
        .appointment.respite { background: var(--warning-gradient); }
        .appointment.fc { background: var(--success-gradient); }
        .appointment.default { background: var(--dark-gradient); }

        .appointment-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .appointment-details {
            font-size: 0.7rem;
            opacity: 0.9;
        }

        /* Modern Buttons */
        .btn-modern {
            border-radius: var(--border-radius);
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border: none;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .btn-modern-primary {
            background: var(--primary-gradient);
            color: white;
        }

        .btn-modern-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: white;
        }

        .btn-modern-success {
            background: var(--success-gradient);
            color: white;
        }

        .btn-modern-success:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            color: white;
        }

        /* Filters */
        .filters-section {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .form-control, .form-select {
            border-radius: var(--border-radius);
            border: 2px solid var(--border-color);
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Legend */
        .service-legend {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 4px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .calendar-controls {
                margin: -1rem 0.5rem 1rem;
                padding: 1.5rem;
            }

            .filters-section {
                flex-direction: column;
                align-items: stretch;
            }

            .week-view {
                grid-template-columns: 60px repeat(7, 1fr);
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Modern Navigation -->
    <nav class="navbar navbar-expand-lg modern-nav">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-brain me-2"></i>
                Scrive
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown user-dropdown">
                    <a class="nav-link dropdown-toggle text-light d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div class="fw-semibold"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                                <small class="text-light opacity-75"><?php echo htmlspecialchars($currentUser['facility_name'] ?: 'Administrator'); ?></small>
                            </div>
                            <i class="fas fa-user-circle fa-2x"></i>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user me-2 text-primary"></i>
                                    <div>
                                        <small class="text-muted">Logged in as:</small><br>
                                        <strong><?php echo htmlspecialchars($currentUser['username']); ?></strong>
                                    </div>
                                </div>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-cog me-2"></i>
                                Account Settings
                            </a>
                        </li>
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

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <div class="page-header-content">
                <div class="row align-items-center">
                    <div class="col">
                        <h1 class="page-title">
                            <i class="fas fa-calendar-alt me-3"></i>
                            Calendar & Scheduling
                        </h1>
                        <p class="page-subtitle">
                            Intelligent appointment scheduling for autism waiver services with staff optimization and conflict resolution
                        </p>
                    </div>
                    <div class="col-auto">
                        <a href="clients.php" class="btn btn-light me-3">
                            <i class="fas fa-users me-2"></i>
                            Clients
                        </a>
                        <button class="btn btn-modern-success" onclick="createAppointment()">
                            <i class="fas fa-plus me-2"></i>
                            New Appointment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Calendar Controls -->
        <div class="calendar-controls">
            <!-- Messages -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- View Tabs -->
            <div class="view-tabs">
                <button class="view-tab <?php echo $view_type === 'day' ? 'active' : ''; ?>" 
                        onclick="changeView('day')">Day</button>
                <button class="view-tab <?php echo $view_type === 'week' ? 'active' : ''; ?>" 
                        onclick="changeView('week')">Week</button>
                <button class="view-tab <?php echo $view_type === 'month' ? 'active' : ''; ?>" 
                        onclick="changeView('month')">Month</button>
            </div>

            <!-- Filters -->
            <div class="filters-section">
                <div>
                    <label for="date_picker" class="form-label fw-semibold">Date</label>
                    <input type="date" class="form-control" id="date_picker" value="<?php echo $view_date; ?>">
                </div>
                <div>
                    <label for="staff_filter" class="form-label fw-semibold">Staff Member</label>
                    <select class="form-select" id="staff_filter">
                        <option value="">All Staff</option>
                        <?php foreach ($staff as $member): ?>
                            <option value="<?php echo $member['employee_id']; ?>" 
                                    <?php echo $selected_staff == $member['employee_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($member['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="client_filter" class="form-label fw-semibold">Client</label>
                    <select class="form-select" id="client_filter">
                        <option value="">All Clients</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" 
                                    <?php echo $selected_client == $client['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex align-items-end">
                    <button class="btn btn-modern btn-modern-primary" onclick="applyFilters()">
                        <i class="fas fa-search me-2"></i>
                        Apply Filters
                    </button>
                </div>
            </div>

            <!-- Service Legend -->
            <div class="service-legend">
                <div class="legend-item">
                    <div class="legend-color" style="background: var(--primary-gradient);"></div>
                    <span>IISS - Intensive Individual Support</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: var(--secondary-gradient);"></div>
                    <span>TI - Therapeutic Integration</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: var(--warning-gradient);"></div>
                    <span>Respite Care</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background: var(--success-gradient);"></div>
                    <span>FC - Family Consultation</span>
                </div>
            </div>
        </div>

        <!-- Calendar Display -->
        <div class="calendar-container">
            <div class="calendar-header">
                <div class="calendar-title">
                    <?php 
                    if ($view_type === 'week') {
                        $week_dates = getCurrentWeekDates($view_date);
                        echo date('F j', strtotime($week_dates[0])) . ' - ' . date('F j, Y', strtotime($week_dates[6]));
                    } else {
                        echo date('F Y', strtotime($view_date));
                    }
                    ?>
                </div>
                <div class="calendar-nav">
                    <button class="nav-btn" onclick="navigateCalendar('prev')">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="nav-btn" onclick="navigateCalendar('today')">
                        <i class="fas fa-calendar-day"></i>
                    </button>
                    <button class="nav-btn" onclick="navigateCalendar('next')">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <?php if ($view_type === 'week'): ?>
                <!-- Week View -->
                <div class="week-view">
                    <!-- Time Column -->
                    <div class="time-column">
                        <div class="time-slot"></div>
                        <?php for ($hour = 7; $hour <= 18; $hour++): ?>
                            <div class="time-slot">
                                <?php echo date('g A', strtotime("{$hour}:00")); ?>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Day Columns -->
                    <?php 
                    $week_dates = getCurrentWeekDates($view_date);
                    foreach ($week_dates as $date): 
                        $is_today = $date === date('Y-m-d');
                        $day_appointments = array_filter($appointments, function($apt) use ($date) {
                            return $apt['date'] === $date;
                        });
                    ?>
                        <div class="day-column">
                            <div class="day-header <?php echo $is_today ? 'today' : ''; ?>">
                                <div class="day-date"><?php echo date('j', strtotime($date)); ?></div>
                                <div class="day-name"><?php echo date('D', strtotime($date)); ?></div>
                            </div>
                            
                            <?php for ($hour = 7; $hour <= 18; $hour++): ?>
                                <div class="hour-slot" 
                                     data-date="<?php echo $date; ?>" 
                                     data-hour="<?php echo $hour; ?>"
                                     onclick="createAppointmentAt('<?php echo $date; ?>', '<?php echo $hour; ?>')">
                                     
                                    <?php foreach ($day_appointments as $apt): 
                                        $apt_hour = (int)date('G', strtotime($apt['start_time']));
                                        if ($apt_hour === $hour):
                                            $service_class = strtolower($apt['service_type']['abbreviation'] ?? 'default');
                                            $duration_minutes = $apt['duration'] ?? 60;
                                            $height = ($duration_minutes / 60) * 60; // 60px per hour
                                            $minutes_offset = (int)date('i', strtotime($apt['start_time']));
                                            $top_offset = ($minutes_offset / 60) * 60;
                                    ?>
                                        <div class="appointment <?php echo $service_class; ?>" 
                                             style="height: <?php echo $height; ?>px; top: <?php echo $top_offset; ?>px;"
                                             onclick="editAppointment(<?php echo $apt['appointment_id']; ?>)">
                                            <div class="appointment-title">
                                                <?php echo htmlspecialchars($apt['client_name']); ?>
                                            </div>
                                            <div class="appointment-details">
                                                <?php echo date('g:i A', strtotime($apt['start_time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($apt['end_time'])); ?>
                                                <br>
                                                <?php echo htmlspecialchars($apt['service_type']['abbreviation'] ?? 'Appointment'); ?>
                                            </div>
                                        </div>
                                    <?php endif; endforeach; ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Calendar functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize calendar
            initializeCalendar();
        });

        function initializeCalendar() {
            // Add event listeners
            document.getElementById('date_picker').addEventListener('change', function() {
                updateCalendarView();
            });
        }

        function changeView(view) {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('view', view);
            window.location.href = currentUrl.toString();
        }

        function navigateCalendar(direction) {
            const currentDate = new Date(document.getElementById('date_picker').value);
            const view = '<?php echo $view_type; ?>';
            
            let newDate;
            switch (direction) {
                case 'prev':
                    if (view === 'week') {
                        newDate = new Date(currentDate.setDate(currentDate.getDate() - 7));
                    } else if (view === 'month') {
                        newDate = new Date(currentDate.setMonth(currentDate.getMonth() - 1));
                    } else {
                        newDate = new Date(currentDate.setDate(currentDate.getDate() - 1));
                    }
                    break;
                case 'next':
                    if (view === 'week') {
                        newDate = new Date(currentDate.setDate(currentDate.getDate() + 7));
                    } else if (view === 'month') {
                        newDate = new Date(currentDate.setMonth(currentDate.getMonth() + 1));
                    } else {
                        newDate = new Date(currentDate.setDate(currentDate.getDate() + 1));
                    }
                    break;
                case 'today':
                    newDate = new Date();
                    break;
            }
            
            if (newDate) {
                const dateString = newDate.toISOString().split('T')[0];
                const currentUrl = new URL(window.location);
                currentUrl.searchParams.set('date', dateString);
                window.location.href = currentUrl.toString();
            }
        }

        function applyFilters() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('date', document.getElementById('date_picker').value);
            currentUrl.searchParams.set('staff_id', document.getElementById('staff_filter').value);
            currentUrl.searchParams.set('client_id', document.getElementById('client_filter').value);
            window.location.href = currentUrl.toString();
        }

        function createAppointment() {
            alert('New appointment modal coming soon!');
        }

        function createAppointmentAt(date, hour) {
            alert(`Create appointment on ${date} at ${hour}:00 - Integration with OpenEMR calendar coming soon!`);
        }

        function editAppointment(appointmentId) {
            alert(`Edit appointment ${appointmentId} - Integration with OpenEMR editing coming soon!`);
        }

        function updateCalendarView() {
            applyFilters();
        }
    </script>
</body>
</html> 