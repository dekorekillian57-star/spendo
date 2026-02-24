<?php
/**
 * Shop Page
 * 
 * Displays all available packages categorized by type.
 */

// Define ABSPATH for security
define('ABSPATH', dirname(__FILE__));

// Include configuration
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/helpers.php';

// Set page title
$pageTitle = 'Shop';

// Get filter parameters
$typeFilter = $_GET['type'] ?? 'all';

// Get all packages
$packagesQuery = "SELECT * FROM packages";
$params = [];

if ($typeFilter !== 'all') {
    $packagesQuery .= " WHERE type = ?";
    $params[] = $typeFilter;
}

$packagesQuery .= " ORDER BY 
    CASE type
        WHEN 'data' THEN 1
        WHEN 'airtime' THEN 2
        WHEN 'cable' THEN 3
        WHEN 'result_checker' THEN 4
        WHEN 'afa' THEN 5
        ELSE 6
    END,
    price ASC";

$packages = dbFetchAll($packagesQuery, $params);

// Include header
require_once 'includes/header.php';
?>

            <!-- Shop Header -->
            <section class="shop-header">
                <div class="container">
                    <h1>Our Services</h1>
                    <p>Browse our selection of telecom services and find the perfect package for your needs</p>
                    
                    <div class="shop-filters">
                        <a href="shop.php?type=all" class="filter-btn <?php echo $typeFilter === 'all' ? 'active' : ''; ?>">All Services</a>
                        <a href="shop.php?type=data" class="filter-btn <?php echo $typeFilter === 'data' ? 'active' : ''; ?>">Data Bundles</a>
                        <a href="shop.php?type=airtime" class="filter-btn <?php echo $typeFilter === 'airtime' ? 'active' : ''; ?>">Airtime</a>
                        <a href="shop.php?type=cable" class="filter-btn <?php echo $typeFilter === 'cable' ? 'active' : ''; ?>">Cable TV</a>
                        <a href="shop.php?type=result_checker" class="filter-btn <?php echo $typeFilter === 'result_checker' ? 'active' : ''; ?>">Result Checkers</a>
                        <a href="shop.php?type=afa" class="filter-btn <?php echo $typeFilter === 'afa' ? 'active' : ''; ?>">AFA Registration</a>
                    </div>
                </div>
            </section>
            
            <!-- SIM Warning Banner -->
            <section class="sim-warning">
                <div class="container">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>IMPORTANT:</strong> Data bundles DO NOT SUPPORT: Turbonet SIM, Merchant SIM, EVD SIM, Broadband SIM, Blacklist SIM, Roaming SIM, Different Network, Wrong Number, Inactive Number.
                </div>
            </section>
            
            <!-- Packages Grid -->
            <section class="packages-section">
                <div class="container">
                    <?php if (empty($packages)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <h3 class="empty-state-title">No packages found</h3>
                        <p class="empty-state-text">
                            <?php if ($typeFilter !== 'all'): ?>
                                There are no <?php echo getPackageTypeName($typeFilter); ?> available at the moment.
                            <?php else: ?>
                                There are no packages available at the moment. Please check back later.
                            <?php endif; ?>
                        </p>
                        <a href="shop.php" class="btn btn-primary">View All Services</a>
                    </div>
                    <?php else: ?>
                    <div class="packages-grid" id="packagesGrid">
                        <?php foreach ($packages as $package): ?>
                        <div class="package-card" id="package-<?php echo $package['id']; ?>">
                            <div class="package-header">
                                <span class="package-type <?php 
                                    switch($package['type']) {
                                        case 'data': echo 'type-data'; break;
                                        case 'airtime': echo 'type-airtime'; break;
                                        case 'cable': echo 'type-cable'; break;
                                        case 'result_checker': echo 'type-result'; break;
                                        case 'afa': echo 'type-afa'; break;
                                        default: echo 'type-other';
                                    }
                                ?>">
                                    <?php echo getPackageTypeName($package['type']); ?>
                                </span>
                                
                                <?php if (!empty($package['network'])): ?>
                                <span class="package-network"><?php echo htmlspecialchars($package['network']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="package-name"><?php echo htmlspecialchars($package['name']); ?></h3>
                            
                            <div class="package-price">
                                <?php echo formatPrice($package['price']); ?>
                            </div>
                            
                            <p class="package-description">
                                <?php echo htmlspecialchars($package['description'] ?? 'No description available'); ?>
                            </p>
                            
                            <form method="POST" action="cart.php" class="package-form">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="package_id" value="<?php echo $package['id']; ?>">
                                
                                <!-- Quantity selector -->
                                <div class="form-group">
                                    <label for="quantity-<?php echo $package['id']; ?>">Quantity:</label>
                                    <input type="number" id="quantity-<?php echo $package['id']; ?>" 
                                           name="quantity" class="form-control" min="1" value="1"
                                           onchange="updateTotalPrice(<?php echo $package['id']; ?>, <?php echo $package['price']; ?>)">
                                </div>
                                
                                <!-- Recipient details (dynamic based on package type) -->
                                <div class="recipient-details" id="recipient-details-<?php echo $package['id']; ?>">
                                    <?php if ($package['type'] === 'data' || $package['type'] === 'airtime'): ?>
                                    <div class="form-group">
                                        <label for="phone-<?php echo $package['id']; ?>">
                                            <?php echo $package['type'] === 'data' ? 'Phone Number (for data)' : 'Recipient Phone Number'; ?>
                                        </label>
                                        <input type="tel" id="phone-<?php echo $package['id']; ?>" 
                                               name="recipients[0][phone]" class="form-control" required
                                               placeholder="e.g., +233 or 0 followed by 9 digits">
                                    </div>
                                    
                                    <div class="bulk-recipients" style="display: none;" 
                                         id="bulk-recipients-<?php echo $package['id']; ?>">
                                        <!-- Bulk recipients will be added here dynamically -->
                                    </div>
                                    
                                    <button type="button" class="btn btn-outline btn-sm add-recipient"
                                            onclick="addRecipientField(<?php echo $package['id']; ?>, '<?php echo $package['type']; ?>')">
                                        <i class="fas fa-plus"></i> Add Another Recipient
                                    </button>
                                    
                                    <?php elseif ($package['type'] === 'cable'): ?>
                                    <div class="form-group">
                                        <label for="smart-card-<?php echo $package['id']; ?>">Smart Card Number</label>
                                        <input type="text" id="smart-card-<?php echo $package['id']; ?>" 
                                               name="recipients[0][smart_card]" class="form-control" required
                                               placeholder="Enter your smart card number">
                                    </div>
                                    
                                    <?php elseif ($package['type'] === 'result_checker'): ?>
                                    <div class="form-group">
                                        <label for="whatsapp-<?php echo $package['id']; ?>">WhatsApp Number</label>
                                        <input type="tel" id="whatsapp-<?php echo $package['id']; ?>" 
                                               name="recipients[0][whatsapp]" class="form-control" required
                                               placeholder="Results will be sent to this WhatsApp number">
                                    </div>
                                    
                                    <?php elseif ($package['type'] === 'afa'): ?>
                                    <div class="form-group">
                                        <label for="name-<?php echo $package['id']; ?>">Full Name</label>
                                        <input type="text" id="name-<?php echo $package['id']; ?>" 
                                               name="recipients[0][name]" class="form-control" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email-<?php echo $package['id']; ?>">Email Address</label>
                                        <input type="email" id="email-<?php echo $package['id']; ?>" 
                                               name="recipients[0][email]" class="form-control" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="dob-<?php echo $package['id']; ?>">Date of Birth</label>
                                        <input type="date" id="dob-<?php echo $package['id']; ?>" 
                                               name="recipients[0][dob]" class="form-control" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="ghana-card-<?php echo $package['id']; ?>">Ghana Card Number</label>
                                        <input type="text" id="ghana-card-<?php echo $package['id']; ?>" 
                                               name="recipients[0][ghana_card]" class="form-control" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="mtn-number-<?php echo $package['id']; ?>">MTN Number</label>
                                        <input type="tel" id="mtn-number-<?php echo $package['id']; ?>" 
                                               name="recipients[0][mtn_number]" class="form-control" required>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="package-footer">
                                    <div class="total-price" id="total-price-<?php echo $package['id']; ?>">
                                        Total: <?php echo formatPrice($package['price']); ?>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

<?php
// Include footer
require_once 'includes/footer.php';
?>

<script>
    // Function to update total price based on quantity
    function updateTotalPrice(packageId, price) {
        const quantity = document.getElementById('quantity-' + packageId).value;
        const totalPrice = document.getElementById('total-price-' + packageId);
        const total = price * quantity;
        
        totalPrice.innerHTML = 'Total: <?php echo CURRENCY_SYMBOL; ?>' + total.toFixed(2);
    }
    
    // Function to add a recipient field for bulk purchases
    function addRecipientField(packageId, packageType) {
        const bulkRecipients = document.getElementById('bulk-recipients-' + packageId);
        const addRecipientBtn = bulkRecipients.nextElementSibling;
        
        // Show bulk recipients section
        bulkRecipients.style.display = 'block';
        
        // Create new recipient field
        const recipientIndex = bulkRecipients.children.length;
        let recipientField = '';
        
        if (packageType === 'data' || packageType === 'airtime') {
            recipientField = `
                <div class="bulk-recipient-item">
                    <div class="form-group">
                        <label>Phone Number:</label>
                        <div style="display: flex; align-items: center;">
                            <input type="tel" name="recipients[${recipientIndex}][phone]" class="form-control" 
                                   placeholder="e.g., +233 or 0 followed by 9 digits" required>
                            <button type="button" class="btn btn-danger btn-sm remove-recipient" 
                                    onclick="removeRecipientField(this)">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Add to DOM
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = recipientField;
        bulkRecipients.appendChild(tempDiv.firstElementChild);
    }
    
    // Function to remove a recipient field
    function removeRecipientField(element) {
        const recipientItem = element.closest('.bulk-recipient-item');
        recipientItem.remove();
        
        // Hide bulk recipients section if no items left
        const bulkRecipients = recipientItem.closest('.bulk-recipients');
        if (bulkRecipients.children.length === 0) {
            bulkRecipients.style.display = 'none';
        }
    }
    
    // Initialize quantity change listeners
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.package-form').forEach(form => {
            const quantityInput = form.querySelector('input[name="quantity"]');
            if (quantityInput) {
                quantityInput.addEventListener('change', function() {
                    const packageId = this.closest('.package-card').id.replace('package-', '');
                    const packagePrice = parseFloat(this.closest('.package-card').querySelector('.package-price').dataset.price);
                    updateTotalPrice(packageId, packagePrice);
                });
            }
        });
    });
</script>