<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - American Caregivers, Inc.</title>
    <meta name="description" content="Contact American Caregivers Inc for autism waiver and developmental disability services. Silver Spring and Columbia offices serving Maryland communities.">
    <link rel="canonical" href="https://aci.dydact.io/contact" />
    
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
        
        .contact_grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        
        .contact_card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-top: 5px solid var(--aci-blue);
            text-align: center;
        }
        
        .contact_card:nth-child(2) {
            border-top-color: var(--aci-red);
        }
        
        .contact_card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .contact_card h3 {
            color: var(--aci-blue);
            margin-bottom: 20px;
            font-size: 1.8rem;
            font-weight: 600;
        }
        
        .contact_card:nth-child(2) h3 {
            color: var(--aci-red);
        }
        
        .contact_info {
            color: var(--text-light);
            line-height: 1.8;
            margin-bottom: 20px;
        }
        
        .contact_info strong {
            color: var(--text-dark);
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
        
        .contact_card:nth-child(2) .btn_style {
            background: var(--aci-red);
        }
        
        .contact_card:nth-child(2) .btn_style:hover {
            background: #b91c1c;
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
            
            .contact_grid {
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
                            <li><a href="/services">Services</a></li>
                            <li class="current-menu-item"><a href="/contact">Contact Us</a></li>
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
                <h1>Contact Us</h1>
                <p>Get in touch with our team for quality care provision and support services</p>
            </div>
        </div>

        <!-- Contact Information Section -->
        <div class="content_section">
            <div class="wrapper">
                <div class="section_title">
                    <h2>Our Offices</h2>
                    <p>Two convenient locations to serve Maryland communities</p>
                </div>
                <div class="contact_grid">
                    <div class="contact_card">
                        <h3>Silver Spring Administrative Office</h3>
                        <div class="contact_info">
                            <strong>Address:</strong><br>
                            2301 Broadbirch Dr., Suite 135<br>
                            Silver Spring, MD 20904<br><br>
                            
                            <strong>Phone:</strong> 📞 301-408-0100<br>
                            <strong>Fax:</strong> 📠 301-408-0189<br>
                            <strong>Email:</strong> 📧 contact@acgcares.com<br><br>
                            
                            <strong>Office Hours:</strong><br>
                            Monday - Friday: 9:00 AM - 6:00 PM<br>
                            Saturday - Sunday: 10:00 AM - 6:00 PM
                        </div>
                        <a href="mailto:contact@acgcares.com?subject=Service Inquiry" class="btn_style">Email Us</a>
                    </div>
                    
                    <div class="contact_card">
                        <h3>Columbia Office</h3>
                        <div class="contact_info">
                            <strong>Address:</strong><br>
                            10715 Charter Drive, Ste. 100<br>
                            Columbia, MD 21044<br><br>
                            
                            <strong>Phone:</strong> 📞 301-301-0123<br>
                            <strong>Fax:</strong> 📠 301-301-1077<br>
                            <strong>Email:</strong> 📧 American.caregiversinc@gmail.com<br><br>
                            
                            <strong>Office Hours:</strong><br>
                            Monday - Friday: 9:00 AM - 6:00 PM<br>
                            Saturday - Sunday: 10:00 AM - 6:00 PM
                        </div>
                        <a href="mailto:American.caregiversinc@gmail.com?subject=Service Inquiry" class="btn_style">Email Us</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Areas Section -->
        <div class="content_section">
            <div class="wrapper">
                <div class="section_title">
                    <h2>Service Areas</h2>
                    <p>Proudly serving communities across Maryland and Washington DC</p>
                </div>
                <div style="text-align: center; font-size: 1.2rem; color: var(--text-light); line-height: 2.5; max-width: 800px; margin: 0 auto;">
                    <strong style="color: var(--aci-blue);">Montgomery County</strong> • 
                    <strong style="color: var(--aci-red);">Prince George's County</strong> • 
                    <strong style="color: var(--aci-green);">Howard County</strong><br>
                    <strong style="color: var(--medical-blue);">Anne Arundel County</strong> • 
                    <strong style="color: var(--aci-blue);">Baltimore County</strong> • 
                    <strong style="color: var(--aci-red);">Baltimore City</strong> • 
                    <strong style="color: var(--aci-green);">Washington DC</strong>
                </div>
                <div style="text-align: center; margin-top: 40px;">
                    <a href="mailto:contact@acgcares.com?subject=Service Area Inquiry&body=Hello, I would like to know if you provide services in my area. My location is:" class="btn_style" style="background: var(--aci-green);">Check Service Availability</a>
                </div>
            </div>
        </div>

        <!-- Quick Contact Section -->
        <div class="content_section">
            <div class="wrapper">
                <div class="section_title">
                    <h2>Quick Contact Options</h2>
                    <p>Multiple ways to reach us for different needs</p>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin: 40px 0;">
                    <div style="text-align: center; padding: 30px 20px; background: white; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
                        <div style="font-size: 3rem; margin-bottom: 15px;">🏥</div>
                        <h3 style="color: var(--aci-blue); margin-bottom: 15px;">New Client Services</h3>
                        <p style="color: var(--text-light); margin-bottom: 20px;">Interested in our autism waiver or developmental disability services?</p>
                        <a href="application_form.php" class="btn_style">Apply for Services</a>
                    </div>
                    
                    <div style="text-align: center; padding: 30px 20px; background: white; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
                        <div style="font-size: 3rem; margin-bottom: 15px;">💼</div>
                        <h3 style="color: var(--aci-red); margin-bottom: 15px;">Career Opportunities</h3>
                        <p style="color: var(--text-light); margin-bottom: 20px;">Join our team and make a difference in people's lives.</p>
                        <a href="careers.php" class="btn_style" style="background: var(--aci-red);">View Jobs</a>
                    </div>
                    
                    <div style="text-align: center; padding: 30px 20px; background: white; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
                        <div style="font-size: 3rem; margin-bottom: 15px;">👥</div>
                        <h3 style="color: var(--aci-green); margin-bottom: 15px;">Staff Portal</h3>
                        <p style="color: var(--text-light); margin-bottom: 20px;">Current employees can access their portal here.</p>
                        <a href="src/login.php" class="btn_style" style="background: var(--aci-green);">Staff Login</a>
                    </div>
                    
                    <div style="text-align: center; padding: 30px 20px; background: white; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
                        <div style="font-size: 3rem; margin-bottom: 15px;">📞</div>
                        <h3 style="color: var(--medical-blue); margin-bottom: 15px;">Emergency Contact</h3>
                        <p style="color: var(--text-light); margin-bottom: 20px;">24/7 support for current clients and urgent matters.</p>
                        <a href="tel:301-408-0100" class="btn_style" style="background: var(--medical-blue);">Call Now</a>
                    </div>
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
                            <li><a href="src/login.php">Employee Portal</a></li>
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