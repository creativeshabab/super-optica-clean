<?php 
require_once 'config/db.php';
require_once 'includes/functions.php';

if (isset($_GET['id'])) {
    // Single Post
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $post = $stmt->fetch();

    if (!$post) redirect('blog.php');
    
    $page_title = $post['title'];
    $page_desc = substr(strip_tags($post['content']), 0, 160);
} else {
    $page_title = __('our_eyewear_blog');
}

require_once 'includes/header.php';
?>
<link rel="stylesheet" href="<?= getBaseURL() ?>assets/css/blog-page.css?v=1.0">
<?php

if (isset($_GET['id'])) {
    // Single Post
?>
    <!-- Page Hero for Single Post -->
    <section class="page-hero">
        <div class="container">
            <?php renderBreadcrumbs([__('blog') => 'blog.php', $post['title'] => null]); ?>
        </div>
    </section>

    <div class="container section-padding">
        <article class="blog-detail-container">
            <!-- Header -->
            <div class="blog-header">
                <span class="blog-date-badge">
                    <?= date('F d, Y', strtotime($post['created_at'])) ?>
                </span>
                <h1 class="blog-title"><?= htmlspecialchars($post['title']) ?></h1>
            </div>

            <!-- Featured Image -->
            <?php if($post['image']): ?>
                <div class="blog-featured-image">
                    <img src="assets/uploads/<?= $post['image'] ?>" alt="<?= htmlspecialchars($post['title']) ?>">
                </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="blog-content">
                <?php if (!empty($post['show_raw_html'])): ?>
                    <?= $post['content'] ?>
                <?php else: ?>
                    <?= sanitizeAllowedHtml($post['content']) ?>
                <?php endif; ?>
            </div>

            <!-- Footer / Navigation -->
            <div class="blog-footer">
                <a href="blog.php" class="btn-back-to-blog">
                    <i class="fa-solid fa-arrow-left"></i> <?= __('back_to_blog') ?>
                </a>
            </div>
        </article>
    </div>
    <?php
} else {
    // List Posts
    $posts = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll();
    ?>
    <!-- Page Hero for Blog Index -->
    <section class="page-hero">
        <div class="container">
            <?php renderBreadcrumbs([__('blog') => null]); ?>
        </div>
    </section>

    <div class="container section-padding">

        <div class="blog-list-header">
            <div class="blog-list-title-section">
                 <span class="blog-section-label"><?= __('knowledge_hub') ?></span>
                 <h1 class="blog-list-title">Our <span class="highlight">Blog</span></h1>
            </div>
        </div>
        
        <div class="blog-grid">
            <?php foreach ($posts as $p): ?>
                <?php renderBlogCard($p); ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
?>

<?php require_once 'includes/footer.php'; ?>
