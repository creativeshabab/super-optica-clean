<?php require_once 'header.php'; ?>
<?php
$service = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $service = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $display_order = (int)($_POST['display_order'] ?? 0);
    $icon = $_POST['icon_class']; // Keep for backward compatibility
    
    // Process features array into JSON
    $features = [];
    if (isset($_POST['features']) && is_array($_POST['features'])) {
        foreach ($_POST['features'] as $feature) {
            if (!empty(trim($feature))) {
                $features[] = trim($feature);
            }
        }
    }
    $features_json = json_encode($features);

    // Handle Image Upload (Primary)
    $image = $service['image'] ?? null;
    if (isset($_FILES['service_image']) && $_FILES['service_image']['error'] === 0) {
        $uploaded = optimizeUpload($_FILES['service_image'], '../assets/uploads/');
        if ($uploaded) {
            $image = $uploaded;
        }
    }

    // Handle Icon Upload (Fallback for backward compatibility)
    if (isset($_FILES['icon_image']) && $_FILES['icon_image']['error'] === 0) {
        $uploaded = optimizeUpload($_FILES['icon_image'], '../assets/uploads/');
        if ($uploaded) {
            $icon = $uploaded;
        }
    } elseif (empty($icon) && $service) {
        $icon = $service['icon'];
    }

    try {
        if ($service) {
            $stmt = $pdo->prepare("UPDATE services SET title=?, description=?, icon=?, features=?, image=?, display_order=? WHERE id=?");
            $stmt->execute([$title, $description, $icon, $features_json, $image, $display_order, $service['id']]);
            setFlash('success', __('service_updated_success', 'Service updated'));
        } else {
            $stmt = $pdo->prepare("INSERT INTO services (title, description, icon, features, image, display_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $icon, $features_json, $image, $display_order]);
            setFlash('success', __('service_created_success', 'Service created'));
        }
        redirect('services.php');
    } catch (PDOException $e) {
        $error = __('database_error', "Database Error") . ": " . $e->getMessage();
    }
}
?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title"><?= $service ? __('edit_service') : __('add_new_service') ?></h1>
    </div>
    <div class="page-header-actions">
        <a href="services.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> <?= __('back_to_list') ?>
        </a>
    </div>
</div>

