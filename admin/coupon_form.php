<?php require_once 'header.php'; ?>

<?php
$coupon = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $coupon = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['type'];
    $value = $_POST['value'];
    $min_order_amount = $_POST['min_order_amount'] ?: 0;
    $usage_limit = $_POST['usage_limit'] ?: null;
    $start_date = $_POST['start_date'] ?: null;
    $end_date = $_POST['end_date'] ?: null;
    $is_prepaid_only = isset($_POST['is_prepaid_only']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $description = $_POST['description'];

    try {
        if (isset($_POST['id'])) {
            $stmt = $pdo->prepare("UPDATE coupons SET code=?, type=?, value=?, min_order_amount=?, usage_limit=?, start_date=?, end_date=?, is_prepaid_only=?, is_active=?, description=? WHERE id=?");
            $stmt->execute([$code, $type, $value, $min_order_amount, $usage_limit, $start_date, $end_date, $is_prepaid_only, $is_active, $description, $_POST['id']]);
            setFlash('success', __('coupon_updated_success', 'Coupon updated successfully'));
        } else {
            $stmt = $pdo->prepare("INSERT INTO coupons (code, type, value, min_order_amount, usage_limit, start_date, end_date, is_prepaid_only, is_active, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$code, $type, $value, $min_order_amount, $usage_limit, $start_date, $end_date, $is_prepaid_only, $is_active, $description]);
            setFlash('success', __('coupon_created_success', 'Coupon created successfully'));
        }
        redirect('coupons.php');
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $error = __('duplicate_code_error', "Duplicate code: This coupon code already exists.");
        } else {
            $error = __('database_error', "Database Error") . ": " . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title"><?= $coupon ? __('edit_coupon') : __('create_coupon_title') ?></h1>
        <p class="page-subtitle"><?= __('coupon_config_subtitle') ?></p>
    </div>
</div>

<div class="card" style="max-width: 800px; padding: 2.5rem;">
    <?php if (isset($error)): ?>
        <div class="alert status-failed" style="padding: 1rem; border-radius: 12px; margin-bottom: 2rem;">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <?php if ($coupon): ?>
            <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
        <?php endif; ?>

        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div class="form-group">
                <label><?= __('coupon_code') ?></label>
                <input type="text" name="code" value="<?= $coupon['code'] ?? '' ?>" required placeholder="<?= __('enter_coupon_code') ?>" style="text-transform: uppercase; padding: 1rem; border-radius: 12px; border: 1px solid var(--admin-border); width: 100%;">
            </div>
            <div class="form-group">
                <label><?= __('discount_value') ?></label>
                <div style="display: flex; gap: 0.5rem;">
                    <input type="number" name="value" value="<?= $coupon['value'] ?? '' ?>" step="0.01" required placeholder="0.00" style="flex: 2; padding: 1rem; border-radius: 12px; border: 1px solid var(--admin-border);">
                    <select name="type" style="flex: 1; padding: 1rem; border-radius: 12px; border: 1px solid var(--admin-border);">
                        <option value="percent" <?= ($coupon && $coupon['type'] == 'percent') ? 'selected' : '' ?>><?= __('percent_off') ?></option>
                        <option value="fixed" <?= ($coupon && $coupon['type'] == 'fixed') ? 'selected' : '' ?>><?= __('fixed_off') ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1rem;">
            <div class="form-group">
                <label><?= __('min_order_amount') ?></label>
                <input type="number" name="min_order_amount" value="<?= $coupon['min_order_amount'] ?? '0' ?>" step="1" placeholder="0" style="padding: 1rem; border-radius: 12px; border: 1px solid var(--admin-border); width: 100%;">
            </div>
            <div class="form-group">
                <label><?= __('usage_limit') ?> (<?= __('usage_limit_placeholder') ?>)</label>
                <input type="number" name="usage_limit" value="<?= $coupon['usage_limit'] ?? '' ?>" placeholder="<?= __('usage_limit_placeholder') ?>" style="padding: 1rem; border-radius: 12px; border: 1px solid var(--admin-border); width: 100%;">
            </div>
        </div>

        <div class="form-group" style="margin-top: 1rem;">
            <label><?= __('offer_description') ?></label>
            <input type="text" name="description" value="<?= $coupon['description'] ?? '' ?>" placeholder="<?= __('offer_description_placeholder') ?>" style="padding: 1rem; border-radius: 12px; border: 1px solid var(--admin-border); width: 100%;">
        </div>

        <div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1rem;">
            <div class="form-group">
                <label><?= __('start_date') ?></label>
                <input type="date" name="start_date" value="<?= $coupon['start_date'] ? date('Y-m-d', strtotime($coupon['start_date'])) : '' ?>" style="padding: 1rem; border-radius: 12px; border: 1px solid var(--admin-border); width: 100%;">
            </div>
            <div class="form-group">
                <label><?= __('end_date') ?></label>
                <input type="date" name="end_date" value="<?= $coupon['end_date'] ? date('Y-m-d', strtotime($coupon['end_date'])) : '' ?>" style="padding: 1rem; border-radius: 12px; border: 1px solid var(--admin-border); width: 100%;">
            </div>
        </div>

        <div style="display: flex; gap: 2rem; margin-top: 2rem; padding: 1.5rem; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9;">
            <label style="display: flex; align-items: center; gap: 0.8rem; cursor: pointer; font-weight: 700;">
                <input type="checkbox" name="is_prepaid_only" style="width: 20px; height: 20px;" <?= (!$coupon || $coupon['is_prepaid_only']) ? 'checked' : '' ?>>
                <?= __('is_prepaid_only') ?>
            </label>
            <label style="display: flex; align-items: center; gap: 0.8rem; cursor: pointer; font-weight: 700;">
                <input type="checkbox" name="is_active" style="width: 20px; height: 20px;" <?= (!$coupon || $coupon['is_active']) ? 'checked' : '' ?>>
                <?= __('is_active_status') ?>
            </label>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 3rem;">
            <button type="submit" class="btn btn-primary" style="flex: 2; padding: 1rem;">
                <i class="fa-solid fa-save" style="margin-right: 0.5rem;"></i> <?= $coupon ? __('update_coupon') : __('create_coupon_btn') ?>
            </button>
            <a href="coupons.php" class="btn btn-secondary" style="flex: 1; padding: 1rem; text-align: center;"><?= __('cancel') ?></a>
        </div>
    </form>
</div>

<?php require_once 'footer.php'; ?>
