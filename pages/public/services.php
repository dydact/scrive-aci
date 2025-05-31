<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - American Caregivers, Inc.</title>
    <meta name="description" content="Comprehensive autism waiver and developmental disability services including IISS, Therapeutic Integration, Respite Care, DDA Services, and Community First Choice programs in Maryland.">
    <link rel="canonical" href="https://aci.dydact.io/services" />
    
    <!-- Fonts -->
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css?family=Merriweather:400,700,900,400italic,700italic,900italic|Montserrat:400,700|Inter:400,500,600,700&display=swap" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Merriweather:400,700,900,400italic,700italic,900italic|Montserrat:400,700|Inter:400,500,600,700&display=swap" media="print" onload="this.media='all'" />
    
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
            --light-bg: #f8fafc;
            --text-dark: #1e293b;
            --text-light: #64748b;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background: white;
        }
        
        .wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header Styles */
        .header {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .header_con {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .main_logo a {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .main_logo img {
            height: 60px;
            width: auto;
            margin-right: 15px;
        }
        
        .logo-text {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--aci-blue);
            line-height: 1.3;
        }
        
        .head_info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .social_media ul {
            display: flex;
            list-style: none;
            gap: 15px;
        }
        
        .social_media a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: var(--aci-blue);
            border-radius: 50%;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .social_media a:hover {
            background: var(--aci-red);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }
        
        .social_media svg {
            width: 20px;
            height: 20px;
            color: white;
        }
        
        .social_media a.facebook:hover {
            background: #1877f2;
        }
        
        .social_media a.twitter:hover {
            background: #000000;
        }
        
        .social_media a.instagram:hover {
            background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%);
        }
        
        .social_media a.linkedin:hover {
            background: #0077b5;
        }
        
        /* Navigation */
        .nav_area {
            background: var(--aci-blue);
            position: relative;
        }
        
        .page_nav {
            display: flex;
            justify-content: center;
        }
        
        .nav-menu ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .nav-menu a {
            display: block;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .nav-menu a:hover,
        .nav-menu .current-menu-item a {
            background: rgba(255,255,255,0.1);
            border-bottom-color: var(--aci-red);
        }
        
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Page Header */
        .page_header {
            background: linear-gradient(135deg, var(--aci-blue) 0%, var(--medical-blue) 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .page_header h1 {
            font-family: 'Merriweather', serif;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .page_header p {
            font-size: 1.25rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Content Sections */
        .content_section {
            padding: 80px 0;
        }
        
        .content_section:nth-child(even) {
            background: var(--light-bg);
        }
        
        .section_title {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section_title h2 {
            font-family: 'Merriweather', serif;
            font-size: 2.5rem;
            color: var(--aci-blue);
            margin-bottom: 15px;
        }
        
        .section_title p {
            font-size: 1.1rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .services_grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        
        .service_card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-top: 5px solid var(--aci-blue);
            position: relative;
        }
        
        .service_card:nth-child(2) {
            border-top-color: var(--aci-red);
        }
        
        .service_card:nth-child(3) {
            border-top-color: var(--aci-green);
        }
        
        .service_card:nth-child(4) {
            border-top-color: var(--medical-blue);
        }
        
        .service_card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .service_card h3 {
            color: var(--aci-blue);
            margin-bottom: 15px;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .service_card:nth-child(2) h3 {
            color: var(--aci-red);
        }
        
        .service_card:nth-child(3) h3 {
            color: var(--aci-green);
        }
        
        .service_card:nth-child(4) h3 {
            color: var(--medical-blue);
        }
        
        .service_card p {
            color: var(--text-light);
            line-height: 1.7;
            margin-bottom: 20px;
        }
        
        .service_features {
            list-style: none;
            margin: 20px 0;
        }
        
        .service_features li {
            padding: 8px 0;
            color: var(--text-light);
            position: relative;
            padding-left: 25px;
        }
        
        .service_features li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--aci-green);
            font-weight: bold;
        }
        
        .btn_style {
            display: inline-block;
            padding: 12px 25px;
            background: var(--aci-blue);
            color: white;
            text-decoration: none;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.3s;
            margin-top: 15px;
        }
        
        .btn_style:hover {
            background: #1e3a8a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }
        
        .service_card:nth-child(2) .btn_style {
            background: var(--aci-red);
        }
        
        .service_card:nth-child(2) .btn_style:hover {
            background: #b91c1c;
        }
        
        .service_card:nth-child(3) .btn_style {
            background: var(--aci-green);
        }
        
        .service_card:nth-child(3) .btn_style:hover {
            background: #15803d;
        }
        
        .service_card:nth-child(4) .btn_style {
            background: var(--medical-blue);
        }
        
        .service_card:nth-child(4) .btn_style:hover {
            background: #2563eb;
        }
        
        .process_steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }
        
        .step_card {
            text-align: center;
            padding: 30px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .step_number {
            width: 60px;
            height: 60px;
            background: var(--aci-blue);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0 auto 20px;
        }
        
        .step_card:nth-child(2) .step_number {
            background: var(--aci-red);
        }
        
        .step_card:nth-child(3) .step_number {
            background: var(--aci-green);
        }
        
        .step_card:nth-child(4) .step_number {
            background: var(--medical-blue);
        }
        
        .step_card h3 {
            color: var(--aci-blue);
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .step_card p {
            color: var(--text-light);
            line-height: 1.6;
        }
        
        /* Footer */
        .footer {
            background: #1e293b;
            color: white;
            padding: 60px 0 20px;
        }
        
        .footer_content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer_section h4 {
            margin-bottom: 20px;
            color: white;
            font-size: 1.2rem;
        }
        
        .footer_section ul {
            list-style: none;
        }
        
        .footer_section a {
            color: #cbd5e1;
            text-decoration: none;
            line-height: 2;
            transition: color 0.3s;
        }
        
        .footer_section a:hover {
            color: white;
        }
        
        .footer_bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #94a3b8;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header_con {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-menu ul {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--aci-blue);
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            
            .nav-menu ul.active {
                display: flex;
            }
            
            .mobile-menu-toggle {
                display: block;
            }
            
            .page_header h1 {
                font-size: 2rem;
            }
            
            .section_title h2 {
                font-size: 2rem;
            }
            
            .services_grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="protect-me">
        <!-- Header -->
        <header id="header" class="header">
            <div class="wrapper">
                <div class="header_con">
                    <div class="main_logo static">
                        <a href="/">
                            <figure>
                                <img src="/public/images/aci-logo.png" alt="American Caregivers, Inc." onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <span class="logo-text" style="display: none;">
                                    <span style="color: var(--aci-blue);">A</span><span style="color: var(--aci-red);">C</span><span style="color: var(--aci-green);">I</span> American Caregivers, Inc.
                                </span>
                            </figure>
                        </a>
                    </div>

                    <div class="head_info">
                        <div class="social_media">
                            <ul>
                                <li>
                                    <a href="https://www.facebook.com/AmericanCaregiversInc" target="_blank" rel="nofollow" aria-label="Follow us on Facebook" class="facebook">
                                        <svg><use href="/public/images/social-icons.svg#facebook"></use></svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://twitter.com/ACGCares" target="_blank" rel="nofollow" aria-label="Follow us on Twitter/X" class="twitter">
                                        <svg><use href="/public/images/social-icons.svg#twitter"></use></svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://www.instagram.com/americancaregiversinc" target="_blank" rel="nofollow" aria-label="Follow us on Instagram" class="instagram">
                                        <svg><use href="/public/images/social-icons.svg#instagram"></use></svg>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://www.linkedin.com/company/american-caregivers-inc" target="_blank" rel="nofollow" aria-label="Connect with us on LinkedIn" class="linkedin">
                                        <svg><use href="/public/images/social-icons.svg#linkedin"></use></svg>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Navigation -->
        <div id="nav_area" class="nav_area">
            <div class="wrapper">
                <nav class="page_nav">
                    <div class="nav-menu">
                        <ul id="main-menu">
                            <li><a href="/">Home</a></li>
                            <li><a href="/about">About Us</a></li>
                            <li class="current-menu-item"><a href="/services">Services</a></li>
                            <li><a href="/contact">Contact Us</a></li>
                            <li><a href="/application_form">Apply Now</a></li>
                            <li><a href="src/login.php" style="background: var(--aci-red); border-radius: 5px;">Staff Login</a></li>
                        </ul>
                    </div>
                    <button class="mobile-menu-toggle" onclick="toggleMenu()">☰</button>
                </nav>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page_header">
            <div class="wrapper">
                <h1>Our Specialized Services</h1>
                <p>Comprehensive autism waiver and developmental disability programs designed to enhance independence and quality of life</p>
            </div>
        </div>

        <!-- Main Services Section -->
        <div class="content_section">
            <div class="wrapper">
                <div class="section_title">
                    <h2>Core Service Programs</h2>
                    <p>Evidence-based interventions and support services tailored to individual needs</p>
                </div>
                <div class="services_grid">
                    <div class="service_card">
                        <h3>Autism Waiver (AW)</h3>
                        <p>Comprehensive support services specifically designed for individuals with autism spectrum disorders, providing intensive community-based interventions.</p>
                        <ul class="service_features">
                            <li>Individual Intensive Support Services (IISS) - 20 hours/week</li>
                            <li>Therapeutic Integration (TI) - 15 hours/week</li>
                            <li>Respite Care - 8 hours/week</li>
                            <li>Family Consultation - 2 hours/week</li>
                            <li>Behavioral Support Planning</li>
                            <li>Community Integration Training</li>
                        </ul>
                        <a href="mailto:contact@acgcares.com?subject=Autism Waiver Services Inquiry" class="btn_style">Learn More</a>
                    </div>
                    
                    <div class="service_card">
                        <h3>DDA Services</h3>
                        <p>Developmental Disabilities Administration programs providing comprehensive community support and residential habilitation services.</p>
                        <ul class="service_features">
                            <li>Community Habilitation</li>
                            <li>Residential Habilitation</li>
                            <li>Life Skills Training</li>
                            <li>Employment Support</li>
                            <li>Transportation Services</li>
                            <li>24/7 Support Coordination</li>
                        </ul>
                        <a href="mailto:contact@acgcares.com?subject=DDA Services Inquiry" class="btn_style">Learn More</a>
                    </div>
                    
                    <div class="service_card">
                        <h3>Community First Choice (CFC)</h3>
                        <p>Personal care and companion services designed to support independent living and community integration for eligible individuals.</p>
                        <ul class="service_features">
                            <li>Personal Care Assistance</li>
                            <li>Companion Services</li>
                            <li>Medication Management</li>
                            <li>Meal Preparation</li>
                            <li>Light Housekeeping</li>
                            <li>Community Access Support</li>
                        </ul>
                        <a href="mailto:contact@acgcares.com?subject=Community First Choice Inquiry" class="btn_style">Learn More</a>
                    </div>
                    
                    <div class="service_card">
                        <h3>Community Services (CS)</h3>
                        <p>Behavioral support programs, life skills training, and therapeutic services to enhance independence and improve quality of life.</p>
                        <ul class="service_features">
                            <li>Behavioral Support Services</li>
                            <li>Independent Living Skills</li>
                            <li>Social Skills Training</li>
                            <li>Therapeutic Recreation</li>
                            <li>Crisis Intervention</li>
                            <li>Family Support Services</li>
                        </ul>
                        <a href="mailto:contact@acgcares.com?subject=Community Services Inquiry" class="btn_style">Learn More</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Process Section -->
        <div class="content_section">
            <div class="wrapper">
                <div class="section_title">
                    <h2>Our Service Process</h2>
                    <p>A systematic approach to delivering quality care and achieving positive outcomes</p>
                </div>
                <div class="process_steps">
                    <div class="step_card">
                        <div class="step_number">1</div>
                        <h3>Initial Assessment</h3>
                        <p>Comprehensive evaluation of individual needs, strengths, and goals to develop personalized service plans.</p>
                    </div>
                    <div class="step_card">
                        <div class="step_number">2</div>
                        <h3>Service Planning</h3>
                        <p>Collaborative development of individualized treatment plans with clients, families, and healthcare teams.</p>
                    </div>
                    <div class="step_card">
                        <div class="step_number">3</div>
                        <h3>Implementation</h3>
                        <p>Delivery of evidence-based interventions by qualified staff with ongoing monitoring and adjustments.</p>
                    </div>
                    <div class="step_card">
                        <div class="step_number">4</div>
                        <h3>Progress Review</h3>
                        <p>Regular evaluation of outcomes and service effectiveness with continuous quality improvement.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Areas Section -->
        <div class="content_section">
            <div class="wrapper">
                <div class="section_title">
                    <h2>Service Areas</h2>
                    <p>Serving communities across Maryland with quality care and support</p>
                </div>
                <div style="text-align: center; font-size: 1.1rem; color: var(--text-light); line-height: 2;">
                    <strong>Montgomery County</strong> • <strong>Prince George's County</strong> • <strong>Howard County</strong><br>
                    <strong>Anne Arundel County</strong> • <strong>Baltimore County</strong> • <strong>Baltimore City</strong> • <strong>Washington DC</strong>
                </div>
                <div style="text-align: center; margin-top: 40px;">
                    <a href="mailto:contact@acgcares.com?subject=Service Area Inquiry&body=Hello, I would like to know if you provide services in my area." class="btn_style" style="background: var(--aci-green);">Check Service Availability</a>
                </div>
            </div>
        </div>

        <!-- Contact CTA Section -->
        <div class="content_section">
            <div class="wrapper">
                <div class="section_title">
                    <h2>Ready to Get Started?</h2>
                    <p>Contact us today to learn more about our services and how we can support you</p>
                </div>
                <div style="text-align: center;">
                    <div style="display: inline-block; margin: 0 20px;">
                        <h3 style="color: var(--aci-blue); margin-bottom: 10px;">Silver Spring Office</h3>
                        <p style="color: var(--text-light);">📞 301-408-0100</p>
                        <p style="color: var(--text-light);">📧 contact@acgcares.com</p>
                    </div>
                    <div style="display: inline-block; margin: 0 20px;">
                        <h3 style="color: var(--aci-red); margin-bottom: 10px;">Columbia Office</h3>
                        <p style="color: var(--text-light);">📞 301-301-0123</p>
                        <p style="color: var(--text-light);">📧 American.caregiversinc@gmail.com</p>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 30px;">
                    <a href="/contact" class="btn_style" style="background: var(--aci-red); margin-right: 15px;">Contact Us</a>
                    <a href="application_form.php" class="btn_style">Apply for Services</a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="wrapper">
                <div class="footer_content">
                    <div class="footer_section">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="/about">About Us</a></li>
                            <li><a href="/services">Our Services</a></li>
                            <li><a href="application_form.php">Apply for Employment</a></li>
                            <li><a href="/privacy-policy">Privacy Policy</a></li>
                            <li><a href="src/login.php">Staff Login Portal</a></li>
                        </ul>
                    </div>
                    <div class="footer_section">
                        <h4>Service Areas</h4>
                        <p style="color: #cbd5e1; line-height: 1.8;">
                            Montgomery County<br>
                            Prince George's County<br>
                            Howard County<br>
                            Anne Arundel County<br>
                            Baltimore County<br>
                            Baltimore City<br>
                            Washington DC
                        </p>
                    </div>
                    <div class="footer_section">
                        <h4>Contact Information</h4>
                        <p style="color: #cbd5e1; line-height: 1.8;">
                            📧 contact@acgcares.com<br>
                            📞 301-408-0100<br>
                            📠 301-408-0189
                        </p>
                    </div>
                    <div class="footer_section">
                        <h4>Staff Resources</h4>
                        <ul>
                            <li><a href="src/login.php">Staff Login Portal</a></li>
                            <li><a href="autism_waiver_app/mobile_employee_portal.php">Mobile Portal</a></li>
                            <li><a href="/training">Training Resources</a></li>
                            <li><a href="/policies">Company Policies</a></li>
                            <li><a href="/support">IT Support</a></li>
                        </ul>
                    </div>
                </div>
                <div class="footer_bottom">
                    <p>&copy; 2025 American Caregivers Inc. All rights reserved. | Licensed by Maryland Department of Health</p>
                </div>
            </div>
        </footer>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('main-menu');
            menu.classList.toggle('active');
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('main-menu');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (!menu.contains(event.target) && !toggle.contains(event.target)) {
                menu.classList.remove('active');
            }
        });
    </script>
</body>
</html> 