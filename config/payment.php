<?php
/**
 * Payment Gateway Configuration
 * Razorpay Integration Settings
 */

// Razorpay Environment (test/live)
define('RAZORPAY_ENV', 'test'); // Change to 'live' for production

// Razorpay Test API Keys
// Get your keys from: https://dashboard.razorpay.com/app/keys
define('RAZORPAY_TEST_KEY_ID', 'rzp_test_yourkeyid'); // Replace with your test key
define('RAZORPAY_TEST_KEY_SECRET', 'yourtestsecret'); // Replace with your test secret

// Razorpay Live API Keys (for production)
define('RAZORPAY_LIVE_KEY_ID', ''); // Add when going live
define('RAZORPAY_LIVE_KEY_SECRET', ''); // Add when going live

// Get active keys based on environment
function getRazorpayKeyId() {
    return RAZORPAY_ENV === 'live' ? RAZORPAY_LIVE_KEY_ID : RAZORPAY_TEST_KEY_ID;
}

function getRazorpayKeySecret() {
    return RAZORPAY_ENV === 'live' ? RAZORPAY_LIVE_KEY_SECRET : RAZORPAY_TEST_KEY_SECRET;
}

function getRazorpayWebhookSecret() {
    return RAZORPAY_WEBHOOK_SECRET;
}

// Payment Settings
define('RAZORPAY_CURRENCY', 'INR');
define('RAZORPAY_NAME', 'Super Optical');
define('RAZORPAY_DESCRIPTION', 'Order Payment');
define('RAZORPAY_LOGO', ''); // Optional: Add logo URL

// Webhook Secret (for payment verification)
define('RAZORPAY_WEBHOOK_SECRET', ''); // Optional: Set up webhooks later

// Enable/Disable Payment Methods
define('ENABLE_COD', true);
define('ENABLE_ONLINE_PAYMENT', true);

?>
