<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

// Handle Status Update
if (isset($_POST['update_status'])) {
    $id = $_POST['order_id'];
    $status = $_POST['status'];
    
    // Get Order & User Details for Email
    $stmt = $pdo->prepare("SELECT o.*, u.email, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if ($order) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $id]);
        
        // Send Status Update Email
        require_once '../includes/order_templates.php';
        $subject = "Order #$id Status Updated - Super Optical";
        $body = getOrderStatusUpdateTemplate($order['customer_name'], $id, $status);
        sendEmail($order['email'], $subject, $body);

        setFlash('success', 'Order status updated to ' . strtoupper($status) . ' and customer notified.');
    }
    redirect('orders.php');
}

$orders = $pdo->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();
?>

<?php require_once 'header.php'; ?>
<?php
// Handle Status Update
if (isset($_POST['update_status'])) {
    $id = $_POST['order_id'];
    $status = $_POST['status'];
    
    // Get Order & User Details for Email
    $stmt = $pdo->prepare("SELECT o.*, u.email, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if ($order) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$status, $id]);
        
        // Send Status Update Email
        require_once '../includes/order_templates.php';
        $subject = "Order #$id Status Updated - Super Optical";
        $body = getOrderStatusUpdateTemplate($order['customer_name'], $id, $status);
        sendEmail($order['email'], $subject, $body);

        setFlash('success', __('status_updated_success', 'Order status updated successfully'));
    }
    redirect('orders.php');
}

$orders = $pdo->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <div>
        <h1 class="admin-title" style="margin-bottom: 0.5rem;"><?= __('orders_management') ?></h1>
        <p style="color: var(--admin-text-light); font-weight: 500; margin: 0;"><?= __('orders_subtitle') ?></p>
    </div>
</div>

<div class="admin-table-widget">
    <div class="widget-header">
        <div class="widget-title">
            <i class="fa-solid fa-clipboard-list" style="color: var(--admin-primary);"></i>
            <?= __('order_list_desc') ?>
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-light);">
            <?= count($orders) ?> <?= __('items') ?>
        </div>
    </div>
    
    <div class="widget-content">
        <table class="widget-table">
            <thead>
                <tr>
                    <th width="100"><?= __('order_id') ?></th>
                    <th><?= __('customer') ?></th>
                    <th><?= __('total_amount') ?></th>
                    <th><?= __('order_date') ?></th>
                    <th><?= __('status') ?></th>
                    <th width="150" style="text-align: center;"><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--admin-text-light);">
                            <?= __('no_orders') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td style="font-weight: 700; color: var(--admin-sidebar);">#<?= $o['id'] ?></td>
                        <td>
                            <div style="font-weight: 600; color: var(--admin-text);"><?= htmlspecialchars($o['user_name']) ?></div>
                        </td>
                        <td style="font-weight: 700; color: var(--admin-primary);">₹<?= number_format($o['total_amount'], 2) ?></td>
                        <td style="color: var(--admin-text-light); font-size: 0.9rem;"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="status" onchange="this.form.submit()" class="status-badge status-<?= $o['status'] ?>" style="border: none; cursor: pointer; padding-right: 1.5rem; appearance: none; -webkit-appearance: none; background-image: none;">
                                    <option value="pending" <?= $o['status'] == 'pending' ? 'selected' : '' ?>><?= __('pending') ?></option>
                                    <option value="shipped" <?= $o['status'] == 'shipped' ? 'selected' : '' ?>><?= __('shipped') ?></option>
                                    <option value="completed" <?= $o['status'] == 'completed' ? 'selected' : '' ?>><?= __('completed') ?></option>
                                    <option value="cancelled" <?= $o['status'] == 'cancelled' ? 'selected' : '' ?>><?= __('cancelled') ?></option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; justify-content: center; align-items: center;">
                                <a href="order_view.php?id=<?= $o['id'] ?>" class="btn-action btn-action-view" title="<?= __('view_order') ?>">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
