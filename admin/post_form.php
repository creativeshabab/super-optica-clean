<?php require_once 'header.php'; ?>

<?php
$post = null;

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $post = $stmt->fetch();
}

// Ensure 'show_raw_html' column exists on posts (safe, one-time)
$col = $pdo->query("SHOW COLUMNS FROM posts LIKE 'show_raw_html'")->fetch();
if (!$col) {
    try {
        $pdo->exec("ALTER TABLE posts ADD COLUMN show_raw_html TINYINT(1) DEFAULT 0");
    } catch (Exception $e) {
        // Ignore in case user doesn't have permission; we'll handle gracefully below
    }
}
// Re-check after attempting to add
$hasShowRawColumn = (bool)$pdo->query("SHOW COLUMNS FROM posts LIKE 'show_raw_html'")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $slug = strtolower(str_replace(' ', '-', $title));
    $content = $_POST['content'];
    $show_raw_html = isset($_POST['show_raw_html']) ? 1 : 0;
    $image = $post['image'] ?? null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploaded = optimizeUpload($_FILES['image'], '../assets/uploads/');
        if ($uploaded) {
            $image = $uploaded;
        }
    }

    try {
        if ($post) {
            if ($hasShowRawColumn) {
                $stmt = $pdo->prepare("UPDATE posts SET title=?, slug=?, content=?, image=?, show_raw_html=? WHERE id=?");
                $stmt->execute([$title, $slug, $content, $image, $show_raw_html, $post['id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE posts SET title=?, slug=?, content=?, image=? WHERE id=?");
                $stmt->execute([$title, $slug, $content, $image, $post['id']]);
            }
            setFlash('success', __('post_updated_success', 'Post updated'));
        } else {
            if ($hasShowRawColumn) {
                $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, image, show_raw_html) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $content, $image, $show_raw_html]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, image) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $content, $image]);
            }
            setFlash('success', __('post_created_success', 'Post created'));
        }
        redirect('posts.php');
    } catch (PDOException $e) {
        if ($e->getCode() == '23000') {
            $error = __('duplicate_post_error', "Duplicate entry: A post with this title/slug already exists.");
        } else {
            $error = __('database_error', "Database Error") . ": " . $e->getMessage();
        }
    }
}
?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title"><?= $post ? __('edit_post') : __('add_new_post') ?></h1>
    </div>
    <div class="page-header-actions">
        <a href="posts.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> <?= __('back_to_list') ?>
        </a>
    </div>
</div>

<div class="card" style="max-width: 1000px;">
    <h3 class="card-title"><?= __('content_editor') ?></h3>
    
    <?php if (isset($error)): ?>
        <div class="alert" style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #fecaca;">
            <i class="fa-solid fa-circle-exclamation" style="margin-right: 0.5rem;"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label><?= __('post_title') ?></label>
            <input type="text" name="title" value="<?= $post['title'] ?? '' ?>" placeholder="e.g. 5 Tips for Choosing Your Next Frame" required class="form-input">
        </div>

        <div class="form-group">
            <label><?= __('detailed_content') ?></label>
            <textarea name="content" class="rich-editor form-input" rows="12"><?= $post['content'] ?? '' ?></textarea>
            <div style="margin-top: 0.5rem; font-size: 0.9rem; color: var(--text-light);"><?= __('raw_html_help') ?></div>
            <label style="display:inline-flex; align-items:center; gap:0.5rem; margin-top:0.5rem;"><input type="checkbox" name="show_raw_html" value="1" <?= (isset($post['show_raw_html']) && $post['show_raw_html']) ? 'checked' : '' ?>> <span style="font-weight:700;"><?= __('display_raw_html') ?></span></label>
        </div>

        <div class="form-group">
            <label><?= __('featured_image') ?></label>
            <div class="drag-upload-container">
                <div id="postDragBox" class="drag-upload-box">
                    <div class="drag-upload-icon">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <div class="drag-upload-text"><?= __('drag_drop_files') ?></div>
                    <div style="margin: 0.5rem 0; color: #94a3b8; font-size: 0.8rem;">or</div>
                    <button type="button" class="drag-upload-btn"><?= __('browse_files_alt', 'Browse Files') ?></button>
                    <input type="file" name="image" id="postImageInput" style="display: none;">
                </div>
                
                <div id="postPreview" class="drag-preview-container">
                    <img src="" class="drag-preview-image">
                    <div class="drag-preview-info">
                        <div class="drag-preview-name">filename.jpg</div>
                        <div class="drag-preview-size">0.0 KB</div>
                    </div>
                </div>

                <?php if ($post && $post['image']): ?>
                    <div style="margin-top: 1rem; padding: 1rem; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 1rem;">
                        <img src="../assets/uploads/<?= $post['image'] ?>" style="width: 80px; height: 50px; border-radius: 4px; object-fit: cover;">
                        <span style="font-size: 0.85rem; color: var(--admin-text-light);"><?= __('current_featured_image') ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-top: 2rem; border-top: 1px solid #f1f5f9; padding-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2.5rem;">
                <i class="fa-solid fa-paper-plane"></i> <?= $post ? __('update_post') : __('publish_post') ?>
            </button>
            <a href="posts.php" class="btn btn-secondary"><?= __('cancel_editing') ?></a>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        initDragAndDrop('postDragBox', 'postImageInput', 'postPreview');
    });
</script>

<?php require_once 'footer.php'; ?>
