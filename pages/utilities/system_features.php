<?php
session_start();
require_once 'src/config.php';

// Redirect if not admin
if (!isset($_SESSION['access_level']) || $_SESSION['access_level'] < 5) {
    header('Location: /login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Features - ACI Management System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f3f4f6; color: #1e293b; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        h1 { color: #059669; margin-bottom: 2rem; font-size: 2.5rem; text-align: center; }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; margin-bottom: 3rem; }
        .feature-section { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 6px rgba(0,0,0,0.07); border-top: 4px solid #059669; }
        .feature-section h2 { color: #059669; margin-bottom: 1.5rem; font-size: 1.5rem; }
        .feature-list { list-style: none; }
        .feature-list li { padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb; display: flex; align-items: start; }
        .feature-list li:last-child { border-bottom: none; }
        .feature-list a { color: #3b82f6; text-decoration: none; font-weight: 500; margin-left: 0.5rem; }
        .feature-list a:hover { text-decoration: underline; }
        .status { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-left: auto; }
        .status.active { background: #d1fae5; color: #047857; }
        .status.pending { background: #fed7aa; color: #c2410c; }
        .icon { margin-right: 0.5rem; }
        .back-link { display: inline-block; margin-bottom: 2rem; color: #3b82f6; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .setup-notice { background: #fef3c7; border: 1px solid #f59e0b; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; }
        .setup-notice code { background: #1e293b; color: #f3f4f6; padding: 0.25rem 0.5rem; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <a href="/dashboard" class="back-link">← Back to Dashboard</a>
        
        <h1>American Caregivers Inc - System Features</h1>
        
        <div class="setup-notice">
            <strong>Database Setup Required:</strong> Run <code>chmod +x setup/apply_all_schemas.sh && ./setup/apply_all_schemas.sh</code> to enable all features.
        </div>
        
        <div class="feature-grid">
            <!-- Billing & Financial -->
            <div class="feature-section">
                <h2>💰 Billing & Financial Management</h2>
                <ul class="feature-list">
                    <li>
                        <span class="icon">📊</span>
                        <a href="/billing">Billing Dashboard</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">🏥</span>
                        Maryland Medicaid Integration
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">📋</span>
                        <a href="/billing">EDI 837/835 Claims</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">✅</span>
                        Real-time Eligibility Verification (EVS)
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">💵</span>
                        <a href="/payroll">Payroll Reports</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">📈</span>
                        Revenue Cycle Management
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">⏰</span>
                        <a href="/api/time-clock">Time Clock API</a>
                        <span class="status active">Active</span>
                    </li>
                </ul>
            </div>
            
            <!-- Scheduling -->
            <div class="feature-section">
                <h2>📅 Scheduling & Resource Management</h2>
                <ul class="feature-list">
                    <li>
                        <span class="icon">📆</span>
                        <a href="/schedule">Schedule Manager</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">🔄</span>
                        Recurring Appointment Templates
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">👥</span>
                        Group Session Management
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">📍</span>
                        Resource/Room Booking
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">⏳</span>
                        Waitlist Tracking
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">👤</span>
                        Staff Availability Management
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">📱</span>
                        <a href="/calendar">Calendar View</a>
                        <span class="status active">Active</span>
                    </li>
                </ul>
            </div>
            
            <!-- Clinical Documentation -->
            <div class="feature-section">
                <h2>📋 Clinical Documentation</h2>
                <ul class="feature-list">
                    <li>
                        <span class="icon">📝</span>
                        <a href="/session-notes">Session Notes (IISS)</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">🎯</span>
                        <a href="/treatment-plans">Treatment Plans</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">📊</span>
                        Progress Tracking
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">🏥</span>
                        Medical Information Management
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">📄</span>
                        <a href="/forms">Intake Forms</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">🔐</span>
                        HIPAA Compliant Storage
                        <span class="status active">Active</span>
                    </li>
                </ul>
            </div>
            
            <!-- Client Management -->
            <div class="feature-section">
                <h2>👥 Client Management</h2>
                <ul class="feature-list">
                    <li>
                        <span class="icon">👤</span>
                        <a href="/clients">Client Directory</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">➕</span>
                        <a href="/add-client">Add New Client</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">🔒</span>
                        <a href="/secure-clients">Secure Client Access</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">📋</span>
                        Prior Authorization Tracking
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">🏥</span>
                        Insurance Management
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">👨‍👩‍👧</span>
                        Family Contact Management
                        <span class="status active">Active</span>
                    </li>
                </ul>
            </div>
            
            <!-- Staff Management -->
            <div class="feature-section">
                <h2>👔 Staff Management</h2>
                <ul class="feature-list">
                    <li>
                        <span class="icon">👥</span>
                        <a href="/staff">Employee Directory</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">🔄</span>
                        <a href="/role-switcher">Role Switcher</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">📱</span>
                        <a href="/mobile">Mobile Portal</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">👤</span>
                        <a href="/employee-portal">Employee Portal</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">📊</span>
                        <a href="/case-manager">Case Manager Portal</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">⏰</span>
                        Time & Attendance Tracking
                        <span class="status active">Active</span>
                    </li>
                </ul>
            </div>
            
            <!-- Reporting & Analytics -->
            <div class="feature-section">
                <h2>📊 Reporting & Analytics</h2>
                <ul class="feature-list">
                    <li>
                        <span class="icon">📈</span>
                        <a href="/reports">Custom Reports</a>
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">💰</span>
                        Aging Reports (A/R)
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">📊</span>
                        Service Utilization Reports
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">👥</span>
                        Staff Productivity Reports
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">🏥</span>
                        Medicaid Compliance Reports
                        <span class="status active">Active</span>
                    </li>
                    <li>
                        <span class="icon">📊</span>
                        Dashboard Analytics
                        <span class="status active">Active</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 3rem; color: #6b7280;">
            <p>American Caregivers Inc - Autism Waiver Management System v1.0</p>
            <p>Fully integrated with Maryland Medicaid billing requirements</p>
        </div>
    </div>
</body>
</html>