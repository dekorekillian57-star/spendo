<?php
/**
 * User Dashboard Page
 * 
 * Displays user's order history, account information, and allows
 * for account management.
 */

// Define ABSPATH for security
define('ABSPATH', dirname(__FILE__));

// Include configuration
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Require login
requireLogin('login.php');

// Set page title
$pageTitle = 'Dashboard';

// Get user's orders
$orders = getOrdersByUserId($_SESSION['user_id']);

// Handle profile update
$profileSuccess = $profileError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $profileError = "Invalid request. Please try again.";
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        
        // Validate inputs
        if (empty($username)) {
            $profileError = "Username is required";
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $profileError = "Username must be 3-20 characters long and contain only letters, numbers, and underscores";
        }
        
        if (empty($email) || !validateEmail($email)) {
            $profileError = "Valid email is required";
        }
        
        if (empty($phone)) {
            $profileError = "Phone number is required";
        } elseif (!validatePhone($phone)) {
            $profileError = "Invalid phone number format";
        }
        
        // Check if username or email already exists (for other users)
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $_SESSION['user_id']]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            $profileError = "Username or email already exists";
        }
        
        // Update profile if no errors
        if (empty($profileError)) {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, phone = ? WHERE id = ?");
            $result = $stmt->execute([$username, $email, $phone, $_SESSION['user_id']]);
            
            if ($result) {
                // Update session data
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['phone'] = $phone;
                
                $profileSuccess = "Profile updated successfully!";
                // Regenerate CSRF token
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $profileError = "Failed to update profile. Please try again.";
            }
        }
    }
}

// Handle password change
$passwordSuccess = $passwordError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $passwordError = "Invalid request. Please try again.";
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $passwordError = "All password fields are required";
        } elseif (strlen($newPassword) < MIN_PASSWORD_LENGTH) {
            $passwordError = "New password must be at least " . MIN_PASSWORD_LENGTH . " characters long";
        } elseif ($newPassword !== $confirmPassword) {
            $passwordError = "New passwords do not match";
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($currentPassword, $user['password_hash'])) {
                // Hash new password
                $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                
                // Update password
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $result = $stmt->execute([$passwordHash, $_SESSION['user_id']]);
                
                if ($result) {
                    $passwordSuccess = "Password changed successfully!";
                    // Regenerate CSRF token
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $passwordError = "Failed to change password. Please try again.";
                }
            } else {
                $passwordError = "Current password is incorrect";
            }
        }
    }
}

