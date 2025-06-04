<?php
session_start();
require_once 'auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: simple_login.php");
    exit();
}

// Database connection
try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/autism_waiver.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'employee';

// Get user's training progress
$stmt = $pdo->prepare("
    SELECT tm.*, tp.completed_date, tp.score, tp.certificate_url
    FROM training_modules tm
    LEFT JOIN training_progress tp ON tm.id = tp.module_id AND tp.user_id = ?
    ORDER BY tm.category, tm.order_index
");
$stmt->execute([$user_id]);
$training_modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group modules by category
$modules_by_category = [];
foreach ($training_modules as $module) {
    $modules_by_category[$module['category']][] = $module;
}

// Get user's training statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT module_id) as completed_modules,
        AVG(score) as average_score
    FROM training_progress
    WHERE user_id = ? AND completed_date IS NOT NULL
");
$stmt->execute([$user_id]);
$training_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Check for expiring certifications
$stmt = $pdo->prepare("
    SELECT tm.title, tp.completed_date
    FROM training_progress tp
    JOIN training_modules tm ON tp.module_id = tm.id
    WHERE tp.user_id = ? 
    AND tm.requires_renewal = 1
    AND DATE(tp.completed_date, '+' || tm.renewal_months || ' months') <= DATE('now', '+30 days')
    AND DATE(tp.completed_date, '+' || tm.renewal_months || ' months') > DATE('now')
");
$stmt->execute([$user_id]);
$expiring_certifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle module completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_module'])) {
    $module_id = $_POST['module_id'];
    $score = $_POST['score'] ?? null;
    
    $stmt = $pdo->prepare("
        INSERT OR REPLACE INTO training_progress (user_id, module_id, completed_date, score)
        VALUES (?, ?, datetime('now'), ?)
    ");
    $stmt->execute([$user_id, $module_id, $score]);
    
    header("Location: training.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Center - Autism Waiver App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #4a90e2;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .training-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .module-card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s;
            margin-bottom: 1rem;
        }
        .module-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .category-header {
            background-color: #f8f9fa;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #4a90e2;
        }
        .progress-bar-custom {
            height: 25px;
            border-radius: 15px;
        }
        .required-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #dc3545;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
        .completed-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #28a745;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
        .training-path {
            background-color: #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .certification-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .video-placeholder {
            background-color: #343a40;
            color: white;
            padding: 3rem;
            text-align: center;
            border-radius: 0.25rem;
            margin-bottom: 1rem;
        }
        .quiz-section {
            background-color: #e7f3ff;
            border-radius: 0.25rem;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        .resource-link {
            display: inline-block;
            margin-right: 1rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-hands-helping"></i> Autism Waiver App
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="clients.php">
                            <i class="fas fa-users"></i> Clients
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="training.php">
                            <i class="fas fa-graduation-cap"></i> Training
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Training Header -->
    <div class="training-header">
        <div class="container">
            <h1><i class="fas fa-graduation-cap"></i> Training Center</h1>
            <p class="mb-0">Complete required training modules and enhance your professional skills</p>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Training module completed successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($expiring_certifications)): ?>
            <div class="certification-warning">
                <h5><i class="fas fa-exclamation-triangle"></i> Certification Renewal Required</h5>
                <p>The following certifications will expire within 30 days:</p>
                <ul class="mb-0">
                    <?php foreach ($expiring_certifications as $cert): ?>
                        <li><?php echo htmlspecialchars($cert['title']); ?> - Expires: 
                            <?php echo date('M d, Y', strtotime($cert['completed_date'] . ' +1 year')); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Training Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-primary"><?php echo $training_stats['completed_modules'] ?? 0; ?></h3>
                        <p class="mb-0">Modules Completed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <h3 class="text-success"><?php echo round($training_stats['average_score'] ?? 0); ?>%</h3>
                        <p class="mb-0">Average Score</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center">
                    <div class="card-body">
                        <?php
                        $total_modules = count($training_modules);
                        $completed = $training_stats['completed_modules'] ?? 0;
                        $progress = $total_modules > 0 ? round(($completed / $total_modules) * 100) : 0;
                        ?>
                        <h3 class="text-info"><?php echo $progress; ?>%</h3>
                        <p class="mb-0">Overall Progress</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role-Based Training Path -->
        <?php if ($role === 'RBT'): ?>
            <div class="training-path">
                <h4><i class="fas fa-road"></i> RBT Training Path</h4>
                <p>Complete these modules in order to fulfill your RBT training requirements:</p>
                <ol>
                    <li>New Employee Orientation</li>
                    <li>HIPAA Compliance Training</li>
                    <li>RBT Ethics and Professional Conduct</li>
                    <li>Measurement and Data Collection</li>
                    <li>Behavior Reduction Procedures</li>
                    <li>Documentation Standards</li>
                </ol>
            </div>
        <?php elseif ($role === 'BCBA'): ?>
            <div class="training-path">
                <h4><i class="fas fa-road"></i> BCBA Training Requirements</h4>
                <p>Required annual training modules for BCBA certification maintenance:</p>
                <ol>
                    <li>HIPAA Compliance Training</li>
                    <li>Maryland Medicaid Updates</li>
                    <li>Supervision and Training Best Practices</li>
                    <li>Clinical Documentation Standards</li>
                    <li>Ethics for Behavior Analysts</li>
                </ol>
            </div>
        <?php elseif ($role === 'billing'): ?>
            <div class="training-path">
                <h4><i class="fas fa-road"></i> Billing Specialist Training</h4>
                <p>Essential training for billing and administrative staff:</p>
                <ol>
                    <li>Maryland Medicaid Billing Requirements</li>
                    <li>CPT Coding for ABA Services</li>
                    <li>Claims Processing and Submission</li>
                    <li>Denial Management</li>
                    <li>HIPAA for Billing Staff</li>
                </ol>
            </div>
        <?php endif; ?>

        <!-- Training Modules by Category -->
        <?php
        $categories = [
            'orientation' => ['title' => 'New Employee Orientation', 'icon' => 'fa-user-plus'],
            'compliance' => ['title' => 'Compliance Training', 'icon' => 'fa-shield-alt'],
            'clinical' => ['title' => 'Clinical Best Practices', 'icon' => 'fa-heartbeat'],
            'billing' => ['title' => 'Billing & Documentation', 'icon' => 'fa-file-invoice-dollar'],
            'system' => ['title' => 'System Usage Tutorials', 'icon' => 'fa-laptop-code'],
            'professional' => ['title' => 'Professional Development', 'icon' => 'fa-chart-line']
        ];

        // Sample training modules (in production, these would come from the database)
        $sample_modules = [
            'orientation' => [
                ['title' => 'Welcome to ACI', 'duration' => '30 min', 'required' => true, 'completed' => false],
                ['title' => 'Company Policies & Procedures', 'duration' => '45 min', 'required' => true, 'completed' => false],
                ['title' => 'Introduction to ABA', 'duration' => '60 min', 'required' => true, 'completed' => false]
            ],
            'compliance' => [
                ['title' => 'HIPAA Privacy & Security', 'duration' => '45 min', 'required' => true, 'completed' => true],
                ['title' => 'Mandated Reporter Training', 'duration' => '30 min', 'required' => true, 'completed' => false],
                ['title' => 'Workplace Safety', 'duration' => '30 min', 'required' => true, 'completed' => false]
            ],
            'clinical' => [
                ['title' => 'Data Collection Methods', 'duration' => '60 min', 'required' => false, 'completed' => false],
                ['title' => 'Behavior Intervention Plans', 'duration' => '90 min', 'required' => false, 'completed' => false],
                ['title' => 'Parent Training Techniques', 'duration' => '45 min', 'required' => false, 'completed' => false]
            ],
            'billing' => [
                ['title' => 'Maryland Medicaid Guidelines', 'duration' => '60 min', 'required' => true, 'completed' => false],
                ['title' => 'CPT Coding for ABA', 'duration' => '45 min', 'required' => false, 'completed' => false],
                ['title' => 'Documentation Requirements', 'duration' => '30 min', 'required' => true, 'completed' => false]
            ],
            'system' => [
                ['title' => 'Using the Client Portal', 'duration' => '20 min', 'required' => true, 'completed' => true],
                ['title' => 'Mobile App Tutorial', 'duration' => '15 min', 'required' => false, 'completed' => false],
                ['title' => 'Reporting Features', 'duration' => '30 min', 'required' => false, 'completed' => false]
            ]
        ];

        foreach ($categories as $cat_key => $category):
            if (isset($sample_modules[$cat_key])):
        ?>
            <div class="category-header">
                <h4><i class="fas <?php echo $category['icon']; ?>"></i> <?php echo $category['title']; ?></h4>
            </div>
            
            <div class="row mb-4">
                <?php foreach ($sample_modules[$cat_key] as $index => $module): ?>
                    <div class="col-md-4">
                        <div class="card module-card position-relative">
                            <?php if ($module['completed']): ?>
                                <span class="completed-badge">
                                    <i class="fas fa-check"></i> Completed
                                </span>
                            <?php elseif ($module['required']): ?>
                                <span class="required-badge">Required</span>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $module['title']; ?></h5>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-clock"></i> <?php echo $module['duration']; ?>
                                </p>
                                
                                <?php if (!$module['completed']): ?>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                                            data-bs-target="#moduleModal<?php echo $cat_key . $index; ?>">
                                        <i class="fas fa-play"></i> Start Module
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success btn-sm" disabled>
                                        <i class="fas fa-certificate"></i> View Certificate
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Module Modal -->
                    <div class="modal fade" id="moduleModal<?php echo $cat_key . $index; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title"><?php echo $module['title']; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="video-placeholder">
                                        <i class="fas fa-play-circle fa-3x mb-3"></i>
                                        <p>Video content will be displayed here</p>
                                    </div>
                                    
                                    <h6>Module Resources:</h6>
                                    <div class="mb-3">
                                        <a href="#" class="resource-link">
                                            <i class="fas fa-file-pdf"></i> Training Manual
                                        </a>
                                        <a href="#" class="resource-link">
                                            <i class="fas fa-file-alt"></i> Quick Reference Guide
                                        </a>
                                        <a href="#" class="resource-link">
                                            <i class="fas fa-link"></i> Additional Resources
                                        </a>
                                    </div>
                                    
                                    <div class="quiz-section">
                                        <h6><i class="fas fa-question-circle"></i> Knowledge Check</h6>
                                        <p>Complete the quiz to finish this module:</p>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">1. What is the primary goal of HIPAA?</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="q1" id="q1a">
                                                <label class="form-check-label" for="q1a">
                                                    To protect patient health information
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="q1" id="q1b">
                                                <label class="form-check-label" for="q1b">
                                                    To increase healthcare costs
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">2. When should you document client sessions?</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="q2" id="q2a">
                                                <label class="form-check-label" for="q2a">
                                                    Within 24 hours of the session
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="q2" id="q2b">
                                                <label class="form-check-label" for="q2b">
                                                    Within a week
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="module_id" value="<?php echo $cat_key . $index; ?>">
                                        <input type="hidden" name="score" value="100">
                                        <button type="submit" name="complete_module" class="btn btn-success">
                                            <i class="fas fa-check"></i> Complete Module
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php 
            endif;
        endforeach; 
        ?>

        <!-- Additional Resources -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-book"></i> Additional Resources</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Professional Organizations</h6>
                        <ul>
                            <li><a href="https://www.bacb.com" target="_blank">Behavior Analyst Certification Board (BACB)</a></li>
                            <li><a href="https://www.abainternational.org" target="_blank">Association for Behavior Analysis International</a></li>
                            <li><a href="https://health.maryland.gov/mmcp" target="_blank">Maryland Medicaid</a></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Quick Links</h6>
                        <ul>
                            <li><a href="#">Employee Handbook</a></li>
                            <li><a href="#">Clinical Guidelines</a></li>
                            <li><a href="#">Billing Manual</a></li>
                            <li><a href="#">Emergency Procedures</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Training History -->
        <div class="card mt-4 mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history"></i> Your Training History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Module</th>
                                <th>Completed Date</th>
                                <th>Score</th>
                                <th>Certificate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>HIPAA Privacy & Security</td>
                                <td>Jan 15, 2025</td>
                                <td>95%</td>
                                <td><a href="#" class="btn btn-sm btn-outline-primary">Download</a></td>
                            </tr>
                            <tr>
                                <td>Using the Client Portal</td>
                                <td>Jan 10, 2025</td>
                                <td>100%</td>
                                <td><a href="#" class="btn btn-sm btn-outline-primary">Download</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Progress tracking
        document.querySelectorAll('input[type="radio"]').forEach(input => {
            input.addEventListener('change', function() {
                // Track quiz progress
                const modal = this.closest('.modal');
                const radios = modal.querySelectorAll('input[type="radio"]:checked');
                const totalQuestions = modal.querySelectorAll('.mb-3').length;
                
                if (radios.length === totalQuestions) {
                    modal.querySelector('button[name="complete_module"]').disabled = false;
                }
            });
        });

        // Certificate generation placeholder
        function generateCertificate(moduleName, userName, date) {
            alert('Certificate generation will be implemented with PDF generation library');
        }
    </script>
</body>
</html>