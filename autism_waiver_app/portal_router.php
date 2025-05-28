<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎭 Portal Router - American Caregivers</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #2563eb;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--text-color);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .portal-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            padding: 3rem;
            max-width: 1000px;
            width: 100%;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .header .subtitle {
            color: #64748b;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }
        
        .user-selection {
            background: var(--secondary-color);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 2px solid var(--border-color);
        }
        
        .user-selection h3 {
            color: var(--text-color);
            margin-bottom: 1rem;
            font-size: 1.25rem;
        }
        
        .role-select {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            font-size: 1rem;
            background: white;
            font-weight: 600;
        }
        
        .role-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .portal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .portal-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .portal-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .portal-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        .portal-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .portal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.75rem;
        }
        
        .portal-description {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .portal-features {
            list-style: none;
            padding: 0;
        }
        
        .portal-features li {
            color: #059669;
            font-size: 0.9rem;
            padding: 0.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .portal-features li::before {
            content: '✅';
            font-size: 0.8rem;
        }
        
        .role-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--primary-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .employee-portal {
            border-left-color: #059669;
        }
        
        .admin-portal {
            border-left-color: #dc2626;
        }
        
        .supervisor-portal {
            border-left-color: #f59e0b;
        }
        
        .case-manager-portal {
            border-left-color: #2563eb;
        }
        
        .disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .coming-soon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .integration-note {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-top: 2rem;
            text-align: center;
        }
        
        .integration-note h4 {
            color: #92400e;
            margin-bottom: 0.75rem;
            font-size: 1.1rem;
        }
        
        .integration-note p {
            color: #78350f;
            font-size: 0.95rem;
        }
        
        .quick-access {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
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
            background: #1d4ed8;
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
        
        .btn-success:hover {
            background: #047857;
            transform: translateY(-1px);
        }
        
        @media (max-width: 768px) {
            .portal-container {
                padding: 2rem;
            }
            
            .portal-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="portal-container">
        <div class="header">
            <h1>🎭 Choose Your Portal</h1>
            <p class="subtitle">Role-based interfaces optimized for your daily workflow</p>
        </div>
        
        <!-- User Role Selection -->
        <div class="user-selection">
            <h3>👤 Select Your Role</h3>
            <select class="role-select" id="roleSelect" onchange="updatePortalView()">
                <option value="">Choose your role...</option>
                <option value="administrator">👑 Administrator (Level 5)</option>
                <option value="supervisor">👥 Supervisor (Level 4)</option>
                <option value="case_manager">📋 Case Manager (Level 3)</option>
                <option value="direct_care">🤝 Direct Care Staff (Level 2)</option>
                <option value="technician">🔧 Technician (Level 1)</option>
            </select>
        </div>
        
        <!-- Portal Grid -->
        <div class="portal-grid">
            <!-- Employee Portal (Direct Care & Technicians) -->
            <div class="portal-card employee-portal" id="employeePortal" onclick="openEmployeePortal()">
                <div class="role-badge">Levels 1-2</div>
                <div class="portal-icon">🤝</div>
                <h3 class="portal-title">Employee Portal</h3>
                <p class="portal-description">
                    Streamlined interface for direct care staff and technicians focused on efficient session documentation and time tracking.
                </p>
                <ul class="portal-features">
                    <li>Quick session note entry</li>
                    <li>Auto-populated treatment goals</li>
                    <li>Time clock & payroll tracking</li>
                    <li>Client schedule management</li>
                    <li>Progress documentation</li>
                    <li>QuickBooks integration ready</li>
                </ul>
            </div>
            
            <!-- Case Manager Portal -->
            <div class="portal-card case-manager-portal" id="caseManagerPortal" onclick="openCaseManagerPortal()">
                <div class="role-badge">Level 3</div>
                <div class="portal-icon">📋</div>
                <h3 class="portal-title">Case Manager Portal</h3>
                <p class="portal-description">
                    Comprehensive case management with treatment planning, client coordination, and scheduling capabilities.
                </p>
                <ul class="portal-features">
                    <li>Treatment plan development</li>
                    <li>Client enrollment management</li>
                    <li>Session scheduling</li>
                    <li>Progress monitoring</li>
                    <li>Goal tracking & reporting</li>
                    <li>Family communication</li>
                </ul>
            </div>
            
            <!-- Supervisor Portal -->
            <div class="portal-card supervisor-portal" id="supervisorPortal" onclick="openSupervisorPortal()">
                <div class="role-badge">Level 4</div>
                <div class="portal-icon">👥</div>
                <h3 class="portal-title">Supervisor Portal</h3>
                <p class="portal-description">
                    Oversight and management tools for supervising staff, reviewing documentation, and managing authorizations.
                </p>
                <ul class="portal-features">
                    <li>Staff performance oversight</li>
                    <li>Authorization management</li>
                    <li>Documentation review</li>
                    <li>Billing reports access</li>
                    <li>Quality assurance</li>
                    <li>Training coordination</li>
                </ul>
                <div class="coming-soon">Available Soon</div>
            </div>
            
            <!-- Administrator Portal -->
            <div class="portal-card admin-portal" id="adminPortal" onclick="openAdminPortal()">
                <div class="role-badge">Level 5</div>
                <div class="portal-icon">👑</div>
                <h3 class="portal-title">Administrator Portal</h3>
                <p class="portal-description">
                    Full system administration including organizational billing management, user roles, and system configuration.
                </p>
                <ul class="portal-features">
                    <li>Role & permission management</li>
                    <li>Organizational MA access</li>
                    <li>System configuration</li>
                    <li>Security audit logs</li>
                    <li>Financial oversight</li>
                    <li>Compliance monitoring</li>
                </ul>
                <div class="coming-soon">Available Soon</div>
            </div>
        </div>
        
        <!-- Integration Notice -->
        <div class="integration-note">
            <h4>🔗 Future Integration: QuickBooks & Intuit Workforce</h4>
            <p>
                The employee portal will seamlessly integrate with QuickBooks for payroll and Intuit Workforce for 
                time tracking, paystub access, and direct deposit management. This will provide a unified experience 
                for staff to manage their work documentation and payroll information.
            </p>
        </div>
        
        <!-- Quick Access Buttons -->
        <div class="quick-access">
            <a href="secure_clients.php" class="btn btn-secondary">
                🔐 Security Demo
            </a>
            <a href="clients.php" class="btn btn-secondary">
                👥 Client Management
            </a>
            <button class="btn btn-primary" onclick="initializeTreatmentPlans()">
                🎯 Setup Demo Data
            </button>
        </div>
    </div>

    <script>
        // Portal access by role
        const roleAccess = {
            'administrator': ['adminPortal', 'supervisorPortal', 'caseManagerPortal', 'employeePortal'],
            'supervisor': ['supervisorPortal', 'caseManagerPortal', 'employeePortal'],
            'case_manager': ['caseManagerPortal', 'employeePortal'],
            'direct_care': ['employeePortal'],
            'technician': ['employeePortal']
        };
        
        function updatePortalView() {
            const selectedRole = document.getElementById('roleSelect').value;
            const allPortals = ['adminPortal', 'supervisorPortal', 'caseManagerPortal', 'employeePortal'];
            
            // Reset all portals
            allPortals.forEach(portalId => {
                const portal = document.getElementById(portalId);
                portal.classList.remove('disabled');
                const comingSoon = portal.querySelector('.coming-soon');
                if (comingSoon) {
                    comingSoon.style.display = 'block';
                }
            });
            
            if (selectedRole && roleAccess[selectedRole]) {
                const accessiblePortals = roleAccess[selectedRole];
                
                allPortals.forEach(portalId => {
                    const portal = document.getElementById(portalId);
                    
                    if (!accessiblePortals.includes(portalId)) {
                        portal.classList.add('disabled');
                    } else {
                        portal.classList.remove('disabled');
                        
                        // Enable employee portal (it's ready)
                        if (portalId === 'employeePortal') {
                            const comingSoon = portal.querySelector('.coming-soon');
                            if (comingSoon) {
                                comingSoon.style.display = 'none';
                            }
                        }
                        
                        // Enable case manager portal (it's ready)
                        if (portalId === 'caseManagerPortal') {
                            const comingSoon = portal.querySelector('.coming-soon');
                            if (comingSoon) {
                                comingSoon.style.display = 'none';
                            }
                        }
                    }
                });
                
                // Show role-specific message
                showRoleMessage(selectedRole);
            }
        }
        
        function showRoleMessage(role) {
            const messages = {
                'administrator': 'Full system access - All portals available',
                'supervisor': 'Management access - Supervisor, Case Manager, and Employee portals',
                'case_manager': 'Care coordination access - Case Manager and Employee portals',
                'direct_care': 'Direct care access - Employee portal optimized for session documentation',
                'technician': 'Basic access - Employee portal for session notes only'
            };
            
            console.log(`Role selected: ${role} - ${messages[role]}`);
        }
        
        function openEmployeePortal() {
            const selectedRole = document.getElementById('roleSelect').value;
            
            if (!selectedRole) {
                alert('Please select your role first');
                return;
            }
            
            if (!roleAccess[selectedRole].includes('employeePortal')) {
                alert('Access denied: Your role does not have permission to access the Employee Portal');
                return;
            }
            
            // Open employee portal
            window.location.href = 'employee_portal.php';
        }
        
        function openCaseManagerPortal() {
            const selectedRole = document.getElementById('roleSelect').value;
            
            if (!selectedRole) {
                alert('Please select your role first');
                return;
            }
            
            if (!roleAccess[selectedRole].includes('caseManagerPortal')) {
                alert('Access denied: Your role does not have permission to access the Case Manager Portal');
                return;
            }
            
            // Open case manager portal
            window.location.href = 'case_manager_portal.php';
        }
        
        function openSupervisorPortal() {
            alert('👥 Supervisor Portal\n\nComing Soon!\n\nFeatures in development:\n• Staff oversight dashboard\n• Quality assurance tools\n• Performance metrics\n• Documentation review system\n• Training management\n\nExpected release: Q3 2025');
        }
        
        function openAdminPortal() {
            alert('👑 Administrator Portal\n\nComing Soon!\n\nFeatures in development:\n• User role management\n• System configuration\n• Security audit dashboard\n• Organizational MA management\n• Compliance reporting\n\nExpected release: Q3 2025');
        }
        
        function initializeTreatmentPlans() {
            const confirmSetup = confirm('🎯 Setup Demo Treatment Plans\n\nThis will create demo treatment plans and goals for Emma Rodriguez and Michael Chen.\n\nThese plans will enable auto-population in session notes.\n\nProceed?');
            
            if (confirmSetup) {
                fetch('treatment_plan_api.php?endpoint=create_demo_plans', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`✅ Demo Data Created!\n\n${data.message}\n\nPlans created:\n• ${data.plans_created.join('\n• ')}\n\nYou can now test the auto-population feature in the Employee Portal!`);
                    } else {
                        alert('❌ Error creating demo data: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('❌ Error setting up demo data. Check console for details.');
                });
            }
        }
        
        // Initialize view on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-select direct care staff role for demo
            const roleSelect = document.getElementById('roleSelect');
            roleSelect.value = 'direct_care';
            updatePortalView();
        });
    </script>
</body>
</html> 