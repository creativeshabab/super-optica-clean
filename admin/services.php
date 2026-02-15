<?php require_once 'header.php'; ?>
<?php
// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM services WHERE id = ?")->execute([$id]);
    setFlash('success', __('service_deleted_success', 'Service deleted'));
    redirect('services.php');
}

$services = $pdo->query("SELECT * FROM services ORDER BY created_at DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
    <h1 class="admin-title" style="margin-bottom: 0;"><?= __('services_title') ?></h1>
    <a href="service_form.php" class="btn btn-primary" style="font-size: 0.9rem; font-weight: 600;">
        <i class="fa-solid fa-plus"></i> <?= __('add_new_service') ?>
    </a>
</div>

<div class="admin-table-widget">
    <div class="widget-header">
        <div class="widget-title">
            <i class="fa-solid fa-bell-concierge" style="color: var(--admin-primary);"></i>
            <?= __('services_title') ?>
        </div>
        <div style="font-size: 0.85rem; color: var(--admin-text-light);">
            <?= count($services) ?> <?= __('items') ?>
        </div>
    </div>
    <div class="widget-content">
        <table class="widget-table">
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
                    <td>
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
                    <td style="font-weight: 700; color: var(--admin-sidebar); font-size: 1.05rem;"><?= htmlspecialchars($s['title']) ?></td>
                    <td style="color: var(--admin-text-light); font-size: 0.95rem; max-width: 400px;"><?= htmlspecialchars(substr($s['description'], 0, 80)) ?>...</td>
                    <td>
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

<?php require_once 'footer.php'; ?>
