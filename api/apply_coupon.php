<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to use coupons.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$code = strtoupper(trim($_POST['code'] ?? ''));
$subtotal = getCartTotal();

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? LIMIT 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => 'Invalid coupon code.']);
        exit;
    }

    if ($coupon['is_active'] == 0) {
        echo json_encode(['success' => false, 'message' => 'This coupon is currently inactive.']);
        exit;
    }

    // Check expiry
    $now = date('Y-m-d H:i:s');
    if ($coupon['start_date'] && $coupon['start_date'] > $now) {
        echo json_encode(['success' => false, 'message' => 'This coupon is not yet active.']);
        exit;
    }
    if ($coupon['end_date'] && $coupon['end_date'] < $now) {
        echo json_encode(['success' => false, 'message' => 'This coupon has expired.']);
        exit;
    }

    // Check minimum order amount
    if ($subtotal < $coupon['min_order_amount']) {
        echo json_encode(['success' => false, 'message' => 'Minimum order amount for this coupon is ₹' . number_format($coupon['min_order_amount'], 2)]);
        exit;
    }

    // Check usage limit (simplified - just checks the 'usage_limit' column)
    // In a full implementation, you would count orders with this coupon_id
    if ($coupon['usage_limit'] !== null) {
        $usage_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE coupon_id = ?");
        $usage_stmt->execute([$coupon['id']]);
        $used_count = $usage_stmt->fetchColumn();
        if ($used_count >= $coupon['usage_limit']) {
            echo json_encode(['success' => false, 'message' => 'This coupon has reached its usage limit.']);
            exit;
        }
    }

    // Check if it's prepaid only
    // Note: This API just validates. The actual discount is applied in checkout_payment.php 
    // based on the final payment method.
    
    // Store in session
    $_SESSION['applied_coupon'] = [
        'id' => $coupon['id'],
        'code' => $coupon['code'],
        'type' => $coupon['type'],
        'value' => $coupon['value'],
        'is_prepaid_only' => $coupon['is_prepaid_only']
    ];

    $discount = 0;
    if ($coupon['type'] === 'percent') {
        $discount = ($subtotal * $coupon['value']) / 100;
    } else {
        $discount = $coupon['value'];
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Coupon applied successfully!',
        'discount' => $discount,
        'new_total' => $subtotal - $discount,
        'coupon_details' => $_SESSION['applied_coupon']
    ]);

} catch (Exception $e) {
    error_log("Coupon validation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while validating the coupon. Please try again.']);
}
