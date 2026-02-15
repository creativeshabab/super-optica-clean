<?php
   require_once 'config/db.php';
   require_once 'includes/functions.php';
   
   if (!isset($_GET['id']) && !isset($_GET['slug'])) {
       redirect('shop.php');
   }
   
   if (isset($_GET['slug'])) {
       $stmt = $pdo->prepare("SELECT * FROM products WHERE slug = ?");
       $stmt->execute([$_GET['slug']]);
   } else {
       $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
       $stmt->execute([$_GET['id']]);
   }
   
   $product = $stmt->fetch();
   
   if (!$product) {
       // If slug search failed, maybe it was an ID in the slug parameter (legacy/direct)
       if (isset($_GET['slug']) && is_numeric($_GET['slug'])) {
           $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
           $stmt->execute([$_GET['slug']]);
           $product = $stmt->fetch();
       }
       
       if (!$product) {
           redirect('shop.php');
       }
   }
   
   // Fetch Gallery Images
   $gallery_stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC");
   $gallery_stmt->execute([$product['id']]);
   $gallery_images = $gallery_stmt->fetchAll();
   
   // Fetch Color Variants with their Images
   $variants_stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY id ASC");
   $variants_stmt->execute([$product['id']]);
   $variants = $variants_stmt->fetchAll();
   
   // Attach images to variants
   foreach ($variants as &$v) {
       $v_img_stmt = $pdo->prepare("SELECT image_path FROM product_variant_images WHERE product_variant_id = ? ORDER BY id ASC");
       $v_img_stmt->execute([$v['id']]);
       $v['images'] = $v_img_stmt->fetchAll(PDO::FETCH_COLUMN);
   }
   unset($v); // Break reference

   // Fetch Related Products
   $related_stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 4");
   $related_stmt->execute([$product['category_id'], $product['id']]);
   $related_products = $related_stmt->fetchAll();
   
   // SEO Overrides
   $page_title = $product['name'] . " | Buy Eyewear Online";
   $page_desc = substr(strip_tags($product['description']), 0, 160);
   
   require_once 'includes/header.php';
   ?>
   <link rel="stylesheet" href="<?= getBaseURL() ?>assets/css/product-page.css?v=1.0">
   <?php
   
   // Handle Add to Cart
   if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_to_cart']) || isset($_POST['buy_now']))) {
       $qty = (int)$_POST['quantity'];
       if ($qty < 1) {
           $qty = 1;
       }
   
       $id = $product['id'];
   
       if (!isset($_SESSION['cart'])) {
           $_SESSION['cart'] = [];
       }
   
       if (isset($_SESSION['cart'][$id])) {
           $_SESSION['cart'][$id]['quantity'] += $qty;
       } else {
           $_SESSION['cart'][$id] = [
               'id' => $product['id'],
               'name' => $product['name'],
               'price' => $product['price'],
               'image' => $product['image'],
               'quantity' => $qty
           ];
       }
   
       if (isset($_POST['buy_now'])) {
           redirect('checkout.php');
       }
   
       setFlash('success', __('added_to_cart'));
       redirect('cart.php');
   }
   ?>
<!-- Page Hero for Product -->
<section class="page-hero">
   <div class="container">
      <?php 
      // Dynamic Breadcrumbs
      $breadcrumbs = [__('home') => 'index.php'];
      if ($product['category_id']) {
          $catPath = getCategoryHierarchy($product['category_id']);
          foreach ($catPath as $cat) {
              $breadcrumbs[$cat['name']] = 'shop.php?category=' . $cat['slug'];
          }
      }
      $breadcrumbs[$product['name']] = null;
      renderBreadcrumbs($breadcrumbs); 
      ?>
   </div>
