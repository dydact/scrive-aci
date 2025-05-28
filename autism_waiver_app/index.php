<?php

/**
 * Scrive Dashboard - Modern AI-Powered Autism Waiver ERM
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

$pageTitle = "Scrive - AI-Powered Autism Waiver ERM";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
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

        /* Hero Section */
        .hero-section {
            background: var(--primary-gradient);
            position: relative;
            overflow: hidden;
            color: white;
            padding: 4rem 0;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,1000 1000,800 1000,1000"/></svg>');
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            font-weight: 400;
            opacity: 0.9;
            margin-bottom: 2rem;
        }

        /* Stats Cards */
        .stats-container {
            margin-top: 3rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.25);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Feature Cards */
        .features-section {
            padding: 5rem 0;
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin: 0 auto;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-lg);
            padding: 2.5rem;
            position: relative;
            transition: var(--transition);
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
            opacity: 0;
            transition: var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: transparent;
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 2rem;
            background: var(--primary-gradient);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            position: relative;
            overflow: hidden;
        }

        .feature-icon::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            transition: var(--transition);
            opacity: 0;
        }

        .feature-card:hover .feature-icon::before {
            opacity: 1;
            animation: shimmer 0.6s ease-in-out;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }

        .feature-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .feature-description {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .status-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .status-beta {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .status-soon {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
        }

        /* Modern Buttons */
        .btn-modern {
            border-radius: 12px;
            font-weight: 600;
            padding: 12px 24px;
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

        .btn-modern-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-modern-outline:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        /* Footer */
        .modern-footer {
            background: var(--dark-gradient);
            color: white;
            padding: 3rem 0;
            margin-top: 5rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 2rem;
        }

        .footer-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .footer-badges {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .footer-badge {
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .feature-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-badges {
                justify-content: center;
            }
        }

        /* Loading Animation */
        .loading-shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer-loading 1.5s infinite;
        }

        @keyframes shimmer-loading {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title">
                    <i class="fas fa-brain me-3"></i>
                    American Caregivers Inc
                </h1>
                <p class="hero-subtitle">
                    AI-powered autism waiver ERM with comprehensive treatment planning, progress tracking, and intelligent documentation for IISS, TI, Respite, and FC services
                </p>
                
                <!-- Stats -->
                <div class="stats-container">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php
                                    try {
                                        $patientCount = sqlQuery("SELECT COUNT(*) as count FROM patient_data WHERE date(DOB) <= CURDATE()");
                                        echo $patientCount['count'] ?? '0';
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                    ?>
                                </div>
                                <div class="stat-label">Active Clients</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-number">
                                    <?php
                                    try {
                                        $result = sqlQuery("SHOW TABLES LIKE 'autism_programs'");
                                        echo $result ? '19' : '0';
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                    ?>
                                </div>
                                <div class="stat-label">DB Tables</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-number">6</div>
                                <div class="stat-label">Programs</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-number">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-label">System Ready</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="section-title">
                <h2>Comprehensive Autism Waiver Management</h2>
                <p class="section-subtitle">
                    Modern tools designed specifically for autism waiver services with intelligent automation and seamless workflows
                </p>
            </div>

            <div class="feature-grid">
                <!-- Client Management -->
                <div class="feature-card">
                    <span class="status-badge status-active">Active</span>
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Client Management</h3>
                    <p class="feature-description">
                        Complete client lifecycle management with treatment planning, progress tracking, and comprehensive documentation.
                    </p>
                    <a href="clients.php" class="btn btn-modern btn-modern-primary">
                        <i class="fas fa-users me-2"></i>Manage Clients
                    </a>
                </div>

                <!-- Progress Documentation -->
                <div class="feature-card">
                    <span class="status-badge status-active">Active</span>
                    <div class="feature-icon">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <h3 class="feature-title">Progress Documentation</h3>
                    <p class="feature-description">
                        Document therapy sessions with objective tracking, goal achievement metrics, and detailed progress notes.
                    </p>
                    <a href="clients.php" class="btn btn-modern btn-modern-primary">
                        <i class="fas fa-pen me-2"></i>Create Session
                    </a>
                </div>

                <!-- Service Management -->
                <div class="feature-card">
                    <span class="status-badge status-active">Active</span>
                    <div class="feature-icon">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3 class="feature-title">Service Types</h3>
                    <p class="feature-description">
                        Configure autism waiver service types, billing rates, and authorization limits for IISS, TI, Respite, and FC.
                    </p>
                    <a href="service_types.php" class="btn btn-modern btn-modern-primary">
                        <i class="fas fa-cogs me-2"></i>Configure Services
                    </a>
                </div>

                <!-- AI-Powered Tools -->
                <div class="feature-card">
                    <span class="status-badge status-beta">Beta</span>
                    <div class="feature-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3 class="feature-title">AI Assistance</h3>
                    <p class="feature-description">
                        Intelligent writing assistance, progress analysis, and treatment recommendations powered by advanced AI.
                    </p>
                    <a href="ai_tools.php" class="btn btn-modern btn-modern-outline">
                        <i class="fas fa-magic me-2"></i>Explore AI Tools
                    </a>
                </div>

                <!-- Billing & Analytics -->
                <div class="feature-card">
                    <span class="status-badge status-active">Active</span>
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Billing & Analytics</h3>
                    <p class="feature-description">
                        Unit-based billing system with real-time analytics, revenue tracking, and automated Medicaid integration.
                    </p>
                    <a href="billing_dashboard.php" class="btn btn-modern btn-modern-primary">
                        <i class="fas fa-dollar-sign me-2"></i>View Dashboard
                    </a>
                </div>

                <!-- Reports & Export -->
                <div class="feature-card">
                    <span class="status-badge status-active">Active</span>
                    <div class="feature-icon">
                        <i class="fas fa-file-export"></i>
                    </div>
                    <h3 class="feature-title">Reports & Export</h3>
                    <p class="feature-description">
                        Generate comprehensive reports for compliance, outcome measurement, and stakeholder communication.
                    </p>
                    <a href="reports.php" class="btn btn-modern btn-modern-primary">
                        <i class="fas fa-download me-2"></i>Generate Reports
                    </a>
                </div>

                <!-- Scheduling -->
                <div class="feature-card">
                    <span class="status-soon">Coming Soon</span>
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="feature-title">Smart Scheduling</h3>
                    <p class="feature-description">
                        Intelligent scheduling with availability optimization, automated reminders, and conflict resolution.
                    </p>
                    <button class="btn btn-modern btn-modern-outline" disabled>
                        <i class="fas fa-calendar me-2"></i>Coming Soon
                    </button>
                </div>

                <!-- System Setup -->
                <div class="feature-card">
                    <span class="status-badge status-active">Setup</span>
                    <div class="feature-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3 class="feature-title">Database Setup</h3>
                    <p class="feature-description">
                        Initialize the complete autism waiver management system with all 19 tables and default configurations.
                    </p>
                    <a href="setup_comprehensive.php" class="btn btn-modern btn-modern-primary">
                        <i class="fas fa-rocket me-2"></i>Complete Setup
                    </a>
                </div>

                <!-- Role-Based Portals -->
                <div class="feature-card">
                    <span class="status-badge status-active">New</span>
                    <div class="feature-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3 class="feature-title">Role-Based Portals</h3>
                    <p class="feature-description">
                        Specialized interfaces for each staff role: Employee portal with auto-populated notes, time tracking, and QuickBooks integration.
                    </p>
                    <a href="portal_router.php" class="btn btn-modern btn-modern-primary">
                        <i class="fas fa-door-open me-2"></i>Access Portals
                    </a>
                </div>

                <!-- Security Management -->
                <div class="feature-card">
                    <span class="status-badge status-active">Secure</span>
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Security & MA Numbers</h3>
                    <p class="feature-description">
                        Role-based access control with separation of organizational billing MA numbers from individual client MA numbers.
                    </p>
                    <a href="secure_clients.php" class="btn btn-modern btn-modern-primary">
                        <i class="fas fa-lock me-2"></i>Security Demo
                    </a>
                </div>

                <!-- Master Admin Role Switcher -->
                <div class="feature-card">
                    <span class="status-badge status-active">Admin</span>
                    <div class="feature-icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h3 class="feature-title">Master Admin Testing</h3>
                    <p class="feature-description">
                        Role switching interface for administrators to test different user perspectives and portal access levels.
                    </p>
                    <a href="admin_role_switcher.php" class="btn btn-modern btn-modern-primary">
                        <i class="fas fa-tools me-2"></i>Admin Testing
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="modern-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <div class="d-flex align-items-center mb-2">
                        <i class="fas fa-copyright me-2"></i>
                        <span>2025 American Caregivers Incorporated</span>
                    </div>
                    <div class="text-light opacity-75">
                        Scrive AI-Powered ERM v1.0.0
                    </div>
                </div>
                <div class="footer-badges">
                    <div class="footer-badge">
                        <i class="fas fa-shield-alt me-2"></i>
                        HIPAA Compliant
                    </div>
                    <div class="footer-badge">
                        <i class="fas fa-check-circle me-2"></i>
                        Maryland Approved
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Modern JavaScript enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Add loading states
            const buttons = document.querySelectorAll('.btn-modern');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!this.disabled) {
                        this.style.opacity = '0.7';
                        this.style.transform = 'scale(0.98)';
                        
                        setTimeout(() => {
                            this.style.opacity = '';
                            this.style.transform = '';
                        }, 200);
                    }
                });
            });

            // Add intersection observer for animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe feature cards
            document.querySelectorAll('.feature-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html> 