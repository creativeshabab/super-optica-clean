<?php require_once 'header.php'; ?>
<?php
$review = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $review = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    $image = $review['image'] ?? null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], '../assets/uploads/' . $filename)) {
                $image = $filename;
            }
        }
    }

    try {
        if ($review) {
            $stmt = $pdo->prepare("UPDATE reviews SET name=?, rating=?, comment=?, image=? WHERE id=?");
            $stmt->execute([$name, $rating, $comment, $image, $review['id']]);
            setFlash('success', 'Review updated');
        } else {
            $stmt = $pdo->prepare("INSERT INTO reviews (name, rating, comment, image) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $rating, $comment, $image]);
            setFlash('success', 'Review added');
        }
        redirect('reviews.php');
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}
?>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title"><?= $review ? 'Edit' : 'Add' ?> Review</h1>
    </div>
    <div class="page-header-actions">
        <a href="reviews.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="card" style="max-width: 700px;">
    <h3 class="card-title">Customer Feedback Details</h3>
    
    <?php if (isset($error)): ?>
        <div class="alert" style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #fecaca;">
            <i class="fa-solid fa-circle-exclamation" style="margin-right: 0.5rem;"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="admin-grid">
            <div>
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" name="name" value="<?= $review['name'] ?? '' ?>" placeholder="e.g. John Doe" required>
                </div>

                <div class="form-group">
                    <label>Rating</label>
                    <select name="rating" required>
                        <?php for($i=5; $i>=1; $i--): ?>
                            <option value="<?= $i ?>" <?= ($review && $review['rating'] == $i) ? 'selected' : '' ?>>
                                <?= $i ?> Stars <?= $i == 5 ? '(Excellent)' : ($i == 1 ? '(Poor)' : '') ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label style="text-align: center;">Customer Photo</label>
                    <div class="drag-upload-container">
                        <div id="reviewDragBox" class="drag-upload-box" style="border-radius: 50%; width: 150px; height: 150px; padding: 1rem;">
                            <div class="drag-upload-icon" style="width: 40px; height: 60px; font-size: 1.2rem; margin-bottom: 0.5rem;">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <div class="drag-upload-text" style="font-size: 0.8rem;">Drop Photo</div>
                            <button type="button" class="drag-upload-btn" style="padding: 0.2rem 0.8rem; font-size: 0.7rem;">Browse</button>
                            <input type="file" name="image" id="reviewImageInput" style="display: none;">
                        </div>

                        <div id="reviewPreview" class="drag-preview-container" style="flex-direction: column; text-align: center; border-radius: 50%; width: 150px; height: 150px; margin-top: -150px; position: relative; z-index: 5; pointer-events: none; padding: 0; overflow: hidden; border: none; background: white;">
                             <img src="" class="drag-preview-image" style="width: 100%; height: 100%; border-radius: 50%;">
                             <div class="drag-preview-info" style="display: none;"></div>
                        </div>

                        <?php if ($review && $review['image']): ?>
                            <div style="margin-top: 1rem; text-align: center;">
                                <img src="../assets/uploads/<?= $review['image'] ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid var(--admin-primary);">
                                <p style="font-size: 0.75rem; color: var(--admin-text-light); margin-top: 0.3rem;">Current photo</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>Comment</label>
            <textarea name="comment" rows="4" placeholder="What did the customer say?" required><?= $review['comment'] ?? '' ?></textarea>
        </div>

        <div style="margin-top: 2rem; border-top: 1px solid #f1f5f9; padding-top: 2rem;">
            <button type="submit" class="btn btn-primary" style="padding: 0.8rem 2.5rem;">Save Feedback</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        initDragAndDrop('reviewDragBox', 'reviewImageInput', 'reviewPreview');
    });
</script>

<?php require_once 'footer.php'; ?>
