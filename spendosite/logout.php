<?php
/**
 * Logout Script
 * 
 * Handles user logout by destroying the session.
 */

// Define ABSPATH for security
define('ABSPATH', dirname(__FILE__));

// Include configuration
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Log out the user
logoutUser();

// Redirect to home page with success message
$_SESSION['flash_message'] = [
    'message' => 'You have been logged out successfully.',
    'type' => 'success'
];

header('Location: index.php');
exit();