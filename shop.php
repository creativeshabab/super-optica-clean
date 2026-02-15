<?php 
   require_once 'config/db.php';
   require_once 'includes/functions.php';
   
   $category_input = isset($_GET['category']) ? $_GET['category'] : null;
   $category_id = null;
   $current_category = null;

   if ($category_input) {
       if (is_numeric($category_input)) {
           $category_id = $category_input;
       } else {
           // Resolve slug to ID
           $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
           $stmt->execute([$category_input]);
           $current_category = $stmt->fetch();
           if ($current_category) {
               $category_id = $current_category['id'];
           }
       }
   }
   
   $search_query = isset($_GET['search']) ? trim($_GET['search']) : null;
   
   if ($search_query) {
       // Search Logic
       $stmt = $pdo->prepare("SELECT p.*, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.name LIKE ? OR p.description LIKE ? ORDER BY p.created_at DESC");
       $searchTerm = "%$search_query%";
       $stmt->execute([$searchTerm, $searchTerm]);
       $products = $stmt->fetchAll();
       
       $title = "Search Results for: " . htmlspecialchars($search_query);
       $page_title = "Search: $search_query | Super Optical";
       $page_desc = "Search results for $search_query";

   } elseif ($category_id) {
       // Get all child category IDs if this is a parent category
       $category_ids = getAllChildCategoryIds($category_id);
       
       $placeholders = str_repeat('?,', count($category_ids) - 1) . '?';
       $stmt = $pdo->prepare("SELECT p.*, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.category_id IN ($placeholders) ORDER BY p.created_at DESC");
       $stmt->execute($category_ids);
       $products = $stmt->fetchAll();
       
       // Get category name with breadcrumb
       $breadcrumb = getCategoryBreadcrumb($category_id);
       $catName = end($breadcrumb)['name'];
       $title = $catName ? $catName : __('shop');
       
       $page_title = $title . " Eyewear Collection";
       $page_desc = "Browse our premium collection of " . $title . " eyewear. Top quality frames and lenses in Begusarai.";
   } else {
       $products = $pdo->query("SELECT p.*, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC")->fetchAll();
       $title = __('all_products');
       $page_title = "Shop Premium Eyewear | All Collections";
   }
   
   $categories = getCategoriesHierarchical();
   
   require_once 'includes/header.php';
   ?>
<!-- Page Hero -->
<section class="page-hero">
   <div class="container">
      <?php 
      if (isset($search_query) && $search_query) {
          renderBreadcrumbs([__('shop') => 'shop.php', "Search: $search_query" => null]);
      } elseif (isset($category_id) && $category_id) {
          renderBreadcrumbs([__('shop') => 'shop.php', $title => null]);
      } else {
          renderBreadcrumbs([__('shop') => null]);
      }
      ?>
   </div>
</section>

