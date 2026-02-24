<?php
/**
 * Authentication and Authorization Functions
 * 
 * Handles user authentication, session management, and access control.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Check if a regular user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if admin is logged in
 * 
 * @return bool True if admin is logged in, false otherwise
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Authenticate a user
 * 
 * @param string $usernameOrEmail Username or email
 * @param string $password Password
 * @return array|bool User data on success, false on failure
 */
function authenticateUser($usernameOrEmail, $password) {
    global $pdo;
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, username, email, password_hash, phone 
                          FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }
    
    return false;
}

/**
 * Authenticate an admin user
 * 
 * @param string $username Username
 * @param string $password Password
 * @return array|bool Admin data on success, false on failure
 */
function authenticateAdmin($username, $password) {
    global $pdo;
    
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id, username, password_hash 
                          FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        return $admin;
    }
    
    return false;
}

/**
 * Register a new user
 * 
 * @param string $username Username
 * @param string $email Email
 * @param string $password Password
 * @param string $phone Phone number
 * @return array|bool User data on success, error array on failure
 */
function registerUser($username, $email, $password, $phone) {
    global $pdo;
    
    // Validate inputs
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = "Username must be 3-20 characters long and contain only letters, numbers, and underscores";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required";
    }
    
    if (empty($password) || strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = "Password must be at least " . MIN_PASSWORD_LENGTH . " characters long";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
        $errors[] = "Invalid phone number format";
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        if ($stmt->fetchColumn() > 0) {
            return ['success' => false, 'errors' => ['Username or email already exists']];
        }
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Insert new user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, phone, created_at) 
                          VALUES (?, ?, ?, ?, NOW())");
    $result = $stmt->execute([$username, $email, $passwordHash, $phone]);
    
    if ($result) {
        $userId = $pdo->lastInsertId();
        
        // Fetch the newly created user
        $stmt = $pdo->prepare("SELECT id, username, email, phone FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ['success' => true, 'user' => $user];
    }
    
    return ['success' => false, 'errors' => ['Failed to create account. Please try again.']];;
}

/**
 * Log in a user
 * 
 * @param array $user User data
 * @return bool True on success, false on failure
 */
function loginUser($user) {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['phone'] = $user['phone'];
    $_SESSION['logged_in'] = true;
    
    // Update last login time
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    return true;
}

/**
 * Log in an admin user
 * 
 * @param array $admin Admin data
 * @return bool True on success, false on failure
 */
function loginAdmin($admin) {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_logged_in'] = true;
    
    return true;
}

/**
 * Log out the current user
 * 
 * @return void
 */
function logoutUser() {
    // Unset all session variables
    $_SESSION = array();
    
    // If it's desired to kill the session, also delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Redirect to login page if not logged in
 * 
 * @param string $redirectUrl URL to redirect to after login
 * @return void
 */
function requireLogin($redirectUrl = null) {
    if (!isLoggedIn()) {
        if ($redirectUrl === null) {
            $redirectUrl = $_SERVER['REQUEST_URI'];
        }
        $_SESSION['redirect_after_login'] = $redirectUrl;
        header('Location: ../login.php');
        exit();
    }
}

/**
 * Redirect to admin login page if admin is not logged in
 * 
 * @return void
 */
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        $_SESSION['admin_redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

/**
 * Check if current user has permission for a specific action
 * 
 * @param string $permission Permission to check
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($permission) {
    // In this simple implementation, all logged-in users have the same permissions
    // In a more complex system, you might check user roles or specific permissions
    return isLoggedIn();
}

/**
 * Rate limiting for login attempts
 * 
 * @param string $ip IP address
 * @return array Rate limit information
 */
function checkLoginRateLimit($ip) {
    global $pdo;
    
    $timeWindow = LOCKOUT_TIME; // 5 minutes
    $maxAttempts = MAX_LOGIN_ATTEMPTS;
    
    // Clean up old attempts
    $stmt = $pdo->prepare("DELETE FROM login_attempts 
                          WHERE attempt_time < NOW() - INTERVAL ? SECOND");
    $stmt->execute([$timeWindow]);
    
    // Count recent attempts
    $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                          FROM login_attempts 
                          WHERE ip_address = ? AND attempt_time > NOW() - INTERVAL ? SECOND");
    $stmt->execute([$ip, $timeWindow]);
    $attempts = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $remainingAttempts = max(0, $maxAttempts - $attempts['count']);
    $isLocked = $attempts['count'] >= $maxAttempts;
    
    return [
        'attempts' => $attempts['count'],
        'remaining' => $remainingAttempts,
        'locked' => $isLocked,
        'lockout_time' => $timeWindow
    ];
}

/**
 * Record a login attempt
 * 
 * @param string $ip IP address
 * @param bool $success Whether the login was successful
 * @return void
 */
function recordLoginAttempt($ip, $success = false) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO login_attempts (ip_address, success, attempt_time) 
                          VALUES (?, ?, NOW())");
    $stmt->execute([$ip, $success ? 1 : 0]);
}

/**
 * Generate a password reset token
 * 
 * @param string $email User's email
 * @return string|bool Reset token on success, false on failure
 */
function generatePasswordResetToken($email) {
    global $pdo;
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return false;
    }
    
    // Generate token
    $token = bin2hex(random_bytes(50));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Store token
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, created_at) 
                          VALUES (?, ?, NOW())
                          ON DUPLICATE KEY UPDATE token = VALUES(token), created_at = NOW()");
    $result = $stmt->execute([$email, $token]);
    
    if ($result) {
        return $token;
    }
    
    return false;
}

/**
 * Verify a password reset token
 * 
 * @param string $email User's email
 * @param string $token Reset token
 * @return bool True if token is valid, false otherwise
 */
function verifyPasswordResetToken($email, $token) {
    global $pdo;
    
    // Check if token exists and is valid
    $stmt = $pdo->prepare("SELECT * FROM password_resets 
                          WHERE email = ? AND token = ? AND created_at > NOW() - INTERVAL 1 HOUR");
    $stmt->execute([$email, $token]);
    $reset = $stmt->fetch();
    
    return $reset ? true : false;
}

/**
 * Reset user's password
 * 
 * @param string $email User's email
 * @param string $token Reset token
 * @param string $newPassword New password
 * @return bool True on success, false on failure
 */
function resetUserPassword($email, $token, $newPassword) {
    global $pdo;
    
    // Verify token
    if (!verifyPasswordResetToken($email, $token)) {
        return false;
    }
    
    // Validate password
    if (strlen($newPassword) < MIN_PASSWORD_LENGTH) {
        return false;
    }
    
    // Hash new password
    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Update password
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
    $result = $stmt->execute([$passwordHash, $email]);
    
    if ($result) {
        // Delete used token
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);
        
        return true;
    }
    
    return false;
}