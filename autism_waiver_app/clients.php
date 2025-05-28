<?php

/**
 * Enhanced Client Management - Scrive AI-Powered Autism Waiver ERM
 * 
 * @package   Scrive
 * @author    American Caregivers Incorporated
 * @copyright Copyright (c) 2025 American Caregivers Incorporated
 * @license   MIT License
 */

// Include Scrive authentication and API
require_once 'auth.php';
require_once 'api.php';

// Initialize authentication
initScriveAuth();

$api = new OpenEMRAPI();
$error = null;
$success = null;
$clients = [];
$currentUser = null;
$respiteData = [];

try {
    $currentUser = getCurrentScriveUser();
    
    // Get search parameters
    $search = [
        'name' => $_GET['search_name'] ?? '',
        'dob' => $_GET['search_dob'] ?? '',
        'program' => $_GET['search_program'] ?? '',
        'status' => $_GET['search_status'] ?? 'active',
        'jurisdiction' => $_GET['search_jurisdiction'] ?? '',
        'case_coordinator' => $_GET['search_coordinator'] ?? ''
    ];
    
    // Check for success/error messages
    if (isset($_GET['success'])) {
        $success = $_GET['success'];
    }
    if (isset($_GET['error'])) {
        $error = $_GET['error'];
    }
    
    // Get clients with enhanced data
    $clients = $api->getEnhancedClients($search);
    
    // Get respite care data for fiscal year tracking (July 1 - June 30)
    $current_fiscal_year = getCurrentFiscalYear();
    $respiteData = getRespiteCareData($current_fiscal_year);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

/**
 * Get current fiscal year for respite care (July 1 - June 30)
 */
function getCurrentFiscalYear() {
    $now = new DateTime();
    $current_year = $now->format('Y');
    
    // If before July 1, fiscal year is previous year
    if ($now->format('n') < 7) {
        $start_year = $current_year - 1;
        $end_year = $current_year;
    } else {
        $start_year = $current_year;
        $end_year = $current_year + 1;
    }
    
    return [
        'start' => $start_year . '-07-01',
        'end' => $end_year . '-06-30',
        'label' => "FY {$start_year}-{$end_year}"
    ];
}

/**
 * Get respite care hours used for fiscal year
 */
function getRespiteCareData($fiscal_year) {
    try {
        // Check if autism tables exist
        $table_check = sqlQuery("SHOW TABLES LIKE 'autism_session'");
        if (!$table_check) {
            return [];
        }
        
        $sql = "SELECT 
                    s.patient_id as client_id,
                    p.fname,
                    p.lname,
                    SUM(s.duration_minutes) / 60 as total_hours,
                    COUNT(s.session_id) as session_count,
                    a.approved_units as authorized_hours
                FROM autism_session s
                JOIN patient_data p ON s.patient_id = p.pid
                LEFT JOIN autism_client_authorizations a ON s.patient_id = a.client_id 
                WHERE s.service_type = 'Respite' 
                    AND s.date BETWEEN ? AND ?
                GROUP BY s.patient_id";
        
        $result = sqlStatement($sql, [
            $fiscal_year['start'],
            $fiscal_year['end']
        ]);
        
        $data = [];
        while ($row = sqlFetchArray($result)) {
            $data[$row['client_id']] = $row;
        }
        
        return $data;
    } catch (Exception $e) {
        return [];
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management - Scrive</title>
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
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
        }
        
        .controls {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-box {
            flex: 1;
            min-width: 300px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
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
        
        .btn-secondary {
            background: #f1f5f9;
            color: var(--text-color);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .filter-select {
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            background: white;
            font-size: 0.95rem;
            min-width: 150px;
        }
        
        .clients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .client-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        
        .client-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .client-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .client-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        .client-info h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }
        
        .client-info .client-id {
            color: #64748b;
            font-size: 0.9rem;
            font-family: 'Courier New', monospace;
        }
        
        .client-details {
            display: grid;
            gap: 0.75rem;
            margin: 1rem 0;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            background: #f8fafc;
            border-radius: 0.5rem;
            border-left: 3px solid var(--primary-color);
        }
        
        .detail-label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .ma-number {
            font-family: 'Courier New', monospace;
            background: #fef3c7;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            border: 1px solid #fbbf24;
        }
        
        .client-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .btn-edit {
            background: #0f766e;
            color: white;
        }
        
        .btn-edit:hover {
            background: #0d9488;
        }
        
        .btn-view {
            background: #1e40af;
            color: white;
        }
        
        .btn-view:hover {
            background: #1d4ed8;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            margin-top: 2rem;
        }
        
        .empty-icon {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f4f6;
            border-radius: 50%;
            border-top: 2px solid var(--primary-color);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
            animation: fadeIn 0.2s ease-out;
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
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
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
        
        .form-select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            background: white;
            font-size: 1rem;
        }
        
        .security-notice {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .security-notice h4 {
            color: #92400e;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .security-notice p {
            color: #78350f;
            font-size: 0.9rem;
        }
        
        .admin-only {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            border-radius: 0.5rem;
            padding: 0.75rem;
            color: #991b1b;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        .role-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            background: #e0f2fe;
            border: 1px solid #0891b2;
            border-radius: 0.5rem;
            font-size: 0.8rem;
            font-weight: 600;
            color: #0e7490;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>👥 Client Management</h1>
            <p class="subtitle">Manage individual client records and Medical Assistance information</p>
            
            <!-- Security Notice -->
            <div class="security-notice">
                <h4>🔒 Security Notice: Individual vs Organizational MA Numbers</h4>
                <p><strong>Individual Client MA Numbers:</strong> Each client has their own personal Medical Assistance number (like a Social Security number) that you can view and edit here.</p>
                <p><strong>Organizational Billing MA Numbers:</strong> American Caregivers' billing identification numbers for each program are secured and visible only to administrators.</p>
            </div>
            
            <!-- Role indicator would be populated by PHP -->
            <div class="role-indicator">
                🎭 Current Role: Case Manager (Level 3) - Can view client MA numbers
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <div class="search-box">
                <div class="search-icon">🔍</div>
                <input type="text" id="searchInput" placeholder="Search clients by name, MA number, or program...">
            </div>
            
            <select class="filter-select" id="programFilter">
                <option value="">All Programs</option>
                <option value="AW">Autism Waiver (AW)</option>
                <option value="DDA">DDA</option>
                <option value="CFC">CFC</option>
                <option value="CS">Community Supports (CS)</option>
            </select>
            
            <select class="filter-select" id="statusFilter">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="pending">Pending</option>
            </select>
            
            <button class="btn btn-primary" onclick="openAddClientModal()">
                ➕ Add New Client
            </button>
        </div>

        <!-- Clients Grid -->
        <div class="clients-grid" id="clientsGrid">
            <!-- Client cards will be populated by JavaScript -->
        </div>
        
        <!-- Empty State -->
        <div class="empty-state" id="emptyState" style="display: none;">
            <div class="empty-icon">👥</div>
            <h3>No clients found</h3>
            <p>Start by adding your first client or adjust your search filters.</p>
        </div>
    </div>

    <!-- Add/Edit Client Modal -->
    <div class="modal" id="clientModal">
        <div class="modal-content">
            <h2 id="modalTitle">Add New Client</h2>
            
            <form id="clientForm">
                <input type="hidden" id="clientId" name="client_id">
                
                <div class="form-group">
                    <label class="form-label" for="firstName">First Name *</label>
                    <input type="text" class="form-input" id="firstName" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="lastName">Last Name *</label>
                    <input type="text" class="form-input" id="lastName" name="last_name" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="dateOfBirth">Date of Birth *</label>
                    <input type="date" class="form-input" id="dateOfBirth" name="date_of_birth" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="clientMANumber">Individual Client MA Number *</label>
                    <input type="text" class="form-input" id="clientMANumber" name="ma_number" 
                           placeholder="Client's personal MA number (like SSN)" required>
                    <small style="color: #64748b; font-size: 0.85rem;">
                        This is the client's personal Medical Assistance number, not the organizational billing number.
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="program">Waiver Program *</label>
                    <select class="form-select" id="program" name="program" required>
                        <option value="">Select Program</option>
                        <option value="AW">Autism Waiver (AW)</option>
                        <option value="DDA">Developmental Disabilities Administration (DDA)</option>
                        <option value="CFC">Community First Choice (CFC)</option>
                        <option value="CS">Community Supports (CS)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="county">MD County *</label>
                    <select class="form-select" id="county" name="county" required>
                        <option value="">Select County</option>
                        <option value="Allegany">Allegany</option>
                        <option value="Anne Arundel">Anne Arundel</option>
                        <option value="Baltimore City">Baltimore City</option>
                        <option value="Baltimore County">Baltimore County</option>
                        <option value="Calvert">Calvert</option>
                        <option value="Caroline">Caroline</option>
                        <option value="Carroll">Carroll</option>
                        <option value="Cecil">Cecil</option>
                        <option value="Charles">Charles</option>
                        <option value="Dorchester">Dorchester</option>
                        <option value="Frederick">Frederick</option>
                        <option value="Garrett">Garrett</option>
                        <option value="Harford">Harford</option>
                        <option value="Howard">Howard</option>
                        <option value="Kent">Kent</option>
                        <option value="Montgomery">Montgomery</option>
                        <option value="Prince George's">Prince George's</option>
                        <option value="Queen Anne's">Queen Anne's</option>
                        <option value="Somerset">Somerset</option>
                        <option value="St. Mary's">St. Mary's</option>
                        <option value="Talbot">Talbot</option>
                        <option value="Washington">Washington</option>
                        <option value="Wicomico">Wicomico</option>
                        <option value="Worcester">Worcester</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="parentGuardian">Parent/Guardian Name</label>
                    <input type="text" class="form-input" id="parentGuardian" name="parent_guardian">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="emergencyContact">Emergency Contact</label>
                    <input type="text" class="form-input" id="emergencyContact" name="emergency_contact">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="emergencyPhone">Emergency Phone</label>
                    <input type="tel" class="form-input" id="emergencyPhone" name="emergency_phone">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="address">Address</label>
                    <input type="text" class="form-input" id="address" name="address">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="school">School</label>
                    <input type="text" class="form-input" id="school" name="school">
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeClientModal()">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <span class="loading-spinner" id="saveSpinner" style="display: none;"></span>
                        Save Client
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Sample data with individual client MA numbers (not organizational billing numbers)
        let clients = [
            {
                id: 1,
                first_name: 'Emma',
                last_name: 'Rodriguez',
                ma_number: 'MA123456789',  // Individual client's MA number
                program: 'AW',
                county: 'Montgomery',
                date_of_birth: '2015-08-12',
                parent_guardian: 'Maria Rodriguez',
                status: 'active',
                emergency_contact: 'Carlos Rodriguez',
                emergency_phone: '(301) 555-0123',
                address: '123 Main St, Rockville, MD 20850',
                school: 'Rockville Elementary'
            },
            {
                id: 2,
                first_name: 'Michael',
                last_name: 'Johnson',
                ma_number: 'MA987654321',  // Individual client's MA number
                program: 'DDA',
                county: 'Baltimore County',
                date_of_birth: '2012-03-20',
                parent_guardian: 'Sarah Johnson',
                status: 'active',
                emergency_contact: 'David Johnson',
                emergency_phone: '(410) 555-0456',
                address: '456 Oak Ave, Towson, MD 21204',
                school: 'Towson Middle School'
            },
            {
                id: 3,
                first_name: 'Aiden',
                last_name: 'Chen',
                ma_number: 'MA555888999',  // Individual client's MA number
                program: 'CFC',
                county: 'Howard',
                date_of_birth: '2018-11-05',
                parent_guardian: 'Lisa Chen',
                status: 'pending',
                emergency_contact: 'James Chen',
                emergency_phone: '(443) 555-0789',
                address: '789 Elm St, Columbia, MD 21045',
                school: 'Columbia Elementary'
            }
        ];

        function formatAge(birthDate) {
            const today = new Date();
            const birth = new Date(birthDate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            
            return age;
        }

        function getStatusBadge(status) {
            const badges = {
                'active': '<span class="status-badge status-active">✅ Active</span>',
                'inactive': '<span class="status-badge status-inactive">❌ Inactive</span>',
                'pending': '<span class="status-badge status-pending">⏳ Pending</span>'
            };
            return badges[status] || status;
        }

        function getProgramName(abbreviation) {
            const programs = {
                'AW': 'Autism Waiver',
                'DDA': 'DDA',
                'CFC': 'Community First Choice',
                'CS': 'Community Supports'
            };
            return programs[abbreviation] || abbreviation;
        }

        function renderClients(clientsToRender = clients) {
            const grid = document.getElementById('clientsGrid');
            const emptyState = document.getElementById('emptyState');
            
            if (clientsToRender.length === 0) {
                grid.style.display = 'none';
                emptyState.style.display = 'block';
                return;
            }
            
            grid.style.display = 'grid';
            emptyState.style.display = 'none';
            
            grid.innerHTML = clientsToRender.map(client => `
                <div class="client-card">
                    <div class="client-header">
                        <div class="client-avatar">
                            ${client.first_name.charAt(0)}${client.last_name.charAt(0)}
                        </div>
                        <div class="client-info">
                            <h3>${client.first_name} ${client.last_name}</h3>
                            <div class="client-id">Client ID: #${client.id.toString().padStart(4, '0')}</div>
                        </div>
                    </div>
                    
                    <div class="client-details">
                        <div class="detail-item">
                            <span class="detail-label">Age</span>
                            <span class="detail-value">${formatAge(client.date_of_birth)} years old</span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">Individual MA Number</span>
                            <span class="detail-value ma-number">${client.ma_number}</span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">Program</span>
                            <span class="detail-value">${getProgramName(client.program)}</span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">County</span>
                            <span class="detail-value">${client.county}</span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">Parent/Guardian</span>
                            <span class="detail-value">${client.parent_guardian || 'Not specified'}</span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-value">${getStatusBadge(client.status)}</span>
                        </div>
                    </div>
                    
                    <div class="client-actions">
                        <button class="btn btn-view btn-small" onclick="viewClient(${client.id})">
                            👁️ View Details
                        </button>
                        <button class="btn btn-edit btn-small" onclick="editClient(${client.id})">
                            ✏️ Edit
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function filterClients() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const programFilter = document.getElementById('programFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            
            const filtered = clients.filter(client => {
                const matchesSearch = !searchTerm || 
                    client.first_name.toLowerCase().includes(searchTerm) ||
                    client.last_name.toLowerCase().includes(searchTerm) ||
                    client.ma_number.toLowerCase().includes(searchTerm) ||
                    getProgramName(client.program).toLowerCase().includes(searchTerm);
                
                const matchesProgram = !programFilter || client.program === programFilter;
                const matchesStatus = !statusFilter || client.status === statusFilter;
                
                return matchesSearch && matchesProgram && matchesStatus;
            });
            
            renderClients(filtered);
        }

        function openAddClientModal() {
            document.getElementById('modalTitle').textContent = 'Add New Client';
            document.getElementById('clientForm').reset();
            document.getElementById('clientId').value = '';
            document.getElementById('clientModal').classList.add('show');
        }

        function editClient(id) {
            const client = clients.find(c => c.id === id);
            if (!client) return;
            
            document.getElementById('modalTitle').textContent = 'Edit Client';
            document.getElementById('clientId').value = client.id;
            document.getElementById('firstName').value = client.first_name;
            document.getElementById('lastName').value = client.last_name;
            document.getElementById('dateOfBirth').value = client.date_of_birth;
            document.getElementById('clientMANumber').value = client.ma_number;
            document.getElementById('program').value = client.program;
            document.getElementById('county').value = client.county;
            document.getElementById('parentGuardian').value = client.parent_guardian || '';
            document.getElementById('emergencyContact').value = client.emergency_contact || '';
            document.getElementById('emergencyPhone').value = client.emergency_phone || '';
            document.getElementById('address').value = client.address || '';
            document.getElementById('school').value = client.school || '';
            
            document.getElementById('clientModal').classList.add('show');
        }

        function viewClient(id) {
            const client = clients.find(c => c.id === id);
            if (!client) return;
            
            alert(`Client Details:\n\nName: ${client.first_name} ${client.last_name}\nAge: ${formatAge(client.date_of_birth)} years old\nIndividual MA Number: ${client.ma_number}\nProgram: ${getProgramName(client.program)}\nCounty: ${client.county}\nParent/Guardian: ${client.parent_guardian || 'Not specified'}\nStatus: ${client.status}`);
        }

        function closeClientModal() {
            document.getElementById('clientModal').classList.remove('show');
        }

        // Event listeners
        document.getElementById('searchInput').addEventListener('input', filterClients);
        document.getElementById('programFilter').addEventListener('change', filterClients);
        document.getElementById('statusFilter').addEventListener('change', filterClients);

        document.getElementById('clientForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const spinner = document.getElementById('saveSpinner');
            spinner.style.display = 'inline-block';
            
            // Simulate API call
            setTimeout(() => {
                const formData = new FormData(this);
                const clientData = Object.fromEntries(formData.entries());
                
                if (clientData.client_id) {
                    // Update existing client
                    const index = clients.findIndex(c => c.id == clientData.client_id);
                    if (index !== -1) {
                        clients[index] = { ...clients[index], ...clientData, id: parseInt(clientData.client_id) };
                    }
                } else {
                    // Add new client
                    const newId = Math.max(...clients.map(c => c.id)) + 1;
                    clients.push({ 
                        ...clientData, 
                        id: newId, 
                        status: 'active'
                    });
                }
                
                spinner.style.display = 'none';
                closeClientModal();
                renderClients();
                filterClients();
            }, 1000);
        });

        // Close modal when clicking outside
        document.getElementById('clientModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeClientModal();
            }
        });

        // Initial render
        renderClients();
    </script>
</body>
</html> 