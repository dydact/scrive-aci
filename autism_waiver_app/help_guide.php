<?php

/**
 * Scrive Help Guide - Comprehensive Documentation
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

$pageTitle = "Scrive Help Guide - Comprehensive Documentation";

// Determine user role for content filtering
$userRole = 'staff'; // Default role
if (isset($_SESSION['scrive_role'])) {
    $userRole = $_SESSION['scrive_role'];
} elseif (isScriveAdmin()) {
    $userRole = 'admin';
}

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
        }

        /* Modern Navigation */
        .modern-nav {
            background: rgba(26, 29, 41, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .modern-nav .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Help Header */
        .help-header {
            background: var(--primary-gradient);
            color: white;
            padding: 3rem 0;
            position: relative;
            overflow: hidden;
        }

        .help-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,1000 1000,800 1000,1000"/></svg>');
            background-size: cover;
        }

        .help-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        /* Search Box */
        .search-container {
            position: relative;
            max-width: 600px;
            margin: 2rem auto;
        }

        .search-input {
            width: 100%;
            padding: 1rem 3rem 1rem 1.5rem;
            border: 2px solid var(--border-color);
            border-radius: 50px;
            font-size: 1rem;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .quick-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 50px;
            color: var(--text-primary);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }

        .quick-action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Content Layout */
        .help-content {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        /* Sidebar Navigation */
        .help-sidebar {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 100px;
            box-shadow: var(--shadow-sm);
        }

        .help-nav-section {
            margin-bottom: 2rem;
        }

        .help-nav-title {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-secondary);
            margin-bottom: 0.75rem;
            letter-spacing: 0.5px;
        }

        .help-nav-list {
            list-style: none;
            padding: 0;
        }

        .help-nav-item {
            margin-bottom: 0.5rem;
        }

        .help-nav-link {
            display: block;
            padding: 0.5rem 0.75rem;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 8px;
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .help-nav-link:hover {
            background: var(--primary-color);
            color: white;
        }

        .help-nav-link.active {
            background: var(--primary-gradient);
            color: white;
            font-weight: 500;
        }

        /* Main Content */
        .help-main {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-sm);
        }

        /* Help Sections */
        .help-section {
            margin-bottom: 3rem;
            display: none;
        }

        .help-section.active {
            display: block;
        }

        .section-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .section-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            border-radius: 12px;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .section-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        /* Collapsible Content */
        .help-topic {
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .help-topic-header {
            padding: 1.25rem;
            background: #f8f9fa;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: var(--transition);
        }

        .help-topic-header:hover {
            background: #f1f3f5;
        }

        .help-topic-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            color: var(--text-primary);
        }

        .help-topic-icon {
            transition: var(--transition);
        }

        .help-topic.expanded .help-topic-icon {
            transform: rotate(180deg);
        }

        .help-topic-content {
            padding: 0 1.25rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .help-topic.expanded .help-topic-content {
            max-height: 2000px;
            padding: 1.25rem;
        }

        /* Step by Step Tutorials */
        .tutorial-steps {
            counter-reset: step-counter;
            margin-top: 1.5rem;
        }

        .tutorial-step {
            position: relative;
            padding-left: 3rem;
            margin-bottom: 2rem;
            counter-increment: step-counter;
        }

        .tutorial-step::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            width: 2rem;
            height: 2rem;
            background: var(--primary-gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .step-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .step-content {
            color: var(--text-secondary);
        }

        .screenshot-placeholder {
            background: #f8f9fa;
            border: 2px dashed var(--border-color);
            border-radius: var(--border-radius);
            padding: 3rem;
            text-align: center;
            color: var(--text-secondary);
            margin: 1rem 0;
        }

        /* FAQ Section */
        .faq-item {
            margin-bottom: 1rem;
        }

        .faq-question {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .faq-answer {
            color: var(--text-secondary);
            padding-left: 1.5rem;
            margin-bottom: 1rem;
        }

        /* Quick Reference Cards */
        .reference-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .reference-card {
            background: #f8f9fa;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            transition: var(--transition);
        }

        .reference-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .reference-card-title {
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .reference-list {
            list-style: none;
            padding: 0;
        }

        .reference-list li {
            padding: 0.5rem 0;
            color: var(--text-secondary);
            display: flex;
            align-items: start;
            gap: 0.5rem;
        }

        .reference-list li::before {
            content: "→";
            color: var(--primary-color);
            font-weight: 600;
        }

        /* Print Styles */
        @media print {
            .modern-nav,
            .help-sidebar,
            .quick-actions,
            .search-container {
                display: none !important;
            }

            .help-content {
                grid-template-columns: 1fr;
            }

            .help-section {
                display: block !important;
                page-break-inside: avoid;
            }

            .help-topic {
                border: 1px solid #ddd;
            }

            .help-topic-content {
                max-height: none !important;
                padding: 1rem !important;
            }
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .help-content {
                grid-template-columns: 1fr;
            }

            .help-sidebar {
                position: relative;
                top: 0;
                margin-bottom: 2rem;
            }
        }

        /* Loading States */
        .content-loading {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Support Contact */
        .support-contact {
            background: var(--primary-gradient);
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-top: 3rem;
            text-align: center;
        }

        .support-contact h3 {
            margin-bottom: 1rem;
        }

        .support-contact-info {
            display: flex;
            gap: 2rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }

        .contact-method {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Role-based styling */
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--primary-gradient);
            color: white;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 0.5rem;
        }

        .role-content {
            background: #f0f4ff;
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
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
                <a href="index.php" class="nav-link text-light me-3">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
                <button class="btn btn-outline-light btn-sm" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print Guide
                </button>
            </div>
        </div>
    </nav>

    <!-- Help Header -->
    <div class="help-header">
        <div class="container position-relative">
            <h1 class="help-title">
                <i class="fas fa-life-ring me-3"></i>
                Scrive Help Center
            </h1>
            <p class="mb-0">Comprehensive guide for all system features and functions</p>
        </div>
    </div>

    <!-- Search and Quick Actions -->
    <div class="container mt-4">
        <div class="search-container">
            <input type="text" class="search-input" id="helpSearch" placeholder="Search for help topics...">
            <i class="fas fa-search search-icon"></i>
        </div>

        <div class="quick-actions">
            <a href="#getting-started" class="quick-action-btn" data-section="getting-started">
                <i class="fas fa-rocket"></i>
                Getting Started
            </a>
            <a href="#staff-portal" class="quick-action-btn" data-section="staff-portal">
                <i class="fas fa-user-clock"></i>
                Staff Portal
            </a>
            <a href="#case-manager" class="quick-action-btn" data-section="case-manager">
                <i class="fas fa-users-cog"></i>
                Case Manager
            </a>
            <a href="#billing" class="quick-action-btn" data-section="billing">
                <i class="fas fa-file-invoice-dollar"></i>
                Billing
            </a>
            <a href="#troubleshooting" class="quick-action-btn" data-section="troubleshooting">
                <i class="fas fa-tools"></i>
                Troubleshooting
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="help-content">
            <!-- Sidebar Navigation -->
            <aside class="help-sidebar">
                <div class="help-nav-section">
                    <h6 class="help-nav-title">Getting Started</h6>
                    <ul class="help-nav-list">
                        <li class="help-nav-item">
                            <a href="#getting-started" class="help-nav-link active" data-section="getting-started">
                                Overview & Login
                            </a>
                        </li>
                        <li class="help-nav-item">
                            <a href="#navigation" class="help-nav-link" data-section="navigation">
                                Navigation Basics
                            </a>
                        </li>
                        <li class="help-nav-item">
                            <a href="#dashboard" class="help-nav-link" data-section="dashboard">
                                Dashboard Overview
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="help-nav-section">
                    <h6 class="help-nav-title">Staff Functions</h6>
                    <ul class="help-nav-list">
                        <li class="help-nav-item">
                            <a href="#staff-portal" class="help-nav-link" data-section="staff-portal">
                                Staff Portal
                            </a>
                        </li>
                        <li class="help-nav-item">
                            <a href="#time-clock" class="help-nav-link" data-section="time-clock">
                                Time Clock
                            </a>
                        </li>
                        <li class="help-nav-item">
                            <a href="#session-notes" class="help-nav-link" data-section="session-notes">
                                Session Notes
                            </a>
                        </li>
                        <li class="help-nav-item">
                            <a href="#schedules" class="help-nav-link" data-section="schedules">
                                View Schedules
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="help-nav-section">
                    <h6 class="help-nav-title">Management</h6>
                    <ul class="help-nav-list">
                        <li class="help-nav-item">
                            <a href="#case-manager" class="help-nav-link" data-section="case-manager">
                                Case Manager Tools
                            </a>
                        </li>
                        <li class="help-nav-item">
                            <a href="#supervisor" class="help-nav-link" data-section="supervisor">
                                Supervisor Functions
                            </a>
                        </li>
                        <li class="help-nav-item">
                            <a href="#reports" class="help-nav-link" data-section="reports">
                                Reports & Analytics
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="help-nav-section">
                    <h6 class="help-nav-title">Operations</h6>
                    <ul class="help-nav-list">
                        <li class="help-nav-item">
                            <a href="#billing" class="help-nav-link" data-section="billing">
                                Billing Operations
                            </a>
                        </li>
                        <li class="help-nav-item">
                            <a href="#admin" class="help-nav-link" data-section="admin">
                                Admin Functions
                            </a>
                        </li>
                        <li class="help-nav-item">
                            <a href="#settings" class="help-nav-link" data-section="settings">
                                System Settings
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="help-nav-section">
                    <h6 class="help-nav-title">Support</h6>
                    <ul class="help-nav-list">
                        <li class="help-nav-item">
                            <a href="#faq" class="help-nav-link" data-section="faq">
                                FAQ
                            </a>
                        </li>
                        <li class="help-nav-item">
                            <a href="#troubleshooting" class="help-nav-link" data-section="troubleshooting">
                                Troubleshooting
                            </a>
                        </li>
                        <li class="help-nav-item">
                            <a href="#contact" class="help-nav-link" data-section="contact">
                                Contact Support
                            </a>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- Main Help Content -->
            <main class="help-main">
                <!-- Getting Started Section -->
                <section id="getting-started" class="help-section active">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <h2 class="section-title">Getting Started</h2>
                        <p class="section-subtitle">Learn the basics of using Scrive</p>
                    </div>

                    <div class="help-topic expanded">
                        <div class="help-topic-header">
                            <h3 class="help-topic-title">System Login</h3>
                            <i class="fas fa-chevron-down help-topic-icon"></i>
                        </div>
                        <div class="help-topic-content">
                            <div class="tutorial-steps">
                                <div class="tutorial-step">
                                    <h4 class="step-title">Navigate to the Login Page</h4>
                                    <p class="step-content">Open your web browser and go to the Scrive login page. You should see the modern login interface with the Scrive logo.</p>
                                    <div class="screenshot-placeholder">
                                        <i class="fas fa-image fa-3x mb-2"></i>
                                        <p>Screenshot: Login Page</p>
                                    </div>
                                </div>
                                <div class="tutorial-step">
                                    <h4 class="step-title">Enter Your Credentials</h4>
                                    <p class="step-content">Enter your username and password provided by your administrator. Make sure to use the correct case for both fields.</p>
                                </div>
                                <div class="tutorial-step">
                                    <h4 class="step-title">Select Remember Me (Optional)</h4>
                                    <p class="step-content">Check the "Remember Me" box if you're using your personal device to stay logged in for 24 hours.</p>
                                </div>
                                <div class="tutorial-step">
                                    <h4 class="step-title">Click Sign In</h4>
                                    <p class="step-content">Click the "Sign In" button to access your dashboard. You'll be redirected to your role-specific portal.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="help-topic">
                        <div class="help-topic-header">
                            <h3 class="help-topic-title">First Time Setup</h3>
                            <i class="fas fa-chevron-down help-topic-icon"></i>
                        </div>
                        <div class="help-topic-content">
                            <p>When logging in for the first time, you may need to:</p>
                            <ul>
                                <li>Update your password</li>
                                <li>Verify your contact information</li>
                                <li>Set up two-factor authentication (if required)</li>
                                <li>Review and accept system terms</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Navigation Section -->
                <section id="navigation" class="help-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-compass"></i>
                        </div>
                        <h2 class="section-title">Navigation Basics</h2>
                        <p class="section-subtitle">Learn how to navigate through Scrive</p>
                    </div>

                    <div class="help-topic expanded">
                        <div class="help-topic-header">
                            <h3 class="help-topic-title">Main Navigation Menu</h3>
                            <i class="fas fa-chevron-down help-topic-icon"></i>
                        </div>
                        <div class="help-topic-content">
                            <p>The main navigation bar appears at the top of every page and includes:</p>
                            <ul>
                                <li><strong>Scrive Logo:</strong> Click to return to your dashboard</li>
                                <li><strong>User Menu:</strong> Access your profile and logout</li>
                                <li><strong>Role Indicator:</strong> Shows your current role and permissions</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Dashboard Section -->
                <section id="dashboard" class="help-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <h2 class="section-title">Dashboard Overview</h2>
                        <p class="section-subtitle">Understanding your personalized dashboard</p>
                    </div>

                    <div class="reference-cards">
                        <div class="reference-card">
                            <h4 class="reference-card-title">Dashboard Widgets</h4>
                            <ul class="reference-list">
                                <li>Active Clients counter</li>
                                <li>Pending tasks and approvals</li>
                                <li>Recent activity feed</li>
                                <li>Quick access buttons</li>
                                <li>Performance metrics</li>
                            </ul>
                        </div>
                        <div class="reference-card">
                            <h4 class="reference-card-title">Quick Actions</h4>
                            <ul class="reference-list">
                                <li>Create new session note</li>
                                <li>View today's schedule</li>
                                <li>Access client records</li>
                                <li>Generate reports</li>
                                <li>Submit time entries</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Staff Portal Section -->
                <section id="staff-portal" class="help-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <h2 class="section-title">Staff Portal</h2>
                        <p class="section-subtitle">Tools and features for direct care staff</p>
                    </div>

                    <div class="help-topic expanded">
                        <div class="help-topic-header">
                            <h3 class="help-topic-title">Mobile Time Clock</h3>
                            <i class="fas fa-chevron-down help-topic-icon"></i>
                        </div>
                        <div class="help-topic-content">
                            <div class="role-content">
                                <strong>Available for:</strong> <span class="role-badge">Staff</span> <span class="role-badge">Supervisor</span>
                            </div>
                            <div class="tutorial-steps">
                                <div class="tutorial-step">
                                    <h4 class="step-title">Clock In</h4>
                                    <p class="step-content">Open the staff portal and tap the "Clock In" button. Your location and time will be automatically recorded.</p>
                                </div>
                                <div class="tutorial-step">
                                    <h4 class="step-title">Select Client</h4>
                                    <p class="step-content">Choose the client you'll be working with from the dropdown list.</p>
                                </div>
                                <div class="tutorial-step">
                                    <h4 class="step-title">Clock Out</h4>
                                    <p class="step-content">When your session ends, tap "Clock Out" to complete your time entry.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="help-topic">
                        <div class="help-topic-header">
                            <h3 class="help-topic-title">Creating Session Notes</h3>
                            <i class="fas fa-chevron-down help-topic-icon"></i>
                        </div>
                        <div class="help-topic-content">
                            <p>Session notes are automatically pre-populated based on:</p>
                            <ul>
                                <li>Client's treatment plan goals</li>
                                <li>Previous session outcomes</li>
                                <li>Scheduled activities</li>
                                <li>Service type requirements</li>
                            </ul>
                            <p>Simply review, modify as needed, and submit for approval.</p>
                        </div>
                    </div>
                </section>

                <!-- Time Clock Section -->
                <section id="time-clock" class="help-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h2 class="section-title">Time Clock System</h2>
                        <p class="section-subtitle">Track your work hours accurately</p>
                    </div>

                    <div class="help-topic expanded">
                        <div class="help-topic-header">
                            <h3 class="help-topic-title">QuickBooks Integration</h3>
                            <i class="fas fa-chevron-down help-topic-icon"></i>
                        </div>
                        <div class="help-topic-content">
                            <p>Our time clock system integrates seamlessly with QuickBooks for payroll:</p>
                            <ul>
                                <li>Automatic time entry sync</li>
                                <li>Client-specific time allocation</li>
                                <li>Overtime calculation</li>
                                <li>Export to payroll format</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Session Notes Section -->
                <section id="session-notes" class="help-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-file-medical"></i>
                        </div>
                        <h2 class="section-title">Session Documentation</h2>
                        <p class="section-subtitle">Complete and accurate session notes</p>
                    </div>

                    <div class="reference-cards">
                        <div class="reference-card">
                            <h4 class="reference-card-title">IISS Session Notes</h4>
                            <ul class="reference-list">
                                <li>Skills training documentation</li>
                                <li>Behavior intervention tracking</li>
                                <li>Goal progress measurement</li>
                                <li>Caregiver collaboration notes</li>
                            </ul>
                        </div>
                        <div class="reference-card">
                            <h4 class="reference-card-title">Therapeutic Integration (TI)</h4>
                            <ul class="reference-list">
                                <li>Group activity documentation</li>
                                <li>Social skills development</li>
                                <li>Community integration progress</li>
                                <li>Peer interaction notes</li>
                            </ul>
                        </div>
                        <div class="reference-card">
                            <h4 class="reference-card-title">Respite Care</h4>
                            <ul class="reference-list">
                                <li>Care activities provided</li>
                                <li>Health and safety observations</li>
                                <li>Behavioral incidents</li>
                                <li>Family communication</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Case Manager Section -->
                <section id="case-manager" class="help-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h2 class="section-title">Case Manager Functions</h2>
                        <p class="section-subtitle">Client management and oversight tools</p>
                    </div>

                    <div class="help-topic expanded">
                        <div class="help-topic-header">
                            <h3 class="help-topic-title">Client Management</h3>
                            <i class="fas fa-chevron-down help-topic-icon"></i>
                        </div>
                        <div class="help-topic-content">
                            <div class="role-content">
                                <strong>Available for:</strong> <span class="role-badge">Case Manager</span> <span class="role-badge">Supervisor</span> <span class="role-badge">Admin</span>
                            </div>
                            <p>Manage your client caseload efficiently:</p>
                            <ul>
                                <li><strong>Add New Clients:</strong> Complete intake and assessment</li>
                                <li><strong>Treatment Planning:</strong> Create and update individualized plans</li>
                                <li><strong>Service Authorization:</strong> Manage Medicaid authorizations</li>
                                <li><strong>Progress Monitoring:</strong> Track goal achievement</li>
                                <li><strong>Documentation Review:</strong> Approve session notes</li>
                            </ul>
                        </div>
                    </div>

                    <div class="help-topic">
                        <div class="help-topic-header">
                            <h3 class="help-topic-title">Approval Workflows</h3>
                            <i class="fas fa-chevron-down help-topic-icon"></i>
                        </div>
                        <div class="help-topic-content">
                            <p>Review and approve:</p>
                            <ul>
                                <li>Session notes within 48 hours</li>
                                <li>Treatment plan updates</li>
                                <li>Service authorization changes</li>
                                <li>Incident reports</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Supervisor Section -->
                <section id="supervisor" class="help-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h2 class="section-title">Supervisor Tools</h2>
                        <p class="section-subtitle">Staff management and quality oversight</p>
                    </div>

                    <div class="help-topic expanded">
                        <div class="help-topic-header">
                            <h3 class="help-topic-title">Staff Management</h3>
                            <i class="fas fa-chevron-down help-topic-icon"></i>
                        </div>
                        <div class="help-topic-content">
                            <ul>
                                <li>View staff schedules and availability</li>
                                <li>Approve time entries and overtime</li>
                                <li>Monitor productivity metrics</li>
                                <li>Conduct performance reviews</li>
                                <li>Manage staff training records</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Billing Section -->
                <section id="billing" class="help-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <h2 class="section-title">Billing Operations</h2>
                        <p class="section-subtitle">Claims processing and revenue management</p>
                    </div>

                    <div class="help-topic expanded">
                        <div class="help-topic-header">
                            <h3 class="help-topic-title">Medicaid Claims</h3>
                            <i class="fas fa-chevron-down help-topic-icon"></i>
                        </div>
                        <div class="help-topic-content">
                            <div class="role-content">
                                <strong>Available for:</strong> <span class="role-badge">Billing</span> <span class="role-badge">Admin</span>
                            </div>
                            <div class="tutorial-steps">
                                <div class="tutorial-step">
                                    <h4 class="step-title">Generate Claims</h4>
                                    <p class="step-content">System automatically creates claims from approved session notes.</p>
                                </div>
                                <div class="tutorial-step">
                                    <h4 class="step-title">Review and Validate</h4>
                                    <p class="step-content">Check for errors, verify authorizations, and ensure compliance.</p>
                                </div>
                                <div class="tutorial-step">
                                    <h4 class="step-title">Submit to Medicaid</h4>
                                    <p class="step-content">Electronic submission through EDI gateway.</p>
                                </div>
                                <div class="tutorial-step">
                                    <h4 class="step-title">Track Payments</h4>
                                    <p class="step-content">Monitor claim status and reconcile payments.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Admin Section -->
                <section id="admin" class="help-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-cogs"></i>
                        </div>
                        <h2 class="section-title">Administrator Functions</h2>
                        <p class="section-subtitle">System configuration and user management</p>
                    </div>

                    <div class="reference-cards">
                        <div class="reference-card">
                            <h4 class="reference-card-title">User Management</h4>
                            <ul class="reference-list">
                                <li>Create and deactivate user accounts</li>
                                <li>Assign roles and permissions</li>
                                <li>Reset passwords</li>
                                <li>Configure access levels</li>
                                <li>Audit user activity</li>
                            </ul>
                        </div>
                        <div class="reference-card">
                            <h4 class="reference-card-title">System Configuration</h4>
                            <ul class="reference-list">
                                <li>Service types and rates</li>
                                <li>Billing rules and modifiers</li>
                                <li>Document templates</li>
                                <li>Workflow automation</li>
                                <li>Integration settings</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- FAQ Section -->
                <section id="faq" class="help-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h2 class="section-title">Frequently Asked Questions</h2>
                        <p class="section-subtitle">Quick answers to common questions</p>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-chevron-right"></i>
                            How do I reset my password?
                        </div>
                        <div class="faq-answer">
                            Click "Forgot Password" on the login page and follow the email instructions. If you don't receive an email, contact your administrator.
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-chevron-right"></i>
                            Why can't I see certain clients?
                        </div>
                        <div class="faq-answer">
                            Client access is based on your assigned caseload and role permissions. Contact your supervisor if you need access to additional clients.
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-chevron-right"></i>
                            How long do I have to complete session notes?
                        </div>
                        <div class="faq-answer">
                            Session notes must be completed within 24 hours of service delivery to ensure timely billing and compliance with Medicaid requirements.
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-question">
                            <i class="fas fa-chevron-right"></i>
                            Can I access Scrive from my phone?
                        </div>
                        <div class="faq-answer">
                            Yes! Scrive is fully mobile-responsive. You can access all features from any modern smartphone or tablet browser.
                        </div>
                    </div>
                </section>

                <!-- Troubleshooting Section -->
                <section id="troubleshooting" class="help-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h2 class="section-title">Troubleshooting Guide</h2>
                        <p class="section-subtitle">Solutions to common issues</p>
                    </div>

                    <div class="help-topic expanded">
                        <div class="help-topic-header">
                            <h3 class="help-topic-title">Login Issues</h3>
                            <i class="fas fa-chevron-down help-topic-icon"></i>
                        </div>
                        <div class="help-topic-content">
                            <p><strong>Problem:</strong> Cannot log in with correct credentials</p>
                            <p><strong>Solutions:</strong></p>
                            <ul>
                                <li>Check CAPS LOCK is off</li>
                                <li>Clear browser cache and cookies</li>
                                <li>Try a different browser</li>
                                <li>Verify account is active with administrator</li>
                            </ul>
                        </div>
                    </div>

                    <div class="help-topic">
                        <div class="help-topic-header">
                            <h3 class="help-topic-title">Performance Issues</h3>
                            <i class="fas fa-chevron-down help-topic-icon"></i>
                        </div>
                        <div class="help-topic-content">
                            <p><strong>Problem:</strong> System is running slowly</p>
                            <p><strong>Solutions:</strong></p>
                            <ul>
                                <li>Check internet connection speed</li>
                                <li>Close unnecessary browser tabs</li>
                                <li>Update browser to latest version</li>
                                <li>Disable browser extensions temporarily</li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Contact Section -->
                <section id="contact" class="help-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h2 class="section-title">Contact Support</h2>
                        <p class="section-subtitle">We're here to help</p>
                    </div>

                    <div class="support-contact">
                        <h3>Need Additional Help?</h3>
                        <p>Our support team is available Monday through Friday, 8:00 AM - 5:00 PM EST</p>
                        
                        <div class="support-contact-info">
                            <div class="contact-method">
                                <i class="fas fa-phone"></i>
                                <span>1-800-XXX-XXXX</span>
                            </div>
                            <div class="contact-method">
                                <i class="fas fa-envelope"></i>
                                <span>support@scrive.com</span>
                            </div>
                            <div class="contact-method">
                                <i class="fas fa-comments"></i>
                                <span>Live Chat Available</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <h4>Before Contacting Support:</h4>
                        <ol>
                            <li>Check the FAQ section for quick answers</li>
                            <li>Review the troubleshooting guide</li>
                            <li>Gather relevant information (error messages, screenshots)</li>
                            <li>Note your username and facility name</li>
                        </ol>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Section Navigation
            const navLinks = document.querySelectorAll('.help-nav-link, .quick-action-btn');
            const sections = document.querySelectorAll('.help-section');

            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetSection = this.getAttribute('data-section');
                    
                    // Hide all sections
                    sections.forEach(section => {
                        section.classList.remove('active');
                    });
                    
                    // Remove active class from all nav links
                    document.querySelectorAll('.help-nav-link').forEach(navLink => {
                        navLink.classList.remove('active');
                    });
                    
                    // Show target section
                    const targetElement = document.getElementById(targetSection);
                    if (targetElement) {
                        targetElement.classList.add('active');
                        
                        // Update active nav link
                        document.querySelector(`.help-nav-link[data-section="${targetSection}"]`)?.classList.add('active');
                        
                        // Scroll to top of content
                        document.querySelector('.help-main').scrollTop = 0;
                    }
                });
            });

            // Collapsible Topics
            const topicHeaders = document.querySelectorAll('.help-topic-header');
            
            topicHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const topic = this.closest('.help-topic');
                    topic.classList.toggle('expanded');
                });
            });

            // Search Functionality
            const searchInput = document.getElementById('helpSearch');
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                
                if (searchTerm.length < 2) {
                    // Reset all sections to visible
                    sections.forEach(section => {
                        const topics = section.querySelectorAll('.help-topic');
                        topics.forEach(topic => {
                            topic.style.display = '';
                        });
                    });
                    return;
                }

                // Search through all sections
                sections.forEach(section => {
                    const topics = section.querySelectorAll('.help-topic');
                    let hasVisibleContent = false;
                    
                    topics.forEach(topic => {
                        const content = topic.textContent.toLowerCase();
                        if (content.includes(searchTerm)) {
                            topic.style.display = '';
                            topic.classList.add('expanded');
                            hasVisibleContent = true;
                        } else {
                            topic.style.display = 'none';
                        }
                    });
                    
                    // Show section if it has matching content
                    if (hasVisibleContent && !section.classList.contains('active')) {
                        // Show the first section with matches
                        document.querySelectorAll('.help-section').forEach(s => s.classList.remove('active'));
                        section.classList.add('active');
                        
                        // Update nav
                        const sectionId = section.id;
                        document.querySelectorAll('.help-nav-link').forEach(link => {
                            link.classList.remove('active');
                        });
                        document.querySelector(`.help-nav-link[data-section="${sectionId}"]`)?.classList.add('active');
                    }
                });
            });

            // FAQ Toggle
            const faqQuestions = document.querySelectorAll('.faq-question');
            
            faqQuestions.forEach(question => {
                question.addEventListener('click', function() {
                    const answer = this.nextElementSibling;
                    const icon = this.querySelector('i');
                    
                    if (answer.style.display === 'block') {
                        answer.style.display = 'none';
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-right');
                    } else {
                        answer.style.display = 'block';
                        icon.classList.remove('fa-chevron-right');
                        icon.classList.add('fa-chevron-down');
                    }
                });
            });

            // Print functionality
            window.addEventListener('beforeprint', function() {
                // Expand all collapsible content for printing
                document.querySelectorAll('.help-topic').forEach(topic => {
                    topic.classList.add('expanded');
                });
            });

            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Role-based content filtering
            const userRole = '<?php echo $userRole; ?>';
            
            // Hide content not relevant to user's role
            if (userRole === 'staff') {
                // Hide admin-only content
                document.querySelectorAll('[data-role-required="admin"]').forEach(el => {
                    el.style.display = 'none';
                });
            }

            // Add loading animation to images
            document.querySelectorAll('.screenshot-placeholder').forEach(placeholder => {
                placeholder.addEventListener('click', function() {
                    this.innerHTML = '<div class="spinner"></div><p>Loading screenshot...</p>';
                    
                    // Simulate loading
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-image fa-3x mb-2"></i><p>Screenshot unavailable</p>';
                    }, 1500);
                });
            });
        });
    </script>
</body>
</html>