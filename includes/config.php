<?php
// Database configuration for InfinityFree
define('DB_HOST', 'sql307.infinityfree.com');
define('DB_USER', 'if0_38639878');
define('DB_PASS', 'GuE3NI4tkoeD');
define('DB_NAME', 'if0_38639878_monitor');

// Site configuration
define('SITE_NAME', 'Uptime Monitor');
define('BASE_URL', 'https://monitor.gbsm-support.infy.uk');

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include other required files
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
