<?php
/**
 * Create Shiprocket Shipment for an Order
 * 
 * This endpoint creates a shipment in Shiprocket for a given order
 */

require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/IntegrationManager.php';
require_once '../../includes/shiprocket/ShiprocketAPI.php';

// Must be admin
if (!isAdmin()) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if (!isset($_GET['order_id'])) {
    die(json_encode(['success' => false, 'message' => 'Order ID required']));
}

$orderId = $_GET['order_id'];

try {
    // Get order details
    $stmt = $pdo->prepare("
        SELECT o.*, u.name, u.email, u.phone 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        throw new Exception('Order not found');
    }
    
    // Check if already shipped via Shiprocket
    if (!empty($order['shiprocket_order_id'])) {
        setFlash('error', 'Shipment already created for this order');
        header('Location: ../../admin/order_view.php?id=' . $orderId);
        exit;
    }
    
    // Get Shiprocket configuration
    $integrationManager = IntegrationManager::getInstance($pdo);
    $shiprocketConfig = $integrationManager->getIntegration('shiprocket');
    
    if (!$shiprocketConfig || !$shiprocketConfig['is_enabled']) {
        throw new Exception('Shiprocket is not configured or enabled');
    }
    
    // Get order items
    $itemsStmt = $pdo->prepare("
        SELECT oi.*, p.name, p.weight 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $itemsStmt->execute([$orderId]);
    $items = $itemsStmt->fetchAll();
    
    // Build Shiprocket order items array
    $orderItems = [];
    $totalWeight = 0;
    foreach ($items as $item) {
        $orderItems[] = [
            'name' => $item['name'],
            'sku' => 'PROD-' . $item['product_id'],
            'units' => $item['quantity'],
            'selling_price' => $item['price'],
            'discount' => 0,
            'tax' => 0,
            'hsn' => ''
        ];
        $totalWeight += ($item['weight'] ?? 0.5) * $item['quantity'];
    }
    
    // If total weight is 0, set default
    if ($totalWeight == 0) {
        $totalWeight = 0.5;
    }
    
    // Parse address (assuming format: "address, city, state, pincode")
    $addressParts = explode(',', $order['address']);
    $address = trim($addressParts[0] ?? $order['address']);
    $city = trim($addressParts[1] ?? '');
    $state = trim($addressParts[2] ?? '');
    $pincode = trim($addressParts[3] ?? '');
    
    // Split customer name into first and last
    $nameParts = explode(' ', $order['name'], 2);
    $firstName = $nameParts[0];
    $lastName = $nameParts[1] ?? '';
    
    // Initialize Shiprocket API
    $shiprocket = new ShiprocketAPI(
        $shiprocketConfig['config']['email'],
        $shiprocketConfig['config']['password']
    );
    
    // Prepare order data for Shiprocket
    $orderData = [
        'order_id' => 'ORD-' . $orderId,
        'order_date' => date('Y-m-d H:i', strtotime($order['created_at'])),
        'pickup_location' => 'Primary',
        'billing_customer_name' => $firstName,
        'billing_last_name' => $lastName,
        'billing_address' => $address,
        'billing_city' => $city ?: 'Unknown',
        'billing_pincode' => $pincode ?: '000000',
        'billing_state' => $state ?: 'Unknown',
        'billing_country' => 'India',
        'billing_email' => $order['email'],
        'billing_phone' => $order['phone'] ?: '0000000000',
        'shipping_is_billing' => true,
        'order_items' => $orderItems,
        'payment_method' => $order['payment_status'] === 'paid' ? 'Prepaid' : 'COD',
        'sub_total' => $order['total_amount'],
        'weight' => $totalWeight
    ];
    
    // Create order in Shiprocket
    $result = $shiprocket->createOrder($orderData);
    
    if (isset($result['order_id']) && isset($result['shipment_id'])) {
        // Update order with Shiprocket details
        $updateStmt = $pdo->prepare("
            UPDATE orders 
            SET shiprocket_order_id = ?, 
                shiprocket_shipment_id = ?,
                status = 'processing'
            WHERE id = ?
        ");
        $updateStmt->execute([
            $result['order_id'],
            $result['shipment_id'],
            $orderId
        ]);
        
        setFlash('success', 'Shipment created successfully in Shiprocket!');
    } else {
        throw new Exception('Failed to create shipment in Shiprocket');
    }
    
    // Redirect back to order view
    header('Location: ../../admin/order_view.php?id=' . $orderId);
    exit;
    
} catch (Exception $e) {
    error_log('Shiprocket shipment creation error: ' . $e->getMessage());
    setFlash('error', 'Failed to create shipment: ' . $e->getMessage());
    header('Location: ../../admin/order_view.php?id=' . $orderId);
    exit;
}
?>
