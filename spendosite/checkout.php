<?php
/**
 * Checkout Page
 * 
 * Handles the checkout process, collects payment information,
 * and processes the order.
 */

// Define ABSPATH for security
define('ABSPATH', dirname(__FILE__));

// Include configuration
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';
require_once 'includes/paystack.php';

// Set page title
$pageTitle = 'Checkout';

// Check if cart is empty
$cartItems = getCartItems();
if (empty($cartItems)) {
    redirect('cart.php', 'Your cart is empty. Please add items before checking out.', 'error');
}

$cartTotal = calculateCartTotal($cartItems);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        redirect('checkout.php', 'Invalid request. Please try again.', 'error');
    }
    
    // Process payment with Paystack
    $reference = 'REF-' . strtoupper(bin2hex(random_bytes(8)));
    $callbackUrl = SITE_URL . '/payment-verify.php';
    
    if (isLoggedIn()) {
        $email = $_SESSION['email'];
    } else {
        $email = $_POST['guest_email'] ?? '';
        
        // Validate guest email
        if (empty($email) || !validateEmail($email)) {
            redirect('checkout.php', 'Valid email is required for guest checkout.', 'error');
        }
    }
    
    // Initialize Paystack payment
    $paymentData = paystackInitializePayment(
        $cartTotal,
        $email,
        $reference,
        $callbackUrl,
        [
            'cart_items' => count($cartItems),
            'guest_checkout' => !isLoggedIn()
        ]
    );
    
    if ($paymentData && isset($paymentData['data']['authorization_url'])) {
        // Store reference in session for verification
        $_SESSION['payment_reference'] = $reference;
        
        // Redirect to Paystack payment page
        header('Location: ' . $paymentData['data']['authorization_url']);
        exit();
    } else {
        redirect('checkout.php', 'Failed to initialize payment. Please try again.', 'error');
    }
}

// Include header
require_once 'includes/header.php';
?>

            <!-- Checkout Page -->
            <section class="checkout-section">
                <div class="container">
                    <h1>Checkout</h1>
                    
                    <div class="checkout-content">
                        <div class="checkout-steps">
                            <div class="step active">
                                <div class="step-number">1</div>
                                <div class="step-label">Cart</div>
                            </div>
                            <div class="step active">
                                <div class="step-number">2</div>
                                <div class="step-label">Checkout</div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-label">Confirmation</div>
                            </div>
                        </div>
                        
                        <div class="checkout-grid">
                            <div class="checkout-items">
                                <h2>Order Summary</h2>
                                
                                <?php foreach ($cartItems as $item): ?>
                                <div class="checkout-item">
                                    <div class="item-details">
                                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <p class="item-type"><?php echo getPackageTypeName($item['type']); ?></p>
                                        <?php if (!empty($item['network'])): ?>
                                        <p class="item-network"><?php echo htmlspecialchars($item['network']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="item-quantity">
                                        x <?php echo $item['quantity']; ?>
                                    </div>
                                    
                                    <div class="item-price">
                                        <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="checkout-summary">
                                    <div class="summary-row">
                                        <span>Subtotal:</span>
                                        <span><?php echo formatPrice($cartTotal); ?></span>
                                    </div>
                                    <div class="summary-row">
                                        <span>Tax (0%):</span>
                                        <span><?php echo formatPrice(0); ?></span>
                                    </div>
                                    <div class="summary-row total">
                                        <span>Total:</span>
                                        <span><?php echo formatPrice($cartTotal); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="checkout-form">
                                <h2>Payment Information</h2>
                                
                                <?php if (!isLoggedIn()): ?>
                                <div class="guest-checkout-info">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <p>You're checking out as a guest. Please provide your email address to receive order confirmation and tracking information.</p>
                                    </div>
                                    
                                    <form method="POST" action="checkout.php" id="guestEmailForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        
                                        <div class="form-group">
                                            <label for="guest_email">Email Address</label>
                                            <input type="email" id="guest_email" name="guest_email" class="form-control" required
                                                   placeholder="your@email.com">
                                        </div>
                                    </form>
                                </div>
                                <?php endif; ?>
                                
                                <div class="payment-methods">
                                    <h3>Payment Method</h3>
                                    <div class="payment-method active">
                                        <div class="method-header">
                                            <input type="radio" id="paystack" name="payment_method" value="paystack" checked>
                                            <label for="paystack">
                                                <img src="images/paystack.png" alt="Paystack" class="payment-logo">
                                                Credit/Debit Card, Mobile Money
                                            </label>
                                        </div>
                                        <div class="method-details">
                                            <p>Secure payment through Paystack. Accepts Visa, Mastercard, Verve, and Mobile Money.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="checkout-actions">
                                    <a href="cart.php" class="btn btn-outline">
                                        <i class="fas fa-arrow-left"></i> Back to Cart
                                    </a>
                                    
                                    <form method="POST" action="checkout.php" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-credit-card"></i> Pay Now
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

<?php
// Include footer
require_once 'includes/footer.php';
?>