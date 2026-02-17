<?php
/**
 * Create Razorpay Order API Endpoint
 * Called via AJAX from checkout page
 */

// Start session first
session_start();

require_once '../config/db.php';
require_once '../config/payment.php';
require_once '../includes/razorpay/RazorpayAPI.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
if (!IS_PRODUCTION) {
    error_reporting(E_ALL);
    ini_set('display_errors', 0); // Don't display in output, but log them
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Verify cart and checkout data exist
if (empty($_SESSION['cart']) || empty($_SESSION['checkout_data'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid session data']);
    exit;
}

try {
    // Calculate cart total
    $total = 0;
    // Calculate cart total from DB to prevent tampering
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$item['id']]);
        $prod = $stmt->fetch();
        if ($prod) {
            $total += $prod['price'] * $item['quantity'];
        }
    }
    
    if ($total <= 0) {
        throw new Exception('Invalid order amount');
    }
    
    // Get Razorpay Configuration from Database
    require_once '../includes/IntegrationManager.php';
    $integrationManager = IntegrationManager::getInstance($pdo);
    $razorpayConfig = $integrationManager->getIntegration('razorpay');
    
    if (!$razorpayConfig || empty($razorpayConfig['is_enabled'])) {
        throw new Exception('Online payment is currently disabled');
    }
    
    $apiKey = $razorpayConfig['config']['key_id'] ?? '';
    $apiSecret = $razorpayConfig['config']['key_secret'] ?? '';
    
    if (empty($apiKey) || empty($apiSecret)) {
        throw new Exception('Razorpay configuration is missing');
    }

    // Create Razorpay order
    $razorpay = new RazorpayAPI($apiKey, $apiSecret);
    
    $orderData = $razorpay->createOrder(
        $total,
        RAZORPAY_CURRENCY,
        [
            'user_id' => $_SESSION['user_id'],
            'customer_name' => $_SESSION['checkout_data']['customer_name'],
            'phone' => $_SESSION['checkout_data']['phone']
        ]
    );
    
    // Store order details in session for verification
    $_SESSION['razorpay_order_id'] = $orderData['id'];
    $_SESSION['razorpay_amount'] = $total;
    
    // Return order details to frontend
    echo json_encode([
        'success' => true,
        'order_id' => $orderData['id'],
        'amount' => $total,
        'currency' => RAZORPAY_CURRENCY,
        'key_id' => $apiKey,
        'name' => RAZORPAY_NAME,
        'description' => RAZORPAY_DESCRIPTION,
        'prefill' => [
            'name' => $_SESSION['checkout_data']['customer_name'],
            'contact' => $_SESSION['checkout_data']['phone'],
            'email' => $_SESSION['user_email'] ?? ''
        ]
    ]);
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("Razorpay Order Creation Failed: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'debug' => !IS_PRODUCTION ? [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ] : null
    ]);
}
?>
