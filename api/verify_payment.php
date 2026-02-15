<?php
/**
 * Verify Razorpay Payment API Endpoint
 * Called after successful payment from frontend
 */

require_once '../config/db.php';
require_once '../config/payment.php';
require_once '../includes/razorpay/RazorpayAPI.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Verify user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Get payment details from POST
    $razorpayOrderId = $_POST['razorpay_order_id'] ?? '';
    $razorpayPaymentId = $_POST['razorpay_payment_id'] ?? '';
    $razorpaySignature = $_POST['razorpay_signature'] ?? '';
    
    if (empty($razorpayOrderId) || empty($razorpayPaymentId) || empty($razorpaySignature)) {
        throw new Exception('Missing payment parameters');
    }
    
    // Verify session order ID matches
    if (!isset($_SESSION['razorpay_order_id']) || $_SESSION['razorpay_order_id'] !== $razorpayOrderId) {
        throw new Exception('Invalid order ID');
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

    // Verify payment signature
    $razorpay = new RazorpayAPI($apiKey, $apiSecret);
    
    if (!$razorpay->verifyPaymentSignature($razorpayOrderId, $razorpayPaymentId, $razorpaySignature)) {
        throw new Exception('Payment verification failed');
    }
    
    // Fetch payment details from Razorpay
    $paymentDetails = $razorpay->fetchPayment($razorpayPaymentId);
    
    // Verify amount matches
    $sessionAmount = $_SESSION['razorpay_amount'] ?? 0;
    $paidAmount = $paymentDetails['amount'] / 100; // Convert paise to rupees
    
    if (abs($paidAmount - $sessionAmount) > 0.01) {
        throw new Exception('Amount mismatch');
    }
    
    // Create order in database
    $cart = $_SESSION['cart'] ?? [];
    $total = $sessionAmount;
    $checkout = $_SESSION['checkout_data'];
    $userId = $_SESSION['user_id'];
    $orderNumber = 'ORD-' . strtoupper(uniqid());
    $paymentMethod = $paymentDetails['method'] ?? 'online';
    
    $pdo->beginTransaction();
    
    // Create Order with payment details
    $stmt = $pdo->prepare("INSERT INTO orders (
        order_number, user_id, customer_name, phone, total_amount, address, 
        status, payment_method, razorpay_order_id, razorpay_payment_id, 
        razorpay_signature, payment_status, paid_at
    ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, 'paid', NOW())");
    
    $stmt->execute([
        $orderNumber, $userId, $checkout['customer_name'], $checkout['phone'],
        $total, $checkout['address'], $paymentMethod, $razorpayOrderId,
        $razorpayPaymentId, $razorpaySignature
    ]);
    
    $orderId = $pdo->lastInsertId();
    
    // Create Order Items & Reduce Stock
    foreach ($cart as $item) {
        $p_stmt = $pdo->prepare("SELECT name, sku, stock_quantity FROM products WHERE id = ?");
        $p_stmt->execute([$item['id']]);
        $product = $p_stmt->fetch();
        
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, sku, quantity, price) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$orderId, $item['id'], $product['name'] ?? $item['name'], $product['sku'] ?? '', $item['quantity'], $item['price']]);
        
        // Reduce stock
        if (isset($product['stock_quantity']) && $product['stock_quantity'] !== null) {
            $new_stock = max(0, $product['stock_quantity'] - $item['quantity']);
            $update_stock = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            $update_stock->execute([$new_stock, $item['id']]);
            
            checkStockAndNotify($item['id']);
        }
    }
    
    // Log payment transaction
    $logStmt = $pdo->prepare("INSERT INTO payment_logs (
        order_id, razorpay_order_id, razorpay_payment_id, amount, 
        currency, status, method, response_data
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $logStmt->execute([
        $orderId, $razorpayOrderId, $razorpayPaymentId, $total,
        RAZORPAY_CURRENCY, 'success', $paymentMethod, json_encode($paymentDetails)
    ]);
    
    $pdo->commit();
    
    // Create admin notification
    createAdminNotification(
        'new_order',
        '💰 New Paid Order!',
        'Order ' . $orderNumber . ' from ' . $checkout['customer_name'] . ' (₹' . number_format($total, 2) . ') - Payment ID: ' . $razorpayPaymentId,
        $orderId
    );
    
    // Send Confirmation Emails
    try {
        require_once '../includes/order_templates.php';
        $userEmail = $_SESSION['user_email'] ?? '';
        if (!$userEmail) {
            $u_stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $u_stmt->execute([$userId]);
            $userEmail = $u_stmt->fetchColumn();
        }
        if ($userEmail) {
            $customerSubject = "Payment Successful - Order #$orderNumber";
            $customerBody = getOrderEmailTemplate($checkout['customer_name'], $orderId, $cart, $total, $checkout['address']);
            sendEmail($userEmail, $customerSubject, $customerBody);
        }
        $adminEmail = getSetting('contact_email', 'info@superoptical.in');
        $adminSubject = "💰 New Paid Order: #$orderNumber";
        $adminBody = getAdminOrderAlertTemplate($checkout['customer_name'], $orderId, $total);
        sendEmail($adminEmail, $adminSubject, $adminBody);
    } catch (Exception $e) {
        error_log("Order email failed: " . $e->getMessage());
    }
    
    // Clear session data
    unset($_SESSION['cart']);
    unset($_SESSION['checkout_data']);
    unset($_SESSION['razorpay_order_id']);
    unset($_SESSION['razorpay_amount']);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'redirect_url' => '../order_status.php?id=' . $orderId . '&status=success'
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log failed payment attempt
    if (isset($razorpayPaymentId)) {
        try {
            $errorLog = $pdo->prepare("INSERT INTO payment_logs (
                razorpay_order_id, razorpay_payment_id, amount, status, error_description
            ) VALUES (?, ?, ?, 'failed', ?)");
            $errorLog->execute([
                $razorpayOrderId ?? '',
                $razorpayPaymentId,
                $_SESSION['razorpay_amount'] ?? 0,
                $e->getMessage()
            ]);
        } catch (Exception $logError) {
            error_log("Failed to log payment error: " . $logError->getMessage());
        }
    }
    
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
