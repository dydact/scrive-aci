<?php

/**
 * AI Tools - Scrive AI-Powered Autism Waiver ERM
 * 
 * @package   Scrive
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
 * @license   MIT License
 */

// Include Scrive authentication
require_once 'auth.php';

// Initialize authentication
initScriveAuth();

// Get current user
$currentUser = getCurrentScriveUser();

$pageTitle = "AI Tools - Scrive";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .ai-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .ai-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .ai-card:hover {
            transform: translateY(-5px);
        }
        .beta-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-brain me-2"></i>
                Scrive
            </a>
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle text-light" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($currentUser['full_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
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

    <!-- Header -->
    <section class="ai-header py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="mb-0">
                        <i class="fas fa-robot me-3"></i>
                        AI-Powered Tools
                    </h1>
                    <p class="mb-0 mt-2">Intelligent assistance for autism waiver documentation and care planning</p>
                </div>
                <div class="col-auto">
                    <a href="index.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- AI Tools Section -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <!-- Progress Note AI -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card ai-card h-100 position-relative">
                        <div class="beta-badge">BETA</div>
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-edit text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Smart Progress Notes</h5>
                            <p class="card-text">AI-powered assistance for writing comprehensive progress notes with objective analysis and recommendations.</p>
                            <button class="btn btn-primary" disabled>
                                <i class="fas fa-magic me-2"></i>
                                Generate Note
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Goal Analysis -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card ai-card h-100 position-relative">
                        <div class="beta-badge">BETA</div>
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-target text-success" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Goal Achievement Analysis</h5>
                            <p class="card-text">Intelligent analysis of client progress towards treatment goals with data-driven insights.</p>
                            <button class="btn btn-success" disabled>
                                <i class="fas fa-chart-line me-2"></i>
                                Analyze Progress
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Treatment Recommendations -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card ai-card h-100 position-relative">
                        <div class="beta-badge">BETA</div>
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-lightbulb text-warning" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Treatment Recommendations</h5>
                            <p class="card-text">AI-generated treatment plan recommendations based on client history and evidence-based practices.</p>
                            <button class="btn btn-warning" disabled>
                                <i class="fas fa-brain me-2"></i>
                                Get Recommendations
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Documentation Assistant -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card ai-card h-100 position-relative">
                        <div class="beta-badge">BETA</div>
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-file-alt text-info" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Documentation Assistant</h5>
                            <p class="card-text">Smart templates and auto-completion for treatment plans, assessments, and reports.</p>
                            <button class="btn btn-info" disabled>
                                <i class="fas fa-pen-fancy me-2"></i>
                                Start Writing
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Outcome Prediction -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card ai-card h-100 position-relative">
                        <div class="beta-badge">BETA</div>
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-crystal-ball text-danger" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Outcome Prediction</h5>
                            <p class="card-text">Predictive analytics for treatment outcomes and intervention effectiveness.</p>
                            <button class="btn btn-danger" disabled>
                                <i class="fas fa-chart-area me-2"></i>
                                Predict Outcomes
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quality Assurance -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card ai-card h-100 position-relative">
                        <div class="beta-badge">BETA</div>
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-shield-check text-dark" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Quality Assurance</h5>
                            <p class="card-text">AI-powered quality checks for documentation completeness and compliance.</p>
                            <button class="btn btn-dark" disabled>
                                <i class="fas fa-check-double me-2"></i>
                                Check Quality
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Beta Notice -->
            <div class="alert alert-warning mt-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Beta Features:</strong> AI tools are currently in beta testing. Features are being developed 
                with privacy and HIPAA compliance as top priorities. All AI processing will be performed securely 
                with data encryption and access controls.
            </div>

            <!-- Future Features -->
            <div class="row mt-5">
                <div class="col-12">
                    <h3 class="mb-4">Coming Soon</h3>
                </div>
                <div class="col-md-6">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><i class="fas fa-microphone text-primary me-2"></i> Voice-to-text documentation</li>
                        <li class="list-group-item"><i class="fas fa-language text-success me-2"></i> Multi-language support</li>
                        <li class="list-group-item"><i class="fas fa-mobile-alt text-info me-2"></i> Mobile AI assistant</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><i class="fas fa-camera text-warning me-2"></i> Image analysis for behavior tracking</li>
                        <li class="list-group-item"><i class="fas fa-calendar-check text-danger me-2"></i> Intelligent scheduling optimization</li>
                        <li class="list-group-item"><i class="fas fa-users text-dark me-2"></i> Family engagement insights</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        <i class="fas fa-copyright me-1"></i>
                        2025 American Caregivers Incorporated - Scrive AI-Powered ERM v1.0.0
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        HIPAA Compliant | Maryland Approved
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 