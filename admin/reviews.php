<?php require_once 'header.php'; ?>
<?php
// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$id]);
    setFlash('success', __('review_deleted_success', 'Review deleted successfully'));
    redirect('reviews.php');
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
    $where .= " AND (name LIKE ? OR comment LIKE ?)";
    $sqlParams[] = "%$search%";
    $sqlParams[] = "%$search%";
}

// Get Total Count
$countQuery = "SELECT COUNT(*) FROM reviews WHERE $where";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($sqlParams);
$totalItems = $stmt->fetchColumn();

// Get Pagination Data
$pagination = getPaginationData($totalItems, $limit);
$offset = $pagination['offset'];

// Fetch Paginated Reviews
$allowedSorts = ['name', 'rating', 'created_at'];
if (!in_array($sort, $allowedSorts)) { $sort = 'created_at'; }

$query = "SELECT * FROM reviews 
          WHERE $where 
          ORDER BY $sort $order 
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($sqlParams);
$reviews = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title"><?= __('reviews_title') ?></h1>
        <p class="page-subtitle">Manage customer testimonials and star ratings.</p>
    </div>
    <div class="page-header-actions">
        <a href="review_form.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> <?= __('add_new_review') ?>
        </a>
    </div>
</div>

<!-- Listing Controls -->
<div class="listing-controls">
    <form method="GET" class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by customer name or comment..." class="form-control">
        <?php if($search): ?>
            <a href="reviews.php" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--admin-text-light);"><i class="fa-solid fa-xmark" style="position: static; padding: 0;"></i></a>
        <?php endif; ?>
    </form>

    <div class="filter-group">
        <form method="GET" style="display: flex; gap: 0.75rem; align-items: center;">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <select name="sort" onchange="this.form.submit()" class="form-control">
                <option value="created_at" <?= $sort == 'created_at' ? 'selected' : '' ?>>Newest First</option>
                <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>Customer Name</option>
                <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>Rating</option>
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
            <i class="fa-solid fa-star" style="color: var(--admin-primary);"></i>
            <?= __('reviews_title') ?>
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-light);">
            Showing <?= count($reviews) ?> of <?= $totalItems ?> <?= __('items') ?>
        </div>
    </div>
    <div class="widget-content">
        <table class="widget-table responsive-table">
            <thead>
                <tr>
                    <th width="80"><?= __('review_avatar') ?></th>
                    <th><?= __('review_name') ?></th>
                    <th><?= __('review_rating') ?></th>
                    <th><?= __('review_comment') ?></th>
                    <th width="150" style="text-align: center;"><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $r): ?>
                <tr>
                    <td data-label="<?= __('review_avatar') ?>">
                        <?php if ($r['image']): ?>
                            <img src="../assets/uploads/<?= $r['image'] ?>" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
                        <?php else: ?>
                            <div style="width: 48px; height: 48px; background: #e0f2fe; color: #0369a1; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem;">
                                <?= strtoupper(substr($r['name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td data-label="<?= __('review_name') ?>" style="font-weight: 700; color: var(--admin-sidebar);"><?= htmlspecialchars($r['name']) ?></td>
                    <td data-label="<?= __('review_rating') ?>">
                        <div style="color: #f59e0b; display: flex; gap: 2px;">
                            <?php for($i=0; $i<$r['rating']; $i++) echo '<i class="fa-solid fa-star" style="font-size: 0.8rem;"></i>'; ?>
                        </div>
                    </td>
                    <td data-label="<?= __('review_comment') ?>" style="color: var(--admin-text-light); font-size: 0.95rem;"><?= htmlspecialchars(substr($r['comment'], 0, 80)) ?>...</td>
                    <td data-label="<?= __('actions') ?>">
                        <div style="display: flex; gap: 0.5rem; justify-content: center; align-items: center;">
                            <a href="review_form.php?id=<?= $r['id'] ?>" class="btn-action btn-action-edit" title="<?= __('edit_review') ?>">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="reviews.php?delete=<?= $r['id'] ?>" class="btn-action btn-action-delete" onclick="return confirm('<?= __('delete_review_confirm') ?>')" title="<?= __('delete') ?>">
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
