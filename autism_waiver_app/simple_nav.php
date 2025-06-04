<?php
// Simple navigation for autism waiver app
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/autism_waiver_app/">
            <img src="/public/images/aci-logo.png" alt="ACI" height="30" class="d-inline-block align-top me-2">
            Autism Waiver App
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" 
                       href="/autism_waiver_app/">Dashboard</a>
                </li>
                
                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'case_manager', 'staff'])): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'clients.php' ? 'active' : ''; ?>" 
                       href="/autism_waiver_app/clients.php">Clients</a>
                </li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'staff'): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'schedule_manager.php' ? 'active' : ''; ?>" 
                       href="/autism_waiver_app/schedule_manager.php">My Schedule</a>
                </li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'billing_specialist'])): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo $current_dir == 'billing' ? 'active' : ''; ?>" 
                       href="#" id="billingDropdown" role="button" data-bs-toggle="dropdown">
                        Billing
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/autism_waiver_app/billing_dashboard.php">Billing Dashboard</a></li>
                        <li><a class="dropdown-item" href="/autism_waiver_app/billing/claim_management.php">Claim Management</a></li>
                        <li><a class="dropdown-item" href="/autism_waiver_app/billing_integration.php">Session Billing</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/autism_waiver_app/reports.php">Reports</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                        Admin
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/autism_waiver_app/admin_role_switcher.php">Role Switcher</a></li>
                        <li><a class="dropdown-item" href="/autism_waiver_app/service_types.php">Service Types</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/autism_waiver_app/setup.php">System Setup</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> 
                        <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#" onclick="alert('Profile settings coming soon')">
                            <i class="bi bi-gear"></i> Settings
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/autism_waiver_app/logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>