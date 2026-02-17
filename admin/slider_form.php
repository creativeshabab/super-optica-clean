<?php
require_once 'header.php';

$title = "";
$subtitle = "";
$image = "";
$link = "";
$link_text = "Shop Now";
$secondary_link = "shop.php";
$secondary_link_text = "Explore Collection";
$badge_text = "Visit Us";
$id = "";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM sliders WHERE id = ?");
    $stmt->execute([$id]);
    $slider = $stmt->fetch();
    
    if ($slider) {
        $title = $slider['title'];
        $subtitle = $slider['subtitle'];
        $image = $slider['image'];
        $link = $slider['link'];
        $link_text = $slider['link_text'];
        $secondary_link = $slider['secondary_link'] ?? 'shop.php';
        $secondary_link_text = $slider['secondary_link_text'] ?? 'Explore Collection';
        $badge_text = $slider['badge_text'] ?? 'Visit Us';
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $subtitle = $_POST['subtitle'];
    $link = $_POST['link'];
    $link_text = $_POST['link_text'];
    $secondary_link = $_POST['secondary_link'] ?? 'shop.php';
    $secondary_link_text = $_POST['secondary_link_text'] ?? 'Explore Collection';
    $badge_text = $_POST['badge_text'] ?? 'Visit Us';
    
    // Image Upload (Optimized)
    if (!empty($_FILES['image']['name'])) {
        $uploaded = optimizeUpload($_FILES['image'], '../assets/uploads/');
        if ($uploaded) {
            $image = $uploaded;
        }
    }
    
    try {
        if ($id) {
            $sql = "UPDATE sliders SET title = ?, subtitle = ?, image = ?, link = ?, link_text = ?, secondary_link = ?, secondary_link_text = ?, badge_text = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $subtitle, $image, $link, $link_text, $secondary_link, $secondary_link_text, $badge_text, $id]);
            setFlash('success', __('slide_updated_success', 'Slide updated successfully'));
        } else {
            $sql = "INSERT INTO sliders (title, subtitle, image, link, link_text, secondary_link, secondary_link_text, badge_text) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $subtitle, $image, $link, $link_text, $secondary_link, $secondary_link_text, $badge_text]);
            setFlash('success', __('slide_created_success', 'Slide added successfully'));
        }
        
         echo "<script>window.location.href='sliders.php';</script>";
         exit;
    } catch (PDOException $e) {
        $error = __('database_error', "Error") . ": " . $e->getMessage();
    }
}
?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title"><?= $id ? __('edit_slide') : __('add_slide') ?></h1>
    </div>
    <div class="page-header-actions">
        <a href="sliders.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> <?= __('back_to_list') ?>
        </a>
    </div>
</div>

<div class="card" style="max-width: 800px;">
    <h3 class="card-title"><?= __('slide_config') ?></h3>
    
    <?php if (isset($error)): ?>
        <div class="alert" style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #fecaca;">
            <i class="fa-solid fa-circle-exclamation" style="margin-right: 0.5rem;"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label><?= __('slider_headline') ?></label>
            <input type="text" name="title" value="<?= htmlspecialchars($title) ?>" placeholder="<?= __('slider_headline_placeholder') ?>" required class="form-input">
        </div>
        
        <div class="form-group">
            <label><?= __('slider_description') ?></label>
            <textarea name="subtitle" rows="3" placeholder="<?= __('slider_description_placeholder') ?>" class="form-input"><?= htmlspecialchars($subtitle) ?></textarea>
        </div>
        
        <div class="form-group">
            <label><?= __('badge_text', 'Badge Text') ?></label>
            <input type="text" name="badge_text" value="<?= htmlspecialchars($badge_text) ?>" placeholder="e.g. Visit Us, New Arrival, Limited Offer" class="form-input">
            <small style="color: var(--admin-text-light); font-size: 0.85rem; margin-top: 0.25rem; display: block;">Small badge shown above the headline</small>
        </div>
        
        <div class="admin-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label><?= __('button_link') ?> (Primary)</label>
                <input type="text" name="link" value="<?= htmlspecialchars($link) ?>" placeholder="<?= __('button_link_placeholder') ?>" class="form-input">
            </div>
            <div class="form-group">
                <label><?= __('button_text') ?> (Primary)</label>
                <input type="text" name="link_text" value="<?= htmlspecialchars($link_text) ?>" placeholder="<?= __('button_text_placeholder') ?>" class="form-input">
            </div>
        </div>
        
        <div class="admin-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="form-group">
                <label><?= __('secondary_button_link', 'Secondary Button Link') ?></label>
                <input type="text" name="secondary_link" value="<?= htmlspecialchars($secondary_link) ?>" placeholder="e.g. shop.php" class="form-input">
            </div>
            <div class="form-group">
                <label><?= __('secondary_button_text', 'Secondary Button Text') ?></label>
                <input type="text" name="secondary_link_text" value="<?= htmlspecialchars($secondary_link_text) ?>" placeholder="e.g. Explore Collection" class="form-input">
            </div>
        </div>
        
        <div class="form-group">
            <label><?= __('background_image') ?></label>
            <div class="drag-upload-container">
                <div id="sliderDragBox" class="drag-upload-box">
                    <div class="drag-upload-icon">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <div class="drag-upload-text"><?= __('drop_image_here') ?></div>
                    <div style="margin: 0.5rem 0; color: #94a3b8; font-size: 0.8rem;">or</div>
                    <button type="button" class="drag-upload-btn"><?= __('browse_files', 'Browse Files') ?></button>
                    <input type="file" name="image" id="sliderImageInput" style="display: none;">
                </div>
                
                <div id="sliderPreview" class="drag-preview-container">
                    <img src="" class="drag-preview-image">
                    <div class="drag-preview-info">
                        <div class="drag-preview-name">filename.jpg</div>
                        <div class="drag-preview-size">0.0 KB</div>
                    </div>
                </div>

                <?php if ($image): ?>
                    <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; display: inline-block;">
                        <img src="../assets/uploads/<?= $image ?>" width="200" style="border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                        <p style="color: var(--admin-text-light); font-size: 0.8rem; margin-top: 0.5rem; text-align: center;"><?= __('current_image') ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="margin-top: 2rem; border-top: 1px solid #f1f5f9; padding-top: 2rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2.5rem;"><?= __('save_slide') ?></button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        initDragAndDrop('sliderDragBox', 'sliderImageInput', 'sliderPreview');
    });
</script>

<?php require_once 'footer.php'; ?>
