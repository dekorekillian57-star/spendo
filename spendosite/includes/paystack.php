<?php
/**
 * Paystack Payment Integration
 * 
 * Handles communication with Paystack API for payment processing.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

/**
 * Initialize a payment with Paystack
 * 
 * @param float $amount Amount in GHS (will be converted to kobo)
 * @param string $email Customer email
 * @param string $reference Unique transaction reference
 * @param string $callbackUrl Callback URL for redirect after payment
 * @param array $metadata Additional metadata
 * @return array|bool Response from Paystack or false on failure
 */
function paystackInitializePayment($amount, $email, $reference, $callbackUrl, $metadata = []) {
    $url = 'https://api.paystack.co/transaction/initialize';
    
    // Convert amount to kobo (Paystack uses smallest currency unit)
    $amountInKobo = round($amount * 100);
    
    $data = [
        'email' => $email,
        'amount' => $amountInKobo,
        'reference' => $reference,
        'callback_url' => $callbackUrl,
        'metadata' => $metadata
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log('Paystack API error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    if ($httpStatus >= 200 && $httpStatus < 300) {
        return json_decode($response, true);
    }
    
    error_log('Paystack API error (' . $httpStatus . '): ' . $response);
    return false;
}

/**
 * Verify a payment with Paystack
 * 
 * @param string $reference Payment reference
 * @return array|bool Verified payment data or false on failure
 */
function paystackVerifyPayment($reference) {
    $url = 'https://api.paystack.co/transaction/verify/' . urlencode($reference);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log('Paystack API error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    if ($httpStatus >= 200 && $httpStatus < 300) {
        $result = json_decode($response, true);
        
        if ($result['data']['status'] === 'success') {
            return $result['data'];
        }
        
        error_log('Paystack payment verification failed: ' . json_encode($result));
        return false;
    }
    
    error_log('Paystack API error (' . $httpStatus . '): ' . $response);
    return false;
}

/**
 * Handle Paystack webhook
 * 
 * @return array|bool Webhook data or false if invalid
 */
function paystackHandleWebhook() {
    // Get raw POST data
    $webhookBody = file_get_contents('php://input');
    
    // Get signature from header
    $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
    
    if (empty($signature)) {
        http_response_code(401);
        return false;
    }
    
    // Verify signature
    $expectedSignature = hash_hmac('sha512', $webhookBody, PAYSTACK_WEBHOOK_SECRET);
    
    if (!hash_equals($expectedSignature, $signature)) {
        http_response_code(401);
        return false;
    }
    
    // Parse webhook data
    $webhookData = json_decode($webhookBody, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        return false;
    }
    
    return $webhookData;
}

/**
 * Process successful payment
 * 
 * @param string $reference Payment reference
 * @return bool True on success, false on failure
 */
function processSuccessfulPayment($reference) {
    // Verify payment
    $paymentData = paystackVerifyPayment($reference);
    
    if (!$paymentData || $paymentData['status'] !== 'success') {
        error_log('Payment verification failed for reference: ' . $reference);
        return false;
    }
    
    // Check if order exists for this payment reference
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE payment_ref = ?");
    $stmt->execute([$reference]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        error_log('Order not found for payment reference: ' . $reference);
        return false;
    }
    
    try {
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET status = 'processing' WHERE payment_ref = ?");
        $stmt->execute([$reference]);
        
        // Send confirmation email to customer
        $customerEmail = isLoggedIn() ? $_SESSION['email'] : ($order['guest_email'] ?? '');
        
        if (!empty($customerEmail)) {
            $emailContent = '
            <h2>Payment Successful!</h2>
            <p>Thank you for your payment. Your order #' . $order['order_id'] . ' has been received and is being processed.</p>
            <p>Amount: ' . formatPrice($order['total_price']) . '</p>
            <p>Payment Reference: ' . $reference . '</p>
            <p>We will notify you when your order is completed.</p>
            ';
            
            $emailTemplate = generateEmailTemplate($emailContent, [
                '{subject}' => 'Payment Successful - Order #' . $order['order_id']
            ]);
            
            sendEmail($customerEmail, 'Payment Successful - Order #' . $order['order_id'], $emailTemplate);
        }
        
        // Notify admin
        $adminEmailContent = '
        <h2>New Order Received</h2>
        <p>A new order has been placed and paid for:</p>
        <ul>
            <li>Order ID: ' . $order['order_id'] . '</li>
            <li>Amount: ' . formatPrice($order['total_price']) . '</li>
            <li>Payment Reference: ' . $reference . '</li>
            <li>Status: Processing</li>
        </ul>
        <p>Please process this order in the admin panel.</p>
        ';
        
        $adminEmailTemplate = generateEmailTemplate($adminEmailContent, [
            '{subject}' => 'New Order Received - #' . $order['order_id']
        ]);
        
        sendEmail(ADMIN_EMAIL, 'New Order Received - #' . $order['order_id'], $adminEmailTemplate);
        
        return true;
    } catch (Exception $e) {
        error_log('Error processing successful payment: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create a Paystack subscription
 * 
 * @param string $email Customer email
 * @param string $plan Plan code
 * @param string $authorizationCode Authorization code from previous transaction
 * @param string $startDate Start date for subscription (optional)
 * @return array|bool Subscription data or false on failure
 */
function paystackCreateSubscription($email, $plan, $authorizationCode, $startDate = null) {
    $url = 'https://api.paystack.co/subscription';
    
    $data = [
        'customer' => $email,
        'plan' => $plan,
        'authorization' => $authorizationCode
    ];
    
    if ($startDate) {
        $data['start'] = $startDate;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log('Paystack API error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    if ($httpStatus >= 200 && $httpStatus < 300) {
        return json_decode($response, true);
    }
    
    error_log('Paystack API error (' . $httpStatus . '): ' . $response);
    return false;
}

/**
 * List Paystack subscriptions
 * 
 * @param int $perPage Number of subscriptions per page
 * @param int $page Page number
 * @return array|bool Subscriptions or false on failure
 */
function paystackListSubscriptions($perPage = 10, $page = 1) {
    $url = 'https://api.paystack.co/subscription?perPage=' . $perPage . '&page=' . $page;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log('Paystack API error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    if ($httpStatus >= 200 && $httpStatus < 300) {
        return json_decode($response, true);
    }
    
    error_log('Paystack API error (' . $httpStatus . '): ' . $response);
    return false;
}

/**
 * Get Paystack subscription by ID
 * 
 * @param string $subscriptionId Subscription ID
 * @return array|bool Subscription data or false on failure
 */
function paystackGetSubscription($subscriptionId) {
    $url = 'https://api.paystack.co/subscription/' . urlencode($subscriptionId);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log('Paystack API error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    if ($httpStatus >= 200 && $httpStatus < 300) {
        return json_decode($response, true);
    }
    
    error_log('Paystack API error (' . $httpStatus . '): ' . $response);
    return false;
}

/**
 * Enable a Paystack subscription
 * 
 * @param string $subscriptionCode Subscription code
 * @param string $token Token from email
 * @return array|bool Response or false on failure
 */
function paystackEnableSubscription($subscriptionCode, $token) {
    $url = 'https://api.paystack.co/subscription/enable';
    
    $data = [
        'code' => $subscriptionCode,
        'token' => $token
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log('Paystack API error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    if ($httpStatus >= 200 && $httpStatus < 300) {
        return json_decode($response, true);
    }
    
    error_log('Paystack API error (' . $httpStatus . '): ' . $response);
    return false;
}

/**
 * Disable a Paystack subscription
 * 
 * @param string $subscriptionCode Subscription code
 * @param string $token Token from email
 * @return array|bool Response or false on failure
 */
function paystackDisableSubscription($subscriptionCode, $token) {
    $url = 'https://api.paystack.co/subscription/disable';
    
    $data = [
        'code' => $subscriptionCode,
        'token' => $token
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log('Paystack API error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    if ($httpStatus >= 200 && $httpStatus < 300) {
        return json_decode($response, true);
    }
    
    error_log('Paystack API error (' . $httpStatus . '): ' . $response);
    return false;
}

/**
 * Process Paystack webhook event
 * 
 * @param array $webhookData Webhook data
 * @return bool True on success, false on failure
 */
function processPaystackWebhook($webhookData) {
    if (empty($webhookData) || empty($webhookData['event']) || empty($webhookData['data'])) {
        return false;
    }
    
    $event = $webhookData['event'];
    $data = $webhookData['data'];
    
    switch ($event) {
        case 'charge.success':
            // Handle successful charge
            return processSuccessfulPayment($data['reference']);
            
        case 'subscription.create':
            // Handle new subscription
            error_log('New subscription created: ' . json_encode($data));
            return true;
            
        case 'invoice.create':
            // Handle new invoice
            error_log('New invoice created: ' . json_encode($data));
            return true;
            
        case 'invoice.payment_failed':
            // Handle failed payment
            error_log('Invoice payment failed: ' . json_encode($data));
            
            // Update order status to failed
            global $pdo;
            $stmt = $pdo->prepare("UPDATE orders SET status = 'failed' WHERE payment_ref = ?");
            $stmt->execute([$data['invoice']['reference']]);
            
            return true;
            
        default:
            // Log unknown event
            error_log('Unknown Paystack event: ' . $event . ' - ' . json_encode($data));
            return false;
    }
}

/**
 * Get Paystack payment form HTML
 * 
 * @param float $amount Amount in GHS
 * @param string $email Customer email
 * @param string $reference Unique transaction reference
 * @param string $callbackUrl Callback URL
 * @param array $metadata Additional metadata
 * @return string HTML for payment form
 */
function getPaystackPaymentForm($amount, $email, $reference, $callbackUrl, $metadata = []) {
    return '
    <form id="paystack-payment-form">
        <script src="https://js.paystack.co/v1/inline.js" 
                data-key="' . PAYSTACK_PUBLIC_KEY . '"
                data-email="' . htmlspecialchars($email) . '"
                data-amount="' . ($amount * 100) . '"
                data-reference="' . htmlspecialchars($reference) . '"
                data-callback="' . htmlspecialchars($callbackUrl) . '"
                data-metadata=\'' . htmlspecialchars(json_encode($metadata)) . '\'
                data-label="Proceed to Payment"
                data-theme-color="#1a73e8">
        </script>
        <button type="button" onclick="payWithPaystack()" class="btn btn-primary">
            <i class="fas fa-credit-card"></i> Pay Now
        </button>
    </form>
    
    <script>
        function payWithPaystack() {
            var handler = PaystackPop.setup({
                key: "' . PAYSTACK_PUBLIC_KEY . '",
                email: "' . $email . '",
                amount: ' . ($amount * 100) . ',
                ref: "' . $reference . '",
                meta ' . json_encode($metadata) . ',
                callback: function(response) {
                    window.location.href = "' . $callbackUrl . '?reference=" + response.reference;
                },
                onClose: function() {
                    alert("Payment window closed. Please try again.");
                }
            });
            handler.openIframe();
        }
    </script>
    ';
}

/**
 * Check if Paystack is configured properly
 * 
 * @return bool True if configured, false otherwise
 */
function isPaystackConfigured() {
    return !empty(PAYSTACK_SECRET_KEY) && 
           !empty(PAYSTACK_PUBLIC_KEY) && 
           PAYSTACK_SECRET_KEY !== 'sk_test_your_test_secret_key_here' &&
           PAYSTACK_PUBLIC_KEY !== 'pk_test_your_test_public_key_here';
}