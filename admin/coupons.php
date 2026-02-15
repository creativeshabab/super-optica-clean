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

// Fetch Coupons
try {
    $stmt = $pdo->query("SELECT * FROM coupons ORDER BY id DESC");
    $coupons = $stmt->fetchAll();
} catch (PDOException $e) {
    $coupons = [];
    $query_error = __('database_error', "Database Error") . ": " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
    <div>
        <h1 class="admin-title" style="margin-bottom: 0.5rem;"><?= __('coupons_title') ?></h1>
        <p style="color: var(--admin-text-light); font-weight: 500; margin: 0;"><?= __('coupons_subtitle') ?></p>
    </div>
    <a href="coupon_form.php" class="btn btn-primary" style="font-size: 0.9rem; font-weight: 600;">
        <i class="fa-solid fa-plus" style="margin-right: 0.5rem;"></i> <?= __('create_new_coupon') ?>
    </a>
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
            <?= count($coupons) ?> <?= __('items') ?>
        </div>
    </div>
    <div class="widget-content">
        <table class="widget-table">
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
                    <td>
                        <div style="font-family: 'Montserrat', sans-serif; font-weight: 800; color: var(--admin-primary); letter-spacing: 1px; background: #fff1f2; padding: 0.4rem 0.8rem; border-radius: 6px; display: inline-block; border: 1px dashed var(--admin-primary);">
                            <?= htmlspecialchars($c['code']) ?>
                        </div>
                        <?php if($c['description']): ?>
                            <div style="font-size: 0.75rem; color: var(--admin-text-light); margin-top: 0.3rem;">
                                <?= htmlspecialchars($c['description']) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 700;">
                        <?= $c['type'] == 'percent' ? $c['value'].'%' : '₹'.number_format($c['value'], 0) ?>
                        <span style="font-weight: 400; font-size: 0.75rem; color: var(--admin-text-light); display: block;">
                            <?= $c['is_prepaid_only'] ? __('prepaid_only') : __('all_payments') ?>
                        </span>
                    </td>
                    <td>
                        <span style="font-weight: 600;"><?= $c['usage_limit'] ?? __('unlimited') ?></span>
                    </td>
                    <td style="font-size: 0.85rem; color: var(--admin-text-light);">
                        <div><?= __('valid_from') ?>: <?= $c['start_date'] ? date('d M Y', strtotime($c['start_date'])) : __('anytime') ?></div>
                        <div><?= __('valid_to') ?>: <?= $c['end_date'] ? date('d M Y', strtotime($c['end_date'])) : __('never') ?></div>
                    </td>
                    <td style="font-weight: 600;">₹<?= number_format($c['min_order_amount'], 0) ?></td>
                    <td>
                        <a href="coupons.php?toggle_status=<?= $c['id'] ?>&current=<?= $c['is_active'] ?>" 
                           class="badge <?= $status_class ?>" style="text-decoration: none; display: block; text-align: center;">
                            <?= $status_text ?>
                        </a>
                    </td>
                    <td>
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

<?php require_once 'footer.php'; ?>
