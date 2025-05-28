# 🌐 **ACI Website Integration Plan**
## American Caregivers Inc - www.acgcares.com → aci.dydact.io

**Current Website:** www.acgcares.com  
**New Unified Domain:** aci.dydact.io  
**Integration Strategy:** Public website + Staff portal on single domain  

---

## 📋 **WEBSITE STRUCTURE ANALYSIS**

### **Current ACI Website Navigation**
```
├── Home
├── About Us
├── Careers
├── Resources
├── Gallery
├── Calendar
├── Blog
└── Contact Us
```

### **Contact Information to Preserve**
- **Primary Phone:** 301-408-0100
- **Secondary Phone:** 301-301-0123
- **Primary Email:** American.caregiversinc@gmail.com
- **Secondary Email:** contact@acgcares.com
- **Fax Numbers:** 301-408-0189 / 301-301-1077

### **Office Locations**
1. **Silver Spring (Administrative):** 2301 Broadbirch Dr., Suite 135, Silver Spring, MD 20904
2. **Columbia:** 10715 Charter Drive, Ste. 100, Columbia, MD 21044

### **Service Coverage**
Montgomery, PG, Howard, Anne Arundel, Baltimore Counties, and Baltimore City

---

## 🎨 **UNIFIED WEBSITE DESIGN**

### **Homepage Structure (aci.dydact.io)**
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>American Caregivers Inc - Quality Care for Children & Adults with Disabilities</title>
    <meta name="description" content="American Caregivers provides autism waiver services, developmental disabilities support, and quality care across Maryland.">
</head>
<body>
    <!-- Public Navigation -->
    <nav class="main-nav">
        <div class="logo">
            <img src="/assets/images/aci-logo.png" alt="American Caregivers Inc">
        </div>
        <ul class="nav-menu">
            <li><a href="/">Home</a></li>
            <li><a href="/about">About Us</a></li>
            <li><a href="/services">Services</a></li>
            <li><a href="/careers">Careers</a></li>
            <li><a href="/resources">Resources</a></li>
            <li><a href="/gallery">Gallery</a></li>
            <li><a href="/blog">Blog</a></li>
            <li><a href="/contact">Contact Us</a></li>
        </ul>
        <div class="staff-login">
            <a href="/scrive/login.php" class="btn-staff-login">Staff Login</a>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero">
        <h1>Quality Care Provision for Children and Adults with Disabilities</h1>
        <p>Serving Maryland communities with compassionate autism waiver and developmental disability services</p>
        <div class="hero-actions">
            <a href="/services" class="btn-primary">Our Services</a>
            <a href="/contact" class="btn-secondary">Get Started</a>
        </div>
    </section>
    
    <!-- Services Overview -->
    <section class="services-overview">
        <h2>Our Programs</h2>
        <div class="program-grid">
            <div class="program-card">
                <h3>Autism Waiver (AW)</h3>
                <p>Comprehensive support services including IISS, TI, Respite, and Family Consultation</p>
            </div>
            <div class="program-card">
                <h3>DDA Services</h3>
                <p>Developmental Disabilities Administration programs for community integration</p>
            </div>
            <div class="program-card">
                <h3>Community First Choice</h3>
                <p>Personal care and companion services for independent living</p>
            </div>
            <div class="program-card">
                <h3>Community Services</h3>
                <p>Life skills training and behavioral support programs</p>
            </div>
        </div>
    </section>
    
    <!-- Contact Section -->
    <section class="contact-info">
        <h2>Get in Touch</h2>
        <div class="offices">
            <div class="office">
                <h3>Silver Spring Office</h3>
                <p>2301 Broadbirch Dr., Suite 135<br>
                Silver Spring, MD 20904<br>
                Phone: 301-408-0100</p>
            </div>
            <div class="office">
                <h3>Columbia Office</h3>
                <p>10715 Charter Drive, Ste. 100<br>
                Columbia, MD 21044<br>
                Phone: 301-301-0123</p>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="/about">About Us</a></li>
                    <li><a href="/careers">Careers</a></li>
                    <li><a href="/privacy-policy">Privacy Policy</a></li>
                    <li><a href="/scrive/login.php">Employee Portal</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Service Areas</h4>
                <p>Montgomery, PG, Howard, Anne Arundel, Baltimore Counties, and Baltimore City</p>
            </div>
            <div class="footer-section">
                <h4>Office Hours</h4>
                <p>Monday - Friday: 9:00 AM - 6:00 PM<br>
                Saturday - Sunday: 10:00 AM - 6:00 PM</p>
            </div>
            <div class="footer-section">
                <h4>Connect With Us</h4>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 American Caregivers, Inc. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
