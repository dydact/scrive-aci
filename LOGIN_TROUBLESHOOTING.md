# Login Troubleshooting Guide

## Issue: Cannot login with provided credentials

### Quick Fixes to Try on Server

#### 1. Check PHP Session Configuration
Create a test file to verify PHP sessions work:

**Create: `/test_sessions.php`**
```php
<?php
session_start();
echo "PHP Version: " . phpversion() . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";

$_SESSION['test'] = 'working';
echo "Session Test: " . ($_SESSION['test'] ?? 'FAILED') . "<br>";

if (is_writable(session_save_path())) {
    echo "Session path is writable: YES<br>";
} else {
    echo "Session path is writable: NO - THIS IS THE PROBLEM<br>";
}
?>
```

**Access**: `https://aci.dydact.io/test_sessions.php`

#### 2. Fix Session Path Permissions
If sessions aren't writable:
```bash
# Check current session path
php -r "echo ini_get('session.save_path');"

# Make session directory writable
sudo chmod 777 /tmp
# OR create custom session directory
sudo mkdir -p /var/www/sessions
sudo chown www-data:www-data /var/www/sessions
sudo chmod 755 /var/www/sessions
```

#### 3. Alternative Login Test
Create a super simple test login:

**Create: `/test_login.php`**
```php
<?php
session_start();

if ($_POST['test'] ?? false) {
    $_SESSION['logged_in'] = true;
    echo "Login successful! Session working.";
    exit;
}

if ($_SESSION['logged_in'] ?? false) {
    echo "Already logged in via session.";
    exit;
}
?>
<form method="post">
    <input type="hidden" name="test" value="1">
    <button>Test Login</button>
</form>
```

#### 4. Check File Permissions
```bash
# Set correct permissions
chmod 755 autism_waiver_app/
chmod 644 autism_waiver_app/*.php
chown -R www-data:www-data autism_waiver_app/
```

#### 5. Check Apache/Nginx Error Logs
```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log
```

### Immediate Workaround: Direct Dashboard Access

If login is failing, try accessing the dashboard directly with manual session setup:

**Create: `/direct_access.php`**
```php
<?php
session_start();

// Manually set session for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['first_name'] = 'System';
$_SESSION['last_name'] = 'Administrator';
$_SESSION['access_level'] = 5;
$_SESSION['user_type'] = 'admin';

echo "Session manually set. <a href='autism_waiver_app/simple_dashboard.php'>Go to Dashboard</a>";
?>
```

### Alternative: Modify Simple Login for Debugging

Edit `simple_login.php` to add debugging:

```php
<?php
session_start();

// Add at top of file for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug Info:<br>";
echo "Session ID: " . session_id() . "<br>";
echo "POST data: " . print_r($_POST, true) . "<br>";

// ... rest of login code
```

### Server-Specific Solutions

#### If using shared hosting:
```php
// Add to top of simple_login.php
ini_set('session.save_path', '/home/yourusername/tmp/sessions');
if (!is_dir('/home/yourusername/tmp/sessions')) {
    mkdir('/home/yourusername/tmp/sessions', 0755, true);
}
```

#### If using cPanel:
1. Go to PHP Configuration
2. Set `session.save_path` to a writable directory
3. Ensure `session.auto_start` is Off

#### If using VPS/Dedicated:
```bash
# Check PHP-FPM configuration
sudo systemctl status php7.4-fpm

# Restart web services
sudo systemctl restart apache2
sudo systemctl restart php7.4-fpm
```

### Complete Alternative Login System

If all else fails, here's a session-free login:

**Create: `/cookie_login.php`**
```php
<?php
if ($_POST['username'] ?? false) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($username === 'admin' && $password === 'AdminPass123!') {
        // Set cookies instead of sessions
        setcookie('auth_user', 'admin', time() + 3600, '/');
        setcookie('auth_level', '5', time() + 3600, '/');
        header('Location: autism_waiver_app/simple_dashboard.php');
        exit;
    }
}
?>
<form method="post">
    Username: <input name="username" value="admin"><br>
    Password: <input name="password" value="AdminPass123!"><br>
    <button>Login</button>
</form>
```

Then modify dashboard to check cookies:
```php
// At top of simple_dashboard.php
if (!($_COOKIE['auth_user'] ?? false)) {
    header('Location: /cookie_login.php');
    exit;
}
```

### Debug Steps Summary

1. **Test PHP sessions** with `/test_sessions.php`
2. **Check file permissions** (755/644)
3. **View error logs** for specific errors
4. **Try direct dashboard access** with `/direct_access.php`
5. **Use cookie-based login** as fallback

### Expected Working URLs After GitHub Deploy

```
https://aci.dydact.io/autism_waiver_app/simple_login.php
https://aci.dydact.io/autism_waiver_app/simple_dashboard.php
https://aci.dydact.io/autism_waiver_app/iiss_session_note.php
https://aci.dydact.io/autism_waiver_app/treatment_plan_manager.php
https://aci.dydact.io/autism_waiver_app/schedule_manager.php
```

Once deployed from GitHub, these issues should be resolved as the file structure will be clean and proper.