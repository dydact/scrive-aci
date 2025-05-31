<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /src/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Start Guide - American Caregivers Inc</title>
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
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .timer {
            background: #1e40af;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .progress-bar {
            background: #e5e7eb;
            height: 8px;
            border-radius: 4px;
            margin-bottom: 3rem;
            overflow: hidden;
        }
        
        .progress-fill {
            background: #16a34a;
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        
        .step-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: none;
        }
        
        .step-section.active {
            display: block;
        }
        
        .step-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .step-number {
            background: #1e40af;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .step-title {
            font-size: 1.5rem;
            color: #1e293b;
        }
        
        .step-content {
            margin-left: 3.5rem;
        }
        
        .step-content h3 {
            color: #1e40af;
            margin: 1.5rem 0 0.5rem;
        }
        
        .step-content ul {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .step-content li {
            margin-bottom: 0.5rem;
        }
        
        .highlight-box {
            background: #f0f9ff;
            border-left: 4px solid #1e40af;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
        
        .warning-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
        
        .navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }
        
        .nav-btn {
            background: #1e40af;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .nav-btn:hover {
            background: #1e3a8a;
        }
        
        .nav-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }
        
        .completion {
            background: #16a34a;
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            display: none;
        }
        
        .completion h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .completion p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
        }
        
        .action-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .action-link {
            background: white;
            color: #16a34a;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: transform 0.3s;
        }
        
        .action-link:hover {
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .step-content {
                margin-left: 0;
            }
            
            .navigation {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-btn {
                width: 100%;
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
                <span>Quick Start Guide</span>
            </div>
            <a href="/help_center.php" style="color: #1e40af; text-decoration: none;">← Back to Help Center</a>
        </div>
    </div>
    
    <div class="container">
        <div class="timer">
            ⏱️ Estimated time: <span id="timeRemaining">5 minutes</span>
        </div>
        
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill"></div>
        </div>
        
        <!-- Step 1: Login -->
        <div class="step-section active" id="step1">
            <div class="step-header">
                <div class="step-number">1</div>
                <h2 class="step-title">Logging In</h2>
            </div>
            <div class="step-content">
                <p>Welcome to Scrive ACI! Let's get you started with your first login.</p>
                
                <h3>Your Login Credentials</h3>
                <ul>
                    <li><strong>URL:</strong> https://aci.dydact.io/src/login.php</li>
                    <li><strong>Username:</strong> Provided by your administrator</li>
                    <li><strong>Password:</strong> Provided by your administrator</li>
                </ul>
                
                <div class="highlight-box">
                    <strong>💡 Pro Tip:</strong> Bookmark the login page for quick access. On mobile, add it to your home screen for app-like access.
                </div>
                
                <h3>First Login Steps</h3>
                <ol>
                    <li>Enter your username (usually your email or employee ID)</li>
                    <li>Enter your temporary password</li>
                    <li>Click "Login"</li>
                    <li>You may be prompted to change your password - choose something secure!</li>
                </ol>
                
                <div class="warning-box">
                    <strong>⚠️ Security Note:</strong> Never share your login credentials. If you suspect unauthorized access, contact IT immediately.
                </div>
            </div>
        </div>
        
        <!-- Step 2: Dashboard Overview -->
        <div class="step-section" id="step2">
            <div class="step-header">
                <div class="step-number">2</div>
                <h2 class="step-title">Understanding Your Dashboard</h2>
            </div>
            <div class="step-content">
                <p>After logging in, you'll see your personalized dashboard based on your role.</p>
                
                <h3>Key Dashboard Elements</h3>
                <ul>
                    <li><strong>Role Banner:</strong> Shows your current role and access level</li>
                    <li><strong>Quick Actions:</strong> One-click access to common tasks</li>
                    <li><strong>Statistics:</strong> Real-time data relevant to your role</li>
                    <li><strong>Navigation:</strong> Access different parts of the system</li>
                </ul>
                
                <h3>Common Quick Actions by Role</h3>
                <div class="highlight-box">
                    <p><strong>Direct Care Staff:</strong> Mobile Portal, Session Notes, My Clients</p>
                    <p><strong>Case Managers:</strong> My Clients, Treatment Plans, Reports</p>
                    <p><strong>Supervisors:</strong> Team Clients, Supervision, Billing Reports</p>
                    <p><strong>Administrators:</strong> All Clients, Billing, Admin Tools</p>
                </div>
                
                <p>Your dashboard updates in real-time, so you always have current information.</p>
            </div>
        </div>
        
        <!-- Step 3: Basic Navigation -->
        <div class="step-section" id="step3">
            <div class="step-header">
                <div class="step-number">3</div>
                <h2 class="step-title">Basic Navigation</h2>
            </div>
            <div class="step-content">
                <p>Let's explore how to navigate through the system efficiently.</p>
                
                <h3>Main Navigation Areas</h3>
                <ul>
                    <li><strong>Header Bar:</strong> Your profile, help, and logout options</li>
                    <li><strong>Quick Actions:</strong> Buttons for your most common tasks</li>
                    <li><strong>Statistics Cards:</strong> Click to see detailed reports</li>
                </ul>
                
                <h3>Essential Pages</h3>
                <ol>
                    <li><strong>Client List:</strong> View and search all your assigned clients</li>
                    <li><strong>Session Documentation:</strong> Create and edit session notes</li>
                    <li><strong>Mobile Portal:</strong> Access the system from any device</li>
                    <li><strong>Reports:</strong> View your hours, sessions, and performance</li>
                </ol>
                
                <div class="highlight-box">
                    <strong>🔍 Search Tip:</strong> Use Ctrl+F (Windows) or Cmd+F (Mac) to quickly find information on any page.
                </div>
                
                <h3>Getting Help</h3>
                <ul>
                    <li>Click the "Help" button in the top right for contextual assistance</li>
                    <li>Press F1 anytime for keyboard shortcuts</li>
                    <li>Look for "?" icons for field-specific help</li>
                </ul>
            </div>
        </div>
        
        <!-- Step 4: Your First Task -->
        <div class="step-section" id="step4">
            <div class="step-header">
                <div class="step-number">4</div>
                <h2 class="step-title">Completing Your First Task</h2>
            </div>
            <div class="step-content">
                <p>Let's walk through documenting your first client session - the most common task.</p>
                
                <h3>Creating a Session Note</h3>
                <ol>
                    <li><strong>Select Client:</strong> Click "My Clients" or use the Mobile Portal</li>
                    <li><strong>Start Documentation:</strong> Click "New Session Note"</li>
                    <li><strong>Fill Required Fields:</strong>
                        <ul>
                            <li>Date and time (auto-filled to now)</li>
                            <li>Service type (e.g., IISS, Respite)</li>
                            <li>Session duration</li>
                            <li>Activities performed</li>
                            <li>Client response</li>
                        </ul>
                    </li>
                    <li><strong>Rate Goals:</strong> Use the 1-5 scale for each treatment goal</li>
                    <li><strong>Submit:</strong> Click "Save Session Note"</li>
                </ol>
                
                <div class="warning-box">
                    <strong>⏰ Important:</strong> Session notes should be completed within 24 hours of the session for compliance.
                </div>
                
                <h3>Quick Documentation Tips</h3>
                <ul>
                    <li>Be specific about activities and interventions</li>
                    <li>Document both successes and challenges</li>
                    <li>Use professional, objective language</li>
                    <li>Include any parent/caregiver communication</li>
                </ul>
            </div>
        </div>
        
        <!-- Step 5: Best Practices -->
        <div class="step-section" id="step5">
            <div class="step-header">
                <div class="step-number">5</div>
                <h2 class="step-title">Best Practices & Tips</h2>
            </div>
            <div class="step-content">
                <p>Follow these best practices to get the most out of Scrive ACI.</p>
                
                <h3>Security Best Practices</h3>
                <ul>
                    <li><strong>Always log out</strong> when finished, especially on shared devices</li>
                    <li><strong>Use strong passwords</strong> with mix of letters, numbers, symbols</li>
                    <li><strong>Don't share accounts</strong> - each user needs their own login</li>
                    <li><strong>Report suspicious activity</strong> immediately</li>
                </ul>
                
                <h3>Efficiency Tips</h3>
                <ul>
                    <li><strong>Use templates:</strong> Save time with pre-written session templates</li>
                    <li><strong>Mobile first:</strong> Document sessions immediately using mobile</li>
                    <li><strong>Keyboard shortcuts:</strong> Learn shortcuts for faster navigation</li>
                    <li><strong>Batch similar tasks:</strong> Do all session notes at once</li>
                </ul>
                
                <h3>Common Mistakes to Avoid</h3>
                <div class="warning-box">
                    <ul>
                        <li>❌ Waiting too long to document sessions</li>
                        <li>❌ Using vague language in notes</li>
                        <li>❌ Forgetting to rate treatment goals</li>
                        <li>❌ Not logging out properly</li>
                    </ul>
                </div>
                
                <h3>Getting Support</h3>
                <p>Remember, help is always available:</p>
                <ul>
                    <li><strong>In-app help:</strong> Click Help button or press F1</li>
                    <li><strong>Email:</strong> support@acgcares.com</li>
                    <li><strong>Phone:</strong> 301-408-0100 (Mon-Fri 9-5 EST)</li>
                    <li><strong>User Manual:</strong> Comprehensive guide in Help Center</li>
                </ul>
            </div>
        </div>
        
        <!-- Completion Screen -->
        <div class="completion" id="completion">
            <h2>🎉 Congratulations!</h2>
            <p>You've completed the 5-minute quick start guide.</p>
            <p>You're now ready to use Scrive ACI effectively!</p>
            
            <div class="action-links">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/src/dashboard.php" class="action-link">Go to Dashboard</a>
                <?php else: ?>
                    <a href="/src/login.php" class="action-link">Login Now</a>
                <?php endif; ?>
                <a href="/help_center.php" class="action-link">Help Center</a>
                <a href="/USER_MANUAL.md" class="action-link" target="_blank">Full Manual</a>
            </div>
        </div>
        
        <div class="navigation" id="navigation">
            <button class="nav-btn" id="prevBtn" onclick="changeStep(-1)" disabled>Previous</button>
            <button class="nav-btn" id="nextBtn" onclick="changeStep(1)">Next</button>
        </div>
    </div>
    
    <script>
        let currentStep = 1;
        const totalSteps = 5;
        let startTime = Date.now();
        
        function updateProgress() {
            const progress = (currentStep / totalSteps) * 100;
            document.getElementById('progressFill').style.width = progress + '%';
            
            // Update time remaining
            const elapsed = Math.floor((Date.now() - startTime) / 1000);
            const estimatedTotal = 300; // 5 minutes
            const remaining = Math.max(0, estimatedTotal - elapsed);
            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            
            if (remaining > 0) {
                document.getElementById('timeRemaining').textContent = 
                    `${minutes}:${seconds.toString().padStart(2, '0')} remaining`;
            } else {
                document.getElementById('timeRemaining').textContent = 'Take your time!';
            }
        }
        
        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('.step-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show current step or completion
            if (step > totalSteps) {
                document.getElementById('completion').style.display = 'block';
                document.getElementById('navigation').style.display = 'none';
                document.querySelector('.timer').style.display = 'none';
                document.querySelector('.progress-bar').style.display = 'none';
            } else {
                document.getElementById(`step${step}`).classList.add('active');
                
                // Update navigation buttons
                document.getElementById('prevBtn').disabled = step === 1;
                document.getElementById('nextBtn').textContent = 
                    step === totalSteps ? 'Complete' : 'Next';
            }
            
            updateProgress();
        }
        
        function changeStep(direction) {
            currentStep += direction;
            if (currentStep < 1) currentStep = 1;
            if (currentStep > totalSteps + 1) currentStep = totalSteps + 1;
            
            showStep(currentStep);
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Update timer every second
        setInterval(updateProgress, 1000);
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowRight' && currentStep <= totalSteps) {
                changeStep(1);
            } else if (e.key === 'ArrowLeft' && currentStep > 1) {
                changeStep(-1);
            }
        });
        
        // Initialize
        showStep(1);
    </script>
</body>
</html>