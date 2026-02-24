<?php
/**
 * Shopping Cart Page
 * 
 * Displays the user's cart with items, quantities, and total price.
 * Allows users to update quantities or remove items.
 */

// Define ABSPATH for security
define('ABSPATH', dirname(__FILE__));

// Include configuration
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Set page title
$pageTitle = 'Shopping Cart';

// Get cart items
$cartItems = getCartItems();
$cartTotal = calculateCartTotal($cartItems);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        redirect('cart.php', 'Invalid request. Please try again.', 'error');
    }
    
    if ($action === 'update_quantity') {
        $itemId = $_POST['item_id'] ?? '';
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        if ($quantity < 1) {
            $quantity = 1;
        }
        
        if (updateCartItemQuantity($itemId, $quantity)) {
            redirect('cart.php', 'Cart updated successfully!');
        } else {
            redirect('cart.php', 'Failed to update cart item.', 'error');
        }
    } elseif ($action === 'remove_item') {
        $itemId = $_POST['item_id'] ?? '';
        
        if (removeFromCart($itemId)) {
            redirect('cart.php', 'Item removed from cart!');
        } else {
            redirect('cart.php', 'Failed to remove item from cart.', 'error');
        }
    } elseif ($action === 'clear_cart') {
        if (clearCart()) {
            redirect('cart.php', 'Cart cleared successfully!');
        } else {
            redirect('cart.php', 'Failed to clear cart.', 'error');
        }
    }
}

// Include header
require_once 'includes/header.php';
?>

            <!-- Cart Page -->
            <section class="cart-section">
                <div class="container">
                    <h1>Shopping Cart</h1>
                    
                    <?php if (empty($cartItems)): ?>
                    <div class="empty-cart">
                        <div class="empty-cart-icon">🛒</div>
                        <h2>Your cart is empty</h2>
                        <p>Browse our services and add items to your cart</p>
                        <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
                    </div>
                    <?php else: ?>
                    <div class="cart-content">
                        <div class="cart-items">
                            <?php foreach ($cartItems as $index => $item): ?>
                            <div class="cart-item">
                                <div class="item-details">
                                    <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <p class="item-type"><?php echo getPackageTypeName($item['type']); ?></p>
                                    
                                    <?php if (!empty($item['network'])): ?>
                                    <p class="item-network"><?php echo htmlspecialchars($item['network']); ?></p>
                                    <?php endif; ?>
                                    
                                    <!-- Recipient details -->
                                    <div class="item-recipients">
                                        <h4>Recipients:</h4>
                                        <?php if (is_array($item['recipients']) && !empty($item['recipients'])): ?>
                                        <ul>
                                            <?php foreach ($item['recipients'] as $recipient): ?>
                                            <li>
                                                <?php if ($item['type'] === 'data' || $item['type'] === 'airtime'): ?>
                                                    Phone: <?php echo htmlspecialchars($recipient['phone'] ?? 'N/A'); ?>
                                                <?php elseif ($item['type'] === 'cable'): ?>
                                                    Smart Card: <?php echo htmlspecialchars($recipient['smart_card'] ?? 'N/A'); ?>
                                                <?php elseif ($item['type'] === 'result_checker'): ?>
                                                    WhatsApp: <?php echo htmlspecialchars($recipient['whatsapp'] ?? 'N/A'); ?>
                                                <?php elseif ($item['type'] === 'afa'): ?>
                                                    Name: <?php echo htmlspecialchars($recipient['name'] ?? 'N/A'); ?>,
                                                    Ghana Card: <?php echo htmlspecialchars($recipient['ghana_card'] ?? 'N/A'); ?>
                                                <?php endif; ?>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php else: ?>
                                        <p>No recipient details provided</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="item-price">
                                    <span class="price"><?php echo formatPrice($item['price']); ?></span>
                                    <div class="quantity-controls">
                                        <form method="POST" action="cart.php" class="quantity-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="action" value="update_quantity">
                                            <input type="hidden" name="item_id" value="<?php echo $index; ?>">
                                            
                                            <button type="submit" class="quantity-btn" name="quantity" value="<?php echo max(1, $item['quantity'] - 1); ?>">-</button>
                                            <input type="number" name="quantity" class="quantity-input" 
                                                   value="<?php echo $item['quantity']; ?>" min="1" readonly>
                                            <button type="submit" class="quantity-btn" name="quantity" value="<?php echo $item['quantity'] + 1; ?>">+</button>
                                        </form>
                                    </div>
                                    <div class="item-total">
                                        <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                    </div>
                                </div>
                                
                                <div class="item-actions">
                                    <form method="POST" action="cart.php" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="item_id" value="<?php echo $index; ?>">
                                        <button type="submit" class="btn btn-danger" 
                                                onclick="return confirm('Are you sure you want to remove this item from your cart?');">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="cart-summary">
                            <h2>Order Summary</h2>
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
                            
                            <form method="POST" action="cart.php" style="margin-top: 20px;">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="action" value="clear_cart">
                                <button type="submit" class="btn btn-outline btn-block" 
                                        onclick="return confirm('Are you sure you want to clear your cart?');">
                                    <i class="fas fa-trash"></i> Clear Cart
                                </button>
                            </form>
                            
                            <a href="checkout.php" class="btn btn-primary btn-block" style="margin-top: 15px;">
                                <i class="fas fa-shopping-bag"></i> Proceed to Checkout
                            </a>
                            
                            <a href="shop.php" class="btn btn-outline btn-block" style="margin-top: 10px;">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

<?php
// Include footer
require_once 'includes/footer.php';
?>