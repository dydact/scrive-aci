<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 Case Manager Portal - American Caregivers</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #2563eb;
            --secondary-color: #f0f9ff;
            --accent-color: #059669;
            --warning-color: #f59e0b;
            --success-color: #10b981;
            --text-color: #1e293b;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            min-height: 100vh;
            color: var(--text-color);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1.5rem;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-lg);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .header-left h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .header-left .subtitle {
            color: #64748b;
            font-size: 1rem;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .user-info {
            background: var(--secondary-color);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
        }
        
        .user-info .user-name {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .user-info .user-role {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .metrics-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .metric-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }
        
        .metric-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }
        
        .metric-label {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .content-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        .section-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .client-grid {
            display: grid;
            gap: 1rem;
        }
        
        .client-card {
            background: #f8fafc;
            border-radius: 0.75rem;
            padding: 1.5rem;
            border-left: 4px solid var(--primary-color);
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .client-card:hover {
            background: #e2e8f0;
            transform: translateX(4px);
        }
        
        .client-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .client-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-color);
        }
        
        .client-status {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-needs-review {
            background: #fef3c7;
            color: #92400e;
        }
        
        .client-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .treatment-summary {
            background: rgba(37, 99, 235, 0.1);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .treatment-summary h4 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .goals-list {
            list-style: none;
            padding: 0;
        }
        
        .goals-list li {
            padding: 0.25rem 0;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .goals-list li::before {
            content: '📌';
            font-size: 0.8rem;
        }
        
        .quick-actions {
            display: grid;
            gap: 1rem;
        }
        
        .action-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: all 0.2s;
            cursor: pointer;
            border: 2px solid transparent;
            text-align: center;
        }
        
        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .action-card .icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .action-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }
        
        .action-card p {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .recent-activities {
            display: grid;
            gap: 0.75rem;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 0.5rem;
            border-left: 3px solid var(--accent-color);
        }
        
        .activity-icon {
            width: 2rem;
            height: 2rem;
            background: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }
        
        .activity-description {
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 0.25rem;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #9ca3af;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .modal-content {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .metrics-row {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>📋 Case Manager Portal</h1>
                <p class="subtitle">Treatment planning, client coordination, and progress monitoring</p>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-name">Dr. Jennifer Martinez</div>
                    <div class="user-role">Case Manager (Level 3)</div>
                </div>
                <button class="btn btn-primary" onclick="openTreatmentPlanner()">
                    ➕ New Treatment Plan
                </button>
            </div>
        </div>

        <!-- Metrics Dashboard -->
        <div class="metrics-row">
            <div class="metric-card">
                <div class="metric-number">24</div>
                <div class="metric-label">Active Clients</div>
            </div>
            <div class="metric-card">
                <div class="metric-number">18</div>
                <div class="metric-label">Treatment Plans</div>
            </div>
            <div class="metric-card">
                <div class="metric-number">6</div>
                <div class="metric-label">Plans Need Review</div>
            </div>
            <div class="metric-card">
                <div class="metric-number">85%</div>
                <div class="metric-label">Avg Goal Progress</div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Client Management -->
            <div class="content-section">
                <div class="section-header">
                    <h2>👥 My Clients</h2>
                    <div>
                        <button class="btn btn-secondary" onclick="filterClients('all')">All</button>
                        <button class="btn btn-secondary" onclick="filterClients('review')">Need Review</button>
                        <button class="btn btn-primary" onclick="openClientEnrollment()">
                            ➕ Enroll Client
                        </button>
                    </div>
                </div>
                
                <div class="client-grid">
                    <div class="client-card" onclick="openClientDetails('emma_rodriguez')">
                        <div class="client-header">
                            <div class="client-name">Emma Rodriguez</div>
                            <div class="client-status status-active">Active</div>
                        </div>
                        <div class="client-info">
                            <div>Age: 9 years old</div>
                            <div>Program: Autism Waiver</div>
                            <div>MA: ***-**-3456</div>
                            <div>Last Session: 2 days ago</div>
                        </div>
                        <div class="treatment-summary">
                            <h4>📋 Current Treatment Plan: Communication & Social Skills</h4>
                            <ul class="goals-list">
                                <li>Verbal communication (75% progress)</li>
                                <li>Social interaction (60% progress)</li>
                                <li>Behavioral regulation (45% progress)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="client-card" onclick="openClientDetails('michael_chen')">
                        <div class="client-header">
                            <div class="client-name">Michael Chen</div>
                            <div class="client-status status-needs-review">Needs Review</div>
                        </div>
                        <div class="client-info">
                            <div>Age: 12 years old</div>
                            <div>Program: DDA</div>
                            <div>MA: ***-**-7890</div>
                            <div>Last Session: 1 day ago</div>
                        </div>
                        <div class="treatment-summary">
                            <h4>📋 Current Treatment Plan: Independent Living Skills</h4>
                            <ul class="goals-list">
                                <li>Morning routine (85% progress)</li>
                                <li>Frustration management (70% progress)</li>
                                <li>Community integration (55% progress)</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="client-card" onclick="openClientDetails('sarah_johnson')">
                        <div class="client-header">
                            <div class="client-name">Sarah Johnson</div>
                            <div class="client-status status-active">Active</div>
                        </div>
                        <div class="client-info">
                            <div>Age: 7 years old</div>
                            <div>Program: CFC</div>
                            <div>MA: ***-**-2345</div>
                            <div>Last Session: 5 days ago</div>
                        </div>
                        <div class="treatment-summary">
                            <h4>📋 Current Treatment Plan: Sensory Integration</h4>
                            <ul class="goals-list">
                                <li>Sensory processing (40% progress)</li>
                                <li>Motor skills development (65% progress)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions & Activities -->
            <div>
                <!-- Quick Actions -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>⚡ Quick Actions</h2>
                    </div>
                    
                    <div class="quick-actions">
                        <div class="action-card" onclick="openTreatmentPlanner()">
                            <div class="icon">📝</div>
                            <h3>Create Treatment Plan</h3>
                            <p>Build comprehensive treatment plans with SMART goals</p>
                        </div>
                        
                        <div class="action-card" onclick="reviewProgress()">
                            <div class="icon">📊</div>
                            <h3>Review Progress</h3>
                            <p>Analyze client progress and update goals</p>
                        </div>
                        
                        <div class="action-card" onclick="scheduleSession()">
                            <div class="icon">📅</div>
                            <h3>Schedule Sessions</h3>
                            <p>Coordinate staff and client schedules</p>
                        </div>
                        
                        <div class="action-card" onclick="generateReports()">
                            <div class="icon">📄</div>
                            <h3>Generate Reports</h3>
                            <p>Create progress reports for families</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activities -->
                <div class="content-section" style="margin-top: 1.5rem;">
                    <div class="section-header">
                        <h2>🕒 Recent Activities</h2>
                        <button class="btn btn-secondary" onclick="viewAllActivities()">
                            View All
                        </button>
                    </div>
                    
                    <div class="recent-activities">
                        <div class="activity-item">
                            <div class="activity-icon">📝</div>
                            <div class="activity-content">
                                <div class="activity-title">Treatment Plan Updated</div>
                                <div class="activity-description">Emma Rodriguez - Added new communication goal</div>
                                <div class="activity-time">2 hours ago</div>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">👥</div>
                            <div class="activity-content">
                                <div class="activity-title">Staff Meeting Scheduled</div>
                                <div class="activity-description">Progress review for Michael Chen</div>
                                <div class="activity-time">4 hours ago</div>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">📊</div>
                            <div class="activity-content">
                                <div class="activity-title">Progress Report Generated</div>
                                <div class="activity-description">Monthly report for Sarah Johnson's family</div>
                                <div class="activity-time">1 day ago</div>
                            </div>
                        </div>
                        
                        <div class="activity-item">
                            <div class="activity-icon">🎯</div>
                            <div class="activity-content">
                                <div class="activity-title">Goal Achievement</div>
                                <div class="activity-description">Michael Chen completed morning routine goal</div>
                                <div class="activity-time">2 days ago</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Treatment Plan Modal -->
    <div class="modal" id="treatmentPlanModal">
        <div class="modal-content">
            <h2>📝 Create Treatment Plan</h2>
            
            <form id="treatmentPlanForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Client *</label>
                        <select class="form-select" required>
                            <option value="">Select client...</option>
                            <option value="1">Emma Rodriguez</option>
                            <option value="2">Michael Chen</option>
                            <option value="3">Sarah Johnson</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Plan Name *</label>
                        <input type="text" class="form-input" placeholder="e.g. Communication & Social Skills Development" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Date *</label>
                        <input type="date" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Review Date</label>
                        <input type="date" class="form-input">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Plan Overview</label>
                    <textarea class="form-textarea" placeholder="Describe the overall treatment approach and objectives..."></textarea>
                </div>
                
                <h3 style="margin: 2rem 0 1rem 0; color: var(--primary-color);">🎯 Treatment Goals</h3>
                
                <div id="goalsContainer">
                    <div class="goal-section" style="border: 2px solid var(--border-color); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;">
                        <h4 style="margin-bottom: 1rem;">Goal 1</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Goal Category</label>
                                <select class="form-select">
                                    <option value="">Select category...</option>
                                    <option value="Communication Skills">Communication Skills</option>
                                    <option value="Social Interaction">Social Interaction</option>
                                    <option value="Behavioral Regulation">Behavioral Regulation</option>
                                    <option value="Daily Living Skills">Daily Living Skills</option>
                                    <option value="Motor Skills">Motor Skills</option>
                                    <option value="Cognitive Development">Cognitive Development</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Priority</label>
                                <select class="form-select">
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Goal Title</label>
                            <input type="text" class="form-input" placeholder="e.g. Increase verbal communication">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Goal Description</label>
                            <textarea class="form-textarea" placeholder="Detailed description of what the client will achieve..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Target Criteria (SMART Goal)</label>
                            <textarea class="form-textarea" placeholder="Specific, measurable criteria for goal achievement..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: space-between; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="addGoal()">
                        ➕ Add Another Goal
                    </button>
                    
                    <div style="display: flex; gap: 1rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            💾 Save Treatment Plan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        let goalCount = 1;
        
        function openTreatmentPlanner() {
            document.getElementById('treatmentPlanModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('treatmentPlanModal').classList.remove('show');
        }
        
        function addGoal() {
            goalCount++;
            const goalsContainer = document.getElementById('goalsContainer');
            
            const goalSection = document.createElement('div');
            goalSection.className = 'goal-section';
            goalSection.style.cssText = 'border: 2px solid var(--border-color); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1rem;';
            goalSection.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h4>Goal ${goalCount}</h4>
                    <button type="button" class="btn btn-secondary" onclick="removeGoal(this)" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                        ❌ Remove
                    </button>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Goal Category</label>
                        <select class="form-select">
                            <option value="">Select category...</option>
                            <option value="Communication Skills">Communication Skills</option>
                            <option value="Social Interaction">Social Interaction</option>
                            <option value="Behavioral Regulation">Behavioral Regulation</option>
                            <option value="Daily Living Skills">Daily Living Skills</option>
                            <option value="Motor Skills">Motor Skills</option>
                            <option value="Cognitive Development">Cognitive Development</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select class="form-select">
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Goal Title</label>
                    <input type="text" class="form-input" placeholder="e.g. Increase verbal communication">
                </div>
                <div class="form-group">
                    <label class="form-label">Goal Description</label>
                    <textarea class="form-textarea" placeholder="Detailed description of what the client will achieve..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Target Criteria (SMART Goal)</label>
                    <textarea class="form-textarea" placeholder="Specific, measurable criteria for goal achievement..."></textarea>
                </div>
            `;
            
            goalsContainer.appendChild(goalSection);
        }
        
        function removeGoal(button) {
            button.closest('.goal-section').remove();
        }
        
        function openClientDetails(clientId) {
            alert(`🔍 Opening detailed view for ${clientId}\n\nThis would show:\n• Complete treatment history\n• Current goals and progress\n• Session notes\n• Family communication\n• Authorization status\n• Progress charts`);
        }
        
        function openClientEnrollment() {
            alert('➕ Client Enrollment\n\nThis would open a form to:\n• Enroll new clients\n• Set initial assessments\n• Configure service authorizations\n• Assign staff members\n• Create intake documentation');
        }
        
        function filterClients(filter) {
            const cards = document.querySelectorAll('.client-card');
            
            cards.forEach(card => {
                const status = card.querySelector('.client-status');
                
                if (filter === 'all') {
                    card.style.display = 'block';
                } else if (filter === 'review') {
                    card.style.display = status.classList.contains('status-needs-review') ? 'block' : 'none';
                }
            });
        }
        
        function reviewProgress() {
            alert('📊 Progress Review\n\nThis would open:\n• Client progress analytics\n• Goal achievement reports\n• Session outcome data\n• Intervention effectiveness\n• Recommendation engine');
        }
        
        function scheduleSession() {
            alert('📅 Session Scheduling\n\nThis would provide:\n• Staff availability calendar\n• Client preferences\n• Service type scheduling\n• Conflict resolution\n• Automated reminders');
        }
        
        function generateReports() {
            alert('📄 Report Generation\n\nAvailable reports:\n• Progress summaries for families\n• Treatment plan reviews\n• Goal achievement analytics\n• Service utilization reports\n• Compliance documentation');
        }
        
        function viewAllActivities() {
            alert('🕒 All Activities\n\nThis would show:\n• Complete activity timeline\n• Filter by type/client/date\n• Export activity logs\n• Set activity notifications\n• Activity analytics');
        }
        
        // Form submission
        document.getElementById('treatmentPlanForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            alert('✅ Treatment Plan Created!\n\nThe treatment plan has been saved and is now available to direct care staff for session documentation.\n\nGoals will automatically populate in the Employee Portal for this client.');
            
            closeModal();
            this.reset();
            goalCount = 1;
            
            // Reset goals container to show only one goal
            const goalsContainer = document.getElementById('goalsContainer');
            const goalSections = goalsContainer.querySelectorAll('.goal-section');
            for (let i = 1; i < goalSections.length; i++) {
                goalSections[i].remove();
            }
        });
        
        // Close modal when clicking outside
        document.getElementById('treatmentPlanModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html> 