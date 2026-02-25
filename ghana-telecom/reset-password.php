<?php
/**
 * Reset Password Page
 * 
 * Allows users to reset their password using a token.
 */

// Define ABSPATH for security
define('ABSPATH', dirname(__FILE__));

// Include configuration
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Set page title
$pageTitle = 'Reset Password';

// Initialize variables
$email = $token = '';
$errors = [];
$success = '';

// Get email and token from query parameters
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

// Validate token
if (empty($email) || empty($token) || !verifyPasswordResetToken($email, $token)) {
    $errors[] = "Invalid or expired password reset link. Please request a new one.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) {
    // If there are errors, clear them to allow for new request
    $errors = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($newPassword) || empty($confirmPassword)) {
            $errors[] = "All fields are required";
        } elseif (strlen($newPassword) < MIN_PASSWORD_LENGTH) {
            $errors[] = "Password must be at least " . MIN_PASSWORD_LENGTH . " characters long";
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = "Passwords do not match";
        } else {
            // Reset password
            if (resetUserPassword($email, $token, $newPassword)) {
                $success = "Your password has been reset successfully. You can now login with your new password.";
            } else {
                $errors[] = "Failed to reset password. Please try again.";
            }
        }
    }
    
    // Regenerate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include header
require_once 'includes/header.php';
?>

            <!-- Reset Password Form -->
            <section class="auth-section">
                <div class="auth-container">
                    <div class="auth-header">
                        <h1>Reset Password</h1>
                        <p>Enter your new password below</p>
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
                    <div class="auth-footer" style="margin-top: 20px;">
                        <p><a href="login.php" class="btn btn-primary">Login to Your Account</a></p>
                    </div>
                    <?php else: ?>
                    <form method="POST" action="reset-password.php?email=<?php echo urlencode($email); ?>&token=<?php echo urlencode($token); ?>" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required 
                                   placeholder="At least 8 characters">
                            <small class="form-hint">Must be at least <?php echo MIN_PASSWORD_LENGTH; ?> characters long</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                                   placeholder="Confirm your new password">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                        
                        <div class="auth-footer">
                            <p><a href="forgot-password.php">Request a new reset link</a></p>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </section>

<?php
// Include footer
require_once 'includes/footer.php';
?>