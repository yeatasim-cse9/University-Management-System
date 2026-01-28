<?php
/**
 * Application Settings
 * ACADEMIX - Academic Management System
 */

// Application paths
define('BASE_PATH', dirname(__DIR__));
define('ASSETS_PATH', BASE_PATH . '/assets');
define('UPLOADS_PATH', ASSETS_PATH . '/uploads');
define('LOGS_PATH', BASE_PATH . '/logs');

// URL paths
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . '://' . $host . '/academix';

define('BASE_URL', $base_url);
define('ASSETS_URL', BASE_URL . '/assets');
define('UPLOADS_URL', ASSETS_URL . '/uploads');

// Application settings
define('APP_NAME', 'ACADEMIX');
define('UNIVERSITY_NAME', 'University of Barishal');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'Asia/Dhaka');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', LOGS_PATH . '/php_errors.log');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Upload settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']);

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes in seconds
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('REMEMBER_ME_DURATION', 604800); // 7 days in seconds

// Pagination
define('RECORDS_PER_PAGE', 20);

// Attendance settings
define('ATTENDANCE_REQUIRED_PERCENTAGE', 75);
define('ATTENDANCE_EDIT_WINDOW', 24); // hours
