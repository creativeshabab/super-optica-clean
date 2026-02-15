<?php require_once 'header.php'; ?>
<?php
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM reviews WHERE id = ?")->execute([$id]);
    setFlash('success', __('review_deleted_success', 'Review deleted successfully'));
    redirect('reviews.php');
}

$reviews = $pdo->query("SELECT * FROM reviews ORDER BY created_at DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
    <h1 class="admin-title" style="margin-bottom: 0;"><?= __('reviews_title') ?></h1>
    <a href="review_form.php" class="btn btn-primary" style="font-size: 0.9rem; font-weight: 600;"><i class="fas fa-plus"></i> <?= __('add_new_review') ?></a>
</div>

<div class="admin-table-widget">
    <div class="widget-header">
        <div class="widget-title">
            <i class="fa-solid fa-star" style="color: var(--admin-primary);"></i>
            <?= __('reviews_title') ?>
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-light);">
            <?= count($reviews) ?> <?= __('items') ?>
        </div>
    </div>
    <div class="widget-content">
        <table class="widget-table">
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
                    <td>
                        <?php if ($r['image']): ?>
                            <img src="../assets/uploads/<?= $r['image'] ?>" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
                        <?php else: ?>
                            <div style="width: 48px; height: 48px; background: #e0f2fe; color: #0369a1; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem;">
                                <?= strtoupper(substr($r['name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 700; color: var(--admin-sidebar);"><?= htmlspecialchars($r['name']) ?></td>
                    <td>
                        <div style="color: #f59e0b; display: flex; gap: 2px;">
                            <?php for($i=0; $i<$r['rating']; $i++) echo '<i class="fa-solid fa-star" style="font-size: 0.8rem;"></i>'; ?>
                        </div>
                    </td>
                    <td style="color: var(--admin-text-light); font-size: 0.95rem;"><?= htmlspecialchars(substr($r['comment'], 0, 80)) ?>...</td>
                    <td>
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

<?php require_once 'footer.php'; ?>
