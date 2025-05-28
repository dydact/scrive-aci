<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Services - American Caregivers Inc</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #059669;
            --secondary-color: #2563eb;
            --accent-color: #f59e0b;
            --text-color: #1e293b;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --error-color: #ef4444;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background: var(--light-bg);
        }
        
        .header {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .back-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .form-header {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .form-header h1 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 2.5rem;
        }
        
        .form-header p {
            color: #64748b;
            font-size: 1.1rem;
        }
        
        .application-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .section-title {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .required {
            color: var(--error-color);
        }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-item input[type="checkbox"] {
            width: auto;
        }
        
        .submit-section {
            background: var(--light-bg);
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
        }
        
        .submit-btn {
            background: var(--primary-color);
            color: white;
            padding: 1rem 3rem;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .submit-btn:hover {
            background: #047857;
            transform: translateY(-2px);
        }
        
        .submit-btn:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
        }
        
        .success-message {
            background: var(--success-color);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }
        
        .error-message {
            background: var(--error-color);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }
        
        .info-box {
            background: #dbeafe;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        
        .info-box h3 {
            color: #1e40af;
            margin-bottom: 0.5rem;
        }
        
        .info-box p {
            color: #1e40af;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .application-form {
                padding: 1.5rem;
            }
            
            .form-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <a href="/" class="logo">🤝 American Caregivers Inc</a>
            <a href="/" class="back-link">← Back to Home</a>
        </nav>
    </header>

    <div class="container">
        <div class="form-header">
            <h1>📋 Service Application</h1>
            <p>Apply for autism waiver and developmental disability services. Our team will review your application and contact you within 2-3 business days.</p>
        </div>

        <div class="info-box">
            <h3>📞 Need Help with Your Application?</h3>
            <p><strong>Silver Spring Office:</strong> 301-408-0100</p>
            <p><strong>Columbia Office:</strong> 301-301-0123</p>
            <p><strong>Email:</strong> contact@acgcares.com</p>
            <p><strong>Office Hours:</strong> Monday-Friday 9:00 AM - 6:00 PM, Weekends 10:00 AM - 6:00 PM</p>
        </div>

        <div id="successMessage" class="success-message">
            ✅ Your application has been submitted successfully! We will contact you within 2-3 business days.
        </div>

        <div id="errorMessage" class="error-message">
            ❌ There was an error submitting your application. Please try again or contact us directly.
        </div>

        <form class="application-form" id="applicationForm" method="POST" action="process_application.php">
            <!-- Client Information -->
            <div class="form-section">
                <h2 class="section-title">👤 Client Information</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="clientFirstName">First Name <span class="required">*</span></label>
                        <input type="text" id="clientFirstName" name="client_first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="clientLastName">Last Name <span class="required">*</span></label>
                        <input type="text" id="clientLastName" name="client_last_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="clientDOB">Date of Birth <span class="required">*</span></label>
                        <input type="date" id="clientDOB" name="client_dob" required>
                    </div>
                    <div class="form-group">
                        <label for="clientGender">Gender</label>
                        <select id="clientGender" name="client_gender">
                            <option value="">Select Gender</option>
                            <option value="M">Male</option>
                            <option value="F">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="clientAddress">Address <span class="required">*</span></label>
                    <input type="text" id="clientAddress" name="client_address" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="clientCity">City <span class="required">*</span></label>
                        <input type="text" id="clientCity" name="client_city" required>
                    </div>
                    <div class="form-group">
                        <label for="clientZip">ZIP Code <span class="required">*</span></label>
                        <input type="text" id="clientZip" name="client_zip" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="clientPhone">Phone Number</label>
                    <input type="tel" id="clientPhone" name="client_phone">
                </div>
            </div>

            <!-- Parent/Guardian Information -->
            <div class="form-section">
                <h2 class="section-title">👨‍👩‍👧‍👦 Parent/Guardian Information</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="guardianFirstName">First Name <span class="required">*</span></label>
                        <input type="text" id="guardianFirstName" name="guardian_first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="guardianLastName">Last Name <span class="required">*</span></label>
                        <input type="text" id="guardianLastName" name="guardian_last_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="guardianEmail">Email Address <span class="required">*</span></label>
                        <input type="email" id="guardianEmail" name="guardian_email" required>
                    </div>
                    <div class="form-group">
                        <label for="guardianPhone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="guardianPhone" name="guardian_phone" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="relationship">Relationship to Client <span class="required">*</span></label>
                    <select id="relationship" name="relationship" required>
                        <option value="">Select Relationship</option>
                        <option value="Mother">Mother</option>
                        <option value="Father">Father</option>
                        <option value="Guardian">Legal Guardian</option>
                        <option value="Grandparent">Grandparent</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <!-- Medical Information -->
            <div class="form-section">
                <h2 class="section-title">🏥 Medical Information</h2>
                
                <div class="form-group">
                    <label for="primaryDiagnosis">Primary Diagnosis <span class="required">*</span></label>
                    <input type="text" id="primaryDiagnosis" name="primary_diagnosis" required placeholder="e.g., Autism Spectrum Disorder">
                </div>

                <div class="form-group">
                    <label for="secondaryDiagnosis">Secondary Diagnosis</label>
                    <input type="text" id="secondaryDiagnosis" name="secondary_diagnosis">
                </div>

                <div class="form-group">
                    <label for="medications">Current Medications</label>
                    <textarea id="medications" name="medications" placeholder="List all current medications and dosages"></textarea>
                </div>

                <div class="form-group">
                    <label for="allergies">Allergies</label>
                    <textarea id="allergies" name="allergies" placeholder="List any known allergies"></textarea>
                </div>

                <div class="form-group">
                    <label for="physicianName">Primary Care Physician</label>
                    <input type="text" id="physicianName" name="physician_name">
                </div>
            </div>

            <!-- Services Requested -->
            <div class="form-section">
                <h2 class="section-title">🎯 Services Requested</h2>
                
                <div class="form-group">
                    <label>Programs of Interest <span class="required">*</span></label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="aw" name="programs[]" value="Autism Waiver">
                            <label for="aw">Autism Waiver (AW)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="dda" name="programs[]" value="DDA Services">
                            <label for="dda">DDA Services</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="cfc" name="programs[]" value="Community First Choice">
                            <label for="cfc">Community First Choice</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="cs" name="programs[]" value="Community Services">
                            <label for="cs">Community Services</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Service Types of Interest</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="iiss" name="services[]" value="IISS">
                            <label for="iiss">Individual Intensive Support Services (IISS)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="ti" name="services[]" value="TI">
                            <label for="ti">Therapeutic Integration (TI)</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="respite" name="services[]" value="Respite">
                            <label for="respite">Respite Care</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="fc" name="services[]" value="FC">
                            <label for="fc">Family Consultation</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="pca" name="services[]" value="PCA">
                            <label for="pca">Personal Care</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="companion" name="services[]" value="Companion">
                            <label for="companion">Companion Services</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="preferredHours">Preferred Hours per Week</label>
                    <select id="preferredHours" name="preferred_hours">
                        <option value="">Select Hours</option>
                        <option value="1-10">1-10 hours</option>
                        <option value="11-20">11-20 hours</option>
                        <option value="21-30">21-30 hours</option>
                        <option value="31-40">31-40 hours</option>
                        <option value="40+">40+ hours</option>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="form-section">
                <h2 class="section-title">📝 Additional Information</h2>
                
                <div class="form-group">
                    <label for="schoolName">School/Educational Program</label>
                    <input type="text" id="schoolName" name="school_name">
                </div>

                <div class="form-group">
                    <label for="emergencyContact">Emergency Contact Name</label>
                    <input type="text" id="emergencyContact" name="emergency_contact_name">
                </div>

                <div class="form-group">
                    <label for="emergencyPhone">Emergency Contact Phone</label>
                    <input type="tel" id="emergencyPhone" name="emergency_contact_phone">
                </div>

                <div class="form-group">
                    <label for="specialNeeds">Special Needs or Considerations</label>
                    <textarea id="specialNeeds" name="special_needs" placeholder="Please describe any special needs, behavioral considerations, or other important information"></textarea>
                </div>

                <div class="form-group">
                    <label for="goals">Goals for Services</label>
                    <textarea id="goals" name="goals" placeholder="What are your goals for the services? What would you like to achieve?"></textarea>
                </div>

                <div class="form-group">
                    <label for="howHeard">How did you hear about us?</label>
                    <select id="howHeard" name="how_heard">
                        <option value="">Select Option</option>
                        <option value="Website">Website</option>
                        <option value="Referral">Referral from friend/family</option>
                        <option value="Doctor">Doctor/Healthcare provider</option>
                        <option value="Social Media">Social Media</option>
                        <option value="Search Engine">Search Engine</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="submit-section">
                <p style="margin-bottom: 1rem; color: #64748b;">
                    By submitting this application, you consent to American Caregivers Inc contacting you regarding services and scheduling an assessment.
                </p>
                <button type="submit" class="submit-btn" id="submitBtn">
                    📤 Submit Application
                </button>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('applicationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const successMsg = document.getElementById('successMessage');
            const errorMsg = document.getElementById('errorMessage');
            
            // Hide previous messages
            successMsg.style.display = 'none';
            errorMsg.style.display = 'none';
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = '📤 Submitting...';
            
            // Validate required checkboxes
            const programs = document.querySelectorAll('input[name="programs[]"]:checked');
            if (programs.length === 0) {
                errorMsg.textContent = '❌ Please select at least one program of interest.';
                errorMsg.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.textContent = '📤 Submit Application';
                return;
            }
            
            // Submit form via AJAX
            const formData = new FormData(this);
            
            fetch('process_application.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successMsg.style.display = 'block';
                    this.reset();
                    window.scrollTo(0, 0);
                } else {
                    errorMsg.textContent = '❌ ' + (data.message || 'There was an error submitting your application.');
                    errorMsg.style.display = 'block';
                }
            })
            .catch(error => {
                errorMsg.textContent = '❌ There was an error submitting your application. Please try again.';
                errorMsg.style.display = 'block';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = '📤 Submit Application';
            });
        });
    </script>
</body>
</html> 