</section>
<section class="web-wrapper section-padding">
    <div class="container">
   <form method="POST" action="">
    <div class="product-wrapper">
      <!-- Image & Gallery -->
      <div class="product-gallery">
         <div class="main-image-container">
            <?php if ($product['image']): ?>
            <img id="mainProductImage" src="<?= getBaseURL() ?>assets/uploads/<?= $product['image'] ?>" class="main-product-image" alt="<?= htmlspecialchars($product['name']) ?>">
            <div class="image-zoom-overlay"></div>
            
            <?php if (!empty($gallery_images)): ?>
            <div class="slider-controls">
                <button type="button" class="slider-arrow prev" onclick="changeSlide(-1)"><i class="fa-solid fa-chevron-left"></i></button>
                <button type="button" class="slider-arrow next" onclick="changeSlide(1)"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
            <?php endif; ?>

            <?php else: ?>
            <div class="no-image-placeholder">
                <i class="fa-solid fa-image"></i>
            </div>
            <?php endif; ?>
         </div>
         
         <?php if (!empty($gallery_images)): ?>
         <div class="thumbnail-grid">
            <!-- Main Image Thumbnail -->
            <div class="thumbnail active" onclick="switchImage('<?= getBaseURL() ?>assets/uploads/<?= $product['image'] ?>', this)">
               <img src="<?= getBaseURL() ?>assets/uploads/<?= $product['image'] ?>" alt="Main Image">
            </div>
            <?php foreach ($gallery_images as $img): ?>
            <div class="thumbnail" onclick="switchImage('<?= getBaseURL() ?>assets/uploads/<?= $img['image_path'] ?>', this)">
               <img src="<?= getBaseURL() ?>assets/uploads/<?= $img['image_path'] ?>" alt="Gallery Image">
            </div>
            <?php endforeach; ?>
         </div>
         <?php endif; ?>
      </div>
      <!-- Details -->
      <div class="product-details">
         <div class="product-header">
            <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="wishlist-icon"><i class="fa-regular fa-heart"></i></div>
         </div>
         
         <!-- Price Section -->
         <div class="product-price-section">
            <span class="product-price">₹<?= number_format($product['price'], 0) ?></span>
            <?php if ($product['actual_price'] && $product['actual_price'] > $product['price']):
               $discount = round((($product['actual_price'] - $product['price']) / $product['actual_price']) * 100);
               ?>
            <span class="product-mrp">₹<?= number_format($product['actual_price'], 0) ?></span>
            <span class="product-discount">(<?= $discount ?>% off)</span>
            <?php endif; ?>
            <div class="tax-info">Inclusive of all taxes</div>
         </div>

         <!-- Offers Box -->
         <?php
         // Fetch all active prepaid-only offers
         $offer_stmt = $pdo->prepare("SELECT * FROM coupons WHERE is_active = 1 AND is_prepaid_only = 1 AND (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW()) ORDER BY id DESC");
         $offer_stmt->execute();
         $active_offers = $offer_stmt->fetchAll();
         
         if (!empty($active_offers)):
             foreach ($active_offers as $offer):
         ?>
         <div class="offer-box">
             <i class="fa-solid fa-tags"></i>
             <div class="offer-details">
                 <div class="offer-title"><?= htmlspecialchars($offer['description'] ?: "Special Prepaid Offer") ?></div>
                 <div class="offer-code">Use coupon code: <strong><?= htmlspecialchars($offer['code']) ?></strong></div>
             </div>
         </div>
         <?php 
             endforeach;
         else: 
         ?>
         <div class="offer-box green">
             <i class="fa-solid fa-tags"></i>
             <div class="offer-details">
                 <div class="offer-title">Flat 10% Off on Prepaid Orders</div>
                 <div class="offer-code">Use coupon code: <strong>SUPER10</strong></div>
             </div>
         </div>
         <?php endif; ?>
         
         <?php if (!empty($variants)): ?>
         <div class="product-variants mb-6">
            <h4 class="mb-3 font-bold text-accent"><?= __('Choose Color') ?>: <span id="selectedVariantName" class="text-primary font-normal text-sm ml-2"></span></h4>
            <div class="color-swatches flex flex-wrap gap-3">
               <?php foreach ($variants as $v): 
                   $v_image = $v['image_path'] ? getBaseURL() . 'assets/uploads/' . $v['image_path'] : '';
                   $v_gallery = !empty($v['images']) ? htmlspecialchars(json_encode($v['images'])) : '';
               ?>
               <label class="swatch-container relative cursor-pointer group" title="<?= htmlspecialchars($v['color_name']) ?>">
               <input type="radio" name="selected_color" value="<?= htmlspecialchars($v['color_name']) ?>" class="peer sr-only" data-image="<?= $v_image ?>" data-gallery="<?= $v_gallery ?>">
               <span class="swatch block w-8 h-8 rounded-full border border-gray-200 ring-2 ring-transparent peer-checked:ring-primary transition-all color-swatch-item" data-color="<?= $v['color_code'] ?>"></span>
               <span class="color-tooltip absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-black text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none"><?= htmlspecialchars($v['color_name']) ?></span>
               </label>
               <?php endforeach; ?>
            </div>
         </div>
         <?php endif; ?>

         <!-- Frame Dimensions Visuals -->
         <?php 
            $specs = !empty($product['frame_specs']) ? json_decode($product['frame_specs'], true) : [];
            if (!empty($specs)):
         ?>
         <div class="mb-6">
            <label class="text-sm font-bold mb-2 block">Frame Dimensions</label>
            <div class="spec-grid grid grid-cols-3 gap-4 bg-gray-50 p-4 rounded-xl text-center border border-gray-100">
                <?php if (!empty($specs['lens_width'])): ?>
                <div class="spec-item">
                    <i class="fa-solid fa-glasses text-gray-400 mb-1"></i>
                    <div class="font-bold text-accent"><?= $specs['lens_width'] ?> mm</div>
                    <div class="text-xs text-gray-500">Lens Width</div>
                </div>
                <?php endif; ?>
                <?php if (!empty($specs['bridge_width'])): ?>
                <div class="spec-item">
                    <i class="fa-solid fa-bridge text-gray-400 mb-1"></i>
                    <div class="font-bold text-accent"><?= $specs['bridge_width'] ?> mm</div>
                    <div class="text-xs text-gray-500">Bridge</div>
                </div>
                <?php endif; ?>
                <?php if (!empty($specs['temple_length'])): ?>
                <div class="spec-item">
                    <i class="fa-solid fa-ruler-horizontal text-gray-400 mb-1"></i>
                    <div class="font-bold text-accent"><?= $specs['temple_length'] ?> mm</div>
                    <div class="text-xs text-gray-500">Temple</div>
                </div>
                <?php endif; ?>
            </div>
         </div>
         <?php endif; ?>

         <!-- Trust Badges -->
         <div class="trust-badges">
             <div class="trust-badge">
                 <div class="trust-badge-icon green">
                     <i class="fa-solid fa-certificate"></i>
                 </div>
                 <div class="trust-badge-text">100% Authentic</div>
             </div>
             <div class="trust-badge">
                 <div class="trust-badge-icon blue">
                     <i class="fa-solid fa-shield-halved"></i>
                 </div>
                 <div class="trust-badge-text">Super Trust</div>
             </div>
         </div>

         <!-- Action Buttons -->
         <?php 
            $is_in_stock = true;
            if ($product['stock_quantity'] !== null && $product['stock_quantity'] == 0) {
                $is_in_stock = false;
            }
         ?>
         <?php if ($is_in_stock): ?>
         <div class="product-actions">
             <div class="action-buttons-row">
                 <button type="submit" name="buy_now" class="btn-buy-now">
                     <i class="fa-solid fa-bolt"></i> <?= __('buy_now') ?>
                 </button>
                 <button type="submit" name="add_to_cart" class="btn-add-cart">
                     <i class="fa-solid fa-cart-shopping"></i> Add to Cart
                 </button>
                 <?php 
                    // Build WhatsApp message with product details
                    $price = '₹' . number_format($product['price'], 2);
                    $whatsappMessage = "Hi, I'm interested in this product:\n\n" . 
                                      "Product: " . $product['name'] . "\n" .
                                      "Price: " . $price . "\n\n" .
                                      "Please share more details.";
                 ?>
                 <a href="https://wa.me/919523798222?text=<?= urlencode($whatsappMessage) ?>" target="_blank" class="btn-whatsapp" title="Contact us on WhatsApp">
                     <i class="fa-brands fa-whatsapp"></i>
                 </a>
                 <input type="hidden" name="quantity" value="1">
             </div>
         </div>
         <?php else: ?>
         <button type="button" class="btn-disabled" disabled>
             <i class="fa-solid fa-ban"></i> Out of Stock
         </button>
         <?php endif; ?>

         <!-- Description -->
         <div class="mb-6">
            <label class="text-sm font-bold mb-2 block">Description</label>
            <div class="product-description text-gray-500 text-sm leading-relaxed">
                <?php if (!empty($product['show_raw_html'])): ?>
                <pre class="code-view whitespace-pre-wrap font-mono text-xs bg-gray-100 p-2 rounded"><?= htmlspecialchars($product['description']) ?></pre>
                <?php else: ?>
                <?= sanitizeAllowedHtml($product['description']) ?>
                <?php endif; ?>
            </div>
         </div>


            <!-- Product Information Section -->
            <?php if (!empty($product['key_features'])): ?>
             <div class="product-info-section mt-6 border-t border-gray-200 pt-4">
                 <div class="product-info-header font-bold text-gray-800 flex justify-between items-center cursor-pointer mb-3 hover:text-primary transition-colors" onclick="toggleProductInfo()">
                     <span class="text-sm">Product Information</span>
                     <i id="productInfoArrow" class="fa-solid fa-chevron-down text-gray-400 text-xs transition-transform duration-300"></i>
                 </div>
                 <div id="productInfoContent" class="product-info-content" style="max-height: 0; overflow: hidden; transition: max-height 0.4s ease-out;">
                     <ul class="feature-list space-y-2 pb-2">
                        <?php 
                           $features = explode("\n", $product['key_features']);
                           foreach ($features as $f): 
                               if(trim($f) == '') continue;
                        ?>
                        <li class="flex items-start gap-2 text-sm text-gray-600">
                           <span class="w-1.5 h-1.5 rounded-full bg-primary mt-1.5 flex-shrink-0"></span>
                           <span><?= htmlspecialchars(trim($f)) ?></span>
                        </li>
                        <?php endforeach; ?>
                     </ul>
                 </div>
             </div>
             <?php endif; ?>


      </div>
    </div>
    </form>
 </div>
 
 <!-- Related Products Section -->
 <?php if (!empty($related_products)): ?>
 <div class="container section-divider-top">
     <h3 class="section-title text-center mb-4"><span class="text-primary">Related</span> Products</h3>
     <div class="dynamic-product-grid">
         <?php foreach ($related_products as $related): ?>
             <?php renderProductCard($related); ?>
         <?php endforeach; ?>
     </div>
 </div>
 <?php endif; ?>
 
 </section>
 <!-- JSON-LD Product Schema -->
 <script type="application/ld+json">
    {
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": "<?= htmlspecialchars($product['name']) ?>",
        "image": [
            "<?= getBaseURL() ?>assets/uploads/<?= $product['image'] ?>"
            <?php foreach($gallery_images as $img): ?>, "<?= getBaseURL() ?>assets/uploads/<?= $img['image_path'] ?>"<?php endforeach; ?>
        ],
        "description": "<?= htmlspecialchars(strip_tags($product['description'])) ?>",
        "sku": "OPT-<?= $product['id'] ?>",
        "brand": {
            "@type": "Brand",
            "name": "Super Optical"
        },
        "offers": {
            "@type": "Offer",
            "url": "<?= getBaseURL() ?>product.php?id=<?= $product['id'] ?>",
            "priceCurrency": "INR",
            "price": "<?= $product['price'] ?>",
            "itemCondition": "https://schema.org/NewCondition",
            "availability": "https://schema.org/InStock"
        }
    }
 </script>
 <script>
    // --- Image Slider Logic ---
    let currentSlideIndex = 0;
    const mainImage = document.getElementById('mainProductImage');
    // We use a getter for thumbnails because they are re-rendered dynamically
    const getThumbnails = () => document.querySelectorAll('.thumbnail');
    
    // Initial Gallery (PHP generated)
    let galleryImages = [
        "<?= getBaseURL() ?>assets/uploads/<?= $product['image'] ?>",
        <?php foreach($gallery_images as $img): ?>"<?= getBaseURL() ?>assets/uploads/<?= $img['image_path'] ?>",<?php endforeach; ?>
    ];

    function switchImage(src, element) {
        mainImage.src = src;
        
        // Update active thumbnail
        const thumbs = getThumbnails();
        thumbs.forEach((t, index) => {
            t.classList.remove('active');
            if (t === element) {
                currentSlideIndex = index;
                t.classList.add('active');
                if (t.scrollIntoView) {
                    t.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                }
            }
        });
    }

    function changeSlide(direction) {
        currentSlideIndex += direction;
        
        // Loop interactions
        if (currentSlideIndex >= galleryImages.length) currentSlideIndex = 0;
        if (currentSlideIndex < 0) currentSlideIndex = galleryImages.length - 1;

        // Trigger switch
        const thumbs = getThumbnails();
        const targetThumb = thumbs[currentSlideIndex];
        
        // If we have thumbnails, click the corresponding one to switch
        if (targetThumb) {
            // We pass the element to switchImage to handle active state
            switchImage(galleryImages[currentSlideIndex], targetThumb);
        } else {
            // Fallback if no thumbnails (rare)
            mainImage.src = galleryImages[currentSlideIndex];
        }
    }
    
    // --- Image Zoom Logic (Fixed) ---
    const zoomContainer = document.querySelector('.main-image-container');
    if (zoomContainer) {
        const img = document.getElementById('mainProductImage');
    
        zoomContainer.addEventListener('mousemove', (e) => {
            const { left, top, width, height } = zoomContainer.getBoundingClientRect();
            const x = ((e.clientX - left) / width) * 100;
            const y = ((e.clientY - top) / height) * 100;
            
            // Boundary checks
            const clampX = Math.max(0, Math.min(100, x));
            const clampY = Math.max(0, Math.min(100, y));
    
            img.style.transformOrigin = `${clampX}% ${clampY}%`;
            img.style.transform = 'scale(2)';
        });
    
        zoomContainer.addEventListener('mouseleave', () => {
            img.style.transform = 'scale(1)';
            setTimeout(() => { img.style.transformOrigin = 'center center'; }, 200);
        });
    }

    // --- Variant Logic ---
    // Swatch Color Handler
    document.querySelectorAll('.color-swatch-item').forEach(el => {
        if (el.dataset.color) {
            el.style.backgroundColor = el.dataset.color;
        }
    });

    // Variant Image Switcher & Label Update
    const variantInputs = document.querySelectorAll('input[name="selected_color"]');
    const variantLabel = document.getElementById('selectedVariantName');
    const thumbnailGrid = document.querySelector('.thumbnail-grid');

    variantInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Update Label
            if (variantLabel) variantLabel.textContent = this.value;

            // Update Gallery
            const rawGallery = this.getAttribute('data-gallery');
            const baseUrl = "<?= getBaseURL() ?>assets/uploads/";
            
            // Reset logic
            let newGalleryList = [];

            if (rawGallery && rawGallery !== 'null' && rawGallery !== '""') {
                try {
                    const parsed = JSON.parse(rawGallery);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        newGalleryList = parsed.map(img => baseUrl + img);
                    }
                } catch (e) {
                    console.error("Error parsing gallery data", e);
                }
            }

            // Fallback to single image if no gallery (or add single image to gallery)
            if (newGalleryList.length === 0) {
                 const singleImage = this.getAttribute('data-image');
                 if (singleImage) {
                     newGalleryList.push(singleImage);
                 }
            }

            if (newGalleryList.length > 0) {
                // Update Global Gallery
                galleryImages = newGalleryList;
                currentSlideIndex = 0;

                // Update Main Image
                mainImage.src = galleryImages[0];

                // Re-render Thumbnails
                if (thumbnailGrid) {
                    thumbnailGrid.innerHTML = '';
                    galleryImages.forEach((src, index) => {
                        const thumb = document.createElement('div');
                        thumb.className = index === 0 ? 'thumbnail active' : 'thumbnail';
                        thumb.onclick = function() { switchImage(src, this); };
                        thumb.innerHTML = `<img src="${src}" alt="Variant Image">`;
                        thumbnailGrid.appendChild(thumb);
                    });
                }
            }
        });
    });

    // Toggle Product Information Section with smooth animation
    function toggleProductInfo() {
        const content = document.getElementById('productInfoContent');
        const arrow = document.getElementById('productInfoArrow');
        
        if (content.style.maxHeight === '0px' || content.style.maxHeight === '') {
            // Open - set to scrollHeight for smooth animation
            content.style.maxHeight = content.scrollHeight + 'px';
            arrow.style.transform = 'rotate(180deg)';
        } else {
            // Close
            content.style.maxHeight = '0px';
            arrow.style.transform = 'rotate(0deg)';
        }
    }
 </script>
 <?php require_once 'includes/footer.php'; ?>