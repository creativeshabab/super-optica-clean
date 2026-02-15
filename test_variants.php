<!DOCTYPE html>
<html>
<head>
    <title>Variant Test Page</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .test-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .test-section h2 { margin-top: 0; color: #333; }
        .success { color: #22c55e; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
        pre { background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .gallery { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .gallery img { width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 2px solid #ddd; }
        .variant-btn { padding: 10px 20px; margin: 5px; border: 2px solid #ddd; background: white; cursor: pointer; border-radius: 4px; }
        .variant-btn.active { border-color: #3b82f6; background: #eff6ff; }
    </style>
</head>
<body>
    <h1>🔍 Variant Functionality Test</h1>
    
    <?php
    require_once 'config/db.php';
    
    // Test 1: Check table existence
    echo '<div class="test-section">';
    echo '<h2>Test 1: Database Tables</h2>';
    
    $tables_to_check = ['products', 'product_variants', 'product_variant_images'];
    foreach ($tables_to_check as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->fetch();
        if ($exists) {
            echo "<div class='success'>✓ Table '$table' exists</div>";
        } else {
            echo "<div class='error'>✗ Table '$table' MISSING</div>";
        }
    }
    echo '</div>';
    
    // Test 2: Find a product with variants
    echo '<div class="test-section">';
    echo '<h2>Test 2: Sample Product with Variants</h2>';
    
    $stmt = $pdo->query("
        SELECT p.*, COUNT(pv.id) as variant_count 
        FROM products p 
        LEFT JOIN product_variants pv ON p.id = pv.product_id 
        GROUP BY p.id 
        HAVING variant_count > 0 
        LIMIT 1
    ");
    $product = $stmt->fetch();
    
    if ($product) {
        echo "<div class='success'>✓ Found product: {$product['name']} (ID: {$product['id']})</div>";
        echo "<div>Variant count: {$product['variant_count']}</div>";
        
        // Get variants
        $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
        $stmt->execute([$product['id']]);
        $variants = $stmt->fetchAll();
        
        echo '<h3>Variants:</h3>';
        foreach ($variants as $v) {
            echo "<div style='margin: 10px 0; padding: 10px; background: #f8f8f8; border-radius: 4px;'>";
            echo "<strong>{$v['color_name']}</strong> (ID: {$v['id']})<br>";
            echo "Color Code: <span style='display:inline-block; width:20px; height:20px; background:{$v['color_code']}; border:1px solid #ccc; vertical-align:middle;'></span> {$v['color_code']}<br>";
            echo "Main Image: {$v['image_path']}<br>";
            
            // Check variant images
            $stmt = $pdo->prepare("SELECT * FROM product_variant_images WHERE product_variant_id = ?");
            $stmt->execute([$v['id']]);
            $v_images = $stmt->fetchAll();
            
            if (count($v_images) > 0) {
                echo "<div class='success'>✓ Gallery images: " . count($v_images) . "</div>";
                echo "<div class='gallery'>";
                foreach ($v_images as $img) {
                    echo "<img src='assets/uploads/{$img['image_path']}' alt='Variant Image'>";
                }
                echo "</div>";
            } else {
                echo "<div class='warning'>⚠ No gallery images (only main image available)</div>";
            }
            echo "</div>";
        }
        
        // Test 3: JavaScript Test
        echo '<h3>Test 3: Interactive Variant Switching</h3>';
        echo '<div id="test-area">';
        echo '<div id="main-image-container" style="margin: 20px 0;">';
        echo '<img id="testMainImage" src="assets/uploads/' . $product['image'] . '" style="max-width: 400px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
        echo '</div>';
        
        echo '<div id="variant-buttons">';
        foreach ($variants as $idx => $v) {
            $v_images_json = [];
            $stmt = $pdo->prepare("SELECT image_path FROM product_variant_images WHERE product_variant_id = ?");
            $stmt->execute([$v['id']]);
            $v_images_json = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $gallery_json = htmlspecialchars(json_encode($v_images_json));
            $main_img = $v['image_path'] ? 'assets/uploads/' . $v['image_path'] : '';
            
            echo "<button class='variant-btn' onclick='switchVariant(this)' 
                    data-main-image='$main_img' 
                    data-gallery='$gallery_json'
                    data-name='{$v['color_name']}'>
                    {$v['color_name']}
                  </button>";
        }
        echo '</div>';
        
        echo '<div id="gallery-preview" class="gallery"></div>';
        echo '<div id="debug-output" style="margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 4px;"></div>';
        echo '</div>';
        
    } else {
        echo "<div class='error'>✗ No products with variants found</div>";
        echo "<div>Please add variants to a product in the admin panel first.</div>";
    }
    
    echo '</div>';
    ?>
    
    <script>
    function switchVariant(btn) {
        // Remove active class from all buttons
        document.querySelectorAll('.variant-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        
        const mainImage = btn.getAttribute('data-main-image');
        const galleryData = btn.getAttribute('data-gallery');
        const variantName = btn.getAttribute('data-name');
        
        const debugOutput = document.getElementById('debug-output');
        debugOutput.innerHTML = '<strong>Debug Info:</strong><br>';
        debugOutput.innerHTML += 'Variant: ' + variantName + '<br>';
        debugOutput.innerHTML += 'Main Image: ' + mainImage + '<br>';
        debugOutput.innerHTML += 'Gallery Data: ' + galleryData + '<br>';
        
        // Update main image
        if (mainImage) {
            document.getElementById('testMainImage').src = mainImage;
            debugOutput.innerHTML += '<span class="success">✓ Main image updated</span><br>';
        }
        
        // Parse and display gallery
        try {
            const gallery = JSON.parse(galleryData);
            debugOutput.innerHTML += 'Gallery images count: ' + gallery.length + '<br>';
            
            const galleryContainer = document.getElementById('gallery-preview');
            galleryContainer.innerHTML = '';
            
            if (gallery.length > 0) {
                gallery.forEach(img => {
                    const imgEl = document.createElement('img');
                    imgEl.src = 'assets/uploads/' + img;
                    galleryContainer.appendChild(imgEl);
                });
                debugOutput.innerHTML += '<span class="success">✓ Gallery rendered</span><br>';
            } else {
                galleryContainer.innerHTML = '<div class="warning">No gallery images for this variant</div>';
                debugOutput.innerHTML += '<span class="warning">⚠ No gallery images</span><br>';
            }
        } catch (e) {
            debugOutput.innerHTML += '<span class="error">✗ Error parsing gallery: ' + e.message + '</span><br>';
        }
    }
    
    // Auto-select first variant
    window.addEventListener('DOMContentLoaded', () => {
        const firstBtn = document.querySelector('.variant-btn');
        if (firstBtn) {
            firstBtn.click();
        }
    });
    </script>
</body>
</html>
