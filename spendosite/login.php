<?php
/**
 * User Login Page
 * 
 * Allows existing users to log in to their accounts.
 */

// Define ABSPATH for security
define('ABSPATH', dirname(__FILE__));

// Include configuration
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Set page title
$pageTitle = 'Login';

// Initialize variables
$email = '';
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        $usernameOrEmail = sanitizeInput($_POST['username_or_email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Rate limiting
        $ip = getClientIp();
        $rateLimit = checkLoginRateLimit($ip);
        
        if ($rateLimit['locked']) {
            $errors[] = "Too many login attempts. Please try again in " . ($rateLimit['lockout_time'] / 60) . " minutes.";
        } else {
            // Validate inputs
            if (empty($usernameOrEmail) || empty($password)) {
                $errors[] = "Username/email and password are required";
            } else {
                // Attempt to authenticate
                $user = authenticateUser($usernameOrEmail, $password);
                
                if ($user) {
                    // Login successful
                    loginUser($user);
                    recordLoginAttempt($ip, true);
                    
                    // Redirect to dashboard or previous page
                    $redirectUrl = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                    unset($_SESSION['redirect_after_login']);
                    
                    header('Location: ' . $redirectUrl);
                    exit();
                } else {
                    // Login failed
                    $errors[] = "Invalid username/email or password";
                    recordLoginAttempt($ip, false);
                }
            }
        }
    }
    
    // Regenerate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include header
require_once 'includes/header.php';
?>

            <!-- Login Form -->
            <section class="auth-section">
                <div class="auth-container">
                    <div class="auth-header">
                        <h1>Welcome Back</h1>
                        <p>Log in to your Ghana Telecom Services account</p>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="login.php" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label for="username_or_email">Username or Email</label>
                            <input type="text" id="username_or_email" name="username_or_email" class="form-control" required 
                                   placeholder="Enter your username or email" autofocus>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required 
                                   placeholder="Enter your password">
                        </div>
                        
                        <div class="form-options">
                            <div class="form-checkbox">
                                <input type="checkbox" id="remember" name="remember">
                                <label for="remember">Remember me</label>
                            </div>
                            <a href="forgot-password.php" class="form-link">Forgot password?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Log In</button>
                        
                        <div class="auth-footer">
                            <p>Don't have an account? <a href="register.php">Register here</a></p>
                            <p>Or <a href="index.php">continue as guest</a></p>
                        </div>
                    </form>
                </div>
            </section>

<?php
// Include footer
require_once 'includes/footer.php';
?>