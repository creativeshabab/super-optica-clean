<?php require_once 'header.php'; ?>

<?php
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$id]);
    setFlash('success', __('post_deleted_success', 'Post deleted'));
    redirect('posts.php');
}

$posts = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
    <h1 class="admin-title" style="margin-bottom: 0;"><?= __('posts_title') ?></h1>
    <a href="post_form.php" class="btn btn-primary" style="font-size: 0.9rem; font-weight: 600;">
        <i class="fa-solid fa-plus"></i> <?= __('add_new_post') ?>
    </a>
</div>

<div class="admin-table-widget">
    <div class="widget-header">
        <div class="widget-title">
            <i class="fa-solid fa-pen-nib" style="color: var(--admin-primary);"></i>
            <?= __('posts_title') ?>
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-light);">
            <?= count($posts) ?> <?= __('items') ?>
        </div>
    </div>
    <div class="widget-content">
        <table class="widget-table">
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
                    <td style="font-weight: 700; color: var(--admin-text); font-size: 1.05rem;">
                        <?= htmlspecialchars($p['title']) ?> <?= !empty($p['show_raw_html']) ? '<span class="badge" style="margin-left:0.5rem;">Code</span>' : '' ?>
                    </td>
                    <td style="color: var(--admin-text-light); font-weight: 500;"><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
                    <td>
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

<?php require_once 'footer.php'; ?>
