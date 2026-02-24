<?php
/**
 * Common Footer Template
 * 
 * Contains the HTML footer for all user-facing pages.
 * Should be included at the end of all user-facing PHP files before closing body tag.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not permitted');
}
?>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3><img src="../images/logo.png" alt="Ghana Telecom Logo" class="footer-logo"> Ghana Telecom</h3>
                    <p>Your trusted provider of mobile data bundles, airtime, cable TV subscriptions, and more in Ghana.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                
                <div class="footer-column">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="../shop.php">Shop All</a></li>
                        <li><a href="../#data-bundles">Data Bundles</a></li>
                        <li><a href="../#cable-tv">Cable TV</a></li>
                        <li><a href="../#result-checkers">Result Checkers</a></li>
                        <li><a href="../#afa-registration">AFA Registration</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Customer Service</h4>
                    <ul>
                        <li><a href="../contact.php">Contact Us</a></li>
                        <li><a href="../faq.php">FAQ</a></li>
                        <li><a href="../terms.php">Terms of Service</a></li>
                        <li><a href="../privacy.php">Privacy Policy</a></li>
                        <li><a href="../shipping.php">Shipping & Returns</a></li>
                    </ul>
                </div>
                
                <div class="footer-column">
                    <h4>Contact Info</h4>
                    <ul class="footer-contact">
                        <li><i class="fas fa-phone"></i> <a href="tel:+233123456789">+233 123 456 789</a></li>
                        <li><i class="fas fa-envelope"></i> <a href="mailto:support@ghanatelecom.com">support@ghanatelecom.com</a></li>
                        <li><i class="fas fa-map-marker-alt"></i> Accra, Ghana</li>
                        <li><i class="fab fa-whatsapp"></i> WhatsApp: <a href="https://wa.me/233123456789"><?php echo WHATSAPP_NUMBER; ?></a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Ghana Telecom Services. All rights reserved.</p>
                <div class="payment-methods">
                    <span>Payments accepted:</span>
                    <img src="../images/paystack.png" alt="Paystack" title="Paystack">
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top" aria-label="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>
    
    <!-- JavaScript -->
    <script>
        // Define global variables for JavaScript
        const SITE_URL = '<?php echo SITE_URL; ?>';
        const CSRF_TOKEN = '<?php echo $_SESSION['csrf_token'] ?? ''; ?>';
        const CURRENCY = '<?php echo CURRENCY; ?>';
        const CURRENCY_SYMBOL = '<?php echo CURRENCY_SYMBOL; ?>';
    </script>
    <script src="../js/validation.js"></script>
    
    <!-- Cookie Consent (optional) -->
    <div class="cookie-consent" id="cookieConsent">
        <div class="cookie-content">
            <p>We use cookies to enhance your experience. By continuing to visit this site, you agree to our use of cookies.</p>
            <button id="acceptCookies" class="btn btn-primary">Accept</button>
        </div>
    </div>

</body>
</html>