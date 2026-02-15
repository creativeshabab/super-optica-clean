<?php require_once 'header.php'; ?>

<?php
// Fetch Stats
$prod_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$order_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$user_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status='completed'")->fetchColumn() ?: 0;

// Fetch Recent Orders (New Widget)
$recent_orders = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Stock Alerts
$low_stock = $pdo->query("SELECT * FROM products WHERE stock_quantity IS NOT NULL AND stock_quantity > 0 AND stock_quantity <= low_stock_threshold ORDER BY stock_quantity ASC LIMIT 5")->fetchAll();
$out_of_stock = $pdo->query("SELECT * FROM products WHERE stock_quantity = 0 ORDER BY id DESC LIMIT 5")->fetchAll();

// Recent Notifications
$recent_notifications = $pdo->query("SELECT * FROM admin_notifications ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>

<h1 class="admin-title"><?= __('dashboard_overview') ?></h1>

<!-- Modern Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
            <i class="fa-solid fa-glasses"></i>
        </div>
        <div class="stat-info">
            <h3><?= __('total_products') ?></h3>
            <p><?= number_format($prod_count) ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(22, 163, 74, 0.1); color: #16a34a;">
            <i class="fa-solid fa-cart-shopping"></i>
        </div>
        <div class="stat-info">
            <h3><?= __('total_orders') ?></h3>
            <p><?= number_format($order_count) ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(147, 51, 234, 0.1); color: #9333ea;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div class="stat-info">
            <h3><?= __('total_customers') ?></h3>
            <p><?= number_format($user_count) ?></p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
            <i class="fa-solid fa-indian-rupee-sign"></i>
        </div>
        <div class="stat-info">
            <h3><?= __('total_revenue') ?></h3>
            <p>₹<?= number_format($revenue) ?></p>
        </div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Main Column: Recent Orders -->
    <div class="main-col">
        <div class="admin-table-widget">
            <div class="widget-header">
                <div class="widget-title">
                    <i class="fa-solid fa-clock-rotate-left" style="color: var(--admin-primary);"></i>
                    <?= __('recent_orders') ?>
                </div>
                <a href="orders.php" class="btn-action btn-action-view" title="<?= __('view_all') ?>">
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            
            <div class="widget-content">
                <?php if (empty($recent_orders)): ?>
                    <p style="padding: 2rem; text-align: center; color: var(--admin-text-light);"><?= __('no_recent_orders') ?></p>
                <?php else: ?>
                    <table class="widget-table">
                        <thead>
                            <tr>
                                <th><?= __('order_id') ?></th>
                                <th><?= __('customer') ?></th>
                                <th><?= __('amount') ?></th>
                                <th><?= __('status') ?></th>
                                <th><?= __('date') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td data-label="<?= __('order_id') ?>">#<?= $order['id'] ?></td>
                                    <td data-label="<?= __('customer') ?>" style="font-weight: 500;"><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></td>
                                    <td data-label="<?= __('amount') ?>">₹<?= number_format($order['total_amount'], 2) ?></td>
                                    <td data-label="<?= __('status') ?>">
                                        <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td data-label="<?= __('date') ?>" style="color: var(--admin-text-light); font-size: 0.8rem;">
                                        <?= date('M d', strtotime($order['created_at'])) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stock Alerts -->
        <div class="admin-grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <!-- Low Stock -->
            <div class="admin-table-widget" style="border-left: 4px solid #f59e0b;">
                <div class="widget-header">
                    <div class="widget-title">
                        <i class="fa-solid fa-triangle-exclamation" style="color: #f59e0b;"></i>
                        <?= __('low_stock') ?>
                    </div>
                </div>
                <div class="widget-content">
                    <?php if (empty($low_stock)): ?>
                        <p style="padding: 1rem; text-align: center; font-size: 0.85rem; color: #16a34a;">
                            <i class="fa-solid fa-check-circle"></i> <?= __('stock_good') ?>
                        </p>
                    <?php else: ?>
                        <?php foreach ($low_stock as $item): ?>
                            <div style="display: flex; justify-content: space-between; padding: 0.75rem 1rem; border-bottom: 1px solid var(--admin-border); align-items: center;">
                                <div>
                                    <div style="font-weight: 600; font-size: 0.85rem; color: var(--admin-text);"><?= htmlspecialchars($item['name']) ?></div>
                                    <div style="font-size: 0.75rem; color: #f59e0b;"><?= __('stock') ?>: <?= $item['stock_quantity'] ?></div>
                                </div>
                                <a href="product_form.php?id=<?= $item['id'] ?>" class="btn-action btn-action-edit" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Out of Stock -->
            <div class="admin-table-widget" style="border-left: 4px solid #ef4444;">
                <div class="widget-header">
                    <div class="widget-title">
                        <i class="fa-solid fa-box-open" style="color: #ef4444;"></i>
                        <?= __('out_of_stock') ?>
                    </div>
                </div>
                <div class="widget-content">
                    <?php if (empty($out_of_stock)): ?>
                        <p style="padding: 1rem; text-align: center; font-size: 0.85rem; color: #16a34a;">
                            <i class="fa-solid fa-check-circle"></i> <?= __('all_stocked') ?>
                        </p>
                    <?php else: ?>
                        <?php foreach ($out_of_stock as $item): ?>
                            <div style="display: flex; justify-content: space-between; padding: 0.75rem 1rem; border-bottom: 1px solid var(--admin-border); align-items: center;">
                                <div>
                                    <div style="font-weight: 600; font-size: 0.85rem; color: var(--admin-text);"><?= htmlspecialchars($item['name']) ?></div>
                                    <div style="font-size: 0.75rem; color: #ef4444; font-weight: 700;"><?= __('out_of_stock_msg') ?></div>
                                </div>
                                <a href="product_form.php?id=<?= $item['id'] ?>" class="btn-action btn-action-edit" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                    <i class="fa-solid fa-plus"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Notifications & Quick Actions -->
    <div class="side-col">
        <div class="card" style="padding: 0; overflow: hidden;">
            <div class="widget-header">
                <div class="widget-title">
                    <i class="fa-solid fa-bell" style="color: var(--admin-primary);"></i>
                    <?= __('notifications') ?>
                </div>
                <a href="notifications.php" style="font-size: 0.75rem; color: var(--admin-primary); font-weight: 600; text-decoration: none;"><?= __('view') ?> <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            
            <div class="widget-content">
                <?php if (empty($recent_notifications)): ?>
                    <p style="padding: 2rem; text-align: center; color: var(--admin-text-light);"><?= __('no_notifications') ?></p>
                <?php else: ?>
                    <?php foreach ($recent_notifications as $notif): ?>
                        <div style="display: flex; gap: 0.75rem; padding: 1rem; border-bottom: 1px solid var(--admin-border); align-items: start;">
                            <?php
                                $icon_color = match($notif['type']) {
                                    'new_order' => '#10b981',
                                    'low_stock' => '#f59e0b',
                                    'out_of_stock' => '#ef4444',
                                    default => '#64748b'
                                };
                                $icon = match($notif['type']) {
                                    'new_order' => 'fa-cart-shopping',
                                    'low_stock' => 'fa-triangle-exclamation',
                                    'out_of_stock' => 'fa-box-open',
                                    default => 'fa-bell'
                                };
                            ?>
                            <div style="width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: <?= $icon_color ?>15; flex-shrink: 0;">
                                <i class="fa-solid <?= $icon ?>" style="color: <?= $icon_color ?>; font-size: 0.85rem;"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 0.85rem; color: var(--admin-text); line-height: 1.3;"><?= htmlspecialchars($notif['title']) ?></div>
                                <div style="color: var(--admin-text-light); font-size: 0.75rem; margin-top: 0.25rem;">
                                    <?= date('M j, g:i A', strtotime($notif['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Links Card -->
        <div class="card" style="margin-top: 1.5rem;">
            <div class="card-title" style="font-size: 1rem; margin-bottom: 1rem;">
                <?= __('quick_actions') ?>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <a href="product_form.php" class="btn btn-secondary" style="justify-content: center; flex-direction: column; gap: 0.5rem; padding: 1rem; height: auto;">
                    <i class="fa-solid fa-plus text-primary" style="font-size: 1.25rem;"></i>
                    <span style="font-size: 0.8rem;"><?= __('add_product') ?></span>
                </a>
                <a href="coupons.php" class="btn btn-secondary" style="justify-content: center; flex-direction: column; gap: 0.5rem; padding: 1rem; height: auto;">
                    <i class="fa-solid fa-ticket text-primary" style="font-size: 1.25rem;"></i>
                    <span style="font-size: 0.8rem;"><?= __('create_coupon') ?></span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