// Include header
require_once 'includes/header.php';
?>

            <!-- Dashboard Page -->
            <section class="dashboard-section">
                <div class="container">
                    <h1>Dashboard</h1>
                    
                    <div class="dashboard-grid">
                        <!-- Sidebar Navigation -->
                        <div class="dashboard-sidebar">
                            <ul class="sidebar-menu">
                                <li class="active"><a href="#overview"><i class="fas fa-home"></i> Overview</a></li>
                                <li><a href="#orders"><i class="fas fa-shopping-bag"></i> Order History</a></li>
                                <li><a href="#profile"><i class="fas fa-user"></i> Profile Settings</a></li>
                                <li><a href="#password"><i class="fas fa-lock"></i> Change Password</a></li>
                                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </div>
                        
                        <!-- Main Content -->
                        <div class="dashboard-content">
                            <!-- Overview Section -->
                            <section id="overview" class="dashboard-section-content active">
                                <h2>Account Overview</h2>
                                
                                <div class="overview-grid">
                                    <div class="overview-card">
                                        <div class="card-icon" style="background-color: #1a73e8;">
                                            <i class="fas fa-shopping-bag"></i>
                                        </div>
                                        <div class="card-content">
                                            <h3><?php echo count($orders); ?></h3>
                                            <p>Total Orders</p>
                                        </div>
                                    </div>
                                    
                                    <div class="overview-card">
                                        <div class="card-icon" style="background-color: #388e3c;">
                                            <i class="fas fa-check-circle"></i>
                                        </div>
                                        <div class="card-content">
                                            <h3><?php 
                                                $completedOrders = array_filter($orders, function($order) {
                                                    return $order['status'] === 'completed';
                                                });
                                                echo count($completedOrders);
                                            ?></h3>
                                            <p>Completed Orders</p>
                                        </div>
                                    </div>
                                    
                                    <div class="overview-card">
                                        <div class="card-icon" style="background-color: #ffa000;">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="card-content">
                                            <h3><?php 
                                                $pendingOrders = array_filter($orders, function($order) {
                                                    return $order['status'] === 'pending' || $order['status'] === 'processing';
                                                });
                                                echo count($pendingOrders);
                                            ?></h3>
                                            <p>Pending Orders</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="welcome-section">
                                    <div class="welcome-user">
                                        <div class="user-avatar">
                                            <?php 
                                                $initials = '';
                                                if (!empty($_SESSION['username'])) {
                                                    $names = explode(' ', $_SESSION['username']);
                                                    $initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));
                                                }
                                                echo $initials;
                                            ?>
                                        </div>
                                        <div class="user-info">
                                            <h2>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
                                            <p>Member since <?php echo formatDate($_SESSION['created_at'], 'F Y'); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="user-contact">
                                        <h3>Contact Information</h3>
                                        <div class="contact-info">
                                            <div class="info-row">
                                                <span class="label"><i class="fas fa-envelope"></i> Email:</span>
                                                <span class="value"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="label"><i class="fas fa-phone"></i> Phone:</span>
                                                <span class="value"><?php echo htmlspecialchars($_SESSION['phone']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                            
                            <!-- Orders Section -->
                            <section id="orders" class="dashboard-section-content">
                                <h2>Order History</h2>
                                
                                <?php if (empty($orders)): ?>
                                <div class="empty-state">
                                    <div class="empty-state-icon">🛒</div>
                                    <h3 class="empty-state-title">No orders found</h3>
                                    <p class="empty-state-text">You haven't placed any orders yet. Start shopping to see your order history here.</p>
                                    <a href="shop.php" class="btn btn-primary">Start Shopping</a>
                                </div>
                                <?php else: ?>
                                <div class="orders-list">
                                    <?php foreach ($orders as $order): ?>
                                    <div class="order-card">
                                        <div class="order-header">
                                            <div class="order-id">Order #<?php echo htmlspecialchars($order['order_id']); ?></div>
                                            <span class="order-date"><?php echo formatDate($order['created_at']); ?></span>
                                        </div>
                                        
                                        <div class="order-details">
                                            <div class="order-package">
                                                <span class="package-name"><?php echo htmlspecialchars($order['package_name']); ?></span>
                                                <span class="package-quantity">x <?php echo $order['quantity']; ?></span>
                                            </div>
                                            
                                            <div class="order-status <?php echo getOrderStatusClass($order['status']); ?>">
                                                <?php echo getOrderStatusLabel($order['status']); ?>
                                            </div>
                                            
                                            <div class="order-amount">
                                                <?php echo formatPrice($order['total_price']); ?>
                                            </div>
                                        </div>
                                        
                                        <div class="order-actions">
                                            <a href="order-tracking.php?order_id=<?php echo urlencode($order['order_id']); ?>" 
                                               class="btn btn-outline">
                                                <i class="fas fa-eye"></i> View Details
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </section>
                            
                            <!-- Profile Section -->
                            <section id="profile" class="dashboard-section-content">
                                <h2>Profile Settings</h2>
                                
                                <?php if ($profileSuccess): ?>
                                <div class="alert alert-success">
                                    <?php echo htmlspecialchars($profileSuccess); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($profileError): ?>
                                <div class="alert alert-danger">
                                    <?php echo htmlspecialchars($profileError); ?>
                                </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="dashboard.php#profile" class="profile-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="update_profile" value="1">
                                    
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" id="username" name="username" class="form-control" 
                                               value="<?php echo htmlspecialchars($_SESSION['username']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" id="email" name="email" class="form-control" 
                                               value="<?php echo htmlspecialchars($_SESSION['email']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" id="phone" name="phone" class="form-control" 
                                               value="<?php echo htmlspecialchars($_SESSION['phone']); ?>" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </form>
                            </section>
                            
                            <!-- Password Section -->
                            <section id="password" class="dashboard-section-content">
                                <h2>Change Password</h2>
                                
                                <?php if ($passwordSuccess): ?>
                                <div class="alert alert-success">
                                    <?php echo htmlspecialchars($passwordSuccess); ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($passwordError): ?>
                                <div class="alert alert-danger">
                                    <?php echo htmlspecialchars($passwordError); ?>
                                </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="dashboard.php#password" class="password-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="change_password" value="1">
                                    
                                    <div class="form-group">
                                        <label for="current_password">Current Password</label>
                                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                                        <small class="form-hint">Must be at least <?php echo MIN_PASSWORD_LENGTH; ?> characters long</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Change Password</button>
                                </form>
                            </section>
                        </div>
                    </div>
                </div>
            </section>

<?php
// Include footer
require_once 'includes/footer.php';
?>

<script>
    // Dashboard tab navigation
    document.addEventListener('DOMContentLoaded', function() {
        const menuItems = document.querySelectorAll('.sidebar-menu li');
        const sections = document.querySelectorAll('.dashboard-section-content');
        
        // Set up click handlers for menu items
        menuItems.forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all items
                menuItems.forEach(i => i.classList.remove('active'));
                
                // Add active class to clicked item
                this.classList.add('active');
                
                // Get target section
                const target = this.querySelector('a').getAttribute('href');
                
                // Hide all sections
                sections.forEach(section => {
                    section.classList.remove('active');
                });
                
                // Show target section
                document.querySelector(target).classList.add('active');
                
                // Scroll to top of section
                window.scrollTo({
                    top: document.querySelector(target).offsetTop - 100,
                    behavior: 'smooth'
                });
            });
        });
        
        // Check for hash in URL and activate corresponding section
        const hash = window.location.hash;
        if (hash) {
            const targetSection = document.querySelector(`.dashboard-section-content${hash}`);
            if (targetSection) {
                // Activate menu item
                const menuItem = document.querySelector(`.sidebar-menu li a[href="${hash}"]`).parentElement;
                menuItems.forEach(i => i.classList.remove('active'));
                menuItem.classList.add('active');
                
                // Show section
                sections.forEach(section => {
                    section.classList.remove('active');
                });
                targetSection.classList.add('active');
            }
        }
    });
</script>