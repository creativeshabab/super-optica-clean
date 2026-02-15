<?php
require_once 'header.php';

// Handle Delete
if (isset($_POST['delete'])) {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM sliders WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', __('slide_deleted_success', 'Slide deleted successfully'));
    header("Location: sliders.php");
    exit;
}

// Fetch Sliders
$sliders = $pdo->query("SELECT * FROM sliders ORDER BY created_at DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
    <h1 class="admin-title" style="margin-bottom: 0;"><?= __('sliders_title') ?></h1>
    <a href="slider_form.php" class="btn btn-primary"><i class="fas fa-plus"></i> <?= __('add_new_slide') ?></a>
</div>

<div class="admin-table-widget">
    <div class="widget-header">
        <div class="widget-title">
            <i class="fa-solid fa-images" style="color: var(--admin-primary);"></i>
            <?= __('existing_slides', 'Existing Slides') ?>
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-light);">
            <?= count($sliders) ?> <?= __('items') ?>
        </div>
    </div>
    <div class="widget-content">
        <table class="widget-table">
            <thead>
                <tr>
                    <th width="50"><?= __('slider_id') ?></th>
                    <th width="120"><?= __('slider_image') ?></th>
                    <th><?= __('slider_content') ?></th>
                    <th><?= __('slider_link') ?></th>
                    <th width="150" style="text-align: center;"><?= __('actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sliders as $s): ?>
                <tr>
                    <td style="font-weight: 600; color: var(--admin-text-light);"><?= $s['id'] ?></td>
                    <td>
                        <?php if ($s['image']): ?>
                            <img src="../assets/uploads/<?= htmlspecialchars($s['image']) ?>" width="100" style="border-radius: 8px; height: 60px; object-fit: cover; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <?php else: ?>
                            <div style="width: 100px; height: 60px; background: #f1f5f9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #cbd5e1;">
                                <i class="fa-solid fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight: 700; font-size: 1.1rem; color: var(--admin-sidebar);"><?= htmlspecialchars($s['title']) ?></div>
                        <div style="color: var(--admin-text-light); font-size: 0.9rem; margin-top: 0.25rem;"><?= htmlspecialchars($s['subtitle']) ?></div>
                    </td>
                    <td>
                        <?php if ($s['link']): ?>
                            <a href="<?= htmlspecialchars($s['link']) ?>" target="_blank" class="btn btn-primary" style="padding: 0.5rem 1.25rem; border-radius: 50px; font-size: 0.8rem; background: var(--admin-sidebar);">
                                <?= $s['link_text'] ?: __('visit_link') ?> <i class="fa-solid fa-arrow-up-right-from-square" style="margin-left: 0.5rem; font-size: 0.7rem;"></i>
                            </a>
                        <?php else: ?>
                            <span style="color: #cbd5e1;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem; justify-content: center; align-items: center;">
                            <a href="slider_form.php?id=<?= $s['id'] ?>" class="btn-action btn-action-edit" title="<?= __('edit_slide') ?>">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                            
                            <form method="POST" style="display: contents;" onsubmit="return confirm('<?= __('delete_slide_confirm') ?>');">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" name="delete" class="btn-action btn-action-delete" title="<?= __('delete') ?>" style="border: none; cursor: pointer;">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($sliders)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 3rem; color: var(--admin-text-light);">
                        <div style="margin-bottom: 1rem; font-size: 2rem; color: #cbd5e1;"><i class="fa-solid fa-images"></i></div>
                        <?= __('no_slides') ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
