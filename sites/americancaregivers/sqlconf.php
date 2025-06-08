<?php
/**
 * OpenEMR Site-specific SQL Configuration
 * 
 * This file contains the database connection settings for the
 * americancaregivers site in the Docker container environment.
 */

// Database connection settings for Docker container
$host = 'mysql';  // Docker container name for MySQL service
$port = '3306';
$login = 'openemr';
$pass = 'openemr';
$dbase = 'openemr';

// OpenEMR specific settings
$sqlconf = array();
$sqlconf["host"] = $host;
$sqlconf["port"] = $port;
$sqlconf["login"] = $login;
$sqlconf["pass"] = $pass;
$sqlconf["dbase"] = $dbase;

// Encoding settings
$sqlconf["db_encoding"] = "utf8mb4";

// Connection flags
$disable_utf8_flag = false;
$sqlconf["mysql_strict"] = false;

// Site ID (matches directory name)
$config = 1;  // This must be set to 1 for OpenEMR to work

// DO NOT MODIFY BELOW THIS LINE
// This is required for OpenEMR compatibility
$GLOBALS['OE_SITES_BASE'] = "/var/www/localhost/htdocs/openemr/sites";
$GLOBALS['OE_SITE_DIR'] = "/var/www/localhost/htdocs/openemr/sites/americancaregivers";
?>