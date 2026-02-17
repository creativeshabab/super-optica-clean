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

// Get Listing Parameters
$params = getListingParams('p.created_at', 'DESC');
$page = $params['page'];
$search = $params['search'];
$sort = $params['sort'];
$order = $params['order'];
$limit = $params['limit'];

// Build Query
$where = "1=1";
$sqlParams = [];

if ($search) {
    if (strpos($search, 'cat:') === 0) {
        $catName = trim(substr($search, 4));
        $where .= " AND c.name LIKE ?";
        $sqlParams[] = "%$catName%";
    } else {
        $where .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
        $sqlParams[] = "%$search%";
        $sqlParams[] = "%$search%";
        $sqlParams[] = "%$search%";
    }
}

// Get Total Count for Pagination
$countQuery = "SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.id WHERE $where";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($sqlParams);
$totalItems = $stmt->fetchColumn();

// Get Pagination Data
$pagination = getPaginationData($totalItems, $limit);
$offset = $pagination['offset'];

// Fetch Paginated Products
$allowedSorts = ['p.name', 'p.price', 'p.stock_quantity', 'p.created_at', 'c.name'];
if (!in_array($sort, $allowedSorts)) { $sort = 'p.created_at'; }

$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          JOIN categories c ON p.category_id = c.id 
          WHERE $where 
          ORDER BY $sort $order 
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($sqlParams);
$products = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title"><?= __('products_title') ?></h1>
        <p class="page-subtitle">Manage your inventory, pricing, and product variants.</p>
    </div>
    <div class="page-header-actions">
        <a href="bulk_upload.php" class="btn btn-secondary">
            <i class="fa-solid fa-upload"></i> <?= __('bulk_upload', 'Bulk Upload') ?>
        </a>
        <a href="product_form.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> <?= __('add_new_product') ?>
        </a>
    </div>
</div>

<!-- Listing Controls -->
<div class="listing-controls">
    <form method="GET" class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, SKU, or 'cat:Category'..." class="form-control">
        <?php if($search): ?>
            <a href="products.php" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--admin-text-light);"><i class="fa-solid fa-xmark" style="position: static; padding: 0;"></i></a>
        <?php endif; ?>
    </form>

    <div class="filter-group">
        <form method="GET" style="display: flex; gap: 0.75rem; align-items: center;">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <select name="sort" onchange="this.form.submit()" class="form-control">
                <option value="p.created_at" <?= $sort == 'p.created_at' ? 'selected' : '' ?>>Newest First</option>
                <option value="p.name" <?= $sort == 'p.name' ? 'selected' : '' ?>>Name (A-Z)</option>
                <option value="p.price" <?= $sort == 'p.price' ? 'selected' : '' ?>>Price (Low to High)</option>
                <option value="p.stock_quantity" <?= $sort == 'p.stock_quantity' ? 'selected' : '' ?>>Stock Level</option>
            </select>
            <select name="order" onchange="this.form.submit()" class="form-control" style="min-width: 100px;">
                <option value="DESC" <?= $order == 'DESC' ? 'selected' : '' ?>>DESC</option>
                <option value="ASC" <?= $order == 'ASC' ? 'selected' : '' ?>>ASC</option>
            </select>
        </form>
    </div>
</div>

<div class="admin-table-widget">
    <div class="widget-header">
        <div class="widget-title">
            <i class="fa-solid fa-glasses" style="color: var(--admin-primary);"></i>
            <?= __('all_products') ?>
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-light);">
            Showing <?= count($products) ?> of <?= $totalItems ?> <?= __('items') ?>
        </div>
    </div>
    
    <div class="widget-content">
        <table class="widget-table responsive-table">
            <thead>
                <tr>
                    <th width="40" style="text-align: center;">
                        <input type="checkbox" id="selectAll" style="width: 18px; height: 18px; cursor: pointer; border-radius: 4px; border: 2px solid var(--admin-border);">
                    </th>
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
                        <td colspan="7" style="text-align: center; padding: 2rem; color: var(--admin-text-light);">
                            <?= __('no_products_found') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                    <tr class="product-row" data-id="<?= $p['id'] ?>">
                        <td data-label="Select" style="text-align: center; vertical-align: middle;">
                            <input type="checkbox" class="product-select" value="<?= $p['id'] ?>" style="width: 17px; height: 17px; cursor: pointer;">
                        </td>
                        <td data-label="<?= __('image') ?>" style="vertical-align: middle;">
                            <?php if($p['image']): ?>
                                <img src="../assets/uploads/<?= $p['image'] ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--admin-border);">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 0.7rem;">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?= __('product_name') ?>" style="font-weight: 500; color: var(--admin-text);">
                            <?= htmlspecialchars($p['name']) ?>
                            <?php if (!empty($p['show_raw_html'])): ?>
                                <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 0.65rem; margin-left: 0.5rem;">HTML</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="<?= __('price') ?>" style="font-weight: 600;">₹<?= number_format($p['price'], 2) ?></td>
                        <td data-label="<?= __('category') ?>">
                            <span class="badge" style="background: #f1f5f9; color: #475569; border: 1px solid var(--admin-border);">
                                <?= htmlspecialchars($p['category_name']) ?>
                            </span>
                        </td>
                        <td data-label="<?= __('stock_status') ?>">
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
                        <td data-label="<?= __('actions') ?>">
                            <div class="flex items-center gap-2 justify-center">
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

<?= renderPagination($page, $pagination['total_pages']) ?>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const productCheckboxes = document.querySelectorAll('.product-select');
    const bulkBar = document.getElementById('bulkActionsBar');
    const selectedCountDisplay = document.getElementById('selectedCount');
    const cancelSelection = document.getElementById('cancelSelection');

    function updateBulkBar() {
        const selectedCount = document.querySelectorAll('.product-select:checked').length;
        selectedCountDisplay.textContent = selectedCount;
        
        if (selectedCount > 0) {
            bulkBar.style.bottom = '30px';
            bulkBar.style.opacity = '1';
            bulkBar.style.visibility = 'visible';
            bulkBar.style.pointerEvents = 'auto';
        } else {
            bulkBar.style.bottom = '-100px';
            bulkBar.style.opacity = '0';
            bulkBar.style.visibility = 'hidden';
            bulkBar.style.pointerEvents = 'none';
        }
    }

    selectAll.addEventListener('change', function() {
        productCheckboxes.forEach(cb => {
            cb.checked = selectAll.checked;
            cb.closest('tr').classList.toggle('selected-row', selectAll.checked);
        });
        updateBulkBar();
    });

    productCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            cb.closest('tr').classList.toggle('selected-row', cb.checked);
            
            // Update Select All state
            const allChecked = Array.from(productCheckboxes).every(p => p.checked);
            selectAll.checked = allChecked;
            selectAll.indeterminate = !allChecked && Array.from(productCheckboxes).some(p => p.checked);
            
            updateBulkBar();
        });
    });

    cancelSelection.addEventListener('click', function() {
        productCheckboxes.forEach(cb => {
            cb.checked = false;
            cb.closest('tr').classList.remove('selected-row');
        });
        selectAll.checked = false;
        selectAll.indeterminate = false;
        updateBulkBar();
    });
});
</script>

<style>
.selected-row {
    background-color: rgba(37, 99, 235, 0.03) !important;
}
</style>

<?php require_once 'footer.php'; ?>
