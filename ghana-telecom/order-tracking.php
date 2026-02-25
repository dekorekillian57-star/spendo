<?php
/**
 * Order Tracking Page
 * 
 * Allows users (both guests and registered) to track their orders
 * using order ID, phone number, smart card number, or transaction reference.
 */

// Define ABSPATH for security
define('ABSPATH', dirname(__FILE__));

// Include configuration
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Set page title
$pageTitle = 'Order Tracking';

// Initialize variables
$order = null;
$errors = [];
$orderId = $phoneNumber = $smartCardNumber = $transactionRef = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        $orderId = sanitizeInput($_POST['order_id'] ?? '');
        $phoneNumber = sanitizeInput($_POST['phone_number'] ?? '');
        $smartCardNumber = sanitizeInput($_POST['smart_card_number'] ?? '');
        $transactionRef = sanitizeInput($_POST['transaction_ref'] ?? '');
        
        // Validate at least one field is provided
        if (empty($orderId) && empty($phoneNumber) && empty($smartCardNumber) && empty($transactionRef)) {
            $errors[] = "Please provide at least one search parameter.";
        } else {
            // Build query based on provided parameters
            $params = [];
            $whereClauses = [];
            
            if (!empty($orderId)) {
                $whereClauses[] = "o.order_id = ?";
                $params[] = $orderId;
            }
            
            if (!empty($phoneNumber)) {
                $whereClauses[] = "JSON_CONTAINS(o.recipients, JSON_OBJECT('phone', ?))";
                $params[] = $phoneNumber;
            }
            
            if (!empty($smartCardNumber)) {
                $whereClauses[] = "JSON_CONTAINS(o.recipients, JSON_OBJECT('smart_card', ?))";
                $params[] = $smartCardNumber;
            }
            
            if (!empty($transactionRef)) {
                $whereClauses[] = "o.payment_ref = ?";
                $params[] = $transactionRef;
            }
            
            $where = implode(' OR ', $whereClauses);
            
            // Fetch order
            $sql = "SELECT o.*, p.name as package_name, u.username 
                    FROM orders o
                    LEFT JOIN packages p ON o.package_id = p.id
                    LEFT JOIN users u ON o.user_id = u.id
                    WHERE $where
                    ORDER BY o.created_at DESC
                    LIMIT 1";
            
            $order = dbFetchRow($sql, $params);
            
            if (!$order) {
                $errors[] = "No order found matching your search criteria.";
            } else {
                // Parse recipients JSON
                $order['recipients'] = json_decode($order['recipients'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $order['recipients'] = [];
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

            <!-- Order Tracking Page -->
            <section class="tracking-section">
                <div class="container">
                    <h1>Track Your Order</h1>
                    <p>Enter your order details to check the status of your purchase</p>
                    
                    <div class="tracking-content">
                        <div class="tracking-form-container">
                            <form method="POST" action="order-tracking.php" class="tracking-form">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <div class="form-group">
                                    <label for="order_id">Order ID</label>
                                    <input type="text" id="order_id" name="order_id" class="form-control"
                                           value="<?php echo htmlspecialchars($orderId); ?>"
                                           placeholder="e.g., ORD-20230224-1A2B3C4D">
                                    <small class="form-hint">Found in your order confirmation email</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone_number">Phone Number</label>
                                    <input type="tel" id="phone_number" name="phone_number" class="form-control"
                                           value="<?php echo htmlspecialchars($phoneNumber); ?>"
                                           placeholder="e.g., +233 or 0 followed by 9 digits">
                                    <small class="form-hint">The phone number used for the purchase</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="smart_card_number">Smart Card Number (if applicable)</label>
                                    <input type="text" id="smart_card_number" name="smart_card_number" class="form-control"
                                           value="<?php echo htmlspecialchars($smartCardNumber); ?>"
                                           placeholder="Enter your smart card number">
                                    <small class="form-hint">Required for cable TV subscriptions</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="transaction_ref">Transaction Reference</label>
                                    <input type="text" id="transaction_ref" name="transaction_ref" class="form-control"
                                           value="<?php echo htmlspecialchars($transactionRef); ?>"
                                           placeholder="e.g., PAY-78901234">
                                    <small class="form-hint">Found in your payment confirmation</small>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-search"></i> Track Order
                                </button>
                            </form>
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
                        
                        <?php if ($order): ?>
                        <div class="order-details">
                            <h2>Order Details</h2>
                            
                            <div class="order-status-card">
                                <div class="status-header">
                                    <h3>Order Status</h3>
                                    <span class="status-badge <?php echo getOrderStatusClass($order['status']); ?>">
                                        <?php echo getOrderStatusLabel($order['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="status-timeline">
                                    <div class="timeline-item active">
                                        <div class="timeline-dot"></div>
                                        <div class="timeline-content">
                                            <h4>Order Placed</h4>
                                            <p><?php echo formatDate($order['created_at']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="timeline-item <?php echo in_array($order['status'], ['processing', 'completed']) ? 'active' : ''; ?>">
                                        <div class="timeline-dot"></div>
                                        <div class="timeline-content">
                                            <h4>Processing</h4>
                                            <p><?php echo in_array($order['status'], ['processing', 'completed']) ? formatDate($order['updated_at']) : 'Pending'; ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="timeline-item <?php echo $order['status'] === 'completed' ? 'active' : ''; ?>">
                                        <div class="timeline-dot"></div>
                                        <div class="timeline-content">
                                            <h4>Completed</h4>
                                            <p><?php echo $order['status'] === 'completed' ? formatDate($order['updated_at']) : 'Pending'; ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="order-info-grid">
                                <div class="info-card">
                                    <h3>Order Information</h3>
                                    <div class="info-row">
                                        <span class="label">Order ID:</span>
                                        <span class="value"><?php echo htmlspecialchars($order['order_id']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="label">Date:</span>
                                        <span class="value"><?php echo formatDate($order['created_at']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="label">Status:</span>
                                        <span class="value <?php echo getOrderStatusClass($order['status']); ?>">
                                            <?php echo getOrderStatusLabel($order['status']); ?>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="label">Payment Reference:</span>
                                        <span class="value"><?php echo htmlspecialchars($order['payment_ref']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="info-card">
                                    <h3>Package Details</h3>
                                    <div class="info-row">
                                        <span class="label">Package:</span>
                                        <span class="value"><?php echo htmlspecialchars($order['package_name']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="label">Type:</span>
                                        <span class="value"><?php echo getPackageTypeName($order['package_type']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="label">Quantity:</span>
                                        <span class="value"><?php echo $order['quantity']; ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="label">Total Amount:</span>
                                        <span class="value"><?php echo formatPrice($order['total_price']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="info-card">
                                    <h3>Recipient Details</h3>
                                    <?php if (is_array($order['recipients']) && !empty($order['recipients'])): ?>
                                        <?php foreach ($order['recipients'] as $index => $recipient): ?>
                                        <div class="recipient-section">
                                            <h4>Recipient <?php echo $index + 1; ?>:</h4>
                                            <?php if ($order['package_type'] === 'data' || $order['package_type'] === 'airtime'): ?>
                                                <div class="info-row">
                                                    <span class="label">Phone:</span>
                                                    <span class="value"><?php echo htmlspecialchars($recipient['phone'] ?? 'N/A'); ?></span>
                                                </div>
                                            <?php elseif ($order['package_type'] === 'cable'): ?>
                                                <div class="info-row">
                                                    <span class="label">Smart Card:</span>
                                                    <span class="value"><?php echo htmlspecialchars($recipient['smart_card'] ?? 'N/A'); ?></span>
                                                </div>
                                            <?php elseif ($order['package_type'] === 'result_checker'): ?>
                                                <div class="info-row">
                                                    <span class="label">WhatsApp:</span>
                                                    <span class="value"><?php echo htmlspecialchars($recipient['whatsapp'] ?? 'N/A'); ?></span>
                                                </div>
                                            <?php elseif ($order['package_type'] === 'afa'): ?>
                                                <div class="info-row">
                                                    <span class="label">Name:</span>
                                                    <span class="value"><?php echo htmlspecialchars($recipient['name'] ?? 'N/A'); ?></span>
                                                </div>
                                                <div class="info-row">
                                                    <span class="label">Ghana Card:</span>
                                                    <span class="value"><?php echo htmlspecialchars($recipient['ghana_card'] ?? 'N/A'); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                    <p>No recipient details available</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($order['status'] === 'completed' && $order['package_type'] === 'result_checker'): ?>
                            <div class="result-instructions">
                                <h3>Result Checker Instructions</h3>
                                <p>Your results will be sent to your WhatsApp number shortly. If you don't receive them within 15 minutes, please contact our support team.</p>
                                <p>WhatsApp: <a href="https://wa.me/<?php echo str_replace(['+', ' '], '', WHATSAPP_NUMBER); ?>"><?php echo WHATSAPP_NUMBER; ?></a></p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="tracking-actions">
                                <a href="order-tracking.php" class="btn btn-outline">
                                    <i class="fas fa-search"></i> Track Another Order
                                </a>
                                
                                <?php if (isLoggedIn()): ?>
                                <a href="dashboard.php" class="btn btn-primary">
                                    <i class="fas fa-user"></i> Go to Dashboard
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

<?php
// Include footer
require_once 'includes/footer.php';
?>