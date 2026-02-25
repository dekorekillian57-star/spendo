<?php
/**
 * User Registration Page
 * 
 * Allows new users to create an account on the platform.
 */

// Define ABSPATH for security
define('ABSPATH', dirname(__FILE__));

// Include configuration
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Set page title
$pageTitle = 'Register';

// Initialize variables
$username = $email = $phone = '';
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $phone = sanitizeInput($_POST['phone'] ?? '');
        
        // Validate inputs
        if (empty($username)) {
            $errors[] = "Username is required";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $errors[] = "Username must be 3-20 characters long and contain only letters, numbers, and underscores";
        }
        
        if (empty($email) || !validateEmail($email)) {
            $errors[] = "Valid email is required";
        }
        
        if (empty($password) || strlen($password) < MIN_PASSWORD_LENGTH) {
            $errors[] = "Password must be at least " . MIN_PASSWORD_LENGTH . " characters long";
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match";
        }
        
        if (empty($phone)) {
            $errors[] = "Phone number is required";
        } elseif (!validatePhone($phone)) {
            $errors[] = "Invalid phone number format. Please use Ghana format (e.g., +233 or 0 followed by 9 digits)";
        }
        
        // If no errors, try to register user
        if (empty($errors)) {
            $result = registerUser($username, $email, $password, $phone);
            
            if ($result['success']) {
                $success = "Registration successful! Please login to continue.";
                
                // Redirect to login after a short delay
                header("Refresh: 3; url=login.php");
            } else {
                $errors = array_merge($errors, $result['errors']);
            }
        }
    }
    
    // Regenerate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include header
require_once 'includes/header.php';
?>

            <!-- Registration Form -->
            <section class="auth-section">
                <div class="auth-container">
                    <div class="auth-header">
                        <h1>Create Your Account</h1>
                        <p>Join Ghana Telecom Services today and enjoy fast, reliable telecom services</p>
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
                    
                    <form method="POST" action="register.php" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?php echo htmlspecialchars($username); ?>" required 
                                   placeholder="Choose a username">
                            <small class="form-hint">3-20 characters, letters, numbers, and underscores only</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($email); ?>" required 
                                   placeholder="your@email.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($phone); ?>" required 
                                   placeholder="+233 or 0 followed by 9 digits">
                            <small class="form-hint">Used for service delivery and account verification</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required 
                                   placeholder="At least 8 characters">
                            <small class="form-hint">Must be at least <?php echo MIN_PASSWORD_LENGTH; ?> characters long</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                                   placeholder="Confirm your password">
                        </div>
                        
                        <div class="form-group">
                            <div class="form-checkbox">
                                <input type="checkbox" id="terms" name="terms" required>
                                <label for="terms">
                                    I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Create Account</button>
                        
                        <div class="auth-footer">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                            <p>Or <a href="index.php">continue as guest</a></p>
                        </div>
                    </form>
                </div>
            </section>

<?php
// Include footer
require_once 'includes/footer.php';
?>