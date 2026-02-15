<?php
// DEBUG VIEW - CLONE OF ORDER_VIEW with Checkpoints
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Force ID 23 if not set
if (!isset($_GET['id'])) $_GET['id'] = 23;

echo "CP 1: Start<br>"; flush();

require_once '../config/db.php';
echo "CP 2: DB Included<br>"; flush();

require_once '../includes/functions.php';
echo "CP 3: Functions Included<br>"; flush();

$stmt = $pdo->prepare("
    SELECT o.*, u.name, u.email, sm.name as shipping_method_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id 
    WHERE o.id = ?
");
$stmt->execute([$_GET['id']]);
$order = $stmt->fetch();

echo "CP 4: Order Fetched<br>"; flush();

if (!$order) die("Order not found");

// Skip POST logic for debug view to keep it simple

require_once 'header.php';
echo "CP 5: Header Included<br>"; flush();

$items = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items->execute([$order['id']]);
$orderItems = $items->fetchAll();

echo "CP 6: Items Fetched (" . count($orderItems) . " items)<br>"; flush();
?>

<div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
    <h1 class="admin-title" style="margin-bottom: 0;">
        <?php echo "CP 7: Before Title Translation<br>"; flush(); ?>
        <?= __('view_order') ?> <span style="color: var(--admin-text-light); font-weight: 400;">#<?= $order['id'] ?></span>
    </h1>
    <a href="orders.php" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> <?= __('back_to_orders') ?>
    </a>
</div>

<?php echo "CP 8: Before Grid<br>"; flush(); ?>

<div class="admin-grid">
    <!-- Left Column: Order Items -->
    <div class="card">
        <h3 class="card-title"><?= __('order_items') ?></h3>
        <div class="table-container">
            <table style="border-spacing: 0;">
                <thead>
                    <tr>
                        <th><?= __('image') ?></th>
                        <th><?= __('product_name') ?></th>
                        <th><?= __('price') ?></th>
                        <th><?= __('quantity') ?></th>
                        <th><?= __('subtotal') ?: 'Subtotal' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php echo "CP 9: Start Loop<br>"; flush(); ?>
                    <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td>
                            <div style="width: 64px; height: 60px;">
                                <?php if($item['image']): ?>
                                    <img src="../assets/uploads/<?= $item['image'] ?>" style="width:100%">
                                <?php else: ?>
                                    Icon
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td>₹<?= number_format($item['price'], 2) ?></td>
                        <td>x<?= $item['quantity'] ?></td>
                        <td>₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php echo "CP 10: End Loop<br>"; flush(); ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 3rem;">
            <h1>₹<?= number_format($order['total_amount'], 2) ?></h1>
        </div>
    </div>

    <?php echo "CP 11: Right Column Start<br>"; flush(); ?>

    <!-- Right Column: Info & Actions -->
    <div>
        <!-- Customer Info -->
        <div class="card">
            <h3 class="card-title"><?= __('customer_info') ?></h3>
            <div class="info-list">
                <div class="info-item">
                    <span><?= __('customer') ?></span>
                    <span><?= htmlspecialchars($order['customer_name'] ?? $order['name']) ?></span>
                </div>
                <!-- POTENTIAL CRASH: Phone if missing? -->
                <?php echo "CP 12: Before Phone<br>"; flush(); ?>
                <div class="info-item">
                    <span><?= __('mobile_number') ?: 'Contact Number' ?></span>
                    <span><?= htmlspecialchars($order['phone'] ?? 'N/A') ?></span>
                </div>
                <div class="info-item">
                    <span><?= __('email_mobile') ?: 'Email Address' ?></span>
                    <span><?= htmlspecialchars($order['email']) ?></span>
                </div>
                <div class="info-item">
                    <span><?= __('order_date') ?></span>
                    <span><?= date('M d, Y | h:i A', strtotime($order['created_at'])) ?></span>
                </div>
            </div>
        </div>

        <?php echo "CP 13: Payment Info<br>"; flush(); ?>

        <!-- Payment Info -->
        <div class="card">
            <h3 class="card-title"><?= __('payment_info') ?></h3>
            <div class="info-list">
                <div class="info-item">
                    <span><?= __('payment_info') ?: 'Payment Method' ?></span>
                    <span><?= htmlspecialchars($order['payment_method']) ?></span>
                </div>
                <div class="info-item">
                    <span><?= __('payment_status') ?></span>
                    <span><?= $order['payment_status'] ?? 'pending' ?></span>
                </div>
            </div>
        </div>

        <?php echo "CP 14: Shipping Info<br>"; flush(); ?>

        <!-- Shipping Info & Actions -->
        <div class="card">
            <h3 class="card-title"><?= __('shipping_info') ?></h3>
            <div class="info-list">
                <!-- Helper checks -->
                <?php if ($order['shipping_method_name']): ?>
                <div class="info-item">
                    <span>Method</span>
                    <span><?= htmlspecialchars($order['shipping_method_name']) ?></span>
                </div>
                <?php endif; ?>
                
                <?php echo "CP 15: Address Section<br>"; flush(); ?>
                
                <div>
                   <p><?= nl2br(htmlspecialchars($order['address'])) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo "CP 16: Footer<br>"; flush(); ?>

<?php require_once 'footer.php'; ?>

<?php echo "CP 17: End of File<br>"; flush(); ?>
