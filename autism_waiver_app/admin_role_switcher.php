<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👑 Master Admin - Role Switcher</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #dc2626;
            --secondary-color: #f8fafc;
            --accent-color: #059669;
            --warning-color: #f59e0b;
            --text-color: #1e293b;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            min-height: 100vh;
            color: var(--text-color);
            padding: 2rem;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 2px solid #dc2626;
        }
        
        .admin-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .admin-header .subtitle {
            color: #64748b;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        
        .warning-notice {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .warning-notice h4 {
            color: #92400e;
            margin-bottom: 0.5rem;
        }
        
        .warning-notice p {
            color: #78350f;
            font-size: 0.9rem;
        }
        
        .role-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .role-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
        }
        
        .role-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .role-card.active {
            border-color: var(--primary-color);
            background: rgba(220, 38, 38, 0.05);
        }
        
        .role-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .role-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }
        
        .role-level {
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .role-description {
            color: #64748b;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .role-permissions {
            list-style: none;
            padding: 0;
        }
        
        .role-permissions li {
            padding: 0.25rem 0;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .permission-yes {
            color: #059669;
        }
        
        .permission-no {
            color: #dc2626;
        }
        
        .current-role {
            background: rgba(220, 38, 38, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: var(--shadow-lg);
        }
        
        .current-role h3 {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
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
            text-align: center;
        }
        
        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .action-card .icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .action-card h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }
        
        .action-card p {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #b91c1c;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #f1f5f9;
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .btn-success {
            background: var(--accent-color);
            color: white;
        }
        
        .testing-tools {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }
        
        .testing-tools h3 {
            color: var(--text-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .test-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .role-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .test-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Admin Header -->
        <div class="admin-header">
            <h1>👑 Master Admin Control Panel</h1>
            <p class="subtitle">Role switching and system testing interface for American Caregivers administrators</p>
            
            <div class="warning-notice">
                <h4>⚠️ Administrator Access Only</h4>
                <p>This interface provides access to ALL system functions including organizational billing data. Use responsibly and ensure compliance with security protocols.</p>
            </div>
        </div>

        <!-- Current Role Status -->
        <div class="current-role">
            <h3>🎭 Current Testing Role: <span id="currentRoleName">Master Administrator</span></h3>
            <div id="currentRoleInfo">
                <p>You have unrestricted access to all system functions and can switch to any role for testing purposes.</p>
            </div>
        </div>

        <!-- Role Selection Grid -->
        <div class="role-grid">
            <div class="role-card" data-role="administrator" onclick="switchRole('administrator')">
                <div class="role-icon">👑</div>
                <div class="role-level">Level 5</div>
                <h3 class="role-title">Administrator</h3>
                <p class="role-description">Full system access including organizational billing management and user role control.</p>
                <ul class="role-permissions">
                    <li class="permission-yes">✅ View Organizational MA Numbers</li>
                    <li class="permission-yes">✅ Manage User Roles</li>
                    <li class="permission-yes">✅ Access All Portals</li>
                    <li class="permission-yes">✅ Security Audit Logs</li>
                </ul>
            </div>

            <div class="role-card" data-role="supervisor" onclick="switchRole('supervisor')">
                <div class="role-icon">👥</div>
                <div class="role-level">Level 4</div>
                <h3 class="role-title">Supervisor</h3>
                <p class="role-description">Management oversight with billing access but no organizational MA visibility.</p>
                <ul class="role-permissions">
                    <li class="permission-no">❌ View Organizational MA Numbers</li>
                    <li class="permission-yes">✅ View Client MA Numbers</li>
                    <li class="permission-yes">✅ Billing Reports</li>
                    <li class="permission-yes">✅ Staff Management</li>
                </ul>
            </div>

            <div class="role-card" data-role="case_manager" onclick="switchRole('case_manager')">
                <div class="role-icon">📋</div>
                <div class="role-level">Level 3</div>
                <h3 class="role-title">Case Manager</h3>
                <p class="role-description">Treatment planning and client coordination with scheduling capabilities.</p>
                <ul class="role-permissions">
                    <li class="permission-no">❌ View Organizational MA Numbers</li>
                    <li class="permission-yes">✅ View Client MA Numbers</li>
                    <li class="permission-yes">✅ Treatment Planning</li>
                    <li class="permission-yes">✅ Session Scheduling</li>
                </ul>
            </div>

            <div class="role-card" data-role="direct_care" onclick="switchRole('direct_care')">
                <div class="role-icon">🤝</div>
                <div class="role-level">Level 2</div>
                <h3 class="role-title">Direct Care Staff</h3>
                <p class="role-description">Session documentation and client interaction with limited access.</p>
                <ul class="role-permissions">
                    <li class="permission-no">❌ View Organizational MA Numbers</li>
                    <li class="permission-yes">✅ View Client MA Numbers</li>
                    <li class="permission-yes">✅ Session Notes</li>
                    <li class="permission-no">❌ Edit Client Data</li>
                </ul>
            </div>

            <div class="role-card" data-role="technician" onclick="switchRole('technician')">
                <div class="role-icon">🔧</div>
                <div class="role-level">Level 1</div>
                <h3 class="role-title">Technician</h3>
                <p class="role-description">Basic session documentation with minimal system access.</p>
                <ul class="role-permissions">
                    <li class="permission-no">❌ View Organizational MA Numbers</li>
                    <li class="permission-no">❌ View Client MA Numbers</li>
                    <li class="permission-yes">✅ Session Documentation</li>
                    <li class="permission-no">❌ Schedule Sessions</li>
                </ul>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-card" onclick="testCurrentRoleAccess()">
                <div class="icon">🧪</div>
                <h4>Test Current Role</h4>
                <p>Check what the current role can access</p>
            </div>

            <div class="action-card" onclick="openPortalRouter()">
                <div class="icon">🎭</div>
                <h4>Portal Router</h4>
                <p>Access role-based portal system</p>
            </div>

            <div class="action-card" onclick="openEmployeePortal()">
                <div class="icon">🤝</div>
                <h4>Employee Portal</h4>
                <p>Test direct care interface</p>
            </div>

            <div class="action-card" onclick="openSecurityDemo()">
                <div class="icon">🔐</div>
                <h4>Security Demo</h4>
                <p>MA number access control test</p>
            </div>
        </div>

        <!-- Testing Tools -->
        <div class="testing-tools">
            <h3>🛠️ System Testing Tools</h3>
            <div class="test-buttons">
                <button class="btn btn-primary" onclick="setupDemoData()">
                    🎯 Setup Demo Data
                </button>
                <button class="btn btn-secondary" onclick="viewSecurityLog()">
                    📊 Security Log
                </button>
                <button class="btn btn-success" onclick="resetToAdmin()">
                    👑 Reset to Admin
                </button>
                <a href="index.php" class="btn btn-secondary">
                    🏠 Main Dashboard
                </a>
            </div>
        </div>
    </div>

    <script>
        let currentRole = 'administrator';
        
        const roleData = {
            administrator: {
                name: 'Master Administrator',
                icon: '👑',
                description: 'Full system access with organizational billing visibility',
                portals: ['All Portals Available'],
                color: '#dc2626'
            },
            supervisor: {
                name: 'Supervisor',
                icon: '👥',
                description: 'Management oversight without organizational MA access',
                portals: ['Supervisor Portal', 'Case Manager Portal', 'Employee Portal'],
                color: '#f59e0b'
            },
            case_manager: {
                name: 'Case Manager',
                icon: '📋',
                description: 'Treatment planning and client coordination',
                portals: ['Case Manager Portal', 'Employee Portal'],
                color: '#2563eb'
            },
            direct_care: {
                name: 'Direct Care Staff',
                icon: '🤝',
                description: 'Session documentation and client interaction',
                portals: ['Employee Portal'],
                color: '#059669'
            },
            technician: {
                name: 'Technician',
                icon: '🔧',
                description: 'Basic session documentation only',
                portals: ['Employee Portal (Limited)'],
                color: '#6b7280'
            }
        };
        
        function switchRole(role) {
            currentRole = role;
            
            // Update visual indicators
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.remove('active');
            });
            document.querySelector(`[data-role="${role}"]`).classList.add('active');
            
            // Update current role display
            const roleInfo = roleData[role];
            document.getElementById('currentRoleName').textContent = roleInfo.name;
            document.getElementById('currentRoleInfo').innerHTML = `
                <p><strong>Role:</strong> ${roleInfo.description}</p>
                <p><strong>Available Portals:</strong> ${roleInfo.portals.join(', ')}</p>
                <p><strong>Testing as:</strong> ${roleInfo.icon} ${roleInfo.name}</p>
            `;
            
            // Store in session/localStorage for other pages to use
            localStorage.setItem('testingRole', role);
            localStorage.setItem('testingRoleName', roleInfo.name);
            
            // Show confirmation
            showAlert(`Switched to ${roleInfo.name}`, 'success');
        }
        
        function testCurrentRoleAccess() {
            const roleInfo = roleData[currentRole];
            
            let message = `🧪 Testing ${roleInfo.name} Access:\n\n`;
            
            switch(currentRole) {
                case 'administrator':
                    message += '✅ Can view organizational MA numbers\n';
                    message += '✅ Can access all portals\n';
                    message += '✅ Can manage user roles\n';
                    message += '✅ Can view security logs\n';
                    break;
                case 'supervisor':
                    message += '❌ Cannot view organizational MA numbers\n';
                    message += '✅ Can view client MA numbers\n';
                    message += '✅ Can access billing reports\n';
                    message += '✅ Can manage authorizations\n';
                    break;
                case 'case_manager':
                    message += '❌ Cannot view organizational MA numbers\n';
                    message += '✅ Can view client MA numbers\n';
                    message += '✅ Can create treatment plans\n';
                    message += '✅ Can schedule sessions\n';
                    break;
                case 'direct_care':
                    message += '❌ Cannot view organizational MA numbers\n';
                    message += '✅ Can view client MA numbers\n';
                    message += '✅ Can document sessions\n';
                    message += '❌ Cannot edit client data\n';
                    break;
                case 'technician':
                    message += '❌ Cannot view organizational MA numbers\n';
                    message += '❌ Cannot view client MA numbers\n';
                    message += '✅ Can document sessions\n';
                    message += '❌ Cannot schedule sessions\n';
                    break;
            }
            
            alert(message);
        }
        
        function openPortalRouter() {
            // Pass current role to portal router
            localStorage.setItem('adminSelectedRole', currentRole);
            window.open('portal_router.php', '_blank');
        }
        
        function openEmployeePortal() {
            if (currentRole === 'direct_care' || currentRole === 'technician' || currentRole === 'administrator') {
                window.open('employee_portal.php', '_blank');
            } else {
                alert(`Access Denied: ${roleData[currentRole].name} cannot access Employee Portal`);
            }
        }
        
        function openSecurityDemo() {
            window.open('secure_clients.php', '_blank');
        }
        
        function setupDemoData() {
            if (confirm('Setup demo treatment plans and goals for testing?')) {
                fetch('treatment_plan_api.php?endpoint=create_demo_plans', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Demo data created successfully!', 'success');
                    } else {
                        showAlert('Error creating demo data: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    showAlert('Error: ' + error.message, 'error');
                });
            }
        }
        
        function viewSecurityLog() {
            if (currentRole === 'administrator') {
                alert('🔗 Opening Security Audit Log\n\nThis would show:\n• User access attempts\n• MA number access logs\n• Permission checks\n• Role changes\n• System security events');
            } else {
                alert('❌ Access Denied\n\nSecurity logs require Administrator access.\n\nCurrent role: ' + roleData[currentRole].name);
            }
        }
        
        function resetToAdmin() {
            switchRole('administrator');
            showAlert('Reset to Master Administrator', 'success');
        }
        
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                color: white;
                font-weight: 600;
                z-index: 1000;
                background: ${type === 'success' ? '#059669' : '#dc2626'};
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            `;
            alertDiv.textContent = message;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.style.opacity = '1';
                alertDiv.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                alertDiv.style.opacity = '0';
                alertDiv.style.transform = 'translateX(100%)';
                setTimeout(() => document.body.removeChild(alertDiv), 300);
            }, 3000);
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            switchRole('administrator');
        });
    </script>
</body>
</html> 