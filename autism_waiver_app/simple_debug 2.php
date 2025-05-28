<?php
require_once 'auth.php';
initScriveAuth();

echo "<h2>Simple Table Check</h2>";

// Test method 1
$test1 = sqlQuery("SHOW TABLES LIKE 'autism_client_enrollments'");
echo "<p>SHOW TABLES result: ";
var_dump($test1);
echo "</p>";

// Test method 2
$test2 = sqlQuery("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'autism_client_enrollments'");
echo "<p>Information schema result: ";
var_dump($test2);
echo "</p>";

// Test method 3 - using try/catch
try {
    $test3 = sqlQuery("SELECT 1 FROM autism_client_enrollments LIMIT 1");
    echo "<p>Direct table access: SUCCESS (table exists)</p>";
} catch (Exception $e) {
    echo "<p>Direct table access: FAILED (table doesn't exist) - " . htmlspecialchars($e->getMessage()) . "</p>";
}

?> 