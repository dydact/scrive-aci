<?php

/**
 * Database Setup for Autism Waiver Application
 * 
 * @package   Autism Waiver App
 * @author    American Caregivers Inc
 * @copyright Copyright (c) 2025 American Caregivers Inc
 * @license   MIT License
 */

// Include OpenEMR database configuration
require_once __DIR__ . '/../interface/globals.php';

// Simple authentication check
if (!isset($_SESSION['authUser']) || empty($_SESSION['authUser'])) {
    header('Location: ../interface/login/login.php?site=default');
    exit;
}

$messages = [];
$error = false;

// Handle setup action
if ($_POST['action'] === 'setup' && $_POST['confirm'] === 'yes') {
    try {
        // SQL for creating autism waiver tables
        $sql_statements = [
            "CREATE TABLE IF NOT EXISTS `autism_plan` (
                `plan_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `patient_id` BIGINT UNSIGNED NOT NULL,
                `plan_type` ENUM('initial','review') NOT NULL,
                `service_types` VARCHAR(255) DEFAULT NULL,
                `date_start` DATE NOT NULL,
                `date_end` DATE DEFAULT NULL,
                `created_by` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `strengths` TEXT DEFAULT NULL,
                `materials` TEXT DEFAULT NULL,
                `evaluator` VARCHAR(255) DEFAULT NULL,
                `next_review_date` DATE DEFAULT NULL,
                `status` ENUM('active','closed') NOT NULL DEFAULT 'active',
                PRIMARY KEY (`plan_id`),
                KEY `idx_autism_plan_patient` (`patient_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `autism_goal` (
                `goal_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `plan_id` INT UNSIGNED NOT NULL,
                `domain` VARCHAR(100) DEFAULT NULL,
                `goal_description` TEXT NOT NULL,
                `baseline` TEXT DEFAULT NULL,
                `sequence` INT UNSIGNED DEFAULT NULL,
                PRIMARY KEY (`goal_id`),
                KEY `idx_autism_goal_plan` (`plan_id`),
                CONSTRAINT `fk_autism_goal_plan` FOREIGN KEY (`plan_id`) REFERENCES `autism_plan` (`plan_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `autism_objective` (
                `obj_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `goal_id` INT UNSIGNED NOT NULL,
                `objective_text` TEXT NOT NULL,
                `target_criterion` TEXT DEFAULT NULL,
                `implementation` TEXT DEFAULT NULL,
                `progress` TEXT DEFAULT NULL,
                `achieved_flag` TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`obj_id`),
                KEY `idx_autism_obj_goal` (`goal_id`),
                CONSTRAINT `fk_autism_obj_goal` FOREIGN KEY (`goal_id`) REFERENCES `autism_goal` (`goal_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `autism_session` (
                `session_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `patient_id` BIGINT UNSIGNED NOT NULL,
                `service_type` ENUM('IISS','TI','Respite','FC','Other') NOT NULL,
                `date` DATE NOT NULL,
                `time_start` TIME NOT NULL,
                `time_end` TIME NOT NULL,
                `duration_minutes` SMALLINT UNSIGNED NOT NULL,
                `author_user_id` INT UNSIGNED NOT NULL,
                `narrative_note` TEXT DEFAULT NULL,
                `created_ts` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`session_id`),
                KEY `idx_autism_session_patient` (`patient_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `autism_session_activity` (
                `activity_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `session_id` INT UNSIGNED NOT NULL,
                `time_recorded` TIME DEFAULT NULL,
                `objective_id` INT UNSIGNED DEFAULT NULL,
                `activity_desc` TEXT DEFAULT NULL,
                `outcome` ENUM('success','partial','failure','n/a') DEFAULT NULL,
                `activity_note` TEXT DEFAULT NULL,
                PRIMARY KEY (`activity_id`),
                KEY `idx_autism_act_session` (`session_id`),
                CONSTRAINT `fk_autism_act_session` FOREIGN KEY (`session_id`) REFERENCES `autism_session` (`session_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS `autism_session_incident` (
                `incident_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `session_id` INT UNSIGNED NOT NULL,
                `incident_time` TIME DEFAULT NULL,
                `incident_type` VARCHAR(50) NOT NULL,
                `description` TEXT DEFAULT NULL,
                PRIMARY KEY (`incident_id`),
                KEY `idx_autism_inc_session` (`session_id`),
                CONSTRAINT `fk_autism_inc_session` FOREIGN KEY (`session_id`) REFERENCES `autism_session` (`session_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];

        foreach ($sql_statements as $sql) {
            $result = sqlStatement($sql);
            if (!$result) {
                throw new Exception("Failed to execute SQL statement");
            }
        }

        $messages[] = ['type' => 'success', 'text' => 'Database tables created successfully!'];
        $messages[] = ['type' => 'info', 'text' => '6 tables have been installed: autism_plan, autism_goal, autism_objective, autism_session, autism_session_activity, autism_session_incident'];
        
    } catch (Exception $e) {
        $error = true;
        $messages[] = ['type' => 'danger', 'text' => 'Error creating tables: ' . $e->getMessage()];
    }
}

// Check current database status
$tablesExist = false;
$tableCount = 0;
try {
    $result = sqlQuery("SHOW TABLES LIKE 'autism_%'");
    if ($result) {
        $tables = sqlStatement("SHOW TABLES LIKE 'autism_%'");
        while ($table = sqlFetchArray($tables)) {
            $tableCount++;
        }
        $tablesExist = $tableCount > 0;
    }
} catch (Exception $e) {
    $messages[] = ['type' => 'warning', 'text' => 'Could not check database status: ' . $e->getMessage()];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Autism Waiver System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-puzzle-piece me-2"></i>
                Autism Waiver System
            </a>
            <a href="index.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i>
                Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-database me-2"></i>
                            Database Setup
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Messages -->
                        <?php foreach ($messages as $message): ?>
                            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message['text']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endforeach; ?>

                        <!-- Current Status -->
                        <div class="mb-4">
                            <h5>Current Database Status</h5>
                            <div class="d-flex align-items-center">
                                <?php if ($tablesExist): ?>
                                    <span class="badge bg-success me-2">
                                        <i class="fas fa-check"></i>
                                        Tables Installed
                                    </span>
                                    <span class="text-muted"><?php echo $tableCount; ?> autism waiver tables found</span>
                                <?php else: ?>
                                    <span class="badge bg-warning me-2">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Not Installed
                                    </span>
                                    <span class="text-muted">No autism waiver tables found</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (!$tablesExist): ?>
                            <!-- Setup Form -->
                            <div class="border rounded p-4 bg-light">
                                <h5 class="text-primary">
                                    <i class="fas fa-cogs me-2"></i>
                                    Install Database Tables
                                </h5>
                                <p class="mb-3">
                                    This will create the following tables in your OpenEMR database:
                                </p>
                                <ul class="list-unstyled mb-4">
                                    <li><i class="fas fa-table text-primary me-2"></i><code>autism_plan</code> - Treatment plan headers</li>
                                    <li><i class="fas fa-table text-primary me-2"></i><code>autism_goal</code> - Goals within plans</li>
                                    <li><i class="fas fa-table text-primary me-2"></i><code>autism_objective</code> - Specific objectives</li>
                                    <li><i class="fas fa-table text-primary me-2"></i><code>autism_session</code> - Session records</li>
                                    <li><i class="fas fa-table text-primary me-2"></i><code>autism_session_activity</code> - Activity logs</li>
                                    <li><i class="fas fa-table text-primary me-2"></i><code>autism_session_incident</code> - Incident reports</li>
                                </ul>

                                <form method="post" onsubmit="return confirm('Are you sure you want to create the database tables? This action cannot be undone.');">
                                    <input type="hidden" name="action" value="setup">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="confirm" value="yes" id="confirmSetup" required>
                                        <label class="form-check-label" for="confirmSetup">
                                            I understand this will create new tables in the OpenEMR database
                                        </label>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-play me-2"></i>
                                        Create Database Tables
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <!-- Already Installed -->
                            <div class="alert alert-success" role="alert">
                                <h5 class="alert-heading">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Database Ready!
                                </h5>
                                <p class="mb-0">
                                    The autism waiver database tables are already installed and ready to use.
                                    You can now create treatment plans and document sessions.
                                </p>
                            </div>
                            <div class="text-center">
                                <a href="index.php" class="btn btn-success">
                                    <i class="fas fa-arrow-right me-2"></i>
                                    Go to Dashboard
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Technical Information -->
                        <div class="mt-4 pt-4 border-top">
                            <h6 class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Technical Information
                            </h6>
                            <small class="text-muted">
                                Tables will be created with InnoDB engine using utf8mb4 character set for full Unicode support.
                                Foreign key constraints ensure data integrity between related tables.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 