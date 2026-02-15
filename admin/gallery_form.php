<?php require_once 'header.php'; ?>
<?php
$gallery_item = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM gallery WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $gallery_item = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $display_order = (int)($_POST['display_order'] ?? 0);
    
    // Handle Image Upload
    $image = $gallery_item['image'] ?? null;
    if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === 0) {
        $uploaded = optimizeUpload($_FILES['gallery_image'], '../assets/uploads/');
        if ($uploaded) {
            $image = $uploaded;
        }
    }

    try {
        if ($gallery_item) {
            $stmt = $pdo->prepare("UPDATE gallery SET title=?, description=?, image=?, category=?, display_order=? WHERE id=?");
            $stmt->execute([$title, $description, $image, $category, $display_order, $gallery_item['id']]);
            setFlash('success', __('gallery_item_updated_success', 'Gallery item updated'));
        } else {
            if (!$image) {
                $error = "Please upload an image";
            } else {
                $stmt = $pdo->prepare("INSERT INTO gallery (title, description, image, category, display_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $image, $category, $display_order]);
                setFlash('success', __('gallery_item_created_success', 'Gallery item created'));
            }
        }
        
        if (!isset($error)) {
            redirect('gallery.php');
        }
    } catch (PDOException $e) {
        $error = __('database_error', "Database Error") . ": " . $e->getMessage();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="admin-title"><?= $gallery_item ? __('edit_gallery_item') : __('add_new_gallery_item') ?></h1>
    <a href="gallery.php" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> <?= __('back_to_gallery') ?></a>
</div>

<div class="card" style="max-width: 700px;">
    <h3 class="card-title"><?= __('gallery_details') ?></h3>
    
    <?php if (isset($error)): ?>
        <div class="alert" style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #fecaca;">
            <i class="fa-solid fa-circle-exclamation" style="margin-right: 0.5rem;"></i> <?= $error ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label><?= __('gallery_item_title') ?> *</label>
            <input type="text" name="title" value="<?= $gallery_item['title'] ?? '' ?>" placeholder="e.g. Eye Examination Room" required class="form-input">
        </div>

        <div class="form-group">
            <label><?= __('gallery_item_description') ?></label>
            <textarea name="description" rows="3" placeholder="<?= __('optional_description') ?>" class="form-input"><?= $gallery_item['description'] ?? '' ?></textarea>
        </div>

        <div class="form-group">
            <label><?= __('gallery_item_category') ?></label>
            <input type="text" name="category" value="<?= $gallery_item['category'] ?? '' ?>" placeholder="e.g. Facilities, Products, Events" class="form-input">
            <small style="color: var(--admin-text-light); display: block; margin-top: 0.5rem;"><?= __('optional_category_help') ?></small>
        </div>

        <!-- Image Upload -->
        <div class="form-group">
            <label><?= __('gallery_item_image') ?> *</label>
            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                <div class="drag-upload-container">
                    <div id="galleryImageDragBox" class="drag-upload-box">
                        <div class="drag-upload-icon">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div class="drag-upload-text"><?= __('drag_drop_gallery_image') ?></div>
                        <button type="button" class="drag-upload-btn"><?= __('browse_files', 'Browse') ?></button>
                        <input type="file" name="gallery_image" id="galleryImageInput" accept="image/*" style="display: none;" <?= !$gallery_item ? 'required' : '' ?>>
                    </div>
                    
                    <div id="galleryImagePreview" class="drag-preview-container">
                        <img src="" class="drag-preview-image">
                        <div class="drag-preview-info">
                            <div class="drag-preview-name">image.jpg</div>
                        </div>
                    </div>
                </div>

                <?php if ($gallery_item && $gallery_item['image']): ?>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
                        <small style="display: block; margin-bottom: 0.5rem; color: var(--admin-text-light);"><?= __('current_image_label') ?></small>
                        <img src="../assets/uploads/<?= $gallery_item['image'] ?>" style="max-width: 100%; border-radius: 8px; border: 1px solid #e2e8f0;">
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Display Order -->
        <div class="form-group">
            <label><?= __('gallery_display_order') ?></label>
            <input type="number" name="display_order" value="<?= $gallery_item['display_order'] ?? 0 ?>" min="0" placeholder="0" class="form-input">
            <small style="color: var(--admin-text-light); display: block; margin-top: 0.5rem;"><?= __('gallery_display_order_help') ?></small>
        </div>

        <div style="margin-top: 2rem; border-top: 1px solid #f1f5f9; padding-top: 2rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2.5rem;">
                <?= $gallery_item ? __('update') : __('create') ?>
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        initDragAndDrop('galleryImageDragBox', 'galleryImageInput', 'galleryImagePreview');
    });
</script>

<?php require_once 'footer.php'; ?>
