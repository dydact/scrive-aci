<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>🤝 ACI Mobile Portal</title>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="#059669">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        :root {
            --primary-color: #059669;
            --secondary-color: #f0f9ff;
            --accent-color: #2563eb;
            --warning-color: #f59e0b;
            --success-color: #10b981;
            --text-color: #1e293b;
            --border-color: #e2e8f0;
            --shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            --card-radius: 12px;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            min-height: 100vh;
            color: var(--text-color);
            font-size: 16px;
            line-height: 1.5;
            overflow-x: hidden;
        }
        
        .mobile-container {
            max-width: 100%;
            padding: 0;
            position: relative;
        }
        
        /* Mobile Header */
        .mobile-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--border-color);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-info-mobile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .user-details h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .user-details p {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .time-badge {
            background: var(--warning-color);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
            min-width: 80px;
        }
        
        /* Quick Stats Bar */
        .stats-bar {
            background: white;
            padding: 1rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 8px;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }
        
        .stat-label {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
        
        /* Main Content */
        .main-content {
            padding: 0 1rem 2rem;
        }
        
        /* Card Styles */
        .mobile-card {
            background: white;
            border-radius: var(--card-radius);
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }
        
        .card-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        /* Client List Mobile */
        .client-list-mobile {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .client-item-mobile {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            border-left: 4px solid var(--primary-color);
            cursor: pointer;
            transition: all 0.2s;
            touch-action: manipulation;
        }
        
        .client-item-mobile:active {
            transform: scale(0.98);
            background: #e2e8f0;
        }
        
        .client-header-mobile {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }
        
        .client-name-mobile {
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-color);
        }
        
        .next-session {
            background: var(--accent-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .client-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #64748b;
            margin-bottom: 0.75rem;
        }
        
        .progress-summary {
            background: rgba(5, 150, 105, 0.1);
            border-radius: 6px;
            padding: 0.75rem;
        }
        
        .progress-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .progress-item:last-child {
            margin-bottom: 0;
        }
        
        .progress-label {
            font-size: 0.85rem;
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .progress-bar-mobile {
            width: 60px;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill-mobile {
            height: 100%;
            background: var(--success-color);
            transition: width 0.3s;
        }
        
        /* Quick Actions */
        .quick-actions-mobile {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .action-btn-mobile {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: var(--card-radius);
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            touch-action: manipulation;
        }
        
        .action-btn-mobile:active {
            transform: scale(0.95);
            border-color: var(--primary-color);
        }
        
        .action-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .action-text {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        /* Time Clock Widget */
        .time-clock-mobile {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
            color: white;
            text-align: center;
            padding: 1.5rem;
            border-radius: var(--card-radius);
            margin-bottom: 1rem;
        }
        
        .clock-time {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-family: 'Courier New', monospace;
        }
        
        .clock-status {
            font-size: 1rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        .clock-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            touch-action: manipulation;
            transition: all 0.2s;
        }
        
        .clock-btn:active {
            transform: scale(0.95);
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* Note Form Mobile */
        .note-form-mobile {
            background: #fef3c7;
            border: 2px dashed var(--warning-color);
            border-radius: var(--card-radius);
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .form-group-mobile {
            margin-bottom: 1rem;
        }
        
        .form-label-mobile {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.9rem;
        }
        
        .form-select-mobile,
        .form-textarea-mobile {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            background: white;
        }
        
        .form-textarea-mobile {
            min-height: 100px;
            resize: vertical;
        }
        
        .submit-btn-mobile {
            width: 100%;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            touch-action: manipulation;
            margin-top: 1rem;
        }
        
        .submit-btn-mobile:active {
            transform: scale(0.98);
            background: #047857;
        }
        
        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid var(--border-color);
            padding: 0.75rem;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            z-index: 100;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0.5rem;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s;
            touch-action: manipulation;
        }
        
        .nav-item:active {
            background: #f1f5f9;
            transform: scale(0.95);
        }
        
        .nav-icon {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
            color: #64748b;
        }
        
        .nav-text {
            font-size: 0.75rem;
            color: #64748b;
        }
        
        .nav-item.active .nav-icon,
        .nav-item.active .nav-text {
            color: var(--primary-color);
        }
        
        /* Add bottom padding to main content for nav */
        .main-content {
            padding-bottom: 6rem;
        }
        
        /* Loading States */
        .loading {
            text-align: center;
            padding: 2rem;
            color: #64748b;
        }
        
        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 375px) {
            .mobile-header {
                padding: 0.75rem;
            }
            
            .stats-bar {
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
            }
            
            .stat-item:last-child {
                grid-column: 1 / -1;
            }
        }
    </style>
</head>
<body>
    <div class="mobile-container">
        <!-- Mobile Header -->
        <div class="mobile-header">
            <div class="header-content">
                <div class="user-info-mobile">
                    <div class="user-avatar">SJ</div>
                    <div class="user-details">
                        <h3>Sarah Johnson</h3>
                        <p>Direct Care Staff</p>
                    </div>
                </div>
                <div class="time-badge" id="currentTime">7:42</div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-number">5</span>
                <span class="stat-label">Today's Clients</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">32.5</span>
                <span class="stat-label">Week Hours</span>
            </div>
            <div class="stat-item">
                <span class="stat-number">18</span>
                <span class="stat-label">Notes Done</span>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Time Clock Widget -->
            <div class="time-clock-mobile">
                <div class="clock-time" id="clockTime">7:42:15</div>
                <div class="clock-status">Currently Clocked In</div>
                <button class="clock-btn" onclick="toggleClock()">Clock Out</button>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions-mobile">
                <div class="action-btn-mobile" onclick="openQuickNote()">
                    <span class="action-icon">📝</span>
                    <span class="action-text">Quick Note</span>
                </div>
                <div class="action-btn-mobile" onclick="viewSchedule()">
                    <span class="action-icon">📅</span>
                    <span class="action-text">Schedule</span>
                </div>
                <div class="action-btn-mobile" onclick="viewPayroll()">
                    <span class="action-icon">💰</span>
                    <span class="action-text">Payroll</span>
                </div>
                <div class="action-btn-mobile" onclick="viewTraining()">
                    <span class="action-icon">🎓</span>
                    <span class="action-text">Training</span>
                </div>
            </div>

            <!-- Today's Clients -->
            <div class="mobile-card">
                <div class="card-header">
                    <h2 class="card-title">📋 Today's Clients</h2>
                </div>
                <div class="client-list-mobile">
                    <div class="client-item-mobile" onclick="selectClient('1')">
                        <div class="client-header-mobile">
                            <div class="client-name-mobile">Emma Rodriguez</div>
                            <div class="next-session">2:00 PM</div>
                        </div>
                        <div class="client-meta">
                            <span>Age: 9 • IISS</span>
                            <span>AW Program</span>
                        </div>
                        <div class="progress-summary">
                            <div class="progress-item">
                                <span class="progress-label">Communication</span>
                                <div class="progress-bar-mobile">
                                    <div class="progress-fill-mobile" style="width: 75%"></div>
                                </div>
                            </div>
                            <div class="progress-item">
                                <span class="progress-label">Social Skills</span>
                                <div class="progress-bar-mobile">
                                    <div class="progress-fill-mobile" style="width: 60%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="client-item-mobile" onclick="selectClient('2')">
                        <div class="client-header-mobile">
                            <div class="client-name-mobile">Michael Chen</div>
                            <div class="next-session">4:00 PM</div>
                        </div>
                        <div class="client-meta">
                            <span>Age: 12 • TI</span>
                            <span>DDA Program</span>
                        </div>
                        <div class="progress-summary">
                            <div class="progress-item">
                                <span class="progress-label">Daily Living</span>
                                <div class="progress-bar-mobile">
                                    <div class="progress-fill-mobile" style="width: 85%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Note Form -->
                <div class="note-form-mobile" id="noteForm" style="display: none;">
                    <h3 style="margin-bottom: 1rem; color: #92400e;">📝 Quick Session Note</h3>
                    
                    <div class="form-group-mobile">
                        <label class="form-label-mobile">Client</label>
                        <select class="form-select-mobile" id="clientSelect">
                            <option value="">Select client...</option>
                            <option value="1">Emma Rodriguez</option>
                            <option value="2">Michael Chen</option>
                        </select>
                    </div>

                    <div class="form-group-mobile">
                        <label class="form-label-mobile">Session Notes</label>
                        <textarea class="form-textarea-mobile" placeholder="What did you work on during this session?"></textarea>
                    </div>

                    <div id="goalRatings" style="display: none;">
                        <h4 style="margin-bottom: 0.75rem; color: var(--primary-color);">Goal Progress</h4>
                        <!-- Goals will be populated here -->
                    </div>

                    <button class="submit-btn-mobile" onclick="saveNote()">💾 Save Note</button>
                </div>
            </div>
        </div>

        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <div class="nav-item active">
                <div class="nav-icon">🏠</div>
                <div class="nav-text">Home</div>
            </div>
            <div class="nav-item" onclick="viewSchedule()">
                <div class="nav-icon">📅</div>
                <div class="nav-text">Schedule</div>
            </div>
            <div class="nav-item" onclick="viewNotes()">
                <div class="nav-icon">📝</div>
                <div class="nav-text">Notes</div>
            </div>
            <div class="nav-item" onclick="viewProfile()">
                <div class="nav-icon">👤</div>
                <div class="nav-text">Profile</div>
            </div>
        </div>
    </div>

    <script>
        // Mobile-optimized JavaScript
        let currentTime = 27735; // 7:42:15 in seconds
        let isTimerRunning = true;
        
        function updateClock() {
            const hours = Math.floor(currentTime / 3600);
            const minutes = Math.floor((currentTime % 3600) / 60);
            const seconds = currentTime % 60;
            
            const timeString = `${hours}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            const shortTime = `${hours}:${minutes.toString().padStart(2, '0')}`;
            
            document.getElementById('clockTime').textContent = timeString;
            document.getElementById('currentTime').textContent = shortTime;
            
            if (isTimerRunning) {
                currentTime++;
            }
        }
        
        function toggleClock() {
            isTimerRunning = !isTimerRunning;
            const btn = document.querySelector('.clock-btn');
            const status = document.querySelector('.clock-status');
            
            if (isTimerRunning) {
                btn.textContent = 'Clock Out';
                status.textContent = 'Currently Clocked In';
            } else {
                btn.textContent = 'Clock In';
                status.textContent = 'Currently Clocked Out';
            }
            
            // Add haptic feedback if available
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }
        }
        
        function selectClient(clientId) {
            document.getElementById('clientSelect').value = clientId;
            loadClientGoals(clientId);
            
            // Scroll to note form
            document.getElementById('noteForm').style.display = 'block';
            document.getElementById('noteForm').scrollIntoView({ behavior: 'smooth' });
        }
        
        function loadClientGoals(clientId) {
            const goalRatings = document.getElementById('goalRatings');
            
            // Mock data - replace with API call
            const goals = {
                '1': [
                    { name: 'Communication Skills', current: 75 },
                    { name: 'Social Interaction', current: 60 }
                ],
                '2': [
                    { name: 'Daily Living Skills', current: 85 }
                ]
            };
            
            const clientGoals = goals[clientId] || [];
            
            if (clientGoals.length > 0) {
                let html = '';
                clientGoals.forEach(goal => {
                    html += `
                        <div style="margin-bottom: 1rem; padding: 0.75rem; background: white; border-radius: 8px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-weight: 600; font-size: 0.9rem;">${goal.name}</span>
                                <span style="font-size: 0.8rem; color: #059669;">${goal.current}%</span>
                            </div>
                            <select style="width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 6px;">
                                <option value="1">1 - No Progress</option>
                                <option value="2">2 - Minimal Progress</option>
                                <option value="3" selected>3 - Some Progress</option>
                                <option value="4">4 - Good Progress</option>
                                <option value="5">5 - Excellent Progress</option>
                            </select>
                        </div>
                    `;
                });
                goalRatings.innerHTML = html;
                goalRatings.style.display = 'block';
            }
        }
        
        function openQuickNote() {
            const noteForm = document.getElementById('noteForm');
            noteForm.style.display = 'block';
            noteForm.scrollIntoView({ behavior: 'smooth' });
        }
        
        function saveNote() {
            // Add haptic feedback
            if (navigator.vibrate) {
                navigator.vibrate([50, 100, 50]);
            }
            
            // Show success message
            const btn = document.querySelector('.submit-btn-mobile');
            const originalText = btn.textContent;
            btn.textContent = '✅ Saved!';
            btn.style.background = '#10b981';
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.background = '';
                document.getElementById('noteForm').style.display = 'none';
            }, 2000);
        }
        
        function viewSchedule() {
            alert('📅 Today\'s Schedule\n\n• Emma Rodriguez - 2:00 PM\n• Michael Chen - 4:00 PM\n• Sarah Johnson - 6:00 PM\n\nTap a client to start session notes');
        }
        
        function viewPayroll() {
            alert('💰 Payroll Summary\n\nThis Week:\n• Hours: 32.5\n• Estimated Pay: $487.50\n• Sessions: 18\n\nTap for full timesheet');
        }
        
        function viewTraining() {
            alert('🎓 Training Materials\n\n• Autism intervention techniques\n• Safety protocols\n• Documentation standards\n\nAccess your training portal');
        }
        
        function viewNotes() {
            alert('📝 Session Notes\n\n• Today: 3 notes completed\n• This week: 18 notes\n• Pending review: 2\n\nView all notes and history');
        }
        
        function viewProfile() {
            alert('👤 Profile\n\n• Update personal info\n• Change password\n• Notification settings\n• Contact support');
        }
        
        // Initialize
        setInterval(updateClock, 1000);
        updateClock();
        
        // Prevent zoom on double tap
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            let now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
        
        // Add loading state for better mobile UX
        window.addEventListener('beforeunload', function() {
            document.body.style.opacity = '0.7';
        });
    </script>
</body>
</html> 