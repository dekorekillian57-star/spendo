<?php
/**
 * Home/Landing Page
 * 
 * The main entry point for the website. Displays featured packages, promotions,
 * and information about the services offered.
 */

// Define ABSPATH for security
define('ABSPATH', dirname(__FILE__));

// Start output buffering to prevent "headers already sent" errors
ob_start();

// Include configuration
require_once 'includes/config.php';
require_once 'includes/db.php';

// Set page title
$pageTitle = 'Home';

// Get featured packages
$featuredDataBundles = dbFetchAll("SELECT * FROM packages WHERE type = 'data' LIMIT 3");
$featuredCable = dbFetchAll("SELECT * FROM packages WHERE type = 'cable' LIMIT 2");
$featuredResultCheckers = dbFetchAll("SELECT * FROM packages WHERE type = 'result_checker' LIMIT 2");

// Include header
require_once 'includes/header.php';
?>

            <!-- Hero Section -->
            <section class="hero">
                <div class="hero-content">
                    <h1>Fast & Reliable Telecom Services in Ghana</h1>
                    <p>Buy mobile data bundles, airtime, cable TV subscriptions, and more with ease</p>
                    <div class="hero-buttons">
                        <a href="shop.php" class="btn btn-primary">Shop Now</a>
                        <a href="#how-it-works" class="btn btn-outline">How It Works</a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="images/hero-image.png" alt="Telecom Services in Ghana">
                </div>
            </section>
            
            <!-- SIM Warning Banner -->
            <section class="sim-warning">
                <div class="container">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>IMPORTANT:</strong> Data bundles DO NOT SUPPORT: Turbonet SIM, Merchant SIM, EVD SIM, Broadband SIM, Blacklist SIM, Roaming SIM, Different Network, Wrong Number, Inactive Number.
                </div>
            </section>
            
            <!-- Featured Data Bundles -->
            <section id="data-bundles" class="featured-section">
                <div class="section-header">
                    <h2>Popular Data Bundles</h2>
                    <a href="shop.php?type=data" class="view-all">View All Data Bundles</a>
                </div>
                
                <div class="packages-grid">
                    <?php foreach ($featuredDataBundles as $bundle): ?>
                    <div class="package-card">
                        <div class="network-badge" style="background-color: <?php 
                            switch(strtolower($bundle['network'])) {
                                case 'mtn': echo '#e52521'; break;
                                case 'airteltigo': echo '#0066cc'; break;
                                case 'telecel': echo '#009933'; break;
                                default: echo '#666666';
                            }
                        ?>;">
                            <?php echo htmlspecialchars($bundle['network']); ?>
                        </div>
                        <h3><?php echo htmlspecialchars($bundle['name']); ?></h3>
                        <div class="package-price"><?php echo formatPrice($bundle['price']); ?></div>
                        <p><?php echo htmlspecialchars($bundle['description'] ?? 'Fast internet bundle'); ?></p>
                        <a href="shop.php#package-<?php echo $bundle['id']; ?>" class="btn btn-primary">Buy Now</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <!-- Featured Cable TV -->
            <section id="cable-tv" class="featured-section">
                <div class="section-header">
                    <h2>Cable TV Subscriptions</h2>
                    <a href="shop.php?type=cable" class="view-all">View All Cable Packages</a>
                </div>
                
                <div class="packages-grid">
                    <?php foreach ($featuredCable as $cable): ?>
                    <div class="package-card">
                        <div class="network-badge" style="background-color: <?php 
                            switch(strtolower($cable['network'])) {
                                case 'startimes': echo '#f9a825'; break;
                                case 'dstv': echo '#e53935'; break;
                                default: echo '#666666';
                            }
                        ?>;">
                            <?php echo htmlspecialchars($cable['network']); ?>
                        </div>
                        <h3><?php echo htmlspecialchars($cable['name']); ?></h3>
                        <div class="package-price"><?php echo formatPrice($cable['price']); ?></div>
                        <p><?php echo htmlspecialchars($cable['description'] ?? 'Enjoy your favorite channels'); ?></p>
                        <a href="shop.php#package-<?php echo $cable['id']; ?>" class="btn btn-primary">Subscribe Now</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <!-- Featured Result Checkers -->
            <section id="result-checkers" class="featured-section">
                <div class="section-header">
                    <h2>Result Checkers</h2>
                    <a href="shop.php?type=result_checker" class="view-all">View All Result Checkers</a>
                </div>
                
                <div class="packages-grid">
                    <?php foreach ($featuredResultCheckers as $result): ?>
                    <div class="package-card">
                        <div class="network-badge" style="background-color: #7b1fa2;">
                            Result Checker
                        </div>
                        <h3><?php echo htmlspecialchars($result['name']); ?></h3>
                        <div class="package-price"><?php echo formatPrice($result['price']); ?></div>
                        <p><?php echo htmlspecialchars($result['description'] ?? 'Check your results instantly'); ?></p>
                        <a href="shop.php#package-<?php echo $result['id']; ?>" class="btn btn-primary">Check Results</a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            
            <!-- AFA Registration -->
            <section id="afa-registration" class="featured-section">
                <div class="section-header">
                    <h2>AFA Registration</h2>
                </div>
                
                <div class="afa-section">
                    <div class="afa-content">
                        <h3>Ghana Football Association Registration</h3>
                        <p>Register with the Ghana Football Association (GFA) through our platform. Complete your registration process quickly and easily.</p>
                        <ul class="afa-features">
                            <li><i class="fas fa-check-circle"></i> Official GFA registration</li>
                            <li><i class="fas fa-check-circle"></i> Fast processing</li>
                            <li><i class="fas fa-check-circle"></i> Support available via WhatsApp</li>
                        </ul>
                        <div class="afa-price">
                            <span class="price"><?php echo formatPrice(15.00); ?></span>
                            <a href="shop.php#afa-package" class="btn btn-primary">Register Now</a>
                        </div>
                    </div>
                    <div class="afa-image">
                        <img src="images/afa-logo.png" alt="Ghana Football Association">
                    </div>
                </div>
            </section>
            
            <!-- How It Works -->
            <section id="how-it-works" class="how-it-works">
                <div class="section-header">
                    <h2>How It Works</h2>
                </div>
                
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h3>Select Package</h3>
                        <p>Browse our catalog and select the telecom service you need</p>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h3>Enter Details</h3>
                        <p>Provide necessary information like phone number or smart card number</p>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h3>Make Payment</h3>
                        <p>Securely pay using Paystack with your preferred payment method</p>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h3>Get Service</h3>
                        <p>Receive your data bundle, airtime, or subscription instantly</p>
                    </div>
                </div>
            </section>
            
            <!-- Testimonials -->
            <section class="testimonials">
                <div class="section-header">
                    <h2>What Our Customers Say</h2>
                </div>
                
                <div class="testimonials-grid">
                    <div class="testimonial-card">
                        <div class="testimonial-content">
                            <p>"I've been using Ghana Telecom for my data bundles and it's been seamless. Fast delivery and excellent customer service!"</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">JD</div>
                            <div class="author-info">
                                <h4>John Doe</h4>
                                <div class="author-location">Accra, Ghana</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial-card">
                        <div class="testimonial-content">
                            <p>"The DSTV subscription process was so easy. I got my package within minutes of payment. Highly recommended!"</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">AS</div>
                            <div class="author-info">
                                <h4>Ama Smith</h4>
                                <div class="author-location">Kumasi, Ghana</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="testimonial-card">
                        <div class="testimonial-content">
                            <p>"I checked my BECE results through this platform and it was instant. No stress compared to the old methods."</p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">KO</div>
                            <div class="author-info">
                                <h4>Kwame Owusu</h4>
                                <div class="author-location">Takoradi, Ghana</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Call to Action -->
            <section class="cta-section">
                <div class="container">
                    <h2>Ready to Get Started?</h2>
                    <p>Join thousands of satisfied customers who trust us for their telecom needs</p>
                    <a href="shop.php" class="btn btn-primary btn-large">Shop Now</a>
                </div>
            </section>

<?php
// Include footer
require_once 'includes/footer.php';
?>