<?php
/**
 * Configuration file for Ghana Telecom E-commerce Platform
 * 
 * This file contains all the essential configuration settings for the application.
 * It should be included at the beginning of all PHP scripts.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not permitted');
}

// Set error reporting (should be turned off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1); // Set to 1 for development, 0 for production

// Define application constants
define('SITE_NAME', 'Ghana Telecom Services');
define('SITE_URL', 'https://yourdomain.com'); // Change to your actual domain
define('BASE_PATH', dirname(dirname(__FILE__)));
define('UPLOAD_DIR', BASE_PATH . '/uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Session configuration for security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);
session_set_cookie_params([
    'lifetime' => 3600, // 1 hour
    'path' => '/',
    'domain' => '', // Set to your domain in production
    'secure' => true, // Set to true if using HTTPS
    'httponly' => true,
    'samesite' => 'Strict'
]);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ghana_telecom');
define('DB_USER', 'root');
define('DB_PASS', '');

define('SITE_URL', 'http://localhost');

// Paystack configuration
define('PAYSTACK_SECRET_KEY', 'sk_test_your_test_secret_key_here'); // Replace with actual key
define('PAYSTACK_PUBLIC_KEY', 'pk_test_your_test_public_key_here'); // Replace with actual key
define('PAYSTACK_WEBHOOK_SECRET', 'your_webhook_secret'); // For webhook verification

// Application settings
define('CURRENCY', 'GHS');
define('CURRENCY_SYMBOL', 'GHS ');
define('MIN_PASSWORD_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 300); // 5 minutes in seconds

// Email configuration
define('ADMIN_EMAIL', 'admin@ghanatelecom.com');
define('SUPPORT_EMAIL', 'support@ghanatelecom.com');
define('EMAIL_FROM_NAME', 'Ghana Telecom Services');
define('EMAIL_FROM_ADDRESS', 'no-reply@ghanatelecom.com');

// WhatsApp configuration for result checkers
define('WHATSAPP_NUMBER', '+233123456789'); // Admin's WhatsApp number

// Timezone setting
date_default_timezone_set('Africa/Accra');

// Create necessary directories if they don't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically for security
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
    // Change session ID for security
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}