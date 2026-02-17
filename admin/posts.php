<?php require_once 'header.php'; ?>
<?php
// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
    setFlash('success', __('post_deleted_success', 'Post deleted'));
    redirect('posts.php');
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
    $where .= " AND (title LIKE ? OR content LIKE ?)";
    $sqlParams[] = "%$search%";
    $sqlParams[] = "%$search%";
}

// Get Total Count
$countQuery = "SELECT COUNT(*) FROM posts WHERE $where";
$stmt = $pdo->prepare($countQuery);
$stmt->execute($sqlParams);
$totalItems = $stmt->fetchColumn();

// Get Pagination Data
$pagination = getPaginationData($totalItems, $limit);
$offset = $pagination['offset'];

// Fetch Paginated Posts
$allowedSorts = ['title', 'created_at'];
if (!in_array($sort, $allowedSorts)) { $sort = 'created_at'; }

$query = "SELECT * FROM posts 
          WHERE $where 
          ORDER BY $sort $order 
          LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($sqlParams);
$posts = $stmt->fetchAll();
?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title"><?= __('posts_title') ?></h1>
        <p class="page-subtitle">Publish and manage your blog articles and news updates.</p>
    </div>
    <div class="page-header-actions">
        <a href="post_form.php" class="btn btn-primary">
            <i class="fa-solid fa-plus"></i> <?= __('add_new_post') ?>
        </a>
    </div>
</div>

<!-- Listing Controls -->
<div class="listing-controls">
    <form method="GET" class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search posts..." class="form-control">
        <?php if($search): ?>
            <a href="posts.php" style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: var(--admin-text-light);"><i class="fa-solid fa-xmark" style="position: static; padding: 0;"></i></a>
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
            <i class="fa-solid fa-pen-nib" style="color: var(--admin-primary);"></i>
            <?= __('posts_title') ?>
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-light);">
            Showing <?= count($posts) ?> of <?= $totalItems ?> <?= __('items') ?>
        </div>
    </div>
    <div class="widget-content">
        <table class="widget-table responsive-table">
            <thead>
                <tr>
                    <th><?= __('post_title') ?></th>
                    <th><?= __('published_date') ?></th>
                    <th width="150" style="text-align: center;"><?= __('action') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $p): ?>
                <tr>
                    <td data-label="<?= __('post_title') ?>" style="font-weight: 700; color: var(--admin-text); font-size: 1.05rem;">
                        <?= htmlspecialchars($p['title']) ?> <?= !empty($p['show_raw_html']) ? '<span class="badge" style="margin-left:0.5rem;">Code</span>' : '' ?>
                    </td>
                    <td data-label="<?= __('published_date') ?>" style="color: var(--admin-text-light); font-weight: 500;"><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
                    <td data-label="<?= __('action') ?>">
                        <div style="display: flex; gap: 0.5rem; justify-content: center; align-items: center;">
                            <a href="post_form.php?id=<?= $p['id'] ?>" class="btn-action btn-action-edit" title="<?= __('edit_post') ?>">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            <a href="posts.php?delete=<?= $p['id'] ?>" class="btn-action btn-action-delete" onclick="return confirm('<?= __('delete_post_confirm') ?>')" title="<?= __('delete_post') ?>">
                                <i class="fa-solid fa-trash-can"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($posts)): ?>
                <tr>
                    <td colspan="3" style="text-align: center; padding: 3rem; color: var(--admin-text-light);"><?= __('no_posts') ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= renderPagination($page, $pagination['total_pages']) ?>

<?php require_once 'footer.php'; ?>
