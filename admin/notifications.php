<?php
require_once 'header.php';

// Handle Mark as Read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $stmt = $pdo->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?");
    $stmt->execute([$_GET['mark_read']]);
    redirect('notifications.php');
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM admin_notifications WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    setFlash('success', 'Notification deleted');
    redirect('notifications.php');
}

// Handle Mark All as Read
if (isset($_POST['mark_all_read'])) {
    $pdo->query("UPDATE admin_notifications SET is_read = 1");
    setFlash('success', 'All notifications marked as read');
    redirect('notifications.php');
}

// Fetch Notifications
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT * FROM admin_notifications";
if ($filter !== 'all') {
    $sql .= " WHERE type = :type";
}
$sql .= " ORDER BY created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
if ($filter !== 'all') {
    $stmt->execute(['type' => $filter]);
} else {
    $stmt->execute();
}
$notifications = $stmt->fetchAll();
?>

<div class="admin-content">
    <div class="page-header">
        <div class="page-header-info">
            <h1 class="page-title"><i class="fa-solid fa-bell" style="color: var(--admin-primary);"></i> Notifications</h1>
        </div>
        <div class="page-header-actions">
            <form method="POST" style="display: inline;">
                <button type="submit" name="mark_all_read" class="btn btn-primary">
                    <i class="fa-solid fa-check-double"></i> Mark All as Read
                </button>
            </form>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="notification-filters" style="display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem;">
        <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>" style="padding: 0.5rem 1rem; color: <?= $filter === 'all' ? 'var(--admin-primary)' : '#64748b' ?>; font-weight: <?= $filter === 'all' ? 'bold' : 'normal' ?>; text-decoration: none; border-bottom: 2px solid <?= $filter === 'all' ? 'var(--admin-primary)' : 'transparent' ?>; margin-bottom: -0.5rem;">
            All (<?= count($notifications) ?>)
        </a>
        <a href="?filter=new_order" class="filter-tab <?= $filter === 'new_order' ? 'active' : '' ?>" style="padding: 0.5rem 1rem; color: <?= $filter === 'new_order' ? 'var(--admin-primary)' : '#64748b' ?>; font-weight: <?= $filter === 'new_order' ? 'bold' : 'normal' ?>; text-decoration: none; border-bottom: 2px solid <?= $filter === 'new_order' ? 'var(--admin-primary)' : 'transparent' ?>; margin-bottom: -0.5rem;">
            <i class="fa-solid fa-cart-shopping"></i> New Orders
        </a>
        <a href="?filter=low_stock" class="filter-tab <?= $filter === 'low_stock' ? 'active' : '' ?>" style="padding: 0.5rem 1rem; color: <?= $filter === 'low_stock' ? 'var(--admin-primary)' : '#64748b' ?>; font-weight: <?= $filter === 'low_stock' ? 'bold' : 'normal' ?>; text-decoration: none; border-bottom: 2px solid <?= $filter === 'low_stock' ? 'var(--admin-primary)' : 'transparent' ?>; margin-bottom: -0.5rem;">
            <i class="fa-solid fa-triangle-exclamation"></i> Low Stock
        </a>
        <a href="?filter=out_of_stock" class="filter-tab <?= $filter === 'out_of_stock' ? 'active' : '' ?>" style="padding: 0.5rem 1rem; color: <?= $filter === 'out_of_stock' ? 'var(--admin-primary)' : '#64748b' ?>; font-weight: <?= $filter === 'out_of_stock' ? 'bold' : 'normal' ?>; text-decoration: none; border-bottom: 2px solid <?= $filter === 'out_of_stock' ? 'var(--admin-primary)' : 'transparent' ?>; margin-bottom: -0.5rem;">
            <i class="fa-solid fa-box-open"></i> Out of Stock
        </a>
    </div>

    <!-- Notifications List -->
    <?php if (empty($notifications)): ?>
        <div class="empty-state" style="text-align: center; padding: 4rem; color: #94a3b8;">
            <i class="fa-solid fa-bell-slash" style="font-size: 4rem; margin-bottom: 1rem; color: #cbd5e1;"></i>
            <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">No notifications</h3>
            <p>You're all caught up! 🎉</p>
        </div>
    <?php else: ?>
        <div class="notifications-container">
            <?php foreach ($notifications as $notif): ?>
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
                <div class="notification-item <?= $notif['is_read'] ? 'read' : 'unread' ?>">
                    <div class="notification-inner">
                        <div class="notification-meta">
                            <span class="notif-icon" style="background: <?= $notif['is_read'] ? '#fff' : '#f8fafc' ?>; border: 2px solid <?= $notif['is_read'] ? 'var(--admin-border)' : 'var(--admin-primary)' ?>; color: <?= $icon_color ?>;">
                                <i class="fa-solid <?= $icon ?>" aria-hidden="true"></i>
                            </span>
                            <div class="notif-content">
                                <h4 class="notif-title"><?= htmlspecialchars($notif['title']) ?></h4>
                                <p class="notif-message"><?= htmlspecialchars($notif['message']) ?></p>
                                <div class="notif-links">
                                    <span class="notif-time"><i class="fa-solid fa-clock"></i> <?= date('M j, Y • g:i A', strtotime($notif['created_at'])) ?></span>
                                    <?php if ($notif['reference_id'] && $notif['type'] === 'new_order'): ?>
                                        <a href="order_view.php?id=<?= $notif['reference_id'] ?>" class="notif-link" title="View Order"><i class="fa-solid fa-arrow-right"></i> View Order #<?= $notif['reference_id'] ?></a>
                                    <?php elseif ($notif['reference_id'] && in_array($notif['type'], ['low_stock', 'out_of_stock'])): ?>
                                        <a href="product_form.php?id=<?= $notif['reference_id'] ?>" class="notif-link" title="View Product"><i class="fa-solid fa-arrow-right"></i> View Product</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="notification-actions">
                            <?php if (!$notif['is_read']): ?>
                                <a href="?mark_read=<?= $notif['id'] ?>" class="btn-action btn-action-view" title="Mark as Read"><i class="fa-solid fa-check"></i></a>
                            <?php endif; ?>
                            <a href="?delete=<?= $notif['id'] ?>" class="btn-action btn-action-delete" title="Delete" onclick="return confirm('Delete this notification?')"><i class="fa-solid fa-trash"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
