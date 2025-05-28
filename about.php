<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - American Caregivers, Inc.</title>
    <meta name="description" content="Learn about American Caregivers Inc, our mission, values, and leadership team including CEO Mary Emah. Quality autism waiver and developmental disability services in Maryland.">
    <link rel="canonical" href="https://aci.dydact.io/about" />
    
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
        
        .text_content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-light);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .text_content p {
            margin-bottom: 20px;
        }
        
        .leadership_grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        
        .leader_card {
            background: white;
            padding: 40px 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s;
            border-top: 4px solid var(--aci-blue);
        }
        
        .leader_card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .leader_card h3 {
            color: var(--aci-blue);
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        
        .leader_card .title {
            color: var(--aci-red);
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .leader_card p {
            color: var(--text-light);
            line-height: 1.6;
        }
        
        .stats_grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }
        
        .stat_card {
            text-align: center;
            padding: 30px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .stat_number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--aci-blue);
            margin-bottom: 10px;
        }
        
        .stat_label {
            color: var(--text-light);
            font-weight: 500;
        }
        
        .values_grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin: 40px 0;
        }
        
        .value_card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            border-top: 4px solid var(--aci-blue);
        }
        
        .value_card:nth-child(2) {
            border-top-color: var(--aci-red);
        }
        
        .value_card:nth-child(3) {
            border-top-color: var(--aci-green);
        }
        
        .value_card h3 {
            color: var(--aci-blue);
            margin-bottom: 15px;
            font-size: 1.5rem;
        }
        
        .value_card:nth-child(2) h3 {
            color: var(--aci-red);
        }
        
        .value_card:nth-child(3) h3 {
            color: var(--aci-green);
        }
        
        .value_card p {
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
                            <li class="current-menu-item"><a href="/about">About Us</a></li>
                            <li><a href="/services">Services</a></li>
                            <li><a href="/careers">Careers</a></li>
                            <li><a href="/resources">Resources</a></li>
                            <li><a href="/gallery">Gallery</a></li>
                            <li><a href="/blog">Blog</a></li>
                            <li><a href="/contact">Contact Us</a></li>
                            <li><a href="login_sqlite.php" style="background: var(--aci-red); border-radius: 5px;">Staff Login</a></li>
                        </ul>
                    </div>
                    <button class="mobile-menu-toggle" onclick="toggleMenu()">☰</button>
                </nav>
            </div>
        </div>

        <!-- Page Header -->
        <div class="page_header">
            <div class="wrapper">
                <h1>About American Caregivers Inc</h1>
                <p>Setting the Standard of Quality Care for individuals with autism and developmental disabilities across Maryland</p>
            </div>
        </div>

        <!-- Our Story Section -->
        <div class="content_section">
            <div class="wrapper">
                <div class="section_title">
                    <h2>Our Story</h2>
                    <p>A decade of dedicated service to Maryland's autism and developmental disability community</p>
                </div>
                <div class="text_content">
                    <p>Founded in 2015, American Caregivers Inc has been dedicated to providing exceptional care and support services for individuals with autism and developmental disabilities throughout Maryland. Our journey began with a simple yet powerful mission: to enhance the quality of life for those we serve while supporting their families and communities.</p>
                    
                    <p>Over the years, we have grown from a small team of passionate caregivers to a comprehensive healthcare organization serving multiple counties across Maryland. Our commitment to excellence, innovation, and compassionate care has made us a trusted partner for families, healthcare providers, and state agencies.</p>
                    
                    <p>Today, American Caregivers Inc stands as a leader in autism waiver services and developmental disability support, continuously setting new standards for quality care in our field.</p>
                </div>
            </div>
        </div>

        <!-- Leadership Section -->
        <div class="content_section">
            <div class="wrapper">
                <div class="section_title">
                    <h2>Leadership Team</h2>
                    <p>Experienced professionals dedicated to excellence in care</p>
                </div>
                <div class="leadership_grid">
                    <div class="leader_card">
                        <h3>Mary Emah</h3>
                        <div class="title">Chief Executive Officer</div>
                        <p>Mary Emah leads American Caregivers Inc with over 15 years of experience in healthcare administration and developmental disability services. Her vision and commitment to quality care have been instrumental in our organization's growth and success.</p>
                    </div>
                    <div class="leader_card">
                        <h3>Amanda Georgie</h3>
                        <div class="title">Executive Staff</div>
                        <p>Amanda brings extensive experience in operations management and staff development, ensuring our team delivers the highest quality of care to every client we serve.</p>
                    </div>
                    <div class="leader_card">
                        <h3>Clinical Leadership Team</h3>
                        <div class="title">Department Heads</div>
                        <p>Our clinical leadership team includes experienced professionals in autism services, developmental disabilities, and therapeutic interventions, ensuring evidence-based care delivery.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Section -->
        <div class="content_section">
            <div class="wrapper">
                <div class="stats_grid">
                    <div class="stat_card">
                        <div class="stat_number">10+</div>
                        <div class="stat_label">Years of Service</div>
                    </div>
                    <div class="stat_card">
                        <div class="stat_number">500+</div>
                        <div class="stat_label">Clients Served</div>
                    </div>
                    <div class="stat_card">
                        <div class="stat_number">90+</div>
                        <div class="stat_label">Dedicated Staff</div>
                    </div>
                    <div class="stat_card">
                        <div class="stat_number">7</div>
                        <div class="stat_label">Counties Served</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mission Section -->
        <div class="content_section">
            <div class="wrapper">
                <div class="section_title">
                    <h2>Our Mission</h2>
                    <p>Empowering individuals with disabilities to achieve their fullest potential</p>
                </div>
                <div class="text_content">
                    <p><strong>To provide quality care provision for children and adults with disabilities, empowering them to achieve their fullest potential while supporting their families and communities.</strong></p>
                    
                    <p>We believe that every individual deserves compassionate, personalized care that respects their dignity, promotes their independence, and enhances their quality of life. Our mission drives everything we do, from the services we provide to the staff we hire and train.</p>
                </div>
            </div>
        </div>

        <!-- Values Section -->
        <div class="content_section">
            <div class="wrapper">
                <div class="section_title">
                    <h2>Our Values</h2>
                    <p>The principles that guide our work every day</p>
                </div>
                <div class="values_grid">
                    <div class="value_card">
                        <h3>Compassion</h3>
                        <p>We approach every interaction with empathy, understanding, and genuine care for the individuals and families we serve.</p>
                    </div>
                    <div class="value_card">
                        <h3>Excellence</h3>
                        <p>We are committed to the highest standards of care, continuously improving our services and exceeding expectations.</p>
                    </div>
                    <div class="value_card">
                        <h3>Integrity</h3>
                        <p>We conduct our business with honesty, transparency, and ethical practices in all our relationships and interactions.</p>
                    </div>
                    <div class="value_card">
                        <h3>Innovation</h3>
                        <p>We embrace new approaches, technologies, and best practices to enhance the care and support we provide.</p>
                    </div>
                    <div class="value_card">
                        <h3>Respect</h3>
                        <p>We honor the dignity, rights, and unique needs of every individual we serve, fostering an inclusive environment.</p>
                    </div>
                    <div class="value_card">
                        <h3>Collaboration</h3>
                        <p>We work closely with families, healthcare providers, and communities to achieve the best outcomes for our clients.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accreditation Section -->
        <div class="content_section">
            <div class="wrapper">
                <div class="section_title">
                    <h2>Leadership & Accreditation</h2>
                    <p>Maintaining the highest standards of care and compliance</p>
                </div>
                <div class="text_content">
                    <p>American Caregivers Inc is licensed by the Maryland Department of Health and maintains all required certifications for providing autism waiver and developmental disability services. Our leadership team brings decades of combined experience in healthcare, social services, and business management.</p>
                    
                    <p>We are committed to maintaining the highest standards of care through continuous training, quality assurance programs, and regular compliance reviews. Our staff members are carefully selected, thoroughly trained, and regularly evaluated to ensure they meet our exacting standards.</p>
                    
                    <p>We participate in ongoing quality improvement initiatives and maintain partnerships with leading healthcare organizations, universities, and research institutions to stay at the forefront of best practices in our field.</p>
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
                            <li><a href="login_sqlite.php">Employee Portal</a></li>
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
                            <li><a href="login_sqlite.php">Staff Login Portal</a></li>
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