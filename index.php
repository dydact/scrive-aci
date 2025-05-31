<?php
// Include domain configuration
require_once __DIR__ . '/config/domain-config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Care in Maryland - American Caregivers, Inc.</title>
    <meta name="description" content="American Caregivers, Inc. is a trusted provider of autism waiver and developmental disability services in Maryland. Quality care provision for children and adults with disabilities.">
    <link rel="canonical" href="<?php echo BASE_URL; ?>/" />
    
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
            color: var(--aci-blue);
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        /* Banner/Hero Section */
        .banner {
            position: relative;
            min-height: 600px;
            background: linear-gradient(135deg, var(--aci-blue) 0%, var(--medical-blue) 100%);
            display: flex;
            align-items: center;
            overflow: hidden;
        }
        
        .banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('/public/images/acgcares/banner-img10.jpg') center/cover;
            opacity: 0.2;
            z-index: 1;
        }
        
        .banner_content {
            position: relative;
            z-index: 2;
            color: white;
            max-width: 600px;
        }
        
        .banner h1 {
            font-family: 'Merriweather', serif;
            font-size: 3rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 20px;
        }
        
        .banner h1 span {
            color: var(--aci-red);
        }
        
        .banner p {
            font-size: 1.25rem;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        
        .btn_style {
            display: inline-block;
            padding: 15px 30px;
            background: var(--aci-red);
            color: white;
            text-decoration: none;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn_style:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        
        /* Services Section */
        .services_section {
            padding: 80px 0;
            background: white;
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }
        
        .service_card {
            background: white;
            padding: 40px 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s;
            border-top: 4px solid var(--aci-blue);
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
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .service_card h3 {
            font-size: 1.5rem;
            color: var(--aci-blue);
            margin-bottom: 15px;
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
            line-height: 1.6;
        }
        
        /* About Section */
        .about_section {
            padding: 80px 0;
            background: var(--light-bg);
        }
        
        .about_content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        
        .about_text h2 {
            font-family: 'Merriweather', serif;
            font-size: 2.5rem;
            color: var(--aci-blue);
            margin-bottom: 20px;
        }
        
        .about_text p {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 20px;
            line-height: 1.8;
        }
        
        .about_image {
            position: relative;
        }
        
        .about_image img {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        /* Contact Section */
        .contact_section {
            padding: 80px 0;
            background: white;
        }
        
        .contact_grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        
        .contact_card {
            text-align: center;
            padding: 40px 30px;
            background: var(--light-bg);
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .contact_card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .contact_card h3 {
            color: var(--aci-blue);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        
        .contact_card:nth-child(2) h3 {
            color: var(--aci-red);
        }
        
        .contact_card p {
            color: var(--text-light);
            line-height: 1.8;
        }
        
        .contact_card .phone {
            color: var(--aci-blue);
            font-weight: 600;
            font-size: 1.25rem;
            margin-top: 15px;
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
            
            .banner h1 {
                font-size: 2rem;
            }
            
            .about_content {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .section_title h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="front_page">
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
                            <li class="current-menu-item"><a href="/">Home</a></li>
                            <li><a href="/about">About Us</a></li>
                            <li><a href="/services">Services</a></li>
                            <li><a href="/contact">Contact Us</a></li>
                            <li><a href="/apply">Apply Now</a></li>
                            <li><a href="/login" style="background: var(--aci-red); border-radius: 5px;">Staff Login</a></li>
                        </ul>
                    </div>
                    <button class="mobile-menu-toggle" onclick="toggleMenu()">☰</button>
                </nav>
            </div>
        </div>

        <!-- Banner -->
        <div id="banner" class="banner">
            <div class="wrapper">
                <div class="banner_content">
                    <h1>Providing Care Through <span>Quality Home &</span> <span>Community Services</span></h1>
                    <p>Serving children and adults with disabilities through personalized autism waiver services and unwavering quality care standards across Maryland.</p>
                    <a href="/about" class="btn_style">GET STARTED</a>
                </div>
            </div>
        </div>

        <!-- Services Section -->
        <div id="services" class="services_section">
            <div class="wrapper">
                <div class="section_title">
                    <h2>Our Specialized Programs</h2>
                    <p>Comprehensive autism waiver and developmental disability services designed to enhance independence and quality of life</p>
                </div>
                <div class="services_grid">
                    <div class="service_card">
                        <h3>Autism Waiver (AW)</h3>
                        <p>Individual Intensive Support Services (IISS), Therapeutic Integration (TI), Respite Care, and Family Consultation to help individuals with autism thrive in their communities.</p>
                    </div>
                    <div class="service_card">
                        <h3>DDA Services</h3>
                        <p>Developmental Disabilities Administration programs providing community support, residential habilitation, and life skills training for individuals with developmental disabilities.</p>
                    </div>
                    <div class="service_card">
                        <h3>Community First Choice</h3>
                        <p>Personal care and companion services designed to support independent living and community integration for eligible individuals across Maryland.</p>
                    </div>
                    <div class="service_card">
                        <h3>Community Services</h3>
                        <p>Behavioral support programs, life skills training, and therapeutic services to enhance independence and improve quality of life for our clients.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- About Section -->
        <div id="about" class="about_section">
            <div class="wrapper">
                <div class="about_content">
                    <div class="about_text">
                        <h2>Setting the Standard of Quality Care</h2>
                        <p>Since 2015, American Caregivers Inc has been dedicated to providing exceptional autism waiver and developmental disability services throughout Maryland. Our commitment to excellence, innovation, and compassionate care has made us a trusted partner for families, healthcare providers, and state agencies.</p>
                        <p>We believe that every individual deserves personalized care that respects their dignity, promotes their independence, and enhances their quality of life. Our mission drives everything we do, from the services we provide to the staff we hire and train.</p>
                        <a href="/about" class="btn_style">Learn More About Us</a>
                    </div>
                    <div class="about_image">
                        <img src="/public/images/acgcares/main-img.jpg" alt="American Caregivers team providing quality care" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Section -->
        <div id="contact" class="contact_section">
            <div class="wrapper">
                <div class="section_title">
                    <h2>Get in Touch</h2>
                    <p>Connect with us for quality care provision for children and adults with disabilities</p>
                </div>
                <div class="contact_grid">
                    <div class="contact_card">
                        <h3>Silver Spring Administrative Office</h3>
                        <p>
                            2301 Broadbirch Dr., Suite 135<br>
                            Silver Spring, MD 20904
                        </p>
                        <p class="phone">📞 301-408-0100</p>
                        <p>📠 301-408-0189</p>
                        <p>📧 contact@acgcares.com</p>
                    </div>
                    <div class="contact_card">
                        <h3>Columbia Office</h3>
                        <p>
                            10715 Charter Drive, Ste. 100<br>
                            Columbia, MD 21044
                        </p>
                        <p class="phone">📞 301-301-0123</p>
                        <p>📠 301-301-1077</p>
                        <p>📧 American.caregiversinc@gmail.com</p>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 40px;">
                    <h3 style="color: var(--aci-blue); margin-bottom: 15px;">Office Hours</h3>
                    <p style="color: var(--text-light);">
                        Monday - Friday: 9:00 AM - 6:00 PM<br>
                        Saturday - Sunday: 10:00 AM - 6:00 PM
                    </p>
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
                            <li><a href="/apply">Apply for Employment</a></li>
                            <li><a href="/privacy-policy">Privacy Policy</a></li>
                            <li><a href="/login">Employee Portal</a></li>
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
                            <li><a href="/login">Staff Login Portal</a></li>
                            <li><a href="/mobile">Mobile Portal</a></li>
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

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html> 