<?php
/**
 * Generate .htaccess from routes configuration
 */

require_once 'src/routes.php';

// Generate new .htaccess content
$htaccessContent = generateHtaccessRules($routes);

// Write to .htaccess file
file_put_contents('.htaccess', $htaccessContent);

echo "✅ .htaccess file generated successfully!\n";
echo "Total routes: " . count($routes) . "\n";