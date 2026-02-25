<?php
/**
 * Forgot Password Page
 * 
 * Allows users to request a password reset link.
 */

// Define ABSPATH for security
define('ABSPATH', dirname(__FILE__));

// Include configuration
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Set page title
$pageTitle = 'Forgot Password';

// Initialize variables
$email = '';
$success = '';
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        
        // Validate email
        if (empty($email) || !validateEmail($email)) {
            $errors[] = "Valid email is required";
        } else {
            // Generate reset token
            $token = generatePasswordResetToken($email);
            
            if ($token) {
                // Create reset link
                $resetLink = SITE_URL . '/reset-password.php?email=' . urlencode($email) . '&token=' . urlencode($token);
                
                // Send email
                $emailContent = '
                <h2>Password Reset Request</h2>
                <p>We received a request to reset your password. Click the link below to reset your password:</p>
                <p><a href="' . $resetLink . '" class="btn">Reset Password</a></p>
                <p>This link will expire in 1 hour.</p>
                <p>If you did not request a password reset, please ignore this email.</p>
                ';
                
                $emailTemplate = generateEmailTemplate($emailContent, [
                    '{subject}' => 'Password Reset Request'
                ]);
                
                if (sendEmail($email, 'Password Reset Request', $emailTemplate)) {
                    $success = "Password reset instructions have been sent to your email address.";
                } else {
                    $errors[] = "Failed to send password reset email. Please try again.";
                }
            } else {
                $errors[] = "Email address not found. Please check and try again.";
            }
        }
    }
    
    // Regenerate CSRF token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include header
require_once 'includes/header.php';
?>

            <!-- Forgot Password Form -->
            <section class="auth-section">
                <div class="auth-container">
                    <div class="auth-header">
                        <h1>Forgot Password</h1>
                        <p>Enter your email address and we'll send you instructions to reset your password</p>
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
                    <?php else: ?>
                    <form method="POST" action="forgot-password.php" class="auth-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($email); ?>" required 
                                   placeholder="your@email.com" autofocus>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Send Reset Instructions</button>
                        
                        <div class="auth-footer">
                            <p><a href="login.php">Back to Login</a></p>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </section>

<?php
// Include footer
require_once 'includes/footer.php';
?>