<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help Center - ACI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; }
        .help-header { 
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
        }
        .help-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        .help-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .help-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="help-header">
        <div class="container text-center">
            <h1 class="display-4">Help Center</h1>
            <p class="lead">Get help with the ACI system</p>
        </div>
    </div>
    
    <div class="container">
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card help-card text-center p-4">
                    <div class="help-icon">📚</div>
                    <h3>User Guide</h3>
                    <p>Comprehensive documentation for all system features</p>
                    <a href="/help/guide" class="btn btn-primary">View Guide</a>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card help-card text-center p-4">
                    <div class="help-icon">🎓</div>
                    <h3>Training Center</h3>
                    <p>Interactive training modules and courses</p>
                    <a href="/training" class="btn btn-primary">Start Training</a>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card help-card text-center p-4">
                    <div class="help-icon">📞</div>
                    <h3>Contact Support</h3>
                    <p>Get help from our support team</p>
                    <a href="/contact" class="btn btn-primary">Contact Us</a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Quick Links</h2>
                        <div class="row">
                            <div class="col-md-6">
                                <h5>For Staff</h5>
                                <ul class="list-unstyled">
                                    <li><a href="/staff/dashboard">Staff Dashboard</a></li>
                                    <li><a href="/staff/clock">Time Clock</a></li>
                                    <li><a href="/staff/schedule">My Schedule</a></li>
                                    <li><a href="/staff/notes">Session Notes</a></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>For Managers</h5>
                                <ul class="list-unstyled">
                                    <li><a href="/supervisor">Supervisor Portal</a></li>
                                    <li><a href="/billing">Billing Dashboard</a></li>
                                    <li><a href="/reports">Reports</a></li>
                                    <li><a href="/admin">Admin Panel</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>