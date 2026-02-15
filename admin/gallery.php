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

// Fetch all gallery items
$stmt = $pdo->query("SELECT * FROM gallery ORDER BY display_order ASC, created_at DESC");
$gallery_items = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="admin-title"><?= __('gallery_title') ?></h1>
    <a href="gallery_form.php" class="btn btn-primary">
        <i class="fa-solid fa-plus"></i> <?= __('add_new_gallery_item') ?>
    </a>
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

<?php require_once 'footer.php'; ?>
