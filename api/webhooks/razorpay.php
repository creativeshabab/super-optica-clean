<?php
/**
 * Razorpay Webhook Handler
 * Receives payment notifications from Razorpay and updates order status
 */

require_once '../../config/db.php';
require_once '../../config/payment.php';
require_once '../../includes/functions.php';

// Log incoming webhook for debugging (optional)
$logFile = __DIR__ . '/../../logs/razorpay_webhook.log';
file_put_contents($logFile, date('[Y-m-d H:i:s] ') . "Webhook received\n", FILE_APPEND);

// Get webhook body
$webhookBody = file_get_contents('php://input');
$webhookSignature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

// Log the payload
file_put_contents($logFile, "Payload: " . $webhookBody . "\n", FILE_APPEND);
file_put_contents($logFile, "Signature: " . $webhookSignature . "\n", FILE_APPEND);

try {
    // Verify webhook signature
    $webhookSecret = getRazorpayWebhookSecret();
    
    if (!empty($webhookSecret)) {
        $expectedSignature = hash_hmac('sha256', $webhookBody, $webhookSecret);
        
        if ($webhookSignature !== $expectedSignature) {
            file_put_contents($logFile, "Signature verification failed\n", FILE_APPEND);
            http_response_code(400);
            echo json_encode(['error' => 'Invalid signature']);
            exit;
        }
    }
    
    // Parse webhook data
    $data = json_decode($webhookBody, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON payload');
    }
    
    $event = $data['event'] ?? '';
    $payload = $data['payload'] ?? [];
    
    file_put_contents($logFile, "Event: " . $event . "\n", FILE_APPEND);
    
    // Handle different events
    switch ($event) {
        case 'payment.authorized':
        case 'payment.captured':
            handlePaymentSuccess($payload, $pdo, $logFile);
            break;
            
        case 'payment.failed':
            handlePaymentFailure($payload, $pdo, $logFile);
            break;
            
        case 'order.paid':
            handleOrderPaid($payload, $pdo, $logFile);
            break;
            
        default:
            file_put_contents($logFile, "Unhandled event: " . $event . "\n", FILE_APPEND);
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    
} catch (Exception $e) {
    file_put_contents($logFile, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Handle successful payment
 */
function handlePaymentSuccess($payload, $pdo, $logFile) {
    $payment = $payload['payment'] ?? [];
    $paymentEntity = $payment['entity'] ?? $payload['payment']['entity'] ?? [];
    
    $paymentId = $paymentEntity['id'] ?? '';
    $orderId = $paymentEntity['order_id'] ?? '';
    $amount = ($paymentEntity['amount'] ?? 0) / 100; // Convert paise to rupees
    $method = $paymentEntity['method'] ?? '';
    $status = $paymentEntity['status'] ?? '';
    
    file_put_contents($logFile, "Payment Success - ID: $paymentId, Order: $orderId, Amount: $amount\n", FILE_APPEND);
    
    // Update order in database
    try {
        // Find order by razorpay_order_id
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET payment_status = 'paid', 
                razorpay_payment_id = ?,
                paid_at = NOW(),
                status = 'processing'
            WHERE razorpay_order_id = ?
        ");
        
        $stmt->execute([$paymentId, $orderId]);
        
        if ($stmt->rowCount() > 0) {
            file_put_contents($logFile, "Order updated successfully\n", FILE_APPEND);
            
            // Get order details for notification
            $orderStmt = $pdo->prepare("SELECT id, order_number, customer_name, total_amount FROM orders WHERE razorpay_order_id = ?");
            $orderStmt->execute([$orderId]);
            $order = $orderStmt->fetch();
            
            if ($order) {
                // Create admin notification
                createAdminNotification(
                    'payment_success',
                    '💰 Payment Received!',
                    'Order ' . $order['order_number'] . ' - Payment of ₹' . number_format($order['total_amount'], 2) . ' received via ' . ucfirst($method),
                    $order['id']
                );
            }
        } else {
            file_put_contents($logFile, "No order found with razorpay_order_id: $orderId\n", FILE_APPEND);
        }
        
        // Log payment in payment_logs table
        $logStmt = $pdo->prepare("
            INSERT INTO payment_logs (
                razorpay_order_id, razorpay_payment_id, amount, currency, 
                status, method, response_data, created_at
            ) VALUES (?, ?, ?, 'INR', ?, ?, ?, NOW())
        ");
        
        $logStmt->execute([
            $orderId,
            $paymentId,
            $amount,
            $status,
            $method,
            json_encode($paymentEntity)
        ]);
        
    } catch (Exception $e) {
        file_put_contents($logFile, "Database error: " . $e->getMessage() . "\n", FILE_APPEND);
        throw $e;
    }
}

/**
 * Handle failed payment
 */
function handlePaymentFailure($payload, $pdo, $logFile) {
    $payment = $payload['payment'] ?? [];
    $paymentEntity = $payment['entity'] ?? $payload['payment']['entity'] ?? [];
    
    $paymentId = $paymentEntity['id'] ?? '';
    $orderId = $paymentEntity['order_id'] ?? '';
    $errorCode = $paymentEntity['error_code'] ?? '';
    $errorDescription = $paymentEntity['error_description'] ?? '';
    
    file_put_contents($logFile, "Payment Failed - ID: $paymentId, Error: $errorDescription\n", FILE_APPEND);
    
    // Log failed payment
    try {
        $logStmt = $pdo->prepare("
            INSERT INTO payment_logs (
                razorpay_order_id, razorpay_payment_id, status, 
                error_description, response_data, created_at
            ) VALUES (?, ?, 'failed', ?, ?, NOW())
        ");
        
        $logStmt->execute([
            $orderId,
            $paymentId,
            "$errorCode: $errorDescription",
            json_encode($paymentEntity)
        ]);
        
    } catch (Exception $e) {
        file_put_contents($logFile, "Database error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

/**
 * Handle order paid event
 */
function handleOrderPaid($payload, $pdo, $logFile) {
    $order = $payload['order'] ?? [];
    $orderEntity = $order['entity'] ?? $payload['order']['entity'] ?? [];
    
    $orderId = $orderEntity['id'] ?? '';
    $status = $orderEntity['status'] ?? '';
    
    file_put_contents($logFile, "Order Paid - ID: $orderId, Status: $status\n", FILE_APPEND);
    
    // Additional handling if needed
    try {
        $stmt = $pdo->prepare("
            UPDATE orders 
            SET payment_status = 'paid', 
                status = 'processing'
            WHERE razorpay_order_id = ? AND payment_status != 'paid'
        ");
        
        $stmt->execute([$orderId]);
        
        if ($stmt->rowCount() > 0) {
            file_put_contents($logFile, "Order marked as paid\n", FILE_APPEND);
        }
        
    } catch (Exception $e) {
        file_put_contents($logFile, "Database error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}
?>