```

---

## 📱 **MOBILE-RESPONSIVE DESIGN**

### **Mobile Navigation**
```css
/* Mobile-First CSS */
@media (max-width: 768px) {
    .main-nav {
        position: sticky;
        top: 0;
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .nav-menu {
        display: none; /* Hidden by default, toggle with JS */
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        flex-direction: column;
    }
    
    .staff-login {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
    }
    
    .btn-staff-login {
        background: #059669;
        color: white;
        padding: 12px 24px;
        border-radius: 50px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
}
```

---

## 🔗 **URL ROUTING STRUCTURE**

### **Public Website URLs**
- `/` - Homepage
- `/about` - About American Caregivers
- `/services` - Service offerings
- `/services/autism-waiver` - Autism Waiver details
- `/services/dda` - DDA program information
- `/services/cfc` - Community First Choice
- `/services/community` - Community Services
- `/careers` - Job opportunities
- `/resources` - Client/family resources
- `/gallery` - Photo gallery
- `/blog` - Company blog
- `/contact` - Contact information
- `/privacy-policy` - Privacy policy

### **Staff Portal URLs**
- `/scrive/` - Staff portal home (redirects to login)
- `/scrive/login.php` - Staff login page
- `/scrive/mobile/` - Mobile portal redirect
- `/scrive/portal_router.php` - Role-based portal selection
- `/scrive/employee/` - Employee portal
- `/scrive/manager/` - Case manager portal
- `/scrive/admin/` - Administrator portal

### **Client Portal URLs (Future)**
- `/client/` - Client/parent portal
- `/client/login` - Client login
- `/client/documents` - Important documents
- `/client/schedule` - Service schedule
- `/client/progress` - Progress reports

---

## 🎯 **INTEGRATION FEATURES**

### **1. Smart Homepage Detection**
```php
<?php
// At aci.dydact.io root index.php
session_start();

// Check if user is logged in staff member
if (isset($_SESSION['staff_id']) && $_SESSION['is_authenticated']) {
    // Show quick access to staff portal
    $showStaffQuickAccess = true;
} else {
    // Show standard public website
    $showStaffQuickAccess = false;
}
?>
```

### **2. Mobile Detection & Routing**
```javascript
// Detect mobile and show appropriate interface
if (/Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent)) {
    // Add mobile-specific features
    document.body.classList.add('mobile-device');
    
    // If on staff portal, suggest mobile app
    if (window.location.pathname.includes('/scrive/')) {
        showMobileAppPrompt();
    }
}
```

### **3. Progressive Web App for Staff**
```json
{
    "name": "ACI Staff Portal",
    "short_name": "ACI Portal",
    "description": "American Caregivers Staff Portal",
    "start_url": "/scrive/mobile_employee_portal.php",
    "display": "standalone",
    "background_color": "#059669",
    "theme_color": "#059669",
    "icons": [
        {
            "src": "/assets/icons/aci-icon-192.png",
            "sizes": "192x192",
            "type": "image/png"
        },
        {
            "src": "/assets/icons/aci-icon-512.png",
            "sizes": "512x512",
            "type": "image/png"
        }
    ]
}
```

---

## 🔐 **SECURITY CONSIDERATIONS**

### **Public vs Private Content**
1. **Public Pages:** No authentication required
2. **Staff Portal:** Requires login with role-based access
3. **Client Portal:** Separate authentication system (future)
4. **Admin Areas:** IP restrictions + 2FA (optional)

### **SSL Configuration**
```apache
<VirtualHost *:443>
    ServerName aci.dydact.io
    DocumentRoot /var/www/aci.dydact.io/public_html
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/aci.dydact.io.crt
    SSLCertificateKeyFile /etc/ssl/private/aci.dydact.io.key
    
    # Force HTTPS
    Header always set Strict-Transport-Security "max-age=31536000"
    
    # Protect staff portal
    <Directory /var/www/aci.dydact.io/public_html/scrive>
        Options -Indexes
        AllowOverride All
    </Directory>
</VirtualHost>
```

---

## 📊 **ANALYTICS & TRACKING**

### **Google Analytics 4**
```html
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'GA_MEASUREMENT_ID');
</script>
```

### **Staff Portal Analytics**
- Track login frequency
- Monitor feature usage
- Measure mobile vs desktop access
- Session duration tracking

---

## 🚀 **DEPLOYMENT CHECKLIST**

### **Pre-Launch**
- [ ] Migrate all public website content
- [ ] Set up SSL certificates
- [ ] Configure DNS records
- [ ] Test all navigation links
- [ ] Verify mobile responsiveness
- [ ] Test staff portal access
- [ ] Set up email forwarding
- [ ] Configure analytics

### **Launch Day**
- [ ] Update DNS to point to new server
- [ ] Monitor for 404 errors
- [ ] Test contact forms
- [ ] Verify staff can login
- [ ] Check mobile experience
- [ ] Monitor server performance

### **Post-Launch**
- [ ] Set up 301 redirects from old URLs
- [ ] Submit sitemap to Google
- [ ] Update business listings
- [ ] Monitor analytics
- [ ] Gather user feedback
- [ ] Optimize based on usage

---

## 📱 **MOBILE APP CONSIDERATIONS**

### **Future Native App Development**
1. **iOS App:** Swift-based native app for iPhone/iPad
2. **Android App:** Kotlin-based native app
3. **Features:**
   - Biometric login
   - Offline session notes
   - Push notifications
   - GPS check-in/out
   - Photo documentation

### **Current PWA Features**
- Install to home screen
- Offline capability
- Push notifications (web)
- Camera access for photos
- GPS location services

---

**This integration plan ensures a seamless experience where aci.dydact.io serves both the public-facing website and the staff portal, with mobile optimization throughout.** 