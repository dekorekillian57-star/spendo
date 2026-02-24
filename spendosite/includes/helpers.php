<?php
/**
 * Helper Functions
 * 
 * Contains utility functions used throughout the application.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not permitted');
}

/**
 * Sanitize user input
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    // Remove HTML tags and encode special characters
    $sanitized = htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
    
    // Additional sanitization based on context
    $sanitized = trim($sanitized);
    $sanitized = stripslashes($sanitized);
    
    return $sanitized;
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Ghana format)
 * 
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function validatePhone($phone) {
    // Ghana phone number format: +233 or 0 followed by 9 digits
    return preg_match('/^(?:\+233|0)[0-9]{9}$/', $phone) === 1;
}

/**
 * Format price with currency symbol
 * 
 * @param float $price Price to format
 * @return string Formatted price
 */
function formatPrice($price) {
    return CURRENCY_SYMBOL . number_format($price, 2);
}

/**
 * Generate a unique order ID
 * 
 * @return string Unique order ID
 */
function generateOrderId() {
    $prefix = 'ORD-';
    $timestamp = date('YmdHis');
    $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    
    return $prefix . $timestamp . '-' . $random;
}

/**
 * Get package type name
 * 
 * @param string $type Package type
 * @return string Human-readable package type name
 */
function getPackageTypeName($type) {
    $types = [
        'data' => 'Data Bundle',
        'airtime' => 'Airtime',
        'cable' => 'Cable TV Subscription',
        'result_checker' => 'Result Checker',
        'afa' => 'AFA Registration'
    ];
    
    return $types[$type] ?? ucfirst($type);
}

/**
 * Get network name with logo
 * 
 * @param string $network Network name
 * @return string HTML with network name and logo
 */
function getNetworkWithLogo($network) {
    if (empty($network)) {
        return '';
    }
    
    $network = strtolower($network);
    $logoPath = '../images/' . str_replace(' ', '', $network) . '.png';
    $altText = ucfirst($network) . ' Logo';
    
    return '<img src="' . $logoPath . '" alt="' . $altText . '" class="network-logo"> ' . ucfirst($network);
}

/**
 * Format date in a human-readable format
 * 
 * @param string $date Date string
 * @param string $format Output format (default: M j, Y g:i a)
 * @return string Formatted date
 */
function formatDate($date, $format = 'M j, Y g:i a') {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Redirect to a URL with optional message
 * 
 * @param string $url URL to redirect to
 * @param string $message Optional message to display
 * @param string $type Message type (success, error, info)
 * @return void
 */
function redirect($url, $message = '', $type = 'success') {
    if (!empty($message)) {
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    header('Location: ' . $url);
    exit();
}

/**
 * Display flash messages
 * 
 * @return void
 */
function displayFlashMessages() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        echo '<div class="flash-message ' . $message['type'] . '">';
        echo '<div class="container">';
        echo '<p>' . htmlspecialchars($message['message']) . '</p>';
        echo '<button class="close-flash">&times;</button>';
        echo '</div>';
        echo '</div>';
    }
}

/**
 * Get client IP address
 * 
 * @return string Client IP address
 */
function getClientIp() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Validate IP address
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    return '127.0.0.1'; // Fallback to localhost
}

/**
 * Send email
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message (HTML)
 * @param array $headers Additional headers
 * @return bool True on success, false on failure
 */
