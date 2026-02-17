<?php require_once 'header.php'; ?>

<?php
// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', __('coupon_deleted_success', 'Coupon deleted successfully'));
    redirect('coupons.php');
}

// Handle Status Toggle
if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $current_status = $_GET['current'] == 1 ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE coupons SET is_active = ? WHERE id = ?");
    $stmt->execute([$current_status, $id]);
    setFlash('success', __('coupon_status_updated', 'Coupon status updated'));
    redirect('coupons.php');
}

// Get Listing Parameters
$params = getListingParams('id', 'DESC');
$page = $params['page'];
$search = $params['search'];
$sort = $params['sort'];
$order = $params['order'];
$limit = $params['limit'];

// Build Query
$where = "1=1";
$sqlParams = [];

if ($search) {
    $where .= " AND (code LIKE ? OR description LIKE ?)";
    $sqlParams[] = "%$search%";
    $sqlParams[] = "%$search%";
}

// Get Total Count
$countQuery = "SELECT COUNT(*) FROM coupons WHERE $where";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($sqlParams);
$totalItems = $stmt->fetchColumn();

// Get Pagination Data
$pagination = getPaginationData($totalItems, $limit);
$offset = $pagination['offset'];

// Fetch Paginated Coupons
$allowedSorts = ['code', 'id', 'end_date'];
if (!in_array($sort, $allowedSorts)) { $sort = 'id'; }

$query = "SELECT * FROM coupons 
          WHERE $where 
          ORDER BY $sort $order 
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($sqlParams);
$coupons = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title"><?= __('coupons_title') ?></h1>
        <p class="page-subtitle"><?= __('coupons_subtitle') ?></p>
    </div>
    <div class="page-header-actions">
        <a href="coupon_form.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> <?= __('create_new_coupon') ?>
        </a>
    </div>
</div>

<!-- Listing Controls -->
<div class="listing-controls">
    <form method="GET" class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search coupons..." class="form-control">
        <?php if($search): ?>
            <a href="coupons.php" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--admin-text-light);"><i class="fa-solid fa-xmark" style="position: static; padding: 0;"></i></a>
        <?php endif; ?>
    </form>

    <div class="filter-group">
        <form method="GET" style="display: flex; gap: 0.75rem; align-items: center;">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <select name="sort" onchange="this.form.submit()" class="form-control">
                <option value="id" <?= $sort == 'id' ? 'selected' : '' ?>>Newest First</option>
                <option value="code" <?= $sort == 'code' ? 'selected' : '' ?>>Coupon Code</option>
                <option value="end_date" <?= $sort == 'end_date' ? 'selected' : '' ?>>Expiry Date</option>
            </select>
            <select name="order" onchange="this.form.submit()" class="form-control" style="min-width: 100px;">
                <option value="DESC" <?= $order == 'DESC' ? 'selected' : '' ?>>DESC</option>
                <option value="ASC" <?= $order == 'ASC' ? 'selected' : '' ?>>ASC</option>
            </select>
        </form>
    </div>
</div>

<?php if (isset($query_error)): ?>
    <div class="alert status-failed" style="padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; border-left: 5px solid #ef4444;">
        <i class="fa-solid fa-circle-exclamation" style="margin-right: 0.5rem;"></i> <?= htmlspecialchars($query_error) ?>
    </div>
<?php endif; ?>


<div class="admin-table-widget">
    <div class="widget-header">
        <div class="widget-title">
            <i class="fa-solid fa-ticket" style="color: var(--admin-primary);"></i>
            <?= __('coupons_title') ?>
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-light);">
            Showing <?= count($coupons) ?> of <?= $totalItems ?> <?= __('items') ?>
        </div>
    </div>
    <div class="widget-content">
        <table class="widget-table responsive-table">
            <thead>
                <tr>
                    <th><?= __('coupon_code') ?></th>
                    <th><?= __('discount_value') ?></th>
                    <th><?= __('usage_limit') ?></th>
                    <th><?= __('validity') ?></th>
                    <th><?= __('min_order') ?></th>
                    <th width="100" style="text-align: center;"><?= __('coupon_status') ?></th>
                    <th width="150" style="text-align: center;"><?= __('coupon_actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($coupons)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 3rem; color: var(--admin-text-light);">
                        <?= __('no_coupons') ?>
                    </td>
                </tr>
                <?php endif; ?>
                
                <?php foreach ($coupons as $c): 
                    $is_expired = false;
                    if ($c['end_date'] && strtotime($c['end_date']) < time()) {
                        $is_expired = true;
                    }
                    $status_class = $c['is_active'] && !$is_expired ? 'status-paid' : 'status-failed';
                    $status_text = $is_expired ? __('expired') : ($c['is_active'] ? __('active') : __('inactive'));
                ?>
                <tr>
                    <td data-label="<?= __('coupon_code') ?>">
                        <div style="font-family: 'Montserrat', sans-serif; font-weight: 800; color: var(--admin-primary); letter-spacing: 1px; background: #fff1f2; padding: 0.4rem 0.8rem; border-radius: 6px; display: inline-block; border: 1px dashed var(--admin-primary);">
                            <?= htmlspecialchars($c['code']) ?>
                        </div>
                        <?php if($c['description']): ?>
                            <div style="font-size: 0.75rem; color: var(--admin-text-light); margin-top: 0.3rem;">
                                <?= htmlspecialchars($c['description']) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?= __('discount_value') ?>" style="font-weight: 700;">
                        <?= $c['type'] == 'percent' ? $c['value'].'%' : '₹'.number_format($c['value'], 0) ?>
                        <span style="font-weight: 400; font-size: 0.75rem; color: var(--admin-text-light); display: block;">
                            <?= $c['is_prepaid_only'] ? __('prepaid_only') : __('all_payments') ?>
                        </span>
                    </td>
                    <td data-label="<?= __('usage_limit') ?>">
                        <span style="font-weight: 600;"><?= $c['usage_limit'] ?? __('unlimited') ?></span>
                    </td>
                    <td data-label="<?= __('validity') ?>" style="font-size: 0.85rem; color: var(--admin-text-light);">
                        <div><?= __('valid_from') ?>: <?= $c['start_date'] ? date('d M Y', strtotime($c['start_date'])) : __('anytime') ?></div>
                        <div><?= __('valid_to') ?>: <?= $c['end_date'] ? date('d M Y', strtotime($c['end_date'])) : __('never') ?></div>
                    </td>
                    <td data-label="<?= __('min_order') ?>" style="font-weight: 600;">₹<?= number_format($c['min_order_amount'], 0) ?></td>
                    <td data-label="<?= __('coupon_status') ?>">
                        <a href="coupons.php?toggle_status=<?= $c['id'] ?>&current=<?= $c['is_active'] ?>" 
                           class="badge <?= $status_class ?>" style="text-decoration: none; display: block; text-align: center;">
                            <?= $status_text ?>
                        </a>
                    </td>
                    <td data-label="<?= __('coupon_actions') ?>">
                        <div style="display: flex; gap: 0.5rem; justify-content: center; align-items: center;">
                            <a href="coupon_form.php?id=<?= $c['id'] ?>" class="btn-action btn-action-edit" title="<?= __('edit_coupon') ?>">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="coupons.php?delete=<?= $c['id'] ?>" class="btn-action btn-action-delete" onclick="return confirm('<?= __('delete_coupon_confirm') ?>')" title="<?= __('delete') ?>">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?= renderPagination($page, $pagination['total_pages']) ?>

<?php require_once 'footer.php'; ?>
