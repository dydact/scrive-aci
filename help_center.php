<?php
session_start();

// Check if user is logged in (optional - help can be available to all)
$isLoggedIn = isset($_SESSION['authUser']);
$userRole = $_SESSION['authUserRole'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - American Caregivers Inc</title>
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
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        .logo-text .a { color: #1e40af; }
        .logo-text .c { color: #dc2626; }
        .logo-text .i { color: #16a34a; }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: #1e40af;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .hero {
            text-align: center;
            padding: 3rem 0;
        }
        
        .hero h1 {
            font-size: 2.5rem;
            color: #1e293b;
            margin-bottom: 1rem;
        }
        
        .hero p {
            font-size: 1.25rem;
            color: #64748b;
            margin-bottom: 2rem;
        }
        
        .search-box {
            max-width: 600px;
            margin: 0 auto 3rem;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 1rem 3rem 1rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .search-box button {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: #1e40af;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
        }
        
        .help-categories {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .help-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .help-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .help-card h3 {
            color: #1e40af;
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }
        
        .help-card ul {
            list-style: none;
        }
        
        .help-card li {
            padding: 0.5rem 0;
        }
        
        .help-card a {
            color: #64748b;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .help-card a:hover {
            color: #1e40af;
            text-decoration: underline;
        }
        
        .faq-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 3rem;
        }
        
        .faq-section h2 {
            color: #1e293b;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .faq-item {
            border-bottom: 1px solid #e5e7eb;
            padding: 1.5rem 0;
        }
        
        .faq-item:last-child {
            border-bottom: none;
        }
        
        .faq-question {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .faq-answer {
            color: #64748b;
            display: none;
            padding-top: 0.5rem;
        }
        
        .faq-answer.active {
            display: block;
        }
        
        .contact-section {
            background: #f0f9ff;
            padding: 3rem;
            border-radius: 12px;
            text-align: center;
        }
        
        .contact-section h2 {
            color: #1e293b;
            margin-bottom: 1rem;
        }
        
        .contact-options {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .contact-option {
            text-align: center;
        }
        
        .contact-option h4 {
            color: #1e40af;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .help-categories {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-text">
                    <span class="a">A</span><span class="c">C</span><span class="i">I</span>
                </div>
                <span>Help Center</span>
            </div>
            <div class="nav-links">
                <a href="/index.php">Home</a>
                <a href="/quick_start_guide.php">Quick Start</a>
                <a href="/USER_MANUAL.md" target="_blank">User Manual</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/src/dashboard.php">Dashboard</a>
                <?php else: ?>
                    <a href="/src/login.php">Staff Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="hero">
            <h1>How can we help you?</h1>
            <p>Find answers, guides, and support for the Scrive ACI system</p>
        </div>
        
        <div class="search-box">
            <input type="text" id="helpSearch" placeholder="Search for help articles...">
            <button onclick="searchHelp()">Search</button>
        </div>
        
        <div class="help-categories">
            <div class="help-card">
                <h3>📚 Getting Started</h3>
                <ul>
                    <li><a href="/quick_start_guide.php">5-Minute Quick Start Guide</a></li>
                    <li><a href="#" onclick="startTour('first-login')">First Login Tutorial</a></li>
                    <li><a href="#" onclick="startTour('dashboard-overview')">Dashboard Overview</a></li>
                    <li><a href="/USER_MANUAL.md#role-based-access">Understanding User Roles</a></li>
                </ul>
            </div>
            
            <div class="help-card">
                <h3>📝 Session Documentation</h3>
                <ul>
                    <li><a href="#" onclick="startTour('create-session')">Creating Session Notes</a></li>
                    <li><a href="/USER_MANUAL.md#session-documentation">Session Note Requirements</a></li>
                    <li><a href="#" onclick="startTour('mobile-session')">Mobile Session Entry</a></li>
                    <li><a href="/USER_MANUAL.md#billing-integration">Billing & Claims</a></li>
                </ul>
            </div>
            
            <div class="help-card">
                <h3>👥 Client Management</h3>
                <ul>
                    <li><a href="/USER_MANUAL.md#client-management">Managing Client Records</a></li>
                    <li><a href="#" onclick="startTour('treatment-plan')">Treatment Plans</a></li>
                    <li><a href="/USER_MANUAL.md#program-enrollment">Program Enrollment</a></li>
                    <li><a href="/USER_MANUAL.md#client-reports">Client Reports</a></li>
                </ul>
            </div>
            
            <div class="help-card">
                <h3>🔐 Security & Privacy</h3>
                <ul>
                    <li><a href="/USER_MANUAL.md#security-features">HIPAA Compliance</a></li>
                    <li><a href="/USER_MANUAL.md#ma-number-security">MA Number Protection</a></li>
                    <li><a href="/USER_MANUAL.md#role-permissions">Role Permissions</a></li>
                    <li><a href="/USER_MANUAL.md#audit-logging">Audit Trails</a></li>
                </ul>
            </div>
            
            <div class="help-card">
                <h3>💼 For Administrators</h3>
                <ul>
                    <li><a href="/USER_MANUAL.md#admin-features">Admin Dashboard</a></li>
                    <li><a href="/USER_MANUAL.md#user-management">User Management</a></li>
                    <li><a href="/USER_MANUAL.md#billing-reports">Billing Reports</a></li>
                    <li><a href="/USER_MANUAL.md#system-settings">System Settings</a></li>
                </ul>
            </div>
            
            <div class="help-card">
                <h3>📱 Mobile Features</h3>
                <ul>
                    <li><a href="#" onclick="startTour('mobile-portal')">Mobile Portal Guide</a></li>
                    <li><a href="/USER_MANUAL.md#mobile-features">Mobile Documentation</a></li>
                    <li><a href="/USER_MANUAL.md#offline-mode">Offline Mode</a></li>
                    <li><a href="/USER_MANUAL.md#mobile-sync">Data Synchronization</a></li>
                </ul>
            </div>
        </div>
        
        <div class="faq-section">
            <h2>Frequently Asked Questions</h2>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    How do I reset my password?
                    <span>▼</span>
                </div>
                <div class="faq-answer">
                    Click the "Forgot your password?" link on the login page. Enter your username or email address, and you'll receive instructions to reset your password. If you don't receive the email, contact your administrator.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    What browsers are supported?
                    <span>▼</span>
                </div>
                <div class="faq-answer">
                    Scrive ACI works best with modern browsers: Chrome (version 90+), Firefox (version 88+), Safari (version 14+), and Edge (version 90+). For the best experience, keep your browser updated to the latest version.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    How do I document a session on mobile?
                    <span>▼</span>
                </div>
                <div class="faq-answer">
                    Access the Mobile Portal from your phone's browser. Select the client, tap "New Session Note," fill in the required fields, and submit. The system will sync when you have an internet connection.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    What are the different user roles?
                    <span>▼</span>
                </div>
                <div class="faq-answer">
                    There are 5 main roles: Administrator (full access), Supervisor (team management), Case Manager (treatment planning), Direct Care Staff (session documentation), and Technician (basic documentation). Each role has specific permissions tailored to their responsibilities.
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    How is client data protected?
                    <span>▼</span>
                </div>
                <div class="faq-answer">
                    All data is encrypted in transit and at rest. Client MA numbers are masked based on user roles. The system maintains detailed audit logs and complies with HIPAA requirements. Regular backups ensure data safety.
                </div>
            </div>
        </div>
        
        <div class="contact-section">
            <h2>Still need help?</h2>
            <p>Our support team is here to assist you</p>
            
            <div class="contact-options">
                <div class="contact-option">
                    <h4>📧 Email Support</h4>
                    <p>support@acgcares.com</p>
                    <p>Response within 24 hours</p>
                </div>
                
                <div class="contact-option">
                    <h4>📞 Phone Support</h4>
                    <p>301-408-0100</p>
                    <p>Mon-Fri 9AM-5PM EST</p>
                </div>
                
                <div class="contact-option">
                    <h4>💬 Live Chat</h4>
                    <p><a href="#" onclick="alert('Live chat coming soon!')">Start Chat</a></p>
                    <p>Available during business hours</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="/public/assets/js/interactive-help.js"></script>
    <script>
        function searchHelp() {
            const query = document.getElementById('helpSearch').value;
            if (query) {
                alert('Search functionality coming soon! For now, please browse the categories below.');
            }
        }
        
        function toggleFAQ(element) {
            const answer = element.nextElementSibling;
            const arrow = element.querySelector('span');
            
            if (answer.classList.contains('active')) {
                answer.classList.remove('active');
                arrow.textContent = '▼';
            } else {
                // Close all other FAQs
                document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('active'));
                document.querySelectorAll('.faq-question span').forEach(s => s.textContent = '▼');
                
                // Open this one
                answer.classList.add('active');
                arrow.textContent = '▲';
            }
        }
        
        function startTour(tourName) {
            if (window.InteractiveHelp) {
                window.InteractiveHelp.startTour(tourName);
            } else {
                alert('Interactive tour loading... Please try again in a moment.');
            }
        }
        
        // Handle Enter key in search box
        document.getElementById('helpSearch').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchHelp();
            }
        });
    </script>
</body>
</html>