<?php
// Simple router for clean URLs
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove leading slash
$path = ltrim($path, '/');

// Route mapping
$routes = [
    '' => 'index.php',
    'about' => 'about.php',
    'services' => 'services.php',
    'careers' => 'careers.php',
    'contact' => 'contact.php',
    'resources' => 'resources.php',
    'blog' => 'blog.php',
    'gallery' => 'gallery.php',
    'privacy-policy' => 'privacy-policy.php',
    'employee-portal' => 'employee-portal.php',
    'apply-for-employment' => 'apply-for-employment.php'
];

// Check if route exists
if (array_key_exists($path, $routes)) {
    $file = $routes[$path];
    // Files are in the root directory, which is one level up from src/
    $filePath = __DIR__ . '/../' . $file;
    if (file_exists($filePath)) {
        include $filePath;
        return true;
    }
}

// Check if it's a direct file request (relative to root)
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $path)) {
    return false; // Let PHP serve the file directly
}

// Check for static assets (relative to root)
if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|pdf|txt)$/', $path)) {
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $path)) {
        return false; // Let PHP serve static files directly from root
    }
}

// 404 for unknown routes
http_response_code(404);
echo "<!DOCTYPE html>
<html>
<head>
    <title>404 - Page Not Found</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        h1 { color: #1e40af; }
    </style>
</head>
<body>
    <h1>404 - Page Not Found</h1>
    <p>The page you're looking for doesn't exist.</p>
    <a href='/'>Return to Homepage</a>
</body>
</html>";
return true;
?> 