<section class="web-wrapper section-padding">
    <div class="container mx-auto px-4">
    <!-- Category Filter -->
    <div class="flex flex-row justify-between items-center mb-8 gap-4">
       <div class=" text-left text-md-left m-0">
          <span class="text-primary font-bold uppercase tracking-widest text-sm"><?= __('browse_our') ?></span>
          <h2 class="page-title-responsive font-black text-gray-800 mt-2"><?= __('eyewear') ?> <span class="text-primary"><?= __('collection') ?></span></h2>
       </div>
       
       <!-- Mobile Filter Button (Icon Only) -->
       <!-- Mobile Filter Button (Icon Only - Fixed Width) -->
       <button class="btn btn-outline md:hidden w-10 h-10 flex-shrink-0 flex items-center justify-center rounded-full border-gray-200 text-gray-700 hover:border-primary hover:text-primary transition-colors shadow-sm" id="mobileFilterBtn" title="Filter Categories">
          <i class="fa-solid fa-sliders"></i>
       </button>
       
       <!-- Desktop Category Scroll -->
       <div class="category-scroll hidden md:flex gap-2 overflow-x-auto pb-2">
         <a href="shop.php" class="btn <?= !$category_id ? 'btn-primary' : 'btn-outline' ?> rounded-full whitespace-nowrap transition-colors"><?= __('all') ?></a>
         <?php 
            foreach ($categories as $cat): 
                // Only show parent categories as main filters
                if ($cat['parent_id'] === null):
            ?>
         <a href="shop.php?category=<?= $cat['slug'] ?>" class="btn <?= ($category_id == $cat['id'] || $category_input == $cat['slug']) ? 'btn-primary' : 'btn-outline' ?> rounded-full whitespace-nowrap transition-colors">
         <?= htmlspecialchars($cat['name']) ?>
         </a>
        <?php 
           endif;
           endforeach; 
           ?>
     </div>
    </div>

    <!-- Product Grid -->
    <?php if (count($products) > 0): ?>
    <div class="dynamic-product-grid-v2">
        <?php foreach ($products as $p): ?>
            <?php renderProductCard($p); ?>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
       <div class="col-span-full py-20 text-center">
           <div class="w-24 h-24 bg-primary/5 rounded-full flex items-center justify-center mx-auto mb-6">
               <i class="fa-solid fa-box-open text-4xl text-primary/40"></i>
           </div>
           <h3 class="text-2xl font-bold text-gray-800 mb-2"><?= __('no_products_found') ?></h3>
           <p class="text-gray-500 mb-8 max-w-md mx-auto"><?= __('no_products_desc') ?></p>
           <a href="shop.php" class="btn btn-primary rounded-full shadow-lg hover:shadow-xl transition-all inline-flex items-center gap-2">
               <i class="fa-solid fa-layer-group"></i> <?= __('view_all_products') ?>
           </a>
       </div>
    <?php endif; ?>
 </div>
</section>

<!-- Mobile Category Filter Modal -->
<div class="mobile-filter-modal" id="mobileFilterModal">
   <div class="mobile-filter-overlay" id="mobileFilterOverlay"></div>
   <div class="mobile-filter-content">
      <div class="mobile-filter-header">
         <h3><i class="fa-solid fa-filter"></i> <?= __('filter_by_category') ?></h3>
         <button class="mobile-filter-close" id="mobileFilterClose">
            <i class="fa-solid fa-times"></i>
         </button>
      </div>
      <div class="mobile-filter-body">
         <div class="mobile-category-grid">
            <a href="shop.php" class="mobile-category-item <?= !$category_id ? 'active' : '' ?>">
               <i class="fa-solid fa-th"></i>
               <span><?= __('all') ?></span>
            </a>
            <?php foreach ($categories as $cat): 
               if ($cat['parent_id'] === null): ?>
            <a href="shop.php?category=<?= $cat['slug'] ?>" class="mobile-category-item <?= ($category_id == $cat['id'] || $category_input == $cat['slug']) ? 'active' : '' ?>">
               <i class="fa-solid fa-glasses"></i>
               <span><?= htmlspecialchars($cat['name']) ?></span>
            </a>
            <?php endif; endforeach; ?>
         </div>
      </div>
   </div>
</div>

<script>
// Mobile Category Filter Modal
document.addEventListener('DOMContentLoaded', function() {
    const mobileFilterBtn = document.getElementById('mobileFilterBtn');
    const mobileFilterModal = document.getElementById('mobileFilterModal');
    const mobileFilterOverlay = document.getElementById('mobileFilterOverlay');
    const mobileFilterClose = document.getElementById('mobileFilterClose');

    if (mobileFilterBtn && mobileFilterModal) {
        mobileFilterBtn.addEventListener('click', function() {
            mobileFilterModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        });

        const closeFunc = function() {
            mobileFilterModal.classList.remove('active');
            document.body.style.overflow = '';
        };

        if (mobileFilterOverlay) mobileFilterOverlay.addEventListener('click', closeFunc);
        if (mobileFilterClose) mobileFilterClose.addEventListener('click', closeFunc);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>