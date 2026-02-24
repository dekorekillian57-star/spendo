/**
 * Form Validation and Utility Functions
 * 
 * Contains minimal JavaScript for form validation and interactivity.
 * Uses vanilla JavaScript (no frameworks).
 */

document.addEventListener('DOMContentLoaded', function() {
    // Show preloader until page is fully loaded
    const preloader = document.querySelector('.preloader');
    if (preloader) {
        window.addEventListener('load', function() {
            setTimeout(function() {
                preloader.classList.add('hidden');
            }, 500);
        });
    }
    
    // Mobile menu toggle
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNavigation = document.querySelector('.main-navigation');
    
    if (mobileMenuToggle && mainNavigation) {
        mobileMenuToggle.addEventListener('click', function() {
            mainNavigation.classList.toggle('active');
            this.innerHTML = mainNavigation.classList.contains('active') ? 
                '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
        });
    }
    
    // Close mobile menu when clicking a link
    const navLinks = document.querySelectorAll('.main-navigation a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (mainNavigation && mobileMenuToggle) {
                mainNavigation.classList.remove('active');
                mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });
    });
    
    // Back to top button
    const backToTopButton = document.getElementById('backToTop');
    
    if (backToTopButton) {
        // Show button after scrolling down 300px
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('visible');
            } else {
                backToTopButton.classList.remove('visible');
            }
        });
        
        // Scroll to top when button is clicked
        backToTopButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
    
    // Cookie consent
    const cookieConsent = document.getElementById('cookieConsent');
    const acceptCookies = document.getElementById('acceptCookies');
    
    if (cookieConsent && acceptCookies) {
        // Check if user has already accepted cookies
        if (!localStorage.getItem('cookiesAccepted')) {
            cookieConsent.classList.add('active');
        }
        
        // Accept cookies
        acceptCookies.addEventListener('click', function() {
            localStorage.setItem('cookiesAccepted', 'true');
            cookieConsent.classList.remove('active');
        });
    }
    
    // Close flash messages
    const closeFlashButtons = document.querySelectorAll('.close-flash');
    closeFlashButtons.forEach(button => {
        button.addEventListener('click', function() {
            const flashMessage = this.closest('.flash-message');
            if (flashMessage) {
                flashMessage.style.animation = 'fadeOut 0.5s ease forwards';
                setTimeout(() => {
                    if (flashMessage.parentNode) {
                        flashMessage.parentNode.removeChild(flashMessage);
                    }
                }, 500);
            }
        });
    });
    
    // Auto-hide flash messages after 5 seconds
    setTimeout(() => {
        const flashMessages = document.querySelectorAll('.flash-message');
        flashMessages.forEach(message => {
            message.style.animation = 'fadeOut 0.5s ease 4.5s forwards';
            setTimeout(() => {
                if (message.parentNode) {
                    message.parentNode.removeChild(message);
                }
            }, 5000);
        });
    }, 5000);
    
    // Data bundle network filter
    const networkFilterButtons = document.querySelectorAll('.network-btn');
    const packagesGrid = document.getElementById('packagesGrid');
    
    if (networkFilterButtons.length > 0 && packagesGrid) {
        networkFilterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Update active button
                networkFilterButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                const networkFilter = this.dataset.network;
                
                // Filter packages
                const packages = packagesGrid.querySelectorAll('.package-card');
                packages.forEach(package => {
                    const packageNetwork = package.dataset.network ? 
                        package.dataset.network.toLowerCase() : '';
                    
                    if (networkFilter === 'all' || 
                        packageNetwork.includes(networkFilter) ||
                        (networkFilter === 'dstv' && packageNetwork.includes('dstv')) ||
                        (networkFilter === 'startimes' && packageNetwork.includes('startimes'))) {
                        package.style.display = 'block';
                    } else {
                        package.style.display = 'none';
                    }
                });
            });
        });
    }
    
    // Order tracking form validation
    const trackingForm = document.querySelector('.tracking-form');
    if (trackingForm) {
        trackingForm.addEventListener('submit', function(e) {
            const orderId = document.getElementById('order_id').value.trim();
            const phoneNumber = document.getElementById('phone_number').value.trim();
            const smartCardNumber = document.getElementById('smart_card_number').value.trim();
            const transactionRef = document.getElementById('transaction_ref').value.trim();
            
            if (!orderId && !phoneNumber && !smartCardNumber && !transactionRef) {
                e.preventDefault();
                alert('Please provide at least one search parameter.');
            }
        });
    }
    
    // Password validation on registration and password change
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        if (input.id.includes('password') || input.name.includes('password')) {
            input.addEventListener('blur', function() {
                if (this.value.length > 0 && this.value.length < 8) {
                    alert('Password must be at least 8 characters long.');
                }
            });
        }
    });
    
    // Confirm password match validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (newPassword && confirmPassword) {
        confirmPassword.addEventListener('blur', function() {
            if (this.value && this.value !== newPassword.value) {
                alert('Passwords do not match.');
            }
        });
    }
    
    // Phone number validation
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const phoneRegex = /^(?:\+233|0)[0-9]{9}$/;
            if (this.value && !phoneRegex.test(this.value)) {
                alert('Please enter a valid Ghana phone number (e.g., +233 or 0 followed by 9 digits).');
            }
        });
    });
    
    // Bulk recipient management
    window.addRecipientField = function(packageId, packageType) {
        const bulkRecipients = document.getElementById(`bulk-recipients-${packageId}`);
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
    };
    
    window.removeRecipientField = function(element) {
        const recipientItem = element.closest('.bulk-recipient-item');
        recipientItem.remove();
        
        // Hide bulk recipients section if no items left
        const bulkRecipients = recipientItem.closest('.bulk-recipients');
        if (bulkRecipients.children.length === 0) {
            bulkRecipients.style.display = 'none';
        }
    };
    
    // Update total price when quantity changes
    window.updateTotalPrice = function(packageId, price) {
        const quantity = document.getElementById(`quantity-${packageId}`).value;
        const totalPrice = document.getElementById(`total-price-${packageId}`);
        const total = price * quantity;
        
        totalPrice.innerHTML = `Total: ${CURRENCY_SYMBOL}${total.toFixed(2)}`;
    };
    
    // Admin-specific functionality
    if (document.querySelector('.admin-container')) {
        // Bulk selection
        const selectAllCheckbox = document.getElementById('tableSelectAll');
        const bulkCheckboxes = document.querySelectorAll('.bulk-checkbox');
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');
        const applyBulkBtn = document.getElementById('applyBulkBtn');
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
        
        function updateBulkActions() {
            const checkedCount = document.querySelectorAll('.bulk-checkbox:checked').length;
            
            if (checkedCount > 0) {
                bulkActions.classList.add('active');
                selectedCount.textContent = `${checkedCount} selected`;
                
                // Update selected order IDs
                const ids = [];
                document.querySelectorAll('.bulk-checkbox:checked').forEach(checkbox => {
                    ids.push(checkbox.value);
                });
                document.getElementById('selectedOrderIds').value = ids.join(',');
                
                // Enable buttons
                applyBulkBtn.disabled = false;
                deleteSelectedBtn.disabled = false;
            } else {
                bulkActions.classList.remove('active');
                selectedCount.textContent = '0 selected';
                document.getElementById('selectedOrderIds').value = '';
                applyBulkBtn.disabled = true;
                deleteSelectedBtn.disabled = true;
            }
        }
        
        // Select all checkbox
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                bulkCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
                updateBulkActions();
            });
        }
        
        // Individual checkboxes
        bulkCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // Update "Select All" checkbox
                const allChecked = bulkCheckboxes.length > 0 && 
                                  Array.from(bulkCheckboxes).every(cb => cb.checked);
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                }
                
                updateBulkActions();
            });
        });
    }
});