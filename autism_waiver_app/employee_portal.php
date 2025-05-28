<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🤝 Employee Portal - American Caregivers</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #059669;
            --secondary-color: #f0f9ff;
            --accent-color: #2563eb;
            --warning-color: #f59e0b;
            --success-color: #10b981;
            --text-color: #1e293b;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            color: var(--text-color);
        }
        
        .container {
            max-width: 1200px;
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
        
        .time-clock {
            background: var(--warning-color);
            color: white;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .time-clock:hover {
            background: #d97706;
            transform: translateY(-1px);
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .action-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: all 0.2s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .action-card .icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .action-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }
        
        .action-card p {
            color: #64748b;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .priority-badge {
            display: inline-block;
            background: #ef4444;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
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
            background: #047857;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .client-list {
            display: grid;
            gap: 1rem;
        }
        
        .client-item {
            background: #f8fafc;
            border-radius: 0.5rem;
            padding: 1rem;
            border-left: 4px solid var(--primary-color);
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .client-item:hover {
            background: #e2e8f0;
            transform: translateX(4px);
        }
        
        .client-item .client-name {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }
        
        .client-item .client-details {
            font-size: 0.85rem;
            color: #64748b;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .treatment-goals {
            display: grid;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .goal-item {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 0.5rem;
            padding: 0.75rem;
        }
        
        .goal-item .goal-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }
        
        .goal-item .goal-description {
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .progress-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .progress-bar {
            flex: 1;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: var(--success-color);
            transition: width 0.3s;
        }
        
        .progress-text {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--success-color);
        }
        
        .recent-notes {
            display: grid;
            gap: 0.75rem;
        }
        
        .note-item {
            background: #f8fafc;
            border-radius: 0.5rem;
            padding: 1rem;
            border-left: 4px solid var(--accent-color);
        }
        
        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .note-client {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .note-date {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .note-excerpt {
            font-size: 0.9rem;
            color: #64748b;
            line-height: 1.4;
        }
        
        .quick-note-form {
            background: #f8fafc;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-top: 1rem;
            border: 2px dashed var(--border-color);
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
        
        .form-select {
            width: 100%;
            padding: 0.5rem;
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            background: white;
            font-size: 0.9rem;
        }
        
        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 0.9rem;
            min-height: 80px;
            resize: vertical;
        }
        
        .auto-populate-hint {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #92400e;
        }
        
        .time-tracker {
            background: rgba(245, 158, 11, 0.1);
            border: 2px solid #fbbf24;
            border-radius: 0.75rem;
            padding: 1rem;
            text-align: center;
        }
        
        .time-display {
            font-size: 2rem;
            font-weight: 700;
            color: var(--warning-color);
            margin-bottom: 0.5rem;
        }
        
        .time-status {
            font-size: 0.9rem;
            color: #92400e;
            margin-bottom: 1rem;
        }
        
        .payroll-summary {
            background: rgba(37, 99, 235, 0.1);
            border: 2px solid #3b82f6;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .payroll-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(37, 99, 235, 0.2);
        }
        
        .payroll-item:last-child {
            border-bottom: none;
            font-weight: 600;
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
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .quick-actions {
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
                <h1>🤝 Employee Portal</h1>
                <p class="subtitle">Session notes, time tracking, and client progress</p>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <div class="user-name">Sarah Johnson</div>
                    <div class="user-role">Direct Care Staff</div>
                </div>
                <div class="time-clock" onclick="toggleTimeClock()">
                    ⏰ Clock In/Out
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-card" onclick="openQuickNote()">
                <div class="icon">📝</div>
                <h3>Quick Session Note</h3>
                <p>Enter a session note with auto-populated treatment goals and progress tracking</p>
                <span class="priority-badge">Most Used</span>
            </div>
            
            <div class="action-card" onclick="viewMySchedule()">
                <div class="icon">📅</div>
                <h3>My Schedule</h3>
                <p>View today's appointments and upcoming sessions with clients</p>
            </div>
            
            <div class="action-card" onclick="viewTimesheet()">
                <div class="icon">⏰</div>
                <h3>Time & Payroll</h3>
                <p>Track hours, view timesheets, and check paystub information</p>
            </div>
            
            <div class="action-card" onclick="viewTrainingMaterials()">
                <div class="icon">🎓</div>
                <h3>Training Materials</h3>
                <p>Access treatment protocols, safety guidelines, and training resources</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Left Column: My Clients & Quick Note -->
            <div class="content-section">
                <div class="section-header">
                    <h2>📋 My Clients Today</h2>
                    <button class="btn btn-primary" onclick="openQuickNote()">
                        ➕ Add Session Note
                    </button>
                </div>
                
                <div class="client-list">
                    <div class="client-item" onclick="selectClient('1')">
                        <div class="client-name">Emma Rodriguez</div>
                        <div class="client-details">
                            <span>Age: 9</span>
                            <span>Program: Autism Waiver</span>
                            <span>Next Session: 2:00 PM</span>
                            <span>Service: IISS</span>
                        </div>
                        <div class="treatment-goals">
                            <div class="goal-item">
                                <div class="goal-title">Communication Skills</div>
                                <div class="goal-description">Increase verbal requests using 3-4 word phrases</div>
                                <div class="progress-indicator">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: 75%"></div>
                                    </div>
                                    <span class="progress-text">75%</span>
                                </div>
                            </div>
                            <div class="goal-item">
                                <div class="goal-title">Social Interaction</div>
                                <div class="goal-description">Initiate play activities with peers for 10+ minutes</div>
                                <div class="progress-indicator">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: 60%"></div>
                                    </div>
                                    <span class="progress-text">60%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="client-item" onclick="selectClient('2')">
                        <div class="client-name">Michael Chen</div>
                        <div class="client-details">
                            <span>Age: 12</span>
                            <span>Program: DDA</span>
                            <span>Next Session: 4:00 PM</span>
                            <span>Service: TI</span>
                        </div>
                        <div class="treatment-goals">
                            <div class="goal-item">
                                <div class="goal-title">Daily Living Skills</div>
                                <div class="goal-description">Complete morning routine independently</div>
                                <div class="progress-indicator">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: 85%"></div>
                                    </div>
                                    <span class="progress-text">85%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Note Form -->
                <div class="quick-note-form">
                    <h3>📝 Quick Session Note</h3>
                    <div class="auto-populate-hint">
                        💡 <strong>Smart Form:</strong> Select a client and their treatment goals will auto-populate below
                    </div>
                    
                    <form id="quickNoteForm">
                        <div class="form-group">
                            <label class="form-label">Client *</label>
                            <select class="form-select" id="clientSelect" onchange="populateGoals()">
                                <option value="">Select a client...</option>
                                <option value="1">Emma Rodriguez</option>
                                <option value="2">Michael Chen</option>
                                <option value="3">Aiden Thompson</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Service Type *</label>
                            <select class="form-select" id="serviceSelect">
                                <option value="">Auto-populated from client...</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Session Notes *</label>
                            <textarea class="form-textarea" id="sessionNotes" 
                                placeholder="Describe what was worked on during the session..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Goal Progress</label>
                            <div id="goalProgress">
                                <p style="color: #64748b;">Select a client to see their treatment goals...</p>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            💾 Save Session Note
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Right Column: Time Tracker & Recent Notes -->
            <div>
                <!-- Time Tracker -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>⏰ Time Tracker</h2>
                    </div>
                    
                    <div class="time-tracker">
                        <div class="time-display" id="timeDisplay">7:42:15</div>
                        <div class="time-status" id="timeStatus">Currently clocked in</div>
                        <button class="btn btn-primary" onclick="toggleTimeClock()">
                            Clock Out
                        </button>
                    </div>
                    
                    <div class="payroll-summary">
                        <h4 style="margin-bottom: 0.75rem; color: var(--accent-color);">💰 This Week</h4>
                        <div class="payroll-item">
                            <span>Hours Worked</span>
                            <span>32.5 hrs</span>
                        </div>
                        <div class="payroll-item">
                            <span>Sessions Completed</span>
                            <span>18</span>
                        </div>
                        <div class="payroll-item">
                            <span>Estimated Pay</span>
                            <span>$487.50</span>
                        </div>
                        <button class="btn btn-secondary" style="width: 100%; margin-top: 0.75rem;" onclick="viewFullPayroll()">
                            📊 View Full Timesheet
                        </button>
                    </div>
                </div>
                
                <!-- Recent Notes -->
                <div class="content-section" style="margin-top: 1.5rem;">
                    <div class="section-header">
                        <h2>📄 Recent Notes</h2>
                        <button class="btn btn-secondary" onclick="viewAllNotes()">
                            View All
                        </button>
                    </div>
                    
                    <div class="recent-notes">
                        <div class="note-item">
                            <div class="note-header">
                                <div class="note-client">Emma Rodriguez</div>
                                <div class="note-date">Today, 2:15 PM</div>
                            </div>
                            <div class="note-excerpt">
                                Worked on communication goals. Emma successfully used 4-word phrases 8/10 times...
                            </div>
                        </div>
                        
                        <div class="note-item">
                            <div class="note-header">
                                <div class="note-client">Michael Chen</div>
                                <div class="note-date">Yesterday, 4:30 PM</div>
                            </div>
                            <div class="note-excerpt">
                                Daily living skills session. Michael completed morning routine with minimal prompting...
                            </div>
                        </div>
                        
                        <div class="note-item">
                            <div class="note-header">
                                <div class="note-client">Aiden Thompson</div>
                                <div class="note-date">Yesterday, 10:00 AM</div>
                            </div>
                            <div class="note-excerpt">
                                Behavioral intervention session. Significant improvement in emotional regulation...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Note Modal -->
    <div class="modal" id="sessionNoteModal">
        <div class="modal-content">
            <h2>📝 Complete Session Note</h2>
            
            <form id="fullSessionForm">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Client *</label>
                        <select class="form-select" required>
                            <option value="">Select client...</option>
                            <option value="emma_rodriguez">Emma Rodriguez</option>
                            <option value="michael_chen">Michael Chen</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Service Type *</label>
                        <select class="form-select" required>
                            <option value="">Select service...</option>
                            <option value="IISS">Individual Intensive Support Services</option>
                            <option value="TI">Therapeutic Integration</option>
                            <option value="Respite">Respite Care</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Session Start Time *</label>
                        <input type="time" class="form-select" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Session End Time *</label>
                        <input type="time" class="form-select" required>
                    </div>
                </div>
                
                <div class="auto-populate-hint">
                    🎯 <strong>Auto-Populated Treatment Goals:</strong> The following goals are from Emma's current treatment plan
                </div>
                
                <div class="form-group">
                    <label class="form-label">Goal 1: Communication Skills Progress</label>
                    <textarea class="form-textarea" placeholder="How did the client progress on verbal communication using 3-4 word phrases?"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Goal 2: Social Interaction Progress</label>
                    <textarea class="form-textarea" placeholder="How did the client progress on initiating play activities?"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Overall Session Notes *</label>
                    <textarea class="form-textarea" style="min-height: 120px;" 
                        placeholder="Provide a comprehensive summary of the session, interventions used, client responses, and recommendations..."></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💾 Save Complete Note
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentTime = 27735; // 7:42:15 in seconds
        let isTimerRunning = true;
        let timeInterval;
        
        // Time tracking functions
        function updateTimeDisplay() {
            const hours = Math.floor(currentTime / 3600);
            const minutes = Math.floor((currentTime % 3600) / 60);
            const seconds = currentTime % 60;
            
            document.getElementById('timeDisplay').textContent = 
                `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        
        function startTimer() {
            if (timeInterval) clearInterval(timeInterval);
            timeInterval = setInterval(() => {
                if (isTimerRunning) {
                    currentTime++;
                    updateTimeDisplay();
                }
            }, 1000);
        }
        
        function toggleTimeClock() {
            isTimerRunning = !isTimerRunning;
            const statusElement = document.getElementById('timeStatus');
            const buttons = document.querySelectorAll('.time-clock');
            
            if (isTimerRunning) {
                statusElement.textContent = 'Currently clocked in';
                buttons.forEach(btn => btn.textContent = '⏰ Clock Out');
            } else {
                statusElement.textContent = 'Currently clocked out';
                buttons.forEach(btn => btn.textContent = '⏰ Clock In');
            }
        }
        
        // Client selection and goal population
        function populateGoals() {
            const clientSelect = document.getElementById('clientSelect');
            const serviceSelect = document.getElementById('serviceSelect');
            const goalProgress = document.getElementById('goalProgress');
            
            const selectedClient = clientSelect.value;
            
            if (!selectedClient) {
                serviceSelect.innerHTML = '<option value="">Auto-populated from client...</option>';
                goalProgress.innerHTML = '<p style="color: #64748b;">Select a client to see their treatment goals...</p>';
                return;
            }
            
            // Show loading state
            goalProgress.innerHTML = '<p style="color: #64748b;">🔄 Loading treatment goals...</p>';
            serviceSelect.innerHTML = '<option value="">Loading...</option>';
            
            // Fetch real data from treatment plan API
            fetch(`treatment_plan_api.php?endpoint=client_goals&client_id=${selectedClient}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        goalProgress.innerHTML = `<p style="color: #dc2626;">❌ Error: ${data.error}</p>`;
                        return;
                    }
                    
                    if (data.demo_mode || !data.goals_by_category || Object.keys(data.goals_by_category).length === 0) {
                        // Use fallback demo data if no real data available
                        populateWithDemoData(selectedClient);
                        return;
                    }
                    
                    // Populate with real data
                    populateWithRealData(data);
                })
                .catch(error => {
                    console.error('Error fetching treatment goals:', error);
                    // Fallback to demo data
                    populateWithDemoData(selectedClient);
                });
        }
        
        function populateWithRealData(data) {
            const serviceSelect = document.getElementById('serviceSelect');
            const goalProgress = document.getElementById('goalProgress');
            
            // Update service select with program info
            if (data.client_info && data.client_info.program_code) {
                const serviceOptions = {
                    'AW': 'IISS - Individual Intensive Support Services',
                    'DDA': 'TI - Therapeutic Integration', 
                    'CFC': 'Respite - Respite Care',
                    'CS': 'FC - Family Consultation'
                };
                const serviceType = serviceOptions[data.client_info.program_code] || 'Unknown Service';
                serviceSelect.innerHTML = `<option value="${serviceType}">${serviceType}</option>`;
            }
            
            // Build goal progress section from real data
            let goalsHtml = '';
            
            for (const [category, goals] of Object.entries(data.goals_by_category)) {
                goals.forEach((goal, index) => {
                    goalsHtml += `
                        <div class="goal-item" style="margin-bottom: 0.75rem;" data-goal-id="${goal.goal_id}">
                            <div class="goal-title">${goal.title}</div>
                            <div class="goal-description">${goal.description}</div>
                            ${goal.target_criteria ? `<div style="font-size: 0.85rem; color: #059669; margin-top: 0.25rem;"><strong>Target:</strong> ${goal.target_criteria}</div>` : ''}
                            <div style="margin-top: 0.5rem;">
                                <label style="font-size: 0.85rem; margin-bottom: 0.25rem; display: block;">Progress Rating:</label>
                                <select class="form-select goal-rating" style="width: 250px;" data-goal-id="${goal.goal_id}">
                                    <option value="1">1 - No Progress</option>
                                    <option value="2">2 - Minimal Progress</option>
                                    <option value="3" selected>3 - Some Progress</option>
                                    <option value="4">4 - Good Progress</option>
                                    <option value="5">5 - Excellent Progress</option>
                                </select>
                            </div>
                            <div class="progress-indicator" style="margin-top: 0.5rem;">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: ${goal.current_progress}%"></div>
                                </div>
                                <span class="progress-text">${goal.current_progress}%</span>
                            </div>
                        </div>
                    `;
                });
            }
            
            if (goalsHtml) {
                goalProgress.innerHTML = `
                    <div style="margin-bottom: 1rem;">
                        <h4 style="color: #059669; margin-bottom: 0.5rem;">📋 Treatment Goals from Active Plan</h4>
                        <p style="font-size: 0.85rem; color: #64748b;">Plan: ${data.client_info?.plan_name || 'Current Treatment Plan'}</p>
                    </div>
                    ${goalsHtml}
                `;
            } else {
                goalProgress.innerHTML = '<p style="color: #64748b;">No active treatment goals found for this client.</p>';
            }
        }
        
        function populateWithDemoData(clientId) {
            const serviceSelect = document.getElementById('serviceSelect');
            const goalProgress = document.getElementById('goalProgress');
            
            // Demo data for fallback
            const clientData = {
                '1': {
                    name: 'Emma Rodriguez',
                    service: 'IISS - Individual Intensive Support Services',
                    goals: [
                        {
                            goal_id: 'demo_1',
                            title: 'Communication Skills',
                            description: 'Increase verbal requests using 3-4 word phrases',
                            current_progress: 75
                        },
                        {
                            goal_id: 'demo_2',
                            title: 'Social Interaction', 
                            description: 'Initiate play activities with peers for 10+ minutes',
                            current_progress: 60
                        }
                    ]
                },
                '2': {
                    name: 'Michael Chen',
                    service: 'TI - Therapeutic Integration',
                    goals: [
                        {
                            goal_id: 'demo_3',
                            title: 'Daily Living Skills',
                            description: 'Complete morning routine independently',
                            current_progress: 85
                        },
                        {
                            goal_id: 'demo_4',
                            title: 'Behavioral Regulation',
                            description: 'Use coping strategies when frustrated',
                            current_progress: 70
                        }
                    ]
                }
            };
            
            const selectedData = clientData[clientId];
            
            if (selectedData) {
                // Update service select
                serviceSelect.innerHTML = `<option value="${selectedData.service}">${selectedData.service}</option>`;
                
                // Update goal progress section
                let goalsHtml = `
                    <div style="margin-bottom: 1rem;">
                        <h4 style="color: #f59e0b; margin-bottom: 0.5rem;">⚠️ Demo Data</h4>
                        <p style="font-size: 0.85rem; color: #92400e; background: #fef3c7; padding: 0.5rem; border-radius: 0.25rem;">No real treatment plans found. Using demo data. Run "Setup Demo Data" to create real treatment plans.</p>
                    </div>
                `;
                
                selectedData.goals.forEach((goal, index) => {
                    goalsHtml += `
                        <div class="goal-item" style="margin-bottom: 0.75rem;" data-goal-id="${goal.goal_id}">
                            <div class="goal-title">${goal.title}</div>
                            <div class="goal-description">${goal.description}</div>
                            <div style="margin-top: 0.5rem;">
                                <label style="font-size: 0.85rem; margin-bottom: 0.25rem; display: block;">Progress Rating:</label>
                                <select class="form-select goal-rating" style="width: 250px;" data-goal-id="${goal.goal_id}">
                                    <option value="1">1 - No Progress</option>
                                    <option value="2">2 - Minimal Progress</option>
                                    <option value="3" selected>3 - Some Progress</option>
                                    <option value="4">4 - Good Progress</option>
                                    <option value="5">5 - Excellent Progress</option>
                                </select>
                            </div>
                            <div class="progress-indicator" style="margin-top: 0.5rem;">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: ${goal.current_progress}%"></div>
                                </div>
                                <span class="progress-text">${goal.current_progress}%</span>
                            </div>
                        </div>
                    `;
                });
                
                goalProgress.innerHTML = goalsHtml;
            } else {
                serviceSelect.innerHTML = '<option value="">No service data available</option>';
                goalProgress.innerHTML = '<p style="color: #64748b;">No client data available.</p>';
            }
        }
        
        function selectClient(clientId) {
            document.getElementById('clientSelect').value = clientId;
            populateGoals();
        }
        
        function openQuickNote() {
            document.getElementById('sessionNoteModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('sessionNoteModal').classList.remove('show');
        }
        
        function viewMySchedule() {
            alert('📅 Schedule View\n\nToday\'s Appointments:\n• Emma Rodriguez - 2:00 PM - IISS\n• Michael Chen - 4:00 PM - TI\n• Aiden Thompson - 6:00 PM - Respite\n\nTomorrow: 3 appointments scheduled');
        }
        
        function viewTimesheet() {
            alert('⏰ Time & Payroll\n\n🔗 Integration with Intuit QuickBooks:\n• View detailed timesheets\n• Check paystub history\n• Update direct deposit info\n• Request time off\n\nThis feature will connect to QuickBooks Workforce for seamless payroll management.');
        }
        
        function viewTrainingMaterials() {
            alert('🎓 Training Resources\n\n• Autism intervention techniques\n• Behavioral support strategies\n• Safety protocols\n• Documentation requirements\n• Emergency procedures\n\nAccess your personalized training portal with progress tracking.');
        }
        
        function viewFullPayroll() {
            alert('📊 Full Timesheet\n\nWeek of Jan 15-21, 2025:\n\nMon: 8.5 hrs (4 sessions)\nTue: 7.0 hrs (3 sessions)\nWed: 8.0 hrs (4 sessions)\nThu: 9.0 hrs (4 sessions)\nFri: 0.0 hrs (0 sessions)\n\nTotal: 32.5 hrs\nEst. Pay: $487.50\n\n🔗 View in QuickBooks Workforce');
        }
        
        function viewAllNotes() {
            alert('📄 All Session Notes\n\nFilters:\n• By Client\n• By Date Range\n• By Service Type\n• Pending Review\n\nSearch through all your session documentation with advanced filtering and export options.');
        }
        
        // Form submission handlers
        document.getElementById('quickNoteForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const client = document.getElementById('clientSelect').value;
            if (!client) {
                alert('Please select a client first');
                return;
            }
            
            // Collect goal progress data
            const goalRatings = [];
            document.querySelectorAll('.goal-rating').forEach(select => {
                const goalId = select.getAttribute('data-goal-id');
                const rating = select.value;
                if (goalId && rating) {
                    goalRatings.push({
                        goal_id: goalId,
                        rating: parseInt(rating)
                    });
                }
            });
            
            const sessionData = {
                client_id: client,
                session_date: new Date().toISOString().split('T')[0],
                session_notes: document.getElementById('sessionNotes').value,
                staff_id: 1, // Demo staff ID
                goal_ratings: goalRatings
            };
            
            // Save session note with goal progress
            saveSessionNote(sessionData);
        });
        
        function saveSessionNote(sessionData) {
            // Show saving state
            const submitBtn = document.querySelector('#quickNoteForm button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '💾 Saving...';
            submitBtn.disabled = true;
            
            // First save the session note, then the goal progress
            Promise.all(sessionData.goal_ratings.map(goal => {
                return fetch('treatment_plan_api.php?endpoint=session_progress', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        goal_id: goal.goal_id,
                        session_date: sessionData.session_date,
                        progress_rating: goal.rating,
                        progress_notes: sessionData.session_notes,
                        staff_id: sessionData.staff_id
                    })
                });
            }))
            .then(responses => Promise.all(responses.map(r => r.json())))
            .then(results => {
                const errors = results.filter(r => r.error);
                if (errors.length > 0) {
                    throw new Error(errors[0].error);
                }
                
                // Success
                alert('✅ Session Note Saved!\n\nThe note has been saved and goal progress has been updated. Treatment goal progress has been automatically calculated based on your ratings.');
                
                // Reset form
                document.getElementById('quickNoteForm').reset();
                document.getElementById('goalProgress').innerHTML = '<p style="color: #64748b;">Select a client to see their treatment goals...</p>';
                
                // Refresh client list to show updated progress
                setTimeout(() => {
                    location.reload();
                }, 1000);
            })
            .catch(error => {
                console.error('Error saving session note:', error);
                alert('❌ Error saving session note: ' + error.message);
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }
        
        document.getElementById('fullSessionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            alert('✅ Complete Session Note Saved!\n\nYour comprehensive session note has been saved with all treatment goal progress updates. The information has been automatically synced with the client\'s treatment plan.');
            
            closeModal();
            this.reset();
        });
        
        // Initialize timer
        startTimer();
        updateTimeDisplay();
        
        // Add setup demo data button
        function addSetupButton() {
            const quickActions = document.querySelector('.quick-actions');
            const setupCard = document.createElement('div');
            setupCard.className = 'action-card';
            setupCard.style.cssText = 'background: #fef3c7; border-color: #f59e0b;';
            setupCard.innerHTML = `
                <div class="icon">🎯</div>
                <h3>Setup Demo Data</h3>
                <p>Create treatment plans and goals to test the auto-population feature</p>
            `;
            setupCard.onclick = function() {
                if (confirm('Create demo treatment plans for Emma Rodriguez and Michael Chen?\n\nThis will enable real auto-population of treatment goals.')) {
                    fetch('treatment_plan_api.php?endpoint=create_demo_plans', {
                        method: 'POST'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ Demo data created!\n\nRefresh the page and try selecting a client to see auto-populated treatment goals.');
                            location.reload();
                        } else {
                            alert('❌ Error: ' + data.error);
                        }
                    })
                    .catch(error => {
                        alert('❌ Error creating demo data: ' + error.message);
                    });
                }
            };
            quickActions.appendChild(setupCard);
        }
        
        // Close modal when clicking outside
        document.getElementById('sessionNoteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html> 