function sendEmail($to, $subject, $message, $headers = []) {
    // Set default headers
    $defaultHeaders = [
        'From' => EMAIL_FROM_NAME . ' <' . EMAIL_FROM_ADDRESS . '>',
        'Reply-To' => SUPPORT_EMAIL,
        'X-Mailer' => 'PHP/' . phpversion(),
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    
    // Merge custom headers
    $allHeaders = array_merge($defaultHeaders, $headers);
    $headerString = '';
    
    foreach ($allHeaders as $key => $value) {
        $headerString .= $key . ': ' . $value . "\r\n";
    }
    
    // Log email for debugging (in production, this should be more sophisticated)
    error_log("Email sent to: $to\nSubject: $subject\nMessage: " . strip_tags($message));
    
    // In a real application, you'd use a proper email library like PHPMailer
    // For this example, we'll use the built-in mail() function
    return mail($to, $subject, $message, $headerString);
}

/**
 * Generate email template
 * 
 * @param string $content Email content
 * @param array $replacements Key-value pairs for template replacements
 * @return string Complete email HTML
 */
function generateEmailTemplate($content, $replacements = []) {
    // Default replacements
    $defaultReplacements = [
        '{site_name}' => SITE_NAME,
        '{site_url}' => SITE_URL,
        '{year}' => date('Y'),
        '{logo_url}' => SITE_URL . '/images/logo.png'
    ];
    
    // Merge replacements
    $replacements = array_merge($defaultReplacements, $replacements);
    
    // Apply replacements
    foreach ($replacements as $key => $value) {
        $content = str_replace($key, $value, $content);
    }
    
    // Email template structure
    $template = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $replacements['{subject}'] . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .email-header {
                text-align: center;
                padding-bottom: 20px;
                border-bottom: 1px solid #eee;
            }
            .email-logo {
                max-width: 200px;
            }
            .email-content {
                padding: 20px 0;
            }
            .email-footer {
                text-align: center;
                padding-top: 20px;
                border-top: 1px solid #eee;
                color: #777;
                font-size: 12px;
            }
            .btn {
                display: inline-block;
                background-color: #1a73e8;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 5px;
                margin: 10px 0;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="email-header">
                <img src="{logo_url}" alt="' . SITE_NAME . '" class="email-logo">
            </div>
            
            <div class="email-content">
                ' . $content . '
            </div>
            
            <div class="email-footer">
                &copy; {year} ' . SITE_NAME . '. All rights reserved.<br>
                <a href="{site_url}">' . SITE_NAME . '</a>
            </div>
        </div>
    </body>
    </html>
    ';
    
    // Apply replacements to template
    foreach ($replacements as $key => $value) {
        $template = str_replace($key, $value, $template);
    }
    
    return $template;
}

/**
 * Validate CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get user's cart items
 * 
 * @return array Cart items
 */
function getCartItems() {
    $cartItems = [];
    
    if (isLoggedIn()) {
        // Get cart items from database for logged-in users
        global $pdo;
        $userId = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.type, p.network 
                              FROM user_cart c
                              JOIN packages p ON c.package_id = p.id
                              WHERE c.user_id = ?
                              ORDER BY c.added_at DESC");
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        // Get cart items from session for guests
        global $pdo;
        
        $cartItems = [];
        foreach ($_SESSION['cart'] as $itemId => $item) {
            $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
            $stmt->execute([$item['package_id']]);
            $package = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($package) {
                $cartItems[] = [
                    'id' => $itemId,
                    'package_id' => $item['package_id'],
                    'quantity' => $item['quantity'],
                    'recipients' => $item['recipients'],
                    'name' => $package['name'],
                    'price' => $package['price'],
                    'type' => $package['type'],
                    'network' => $package['network']
                ];
            }
        }
    }
    
    return $cartItems;
}

/**
 * Calculate cart total
 * 
 * @param array $cartItems Cart items
 * @return float Total price
 */
function calculateCartTotal($cartItems) {
    $total = 0;
    
    foreach ($cartItems as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    return $total;
}

/**
 * Get package by ID
 * 
 * @param int $packageId Package ID
 * @return array|bool Package data or false if not found
 */
function getPackageById($packageId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM packages WHERE id = ?");
    $stmt->execute([$packageId]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $package ?: false;
}

/**
 * Add item to cart
 * 
 * @param int $packageId Package ID
 * @param int $quantity Quantity
 * @param array $recipients Recipient details
 * @return bool True on success, false on failure
 */
function addToCart($packageId, $quantity = 1, $recipients = []) {
    $package = getPackageById($packageId);
    
    if (!$package) {
        return false;
    }
    
    if (isLoggedIn()) {
        // Add to database for logged-in users
        global $pdo;
        $userId = $_SESSION['user_id'];
        
        // Check if item already exists in cart
        $stmt = $pdo->prepare("SELECT id FROM user_cart 
                              WHERE user_id = ? AND package_id = ?");
        $stmt->execute([$userId, $packageId]);
        $existingItem = $stmt->fetch();
        
        if ($existingItem) {
            // Update existing item
            $stmt = $pdo->prepare("UPDATE user_cart 
                                  SET quantity = quantity + ?, recipients = ?
                                  WHERE user_id = ? AND package_id = ?");
            return $stmt->execute([$quantity, json_encode($recipients), $userId, $packageId]);
        } else {
            // Add new item
            $stmt = $pdo->prepare("INSERT INTO user_cart (user_id, package_id, quantity, recipients) 
                                  VALUES (?, ?, ?, ?)");
            return $stmt->execute([$userId, $packageId, $quantity, json_encode($recipients)]);
        }
    } else {
        // Add to session for guests
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Generate a unique ID for this cart item
        $itemId = $packageId . '_' . uniqid();
        
        $_SESSION['cart'][$itemId] = [
            'package_id' => $packageId,
            'quantity' => $quantity,
            'recipients' => $recipients
        ];
        
        return true;
    }
}

/**
 * Remove item from cart
 * 
 * @param string $itemId Item ID
 * @return bool True on success, false on failure
 */
function removeFromCart($itemId) {
    if (isLoggedIn()) {
        // Remove from database for logged-in users
        global $pdo;
        $userId = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("DELETE FROM user_cart 
                              WHERE user_id = ? AND id = ?");
        return $stmt->execute([$userId, $itemId]);
    } else {
        // Remove from session for guests
        if (isset($_SESSION['cart'][$itemId])) {
            unset($_SESSION['cart'][$itemId]);
            return true;
        }
        
        return false;
    }
}

/**
 * Update cart item quantity
 * 
 * @param string $itemId Item ID
 * @param int $quantity New quantity
 * @return bool True on success, false on failure
 */
function updateCartItemQuantity($itemId, $quantity) {
    if ($quantity < 1) {
        return removeFromCart($itemId);
    }
    
    if (isLoggedIn()) {
        // Update database for logged-in users
        global $pdo;
        $userId = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("UPDATE user_cart 
                              SET quantity = ? 
                              WHERE user_id = ? AND id = ?");
        return $stmt->execute([$quantity, $userId, $itemId]);
    } else {
        // Update session for guests
        if (isset($_SESSION['cart'][$itemId])) {
            $_SESSION['cart'][$itemId]['quantity'] = $quantity;
            return true;
        }
        
        return false;
    }
}

/**
 * Clear cart
 * 
 * @return bool True on success, false on failure
 */
function clearCart() {
    if (isLoggedIn()) {
        // Clear database cart for logged-in users
        global $pdo;
        $userId = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("DELETE FROM user_cart WHERE user_id = ?");
        return $stmt->execute([$userId]);
    } else {
        // Clear session cart for guests
        $_SESSION['cart'] = [];
        return true;
    }
}

/**
 * Process order
 * 
 * @param array $cartItems Cart items
 * @param array $userData User data (for guests)
 * @param string $paymentRef Payment reference
 * @return bool|array Order ID on success, error array on failure
 */
function processOrder($cartItems, $userData = [], $paymentRef) {
    global $pdo;
    
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        $orderId = generateOrderId();
        $totalPrice = 0;
        
        // Process each cart item
        foreach ($cartItems as $item) {
            $package = getPackageById($item['package_id']);
            if (!$package) {
                throw new Exception("Package not found: " . $item['package_id']);
            }
            
            $itemTotal = $package['price'] * $item['quantity'];
            $totalPrice += $itemTotal;
            
            // Insert order
            $stmt = $pdo->prepare("INSERT INTO orders (
                user_id, order_id, package_type, package_id, quantity, 
                recipients, total_price, status, payment_ref
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            
            $userId = isLoggedIn() ? $_SESSION['user_id'] : null;
            $recipientsJson = json_encode($item['recipients']);
            
            $success = $stmt->execute([
                $userId,
                $orderId,
                $package['type'],
                $package['id'],
                $item['quantity'],
                $recipientsJson,
                $itemTotal,
                $paymentRef
            ]);
            
            if (!$success) {
                throw new Exception("Failed to create order for package: " . $package['name']);
            }
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Clear cart
        clearCart();
        
        return [
            'success' => true,
            'order_id' => $orderId,
            'total_price' => $totalPrice
        ];
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollBack();
        
        error_log("Order processing failed: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get order by ID
 * 
 * @param string $orderId Order ID
 * @return array|bool Order data or false if not found
 */
function getOrderByOrderId($orderId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT o.*, p.name as package_name, u.username 
                          FROM orders o
                          LEFT JOIN packages p ON o.package_id = p.id
                          LEFT JOIN users u ON o.user_id = u.id
                          WHERE o.order_id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        // Parse recipients JSON
        $order['recipients'] = json_decode($order['recipients'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $order['recipients'] = [];
        }
    }
    
    return $order ?: false;
}

/**
 * Get orders by user ID
 * 
 * @param int $userId User ID
 * @return array Orders
 */
function getOrdersByUserId($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT o.*, p.name as package_name 
                          FROM orders o
                          JOIN packages p ON o.package_id = p.id
                          WHERE o.user_id = ?
                          ORDER BY o.created_at DESC");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse recipients JSON for each order
    foreach ($orders as &$order) {
        $order['recipients'] = json_decode($order['recipients'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $order['recipients'] = [];
        }
    }
    
    return $orders;
}

/**
 * Get order status class
 * 
 * @param string $status Order status
 * @return string CSS class for status
 */
function getOrderStatusClass($status) {
    switch ($status) {
        case 'pending':
            return 'status-pending';
        case 'processing':
            return 'status-processing';
        case 'completed':
            return 'status-completed';
        case 'failed':
            return 'status-failed';
        default:
            return 'status-pending';
    }
}

/**
 * Get order status label
 * 
 * @param string $status Order status
 * @return string Status label
 */
function getOrderStatusLabel($status) {
    switch ($status) {
        case 'pending':
            return 'Pending';
        case 'processing':
            return 'Processing';
        case 'completed':
            return 'Completed';
        case 'failed':
            return 'Failed';
        default:
            return 'Pending';
    }
}