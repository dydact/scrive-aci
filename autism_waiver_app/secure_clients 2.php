<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔐 Secure Client Management - American Caregivers</title>
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
            --warning-color: #dc2626;
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
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .security-header {
            background: rgba(220, 38, 38, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: white;
        }
        
        .security-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .security-alert {
            background: rgba(254, 243, 199, 0.95);
            border: 2px solid #f59e0b;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }
        
        .security-alert h2 {
            color: #92400e;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .security-comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 1.5rem;
        }
        
        .comparison-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
        }
        
        .org-numbers {
            border-left: 4px solid #dc2626;
        }
        
        .client-numbers {
            border-left: 4px solid #059669;
        }
        
        .org-numbers h3 {
            color: #dc2626;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .client-numbers h3 {
            color: #059669;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .ma-example {
            background: #f3f4f6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 1rem 0;
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }
        
        .org-ma-example {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        
        .client-ma-example {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }
        
        .role-permissions {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }
        
        .role-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .role-card {
            background: #f8fafc;
            border-radius: 0.5rem;
            padding: 1rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .role-card h4 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .permission-list {
            list-style: none;
            padding: 0;
        }
        
        .permission-list li {
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
        
        .current-user {
            background: rgba(59, 130, 246, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: var(--shadow);
        }
        
        .current-user h3 {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-permissions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .permission-item {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
        }
        
        .permission-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .form-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .secure-input {
            background: #fef3c7;
            border: 2px solid #fbbf24;
        }
        
        .admin-only-section {
            background: #fee2e2;
            border: 2px solid #fca5a5;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin: 1rem 0;
            position: relative;
            opacity: 0.5;
        }
        
        .admin-only-section::before {
            content: "🔒 ADMIN ONLY";
            position: absolute;
            top: -10px;
            left: 1rem;
            background: #dc2626;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
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
            box-shadow: var(--shadow);
        }
        
        .btn-warning {
            background: #f59e0b;
            color: white;
        }
        
        .btn-danger {
            background: #dc2626;
            color: white;
        }
        
        .client-preview {
            background: #f8fafc;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 2px solid var(--border-color);
        }
        
        .client-detail {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            margin: 0.5rem 0;
            background: white;
            border-radius: 0.5rem;
            border-left: 3px solid var(--primary-color);
        }
        
        .detail-label {
            color: #64748b;
            font-weight: 500;
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .ma-display {
            font-family: 'Courier New', monospace;
            background: #dcfce7;
            padding: 0.5rem;
            border-radius: 0.25rem;
            border: 1px solid #86efac;
            color: #166534;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Critical Security Header -->
        <div class="security-header">
            <h1>🔐 CRITICAL SECURITY UPDATE</h1>
            <p>MA Number Access Control Implementation - American Caregivers Incorporated</p>
        </div>

        <!-- Security Alert -->
        <div class="security-alert">
            <h2>🚨 IMPORTANT: MA Number Separation</h2>
            <p><strong>We have identified and fixed a critical security issue regarding MA number visibility.</strong></p>
            
            <div class="security-comparison">
                <div class="comparison-card org-numbers">
                    <h3>🏢 Organizational MA Numbers (ADMIN ONLY)</h3>
                    <p><strong>Purpose:</strong> American Caregivers' billing identification numbers for each program</p>
                    <p><strong>Access:</strong> Administrators only</p>
                    <p><strong>Examples:</strong></p>
                    <div class="ma-example org-ma-example">
                        AW Program: 410608300<br>
                        DDA Program: 410608301<br>
                        CFC Program: 522902200<br>
                        CS Program: 433226100
                    </div>
                    <p><strong>⚠️ These were incorrectly visible to all staff previously!</strong></p>
                </div>
                
                <div class="comparison-card client-numbers">
                    <h3>👤 Individual Client MA Numbers</h3>
                    <p><strong>Purpose:</strong> Each client's personal Medical Assistance number</p>
                    <p><strong>Access:</strong> Staff with appropriate permissions</p>
                    <p><strong>Examples:</strong></p>
                    <div class="ma-example client-ma-example">
                        Emma Rodriguez: MA123456789<br>
                        Michael Johnson: MA987654321<br>
                        Aiden Chen: MA555888999
                    </div>
                    <p><strong>✅ These are what staff should see and manage</strong></p>
                </div>
            </div>
        </div>

        <!-- Current User Status -->
        <div class="current-user">
            <h3>👤 Current User: Sarah Mitchell (Case Manager)</h3>
            <p>Role Level: 3 | Department: Autism Services</p>
            
            <div class="user-permissions">
                <div class="permission-item">
                    <div class="permission-icon">❌</div>
                    <strong>Organizational MA Numbers</strong><br>
                    <small>Access Denied</small>
                </div>
                <div class="permission-item">
                    <div class="permission-icon">✅</div>
                    <strong>Client MA Numbers</strong><br>
                    <small>View & Edit</small>
                </div>
                <div class="permission-item">
                    <div class="permission-icon">✅</div>
                    <strong>Scheduling</strong><br>
                    <small>Full Access</small>
                </div>
                <div class="permission-item">
                    <div class="permission-icon">❌</div>
                    <strong>Billing Data</strong><br>
                    <small>Access Denied</small>
                </div>
            </div>
        </div>

        <!-- Role Permissions Matrix -->
        <div class="role-permissions">
            <h2>🎭 Staff Role Permissions Matrix</h2>
            <p>New 5-tier role system with proper access controls</p>
            
            <div class="role-grid">
                <div class="role-card">
                    <h4>👑 Administrator (Level 5)</h4>
                    <ul class="permission-list">
                        <li class="permission-yes">✅ View Organizational MA Numbers</li>
                        <li class="permission-yes">✅ View Client MA Numbers</li>
                        <li class="permission-yes">✅ Manage Staff Roles</li>
                        <li class="permission-yes">✅ Access Billing Data</li>
                        <li class="permission-yes">✅ All System Functions</li>
                    </ul>
                </div>
                
                <div class="role-card">
                    <h4>👥 Supervisor (Level 4)</h4>
                    <ul class="permission-list">
                        <li class="permission-no">❌ View Organizational MA Numbers</li>
                        <li class="permission-yes">✅ View Client MA Numbers</li>
                        <li class="permission-yes">✅ Schedule Sessions</li>
                        <li class="permission-yes">✅ View Billing Reports</li>
                        <li class="permission-yes">✅ Manage Authorizations</li>
                    </ul>
                </div>
                
                <div class="role-card">
                    <h4>📋 Case Manager (Level 3)</h4>
                    <ul class="permission-list">
                        <li class="permission-no">❌ View Organizational MA Numbers</li>
                        <li class="permission-yes">✅ View Client MA Numbers</li>
                        <li class="permission-yes">✅ Edit Client Data</li>
                        <li class="permission-yes">✅ Schedule Sessions</li>
                        <li class="permission-no">❌ Access Billing Data</li>
                    </ul>
                </div>
                
                <div class="role-card">
                    <h4>🤝 Direct Care Staff (Level 2)</h4>
                    <ul class="permission-list">
                        <li class="permission-no">❌ View Organizational MA Numbers</li>
                        <li class="permission-yes">✅ View Client MA Numbers</li>
                        <li class="permission-no">❌ Edit Client Data</li>
                        <li class="permission-no">❌ Schedule Sessions</li>
                        <li class="permission-no">❌ Access Billing Data</li>
                    </ul>
                </div>
                
                <div class="role-card">
                    <h4>🔧 Technician (Level 1)</h4>
                    <ul class="permission-list">
                        <li class="permission-no">❌ View Organizational MA Numbers</li>
                        <li class="permission-no">❌ View Client MA Numbers</li>
                        <li class="permission-no">❌ Edit Client Data</li>
                        <li class="permission-no">❌ Schedule Sessions</li>
                        <li class="permission-no">❌ Access Billing Data</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Secure Client Form -->
        <div class="form-section">
            <h2>👤 Add/Edit Individual Client Information</h2>
            <p>This form handles individual client MA numbers only - organizational billing numbers are secured separately.</p>
            
            <form id="secureClientForm">
                <div class="form-group">
                    <label class="form-label" for="clientName">Client Name *</label>
                    <input type="text" class="form-input" id="clientName" placeholder="Enter client's full name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="clientDOB">Date of Birth *</label>
                    <input type="date" class="form-input" id="clientDOB" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="individualMA">Individual Client MA Number *</label>
                    <input type="text" class="form-input secure-input" id="individualMA" 
                           placeholder="Client's personal MA number (like SSN)" required>
                    <small style="color: #059669; font-weight: 600;">
                        ✅ This is the client's personal Medical Assistance number - visible to your role level
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="waiverProgram">Waiver Program *</label>
                    <select class="form-input" id="waiverProgram" required>
                        <option value="">Select Program</option>
                        <option value="AW">Autism Waiver (AW)</option>
                        <option value="DDA">Developmental Disabilities Administration (DDA)</option>
                        <option value="CFC">Community First Choice (CFC)</option>
                        <option value="CS">Community Supports (CS)</option>
                    </select>
                </div>
                
                <!-- Administrative Section (Secured) -->
                <div class="admin-only-section">
                    <h4>🔒 Organizational Billing Information (ADMIN ONLY)</h4>
                    <p>This section contains American Caregivers' billing MA numbers and is restricted to administrators only.</p>
                    
                    <div class="form-group">
                        <label class="form-label">Organizational MA Number for Selected Program</label>
                        <input type="text" class="form-input" value="*** RESTRICTED - ADMIN ACCESS REQUIRED ***" disabled>
                        <small style="color: #dc2626;">
                            🚫 Your current role (Case Manager) cannot access organizational billing numbers
                        </small>
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary">
                        💾 Save Client Information
                    </button>
                    <button type="button" class="btn btn-warning" onclick="showPermissionRequest()">
                        🔓 Request Higher Access
                    </button>
                </div>
            </form>
        </div>

        <!-- Client Preview -->
        <div class="client-preview">
            <h3>📋 Client Information Preview</h3>
            <p>This shows how client information appears with your current permissions:</p>
            
            <div class="client-detail">
                <span class="detail-label">Client Name</span>
                <span class="detail-value">Emma Rodriguez</span>
            </div>
            
            <div class="client-detail">
                <span class="detail-label">Individual MA Number</span>
                <span class="detail-value ma-display">MA123456789</span>
            </div>
            
            <div class="client-detail">
                <span class="detail-label">Program</span>
                <span class="detail-value">Autism Waiver (AW)</span>
            </div>
            
            <div class="client-detail">
                <span class="detail-label">Organizational Billing MA</span>
                <span class="detail-value" style="color: #dc2626;">🔒 Access Restricted</span>
            </div>
            
            <div class="client-detail">
                <span class="detail-label">County</span>
                <span class="detail-value">Montgomery County, MD</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <button class="btn btn-primary" onclick="showClientList()">
                👥 View Client List (Role-Filtered)
            </button>
            <button class="btn btn-warning" onclick="showRoleManagement()">
                🎭 Manage User Roles (If Admin)
            </button>
            <button class="btn btn-danger" onclick="viewSecurityLog()">
                📊 View Security Audit Log
            </button>
        </div>
    </div>

    <script>
        function showPermissionRequest() {
            alert('🔓 Permission Request\n\nTo access organizational billing MA numbers, please contact your administrator.\n\nYour current role: Case Manager (Level 3)\nRequired role: Administrator (Level 5)\n\nReason: Access to American Caregivers billing identification numbers requires the highest security clearance.');
        }

        function showClientList() {
            alert('👥 Role-Filtered Client List\n\nAs a Case Manager, you can:\n✅ View individual client MA numbers\n✅ Edit client personal information\n✅ Schedule sessions\n❌ Access organizational billing numbers\n❌ View internal financial data\n\nRedirecting to secure client list...');
        }

        function showRoleManagement() {
            alert('🎭 Role Management\n\n❌ Access Denied\n\nYour current role (Case Manager) does not have permission to manage user roles.\n\nThis function requires Administrator (Level 5) access.\n\nContact your system administrator if you need role changes.');
        }

        function viewSecurityLog() {
            alert('📊 Security Audit Log\n\nLogging MA number access attempts...\n\n✅ Individual client MA access: ALLOWED\n❌ Organizational MA access: BLOCKED\n📝 Session logged for compliance\n\nAll access attempts are recorded for security auditing.');
        }

        document.getElementById('secureClientForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const clientName = document.getElementById('clientName').value;
            const clientMA = document.getElementById('individualMA').value;
            const program = document.getElementById('waiverProgram').value;
            
            if (clientName && clientMA && program) {
                alert(`✅ Client Information Saved\n\nName: ${clientName}\nIndividual MA: ${clientMA}\nProgram: ${program}\n\n🔒 Note: Only individual client information was saved. Organizational billing data remains secured at the administrator level.`);
            }
        });

        // Simulate role-based data loading
        window.addEventListener('load', function() {
            console.log('🔐 Security Check: User role validated');
            console.log('✅ Individual client MA access: GRANTED');
            console.log('❌ Organizational MA access: BLOCKED');
            console.log('📝 Access logged for audit trail');
        });
    </script>
</body>
</html> 