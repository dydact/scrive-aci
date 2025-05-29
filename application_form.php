<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employment Application - American Caregivers Inc</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --aci-blue: #1e40af;
            --aci-red: #dc2626;
            --aci-green: #16a34a;
            --medical-blue: #3b82f6;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background: #f8fafc;
        }
        
        .header {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--aci-blue);
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-header h1 {
            color: var(--aci-blue);
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: #64748b;
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section h3 {
            color: var(--aci-blue);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
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
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--aci-blue);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        .submit-btn {
            background: var(--aci-blue);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        
        .submit-btn:hover {
            background: #1e3a8a;
            transform: translateY(-1px);
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 2rem;
            color: var(--aci-blue);
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .container {
                margin: 1rem;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <span class="logo-text">
                <span style="color: var(--aci-blue);">A</span><span style="color: var(--aci-red);">C</span><span style="color: var(--aci-green);">I</span> American Caregivers, Inc.
            </span>
        </div>
    </header>
    
    <div class="container">
        <a href="/" class="back-link">← Back to Website</a>
        
        <div class="form-header">
            <h1>Employment Application</h1>
            <p>Join our team of dedicated caregivers making a difference in the lives of individuals with autism and developmental disabilities.</p>
        </div>
        
        <form action="process_application.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="application_type" value="employment">
            
            <div class="form-section">
                <h3>Personal Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                </div>
                
                <div class="form-group full-width">
                    <label for="address">Address *</label>
                    <input type="text" id="address" name="address" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City *</label>
                        <input type="text" id="city" name="city" required>
                    </div>
                    <div class="form-group">
                        <label for="state">State *</label>
                        <select id="state" name="state" required>
                            <option value="">Select State</option>
                            <option value="MD" selected>Maryland</option>
                            <option value="DC">Washington DC</option>
                            <option value="VA">Virginia</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="zip">ZIP Code *</label>
                        <input type="text" id="zip" name="zip" required>
                    </div>
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth *</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Position Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="position_applied">Position Applied For *</label>
                        <select id="position_applied" name="position_applied" required>
                            <option value="">Select Position</option>
                            <option value="Direct Support Professional">Direct Support Professional (DSP)</option>
                            <option value="Autism Technician">Autism Technician</option>
                            <option value="Personal Care Assistant">Personal Care Assistant (PCA)</option>
                            <option value="Certified Nursing Assistant">Certified Nursing Assistant (CNA)</option>
                            <option value="Certified Medication Technician">Certified Medication Technician (CMT)</option>
                            <option value="Case Manager">Case Manager</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Administrative">Administrative</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="availability">Availability *</label>
                        <select id="availability" name="availability" required>
                            <option value="">Select Availability</option>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Per Diem">Per Diem</option>
                            <option value="Weekends Only">Weekends Only</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="desired_salary">Desired Salary Range</label>
                    <select id="desired_salary" name="desired_salary">
                        <option value="">Select Range</option>
                        <option value="$15-18/hour">$15-18/hour</option>
                        <option value="$18-22/hour">$18-22/hour</option>
                        <option value="$22-26/hour">$22-26/hour</option>
                        <option value="$26-30/hour">$26-30/hour</option>
                        <option value="$30+/hour">$30+/hour</option>
                        <option value="Salary">Salary Position</option>
                    </select>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Experience & Qualifications</h3>
                <div class="form-group">
                    <label for="education">Highest Level of Education *</label>
                    <select id="education" name="education" required>
                        <option value="">Select Education Level</option>
                        <option value="High School Diploma/GED">High School Diploma/GED</option>
                        <option value="Some College">Some College</option>
                        <option value="Associate Degree">Associate Degree</option>
                        <option value="Bachelor's Degree">Bachelor's Degree</option>
                        <option value="Master's Degree">Master's Degree</option>
                        <option value="Doctoral Degree">Doctoral Degree</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="experience">Years of Experience in Healthcare/Social Services</label>
                    <select id="experience" name="experience">
                        <option value="">Select Experience</option>
                        <option value="No Experience">No Experience</option>
                        <option value="Less than 1 year">Less than 1 year</option>
                        <option value="1-2 years">1-2 years</option>
                        <option value="3-5 years">3-5 years</option>
                        <option value="5-10 years">5-10 years</option>
                        <option value="10+ years">10+ years</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Certifications (Check all that apply)</label>
                    <div class="checkbox-group">
                        <input type="checkbox" id="cpr" name="certifications[]" value="CPR">
                        <label for="cpr">CPR Certified</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="first_aid" name="certifications[]" value="First Aid">
                        <label for="first_aid">First Aid Certified</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="cna_cert" name="certifications[]" value="CNA">
                        <label for="cna_cert">CNA License</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="cmt_cert" name="certifications[]" value="CMT">
                        <label for="cmt_cert">CMT License</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="autism_training" name="certifications[]" value="Autism Training">
                        <label for="autism_training">Autism Training/Certification</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="why_interested">Why are you interested in working with individuals with autism and developmental disabilities? *</label>
                    <textarea id="why_interested" name="why_interested" required placeholder="Please describe your motivation and interest in this field..."></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Additional Information</h3>
                <div class="form-group">
                    <label for="transportation">Do you have reliable transportation? *</label>
                    <select id="transportation" name="transportation" required>
                        <option value="">Select</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="background_check">Are you willing to undergo a background check? *</label>
                    <select id="background_check" name="background_check" required>
                        <option value="">Select</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="start_date">When can you start? *</label>
                    <input type="date" id="start_date" name="start_date" required>
                </div>
                
                <div class="form-group">
                    <label for="additional_info">Additional Information or Questions</label>
                    <textarea id="additional_info" name="additional_info" placeholder="Please share any additional information about yourself or ask any questions..."></textarea>
                </div>
            </div>
            
            <button type="submit" class="submit-btn">Submit Employment Application</button>
        </form>
    </div>
</body>
</html> 