<?php require_once 'header.php'; ?>

<?php
// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', __('gallery_item_deleted_success', 'Gallery item deleted successfully'));
    redirect('gallery.php');
}

// Check if gallery table exists
try {
    $table_check = $pdo->query("SHOW TABLES LIKE 'gallery'")->fetch();
    if (!$table_check) {
        echo '<div class="card" style="max-width: 700px; margin: 2rem auto; text-align: center; padding: 3rem;">';
        echo '<i class="fa-solid fa-database" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 1.5rem;"></i>';
        echo '<h2 style="color: var(--admin-text-dark); margin-bottom: 1rem;">Gallery Table Not Found</h2>';
        echo '<p style="color: var(--admin-text-light); margin-bottom: 2rem;">The gallery database table hasn\'t been created yet. Please run the migration script first.</p>';
        echo '<a href="../create_gallery_table.php" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1rem;">';
        echo '<i class="fa-solid fa-play"></i> Run Migration Script';
        echo '</a>';
        echo '</div>';
        require_once 'footer.php';
        exit;
    }
} catch (PDOException $e) {
    echo '<div class="alert" style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 12px; margin: 2rem; border: 1px solid #fecaca;">';
    echo '<i class="fa-solid fa-circle-exclamation"></i> Database Error: ' . htmlspecialchars($e->getMessage());
    echo '</div>';
    require_once 'footer.php';
    exit;
}

// Get Listing Parameters
$params = getListingParams('display_order', 'ASC');
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
        $cat = substr($search, 4);
        $where .= " AND category LIKE ?";
        $sqlParams[] = "%$cat%";
    } else {
        $where .= " AND (title LIKE ? OR description LIKE ? OR category LIKE ?)";
        $sqlParams[] = "%$search%";
        $sqlParams[] = "%$search%";
        $sqlParams[] = "%$search%";
    }
}

// Get Total Count
$countQuery = "SELECT COUNT(*) FROM gallery WHERE $where";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($sqlParams);
$totalItems = $stmt->fetchColumn();

// Get Pagination Data
$pagination = getPaginationData($totalItems, $limit);
$offset = $pagination['offset'];

// Fetch Paginated Gallery Items
$allowedSorts = ['title', 'display_order', 'created_at'];
if (!in_array($sort, $allowedSorts)) { $sort = 'display_order'; }

$query = "SELECT * FROM gallery 
          WHERE $where 
          ORDER BY $sort $order 
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($sqlParams);
$gallery_items = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title"><?= __('gallery_title') ?></h1>
        <p class="page-subtitle">Showcase your products and clinic through a visual portfolio.</p>
    </div>
    <div class="page-header-actions">
        <a href="gallery_form.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> <?= __('add_new_gallery_item') ?>
        </a>
    </div>
</div>

<!-- Listing Controls -->
<div class="listing-controls">
    <form method="GET" class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by title or category (cat:Category)..." class="form-control">
        <?php if($search): ?>
            <a href="gallery.php" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--admin-text-light);"><i class="fa-solid fa-xmark" style="position: static; padding: 0;"></i></a>
        <?php endif; ?>
    </form>

    <div class="filter-group">
        <form method="GET" style="display: flex; gap: 0.75rem; align-items: center;">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <select name="sort" onchange="this.form.submit()" class="form-control">
                <option value="display_order" <?= $sort == 'display_order' ? 'selected' : '' ?>>Display Order</option>
                <option value="created_at" <?= $sort == 'created_at' ? 'selected' : '' ?>>Newest First</option>
                <option value="title" <?= $sort == 'title' ? 'selected' : '' ?>>Title</option>
            </select>
            <select name="order" onchange="this.form.submit()" class="form-control" style="min-width: 100px;">
                <option value="ASC" <?= $order == 'ASC' ? 'selected' : '' ?>>ASC</option>
                <option value="DESC" <?= $order == 'DESC' ? 'selected' : '' ?>>DESC</option>
            </select>
        </form>
    </div>
</div>

<?php if (empty($gallery_items)): ?>
    <div class="card text-center" style="padding: 3rem;">
        <i class="fa-solid fa-images" style="font-size: 4rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
        <h3 style="color: var(--admin-text-light); margin-bottom: 1rem;"><?= __('no_gallery_items') ?></h3>
        <p style="color: var(--admin-text-light); margin-bottom: 2rem;"><?= __('gallery_empty_desc') ?></p>
        <a href="gallery_form.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> <?= __('add_first_gallery_item') ?>
        </a>
    </div>
<?php else: ?>
    <div class="admin-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
        <?php foreach ($gallery_items as $item): ?>
        <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
            <div style="height: 200px; background: #f1f5f9; position: relative;">
                <?php if ($item['image']): ?>
                <img src="../assets/uploads/<?= $item['image'] ?>" style="width: 100%; height: 100%; object-fit: cover;" alt="<?= htmlspecialchars($item['title']) ?>">
                <?php else: ?>
                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #cbd5e1;">
                    <i class="fa-solid fa-image" style="font-size: 3rem;"></i>
                </div>
                <?php endif; ?>
                
                <?php if ($item['category']): ?>
                <span style="position: absolute; top: 0.75rem; left: 0.75rem; background: rgba(0,0,0,0.7); color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">
                    <?= htmlspecialchars($item['category']) ?>
                </span>
                <?php endif; ?>
            </div>
            
            <div style="padding: 1.25rem; flex: 1; display: flex; flex-direction: column;">
                <h3 style="margin: 0 0 0.5rem 0; font-size: 1.1rem; color: var(--admin-text-dark);">
                    <?= htmlspecialchars($item['title']) ?>
                </h3>
                
                <?php if ($item['description']): ?>
                <p style="color: var(--admin-text-light); font-size: 0.9rem; margin-bottom: 1rem; line-height: 1.5; flex: 1;">
                    <?= htmlspecialchars(substr($item['description'], 0, 80)) ?><?= strlen($item['description']) > 80 ? '...' : '' ?>
                </p>
                <?php endif; ?>
                
                <div style="display: flex; gap: 0.5rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; margin-top: auto;">
                    <a href="gallery_form.php?id=<?= $item['id'] ?>" class="btn btn-secondary" style="flex: 1; font-size: 0.85rem; padding: 0.5rem;">
                        <i class="fa-solid fa-edit"></i> <?= __('edit') ?>
                    </a>
                    <a href="gallery.php?delete=<?= $item['id'] ?>" class="btn" style="flex: 1; background: #fee2e2; color: #b91c1c; font-size: 0.85rem; padding: 0.5rem;" onclick="return confirm('<?= __('delete_gallery_confirm') ?>')">
                        <i class="fa-solid fa-trash"></i> <?= __('delete') ?>
                    </a>
                </div>
                
                <div style="margin-top: 0.75rem; font-size: 0.8rem; color: var(--admin-text-light); text-align: center;">
                    <?= __('gallery_display_order') ?>: <?= $item['display_order'] ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?= renderPagination($page, $pagination['total_pages']) ?>

<?php require_once 'footer.php'; ?>
