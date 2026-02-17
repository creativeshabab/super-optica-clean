<?php require_once '../config/db.php'; ?>
<?php require_once '../includes/functions.php'; ?>
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

// Get Listing Parameters
$params = getListingParams('o.created_at', 'DESC');
$page = $params['page'];
$search = $params['search'];
$sort = $params['sort'];
$order = $params['order'];
$limit = $params['limit'];
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

// Build Query
$where = "1=1";
$sqlParams = [];

if ($search) {
    if (is_numeric($search)) {
        $where .= " AND o.id = ?";
        $sqlParams[] = (int)$search;
    } else {
        $where .= " AND u.name LIKE ?";
        $sqlParams[] = "%$search%";
    }
}

if ($status_filter) {
    $where .= " AND o.status = ?";
    $sqlParams[] = $status_filter;
}

// Get Total Count
$countQuery = "SELECT COUNT(*) FROM orders o JOIN users u ON o.user_id = u.id WHERE $where";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($sqlParams);
$totalItems = $stmt->fetchColumn();

// Get Pagination Data
$pagination = getPaginationData($totalItems, $limit);
$offset = $pagination['offset'];

// Fetch Paginated Orders
$allowedSorts = ['o.created_at', 'o.total_amount', 'o.id', 'u.name'];
if (!in_array($sort, $allowedSorts)) { $sort = 'o.created_at'; }

$query = "SELECT o.*, u.name as user_name 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE $where 
          ORDER BY $sort $order 
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($sqlParams);
$orders = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title"><?= __('orders_management') ?></h1>
        <p class="page-subtitle"><?= __('orders_subtitle') ?></p>
    </div>
</div>

<!-- Listing Controls -->
<div class="listing-controls">
    <form method="GET" class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by Order ID or Customer Name..." class="form-control">
        <?php if($search || $status_filter): ?>
            <a href="orders.php" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--admin-text-light);"><i class="fa-solid fa-xmark" style="position: static; padding: 0;"></i></a>
        <?php endif; ?>
    </form>

    <div class="filter-group">
        <form method="GET" style="display: flex; gap: 0.75rem; align-items: center;">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            
            <select name="status_filter" onchange="this.form.submit()" class="form-control">
                <option value="">All Statuses</option>
                <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="shipped" <?= $status_filter == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>

            <select name="sort" onchange="this.form.submit()" class="form-control">
                <option value="o.created_at" <?= $sort == 'o.created_at' ? 'selected' : '' ?>>Newest Orders</option>
                <option value="o.total_amount" <?= $sort == 'o.total_amount' ? 'selected' : '' ?>>Order Total</option>
                <option value="u.name" <?= $sort == 'u.name' ? 'selected' : '' ?>>Customer Name</option>
            </select>
        </form>
    </div>
</div>

<div class="admin-table-widget">
    <div class="widget-header">
        <div class="widget-title">
            <i class="fa-solid fa-clipboard-list" style="color: var(--admin-primary);"></i>
            <?= __('order_list_desc') ?>
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-light);">
            Showing <?= count($orders) ?> of <?= $totalItems ?> <?= __('items') ?>
        </div>
    </div>
    
    <div class="widget-content">
        <table class="widget-table responsive-table">
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
                        <td data-label="<?= __('order_id') ?>" style="font-weight: 700; color: var(--admin-sidebar);">#<?= $o['id'] ?></td>
                        <td data-label="<?= __('customer') ?>">
                            <div style="font-weight: 600; color: var(--admin-text);"><?= htmlspecialchars($o['user_name']) ?></div>
                        </td>
                        <td data-label="<?= __('total_amount') ?>" style="font-weight: 700; color: var(--admin-primary);">₹<?= number_format($o['total_amount'], 2) ?></td>
                        <td data-label="<?= __('order_date') ?>" style="color: var(--admin-text-light); font-size: 0.9rem;"><?= date('M d, Y', strtotime($o['created_at'])) ?></td>
                        <td data-label="<?= __('status') ?>">
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
                        <td data-label="<?= __('actions') ?>">
                        <div class="flex items-center gap-2 justify-center">
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

<?= renderPagination($page, $pagination['total_pages']) ?>

<?php require_once 'footer.php'; ?>