<div class="card" style="max-width: 700px;">
    <h3 class="card-title"><?= __('service_details') ?></h3>
    
    <?php if (isset($error)): ?>
        <div class="alert" style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #fecaca;">
            <i class="fa-solid fa-circle-exclamation" style="margin-right: 0.5rem;"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label><?= __('service_title_label') ?></label>
            <input type="text" name="title" value="<?= $service['title'] ?? '' ?>" placeholder="e.g. Complete Eye Care" required class="form-input">
        </div>

        <div class="form-group">
            <label><?= __('service_description_label') ?></label>
            <textarea name="description" rows="4" placeholder="Describe the service in detail..." required class="form-input"><?= $service['description'] ?? '' ?></textarea>
        </div>

        <!-- Service Image Upload (Primary) -->
        <div class="form-group">
            <label><?= __('service_image_label') ?></label>
            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div class="drag-upload-container">
                    <div id="serviceImageDragBox" class="drag-upload-box">
                        <div class="drag-upload-icon">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div class="drag-upload-text"><?= __('drag_drop_service_image') ?></div>
                        <button type="button" class="drag-upload-btn"><?= __('browse_files_alt', 'Browse') ?></button>
                        <input type="file" name="service_image" id="serviceImageInput" accept="image/*" style="display: none;">
                    </div>
                    
                    <div id="serviceImagePreview" class="drag-preview-container">
                        <img src="" class="drag-preview-image">
                        <div class="drag-preview-info">
                            <div class="drag-preview-name">image.jpg</div>
                        </div>
                    </div>
                </div>

                <?php if ($service && $service['image']): ?>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
                        <small style="display: block; margin-bottom: 0.5rem; color: var(--admin-text-light);"><?= __('current_image_label') ?></small>
                        <img src="../assets/uploads/<?= $service['image'] ?>" style="max-width: 200px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Dynamic Features -->
        <div class="form-group">
            <label><?= __('service_features_label') ?></label>
            <div id="featuresContainer" style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                <?php 
                $existing_features = [];
                if ($service && !empty($service['features'])) {
                    $existing_features = json_decode($service['features'], true) ?: [];
                }
                
                if (empty($existing_features)) {
                    $existing_features = ['', '', '']; // Default 3 empty fields
                }
                
                foreach ($existing_features as $index => $feature): 
                ?>
                <div class="feature-input-row" style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem; align-items: center;">
                    <i class="fa-solid fa-circle-check" style="color: var(--primary); font-size: 1.2rem;"></i>
                    <input type="text" name="features[]" value="<?= htmlspecialchars($feature) ?>" placeholder="e.g. Expert Consultation" style="flex: 1;" class="form-input">
                    <button type="button" class="btn-remove-feature" onclick="removeFeature(this)" style="background: #fee2e2; color: #b91c1c; border: none; padding: 0.5rem 0.75rem; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addFeature()" class="btn btn-secondary" style="margin-top: 1rem;">
                <i class="fa-solid fa-plus"></i> <?= __('add_feature') ?>
            </button>
        </div>

        <!-- Display Order -->
        <div class="form-group">
            <label><?= __('display_order') ?></label>
            <input type="number" name="display_order" value="<?= $service['display_order'] ?? 0 ?>" min="0" placeholder="0" class="form-input">
            <small style="color: var(--admin-text-light); display: block; margin-top: 0.5rem;"><?= __('display_order_help_service') ?></small>
        </div>

        <!-- Legacy Icon Field (Collapsed) -->
        <details style="margin-top: 2rem;">
            <summary style="cursor: pointer; padding: 1rem; background: #f8fafc; border-radius: 8px; font-weight: 600;">
                <?= __('advanced_icon_badge') ?>
            </summary>
            <div class="form-group" style="margin-top: 1rem;">
                <label><?= __('icon_badge_label') ?></label>
                <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <div class="admin-grid" style="grid-template-columns: 1fr 1fr; align-items: center;">
                        <div>
                            <small style="display: block; margin-bottom: 0.5rem; color: var(--admin-text-light);"><?= __('fontawesome_class') ?></small>
                            <input type="text" name="icon_class" placeholder="e.g. fa-solid fa-eye" value="<?= ($service && strpos($service['icon'] ?? '', 'fa-') !== false) ? $service['icon'] : '' ?>" class="form-input">
                        </div>
                        <div>
                            <small style="display: block; margin-bottom: 0.5rem; color: var(--admin-text-light);"><?= __('upload_custom_icon') ?></small>
                            <div class="drag-upload-container">
                                <div id="serviceDragBox" class="drag-upload-box" style="padding: 1.5rem 1rem; min-height: 150px;">
                                    <div class="drag-upload-icon" style="width: 40px; height: 60px; font-size: 1.2rem; margin-bottom: 0.5rem;">
                                        <i class="fa-solid fa-cloud-arrow-up"></i>
                                    </div>
                                    <div class="drag-upload-text" style="font-size: 0.9rem;"><?= __('drag_drop_files') ?></div>
                                    <button type="button" class="drag-upload-btn" style="padding: 0.3rem 1rem; font-size: 0.8rem;"><?= __('browse_files_alt', 'Browse') ?></button>
                                    <input type="file" name="icon_image" id="serviceIconInput" style="display: none;">
                                </div>
                                
                                <div id="servicePreview" class="drag-preview-container">
                                    <img src="" class="drag-preview-image" style="width: 40px; height: 60px;">
                                    <div class="drag-preview-info">
                                        <div class="drag-preview-name" style="font-size: 0.8rem;">file.jpg</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($service && $service['icon']): ?>
                        <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; display: flex; align-items: center; gap: 1rem;">
                            <div style="width: 50px; height: 50px; background: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; border: 1px solid #e2e8f0; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                                <?php if (strpos($service['icon'], 'fa-') !== false): ?>
                                    <i class="<?= $service['icon'] ?>" style="font-size: 1.5rem; color: var(--admin-sidebar);"></i>
                                <?php else: ?>
                                    <img src="../assets/uploads/<?= $service['icon'] ?>" style="width: 30px; height: 30px; object-fit: contain;">
                                <?php endif; ?>
                            </div>
                            <span style="font-size: 0.85rem; color: var(--admin-text-light);"><?= __('currently_active_icon') ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </details>

        <div style="margin-top: 2rem; border-top: 1px solid #f1f5f9; padding-top: 2rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2.5rem;">
                <?= $service ? __('update') : __('create') ?>
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        initDragAndDrop('serviceDragBox', 'serviceIconInput', 'servicePreview');
        initDragAndDrop('serviceImageDragBox', 'serviceImageInput', 'serviceImagePreview');
    });
    
    function addFeature() {
        const container = document.getElementById('featuresContainer');
        const newRow = document.createElement('div');
        newRow.className = 'feature-input-row';
        newRow.style.cssText = 'display: flex; gap: 0.5rem; margin-bottom: 0.75rem; align-items: center;';
        newRow.innerHTML = `
            <i class="fa-solid fa-circle-check" style="color: var(--primary); font-size: 1.2rem;"></i>
            <input type="text" name="features[]" placeholder="e.g. Latest Technology" style="flex: 1;" class="form-input">
            <button type="button" class="btn-remove-feature" onclick="removeFeature(this)" style="background: #fee2e2; color: #b91c1c; border: none; padding: 0.5rem 0.75rem; border-radius: 6px; cursor: pointer;">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;
        container.appendChild(newRow);
    }
    
    function removeFeature(btn) {
        btn.closest('.feature-input-row').remove();
    }
</script>

<?php require_once 'footer.php'; ?>
