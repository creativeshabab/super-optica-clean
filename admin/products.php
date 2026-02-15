<?php require_once 'header.php'; ?>

<?php
// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', __('product_deleted_success', 'Product deleted successfully'));
    redirect('products.php');
}

// Fetch Products
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
$products = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1 class="admin-title" style="margin-bottom: 0;"><?= __('products_title') ?></h1>
    <a href="product_form.php" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> <?= __('add_new_product') ?>
    </a>
</div>

<div class="admin-table-widget">
    <div class="widget-header">
        <div class="widget-title">
            <i class="fa-solid fa-glasses" style="color: var(--admin-primary);"></i>
            <?= __('all_products') ?>
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-light);">
            <?= count($products) ?> <?= __('items') ?>
        </div>
    </div>
    
    <div class="widget-content">
        <table class="widget-table">
            <thead>
                <tr>
                    <th width="80"><?= __('image') ?></th>
                    <th><?= __('product_name') ?></th>
                    <th><?= __('price') ?></th>
                    <th><?= __('category') ?></th>
                    <th><?= __('stock_status') ?></th>
                    <th width="120" style="text-align: center;"><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--admin-text-light);">
                            <?= __('no_products_found') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td style="vertical-align: middle;">
                            <?php if($p['image']): ?>
                                <img src="../assets/uploads/<?= $p['image'] ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--admin-border);">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 0.7rem;">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 500; color: var(--admin-text);">
                            <?= htmlspecialchars($p['name']) ?>
                            <?php if (!empty($p['show_raw_html'])): ?>
                                <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 0.65rem; margin-left: 0.5rem;">HTML</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 600;">₹<?= number_format($p['price'], 2) ?></td>
                        <td>
                            <span class="badge" style="background: #f1f5f9; color: #475569; border: 1px solid var(--admin-border);">
                                <?= htmlspecialchars($p['category_name']) ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                                $stock = $p['stock_quantity'];
                                $threshold = $p['low_stock_threshold'];
                                if ($stock == 0) {
                                    echo '<span class="status-badge status-cancelled">' . __('out_of_stock') . '</span>';
                                } elseif ($stock <= $threshold) {
                                    echo '<span class="status-badge status-pending">' . __('low_stock') . ' (' . $stock . ')</span>';
                                } else {
                                    echo '<span class="status-badge status-completed">' . __('in_stock') . ' (' . $stock . ')</span>';
                                }
                            ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; justify-content: center; align-items: center;">
                                <a href="product_form.php?id=<?= $p['id'] ?>" class="btn-action btn-action-edit" title="<?= __('edit') ?>">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="products.php?delete=<?= $p['id'] ?>" class="btn-action btn-action-delete" onclick="return confirm('<?= __('confirm_delete') ?>')" title="<?= __('delete') ?>">
                                    <i class="fa-solid fa-trash"></i>
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
