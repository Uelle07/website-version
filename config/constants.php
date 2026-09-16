<?php
/**
 * constants.php
 * -------------------------------------------------------
 * Site-wide constants. Every other file gets its paths and
 * settings from here so nothing is ever hard-coded twice.
 *
 * If the project folder is renamed, or moved to a different
 * XAMPP setup, this is the ONLY file that needs to change.
 * -------------------------------------------------------
 */

// Prevent this file from being loaded twice
if (defined('APP_LOADED')) {
    return;
}
define('APP_LOADED', true);

// Set default application timezone to match MySQL system timezone
date_default_timezone_set('Asia/Manila');

// ---------------------------------------------------------
// Database credentials (Configured for localhost XAMPP)
// ---------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');   
define('DB_NAME', 'coop_member_portal');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ---------------------------------------------------------
// Base URL — auto-detected so it works seamlessly on localhost,
// custom domains, or InfinityFree subdomains (.infinityfreeapp.com, etc.)
// ---------------------------------------------------------
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
    || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

$protocol = $isHttps ? 'https://' : 'http://';
$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Folder the app lives in relative to the web root
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
// Clean up subfolder path to reach the project root
$projectRoot = preg_replace('#/(config|member|staff|pages|assets|uploads|database)(/.*)?$#i', '', $scriptDir);
if ($projectRoot === '/' || $projectRoot === '.') {
    $projectRoot = '';
}

define('BASE_URL', rtrim($protocol . $host . $projectRoot, '/'));

// ---------------------------------------------------------
// Filesystem paths
// ---------------------------------------------------------
define('ROOT_PATH', dirname(__DIR__));                       // .../cooperative-member-information-system
define('UPLOAD_PATH', ROOT_PATH . '/uploads');                // absolute filesystem path
define('UPLOAD_URL', BASE_URL . '/uploads');                  // browser-facing URL

// ---------------------------------------------------------
// Upload rules (used by config/upload.php)
// ---------------------------------------------------------
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/pjpeg', 'image/x-png']);

// ---------------------------------------------------------
// Roles (kept as simple string constants — matches
// `staff.role` VARCHAR column in the schema)
// ---------------------------------------------------------
define('ROLE_MEMBER', 'member');
define('ROLE_STAFF', 'staff');
define('ROLE_ADMIN', 'admin');

// ---------------------------------------------------------
// App environment — set to false before deploying so PHP
// errors are logged instead of shown on screen
// ---------------------------------------------------------
define('APP_DEBUG', true);

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_PATH . '/error.log');
}
