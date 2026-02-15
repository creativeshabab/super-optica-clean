<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

header("Content-Type: application/xml; charset=utf-8");

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

$base_url = getBaseURL();

// Static Pages
$static_pages = ['index.php', 'shop.php', 'services.php', 'gallery.php', 'reviews.php', 'blog.php', 'contact.php'];
foreach ($static_pages as $page) {
    echo '<url>';
    echo '<loc>' . $base_url . $page . '</loc>';
    echo '<changefreq>weekly</changefreq>';
    echo '<priority>0.8</priority>';
    echo '</url>';
}

// Products
$products = $pdo->query("SELECT id, slug FROM products")->fetchAll();
foreach ($products as $p) {
    echo '<url>';
    echo '<loc>' . getProductURL($p) . '</loc>';
    echo '<changefreq>monthly</changefreq>';
    echo '<priority>0.7</priority>';
    echo '</url>';
}

// Blog Posts
$posts = $pdo->query("SELECT id FROM posts")->fetchAll();
foreach ($posts as $post) {
    echo '<url>';
    echo '<loc>' . $base_url . 'blog.php?id=' . $post['id'] . '</loc>';
    echo '<changefreq>monthly</changefreq>';
    echo '<priority>0.6</priority>';
    echo '</url>';
}

echo '</urlset>';
?>
