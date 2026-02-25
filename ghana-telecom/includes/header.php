<?php
/**
 * Common Header Template
 * 
 * Contains the HTML header, navigation, and top elements for all user-facing pages.
 * Should be included at the beginning of all user-facing PHP files.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not permitted');
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page name for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' | ' : ''; ?>Ghana Telecom Services</title>
    
    <!-- Favicon -->
    <link rel="icon" href="../images/favicon.ico" type="image/x-icon">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../css/style.css">
    
    <!-- Google Fonts (for production, consider self-hosting) -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Meta Tags -->
    <meta name="description" content="Buy mobile data bundles, airtime, cable TV subscriptions, and more in Ghana">
    <meta name="keywords" content="Ghana, telecom, mobile data, airtime, cable TV, DSTV, Startimes, BECE, WASSCE">
    <meta name="author" content="Ghana Telecom Services">
    
    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:title" content="Ghana Telecom Services">
    <meta property="og:description" content="Buy mobile data bundles, airtime, cable TV subscriptions, and more in Ghana">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/images/logo.png">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Ghana Telecom Services">
    <meta name="twitter:description" content="Buy mobile data bundles, airtime, cable TV subscriptions, and more in Ghana">
    <meta name="twitter:image" content="<?php echo SITE_URL; ?>/images/logo.png">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
</head>
<body>
    <!-- Skip to content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <!-- Preloader (optional) -->
    <div id="preloader" class="preloader">
        <div class="spinner"></div>
    </div>
    
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-top">
                <div class="logo">
                    <a href="../index.php">
                        <img src="../images/logo.png" alt="Ghana Telecom Services Logo">
                        <span>Ghana Telecom</span>
                    </a>
                </div>
                
                <div class="header-actions">
                    <?php if (isLoggedIn()): ?>
                        <a href="../dashboard.php" class="btn btn-primary header-btn">
                            <i class="fas fa-user"></i> Dashboard
                        </a>
                        <a href="../logout.php" class="btn btn-outline header-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="../login.php" class="btn btn-outline header-btn">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="../register.php" class="btn btn-primary header-btn">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    <?php endif; ?>
                </div>
                
                <button class="mobile-menu-toggle" aria-label="Toggle navigation">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <nav class="main-navigation">
                <ul class="nav-menu">
                    <li><a href="../index.php" <?php echo $currentPage === 'index.php' ? 'class="active"' : ''; ?>>Home</a></li>
                    <li><a href="../shop.php" <?php echo $currentPage === 'shop.php' ? 'class="active"' : ''; ?>>Shop</a></li>
                    <li><a href="../#data-bundles" <?php echo strpos($currentPage, 'index.php') !== false ? 'class="active"' : ''; ?>>Data Bundles</a></li>
                    <li><a href="../#cable-tv" <?php echo strpos($currentPage, 'index.php') !== false ? 'class="active"' : ''; ?>>Cable TV</a></li>
                    <li><a href="../#result-checkers" <?php echo strpos($currentPage, 'index.php') !== false ? 'class="active"' : ''; ?>>Result Checkers</a></li>
                    <li><a href="../#afa-registration" <?php echo strpos($currentPage, 'index.php') !== false ? 'class="active"' : ''; ?>>AFA Registration</a></li>
                    <li><a href="../contact.php" <?php echo $currentPage === 'contact.php' ? 'class="active"' : ''; ?>>Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>
    
    <!-- Announcement Bar -->
    <div class="announcement-bar">
        <div class="container">
            <i class="fas fa-exclamation-circle"></i>
            <span>IMPORTANT: Data bundles DO NOT SUPPORT: Turbonet SIM, Merchant SIM, EVD SIM, Broadband SIM, Blacklist SIM, Roaming SIM, Different Network, Wrong Number, Inactive Number.</span>
        </div>
    </div>
    
    <!-- Main Content -->
    <main id="main-content">
        <div class="container">