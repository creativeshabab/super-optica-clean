<?php require_once 'header.php'; ?>
<?php
// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM services WHERE id = ?")->execute([$id]);
    setFlash('success', __('service_deleted_success', 'Service deleted'));
    redirect('services.php');
}

// Get Listing Parameters
$params = getListingParams('created_at', 'DESC');
$page = $params['page'];
$search = $params['search'];
$sort = $params['sort'];
$order = $params['order'];
$limit = $params['limit'];

// Build Query
$where = "1=1";
$sqlParams = [];

if ($search) {
    $where .= " AND (title LIKE ? OR description LIKE ?)";
    $sqlParams[] = "%$search%";
    $sqlParams[] = "%$search%";
}

// Get Total Count
$countQuery = "SELECT COUNT(*) FROM services WHERE $where";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($sqlParams);
$totalItems = $stmt->fetchColumn();

// Get Pagination Data
$pagination = getPaginationData($totalItems, $limit);
$offset = $pagination['offset'];

// Fetch Paginated Services
$allowedSorts = ['title', 'created_at'];
if (!in_array($sort, $allowedSorts)) { $sort = 'created_at'; }

$query = "SELECT * FROM services 
          WHERE $where 
          ORDER BY $sort $order 
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($sqlParams);
$services = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title"><?= __('services_title') ?></h1>
        <p class="page-subtitle">Highlight your core offerings and specialties.</p>
    </div>
    <div class="page-header-actions">
        <a href="service_form.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> <?= __('add_new_service') ?>
        </a>
    </div>
</div>

<!-- Listing Controls -->
<div class="listing-controls">
    <form method="GET" class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search services..." class="form-control">
        <?php if($search): ?>
            <a href="services.php" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--admin-text-light);"><i class="fa-solid fa-xmark" style="position: static; padding: 0;"></i></a>
        <?php endif; ?>
    </form>

    <div class="filter-group">
        <form method="GET" style="display: flex; gap: 0.75rem; align-items: center;">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <select name="sort" onchange="this.form.submit()" class="form-control">
                <option value="created_at" <?= $sort == 'created_at' ? 'selected' : '' ?>>Newest First</option>
                <option value="title" <?= $sort == 'title' ? 'selected' : '' ?>>Title</option>
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
            <i class="fa-solid fa-bell-concierge" style="color: var(--admin-primary);"></i>
            <?= __('services_title') ?>
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-light);">
            Showing <?= count($services) ?> of <?= $totalItems ?> <?= __('items') ?>
        </div>
    </div>
    <div class="widget-content">
        <table class="widget-table responsive-table">
            <thead>
                <tr>
                    <th width="80"><?= __('service_icon') ?></th>
                    <th><?= __('service_title') ?></th>
                    <th><?= __('service_description') ?></th>
                    <th width="150" style="text-align: center;"><?= __('action') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $s): ?>
                <tr>
                    <td data-label="<?= __('service_icon') ?>">
                        <div style="width: 50px; height: 50px; background: #f8fafc; border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0;">
                            <?php if ($s['icon'] && strpos($s['icon'], 'fa-') !== false): ?>
                                <i class="<?= $s['icon'] ?>" style="font-size: 1.25rem; color: var(--admin-sidebar);"></i>
                            <?php elseif ($s['icon']): ?>
                                <img src="../assets/uploads/<?= $s['icon'] ?>" style="width: 30px; height: 30px; object-fit: contain;">
                            <?php else: ?>
                                <i class="fa-solid fa-star" style="color: #cbd5e1;"></i>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td data-label="<?= __('service_title') ?>" style="font-weight: 700; color: var(--admin-sidebar); font-size: 1.05rem;"><?= htmlspecialchars($s['title']) ?></td>
                    <td data-label="<?= __('service_description') ?>" style="color: var(--admin-text-light); font-size: 0.95rem; max-width: 400px;"><?= htmlspecialchars(substr($s['description'], 0, 80)) ?>...</td>
                    <td data-label="<?= __('action') ?>">
                        <div style="display: flex; gap: 0.5rem; justify-content: center; align-items: center;">
                            <a href="service_form.php?id=<?= $s['id'] ?>" class="btn-action btn-action-edit" title="<?= __('edit_service') ?>">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="services.php?delete=<?= $s['id'] ?>" class="btn-action btn-action-delete" onclick="return confirm('<?= __('delete_service_confirm') ?>')" title="<?= __('delete_service') ?>">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($services)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 3rem; color: var(--admin-text-light);"><?= __('no_services') ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= renderPagination($page, $pagination['total_pages']) ?>

<?php require_once 'footer.php'; ?>
