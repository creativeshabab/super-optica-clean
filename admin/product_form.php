<?php require_once 'header.php'; ?>

<?php
$product = null;
$error = null;

// Ensure 'show_raw_html' column exists on products (safe, one-time)
$prodCol = $pdo->query("SHOW COLUMNS FROM products LIKE 'show_raw_html'")->fetch();
if (!$prodCol) {
    try {
        $pdo->exec("ALTER TABLE products ADD COLUMN show_raw_html TINYINT(1) DEFAULT 0");
    } catch (Exception $e) { /* ignore */ }
}
$hasShowRawProductColumn = (bool)$pdo->query("SHOW COLUMNS FROM products LIKE 'show_raw_html'")->fetch();

// Fetch Categories
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

// Handle Edit Fetch
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $product = $stmt->fetch();

    // Fetch Gallery Images
    $gallery = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ?");
    $gallery->execute([$_GET['id']]);
    $product_gallery = $gallery->fetchAll();

    // Fetch Variants
    $variants = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
    $variants->execute([$_GET['id']]);
    $product_variants = $variants->fetchAll();
}

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $description = $_POST['description'];
    $show_raw_html = isset($_POST['show_raw_html']) ? 1 : 0;
    $price = $_POST['price'];
    $actual_price = $_POST['actual_price'] ?: null;
    $category_id = $_POST['category_id'];
    $stock_quantity = isset($_POST['stock_quantity']) && $_POST['stock_quantity'] !== '' ? (int)$_POST['stock_quantity'] : null;
    $low_stock_threshold = isset($_POST['low_stock_threshold']) && $_POST['low_stock_threshold'] !== '' ? (int)$_POST['low_stock_threshold'] : 10;
    $image = $product['image'] ?? null;
    $key_features = $_POST['key_features'] ?? '';
    
    // Handle Specs JSON
    $specs = [];
    if (isset($_POST['specs'])) {
        foreach ($_POST['specs'] as $k => $v) {
            if (!empty($v)) $specs[$k] = $v;
        }
    }
    $frame_specs = !empty($specs) ? json_encode($specs) : null;

    // Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploaded = optimizeUpload($_FILES['image'], '../assets/uploads/');
        if ($uploaded) $image = $uploaded;
    }

    // Duplicate Check
    if ($product) {
        $checkStmt = $pdo->prepare("SELECT id FROM products WHERE (name = ? OR slug = ?) AND id != ?");
        $checkStmt->execute([$name, $slug, $product['id']]);
    } else {
        $checkStmt = $pdo->prepare("SELECT id FROM products WHERE name = ? OR slug = ?");
        $checkStmt->execute([$name, $slug]);
    }
    
    if ($checkStmt->fetch()) {
        $error = __('duplicate_product_error', 'A product with this name already exists.');
    } else {
        try {
            if ($product) {
                // Update
                $sql = "UPDATE products SET name=?, slug=?, description=?, price=?, actual_price=?, category_id=?, image=?, stock_quantity=?, low_stock_threshold=?, key_features=?, frame_specs=?";
                $params = [$name, $slug, $description, $price, $actual_price, $category_id, $image, $stock_quantity, $low_stock_threshold, $key_features, $frame_specs];
                
                if ($hasShowRawProductColumn) {
                    $sql .= ", show_raw_html=?";
                    $params[] = $show_raw_html;
                }
                $sql .= " WHERE id=?";
                $params[] = $product['id'];
                
                $pdo->prepare($sql)->execute($params);
                $product_id = $product['id'];
                setFlash('success', __('product_updated_success', 'Product updated successfully'));
            } else {
                // Create
                $cols = "name, slug, description, price, actual_price, category_id, image, stock_quantity, low_stock_threshold, key_features, frame_specs";
                $vals = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
                $params = [$name, $slug, $description, $price, $actual_price, $category_id, $image, $stock_quantity, $low_stock_threshold, $key_features, $frame_specs];
                
                if ($hasShowRawProductColumn) {
                    $cols .= ", show_raw_html";
                    $vals .= ", ?";
                    $params[] = $show_raw_html;
                }
                
                $pdo->prepare("INSERT INTO products ($cols) VALUES ($vals)")->execute($params);
                $product_id = $pdo->lastInsertId();
                setFlash('success', __('product_created_success', 'Product created successfully'));
            }

            // Handle Gallery
            if (isset($_FILES['gallery']) && !empty($_FILES['gallery']['name'][0])) {
                foreach ($_FILES['gallery']['name'] as $key => $val) {
                    if ($_FILES['gallery']['error'][$key] === 0) {
                        $fileItem = [
                            'name' => $_FILES['gallery']['name'][$key],
                            'type' => $_FILES['gallery']['type'][$key],
                            'tmp_name' => $_FILES['gallery']['tmp_name'][$key],
                            'error' => $_FILES['gallery']['error'][$key],
                            'size' => $_FILES['gallery']['size'][$key]
                        ];
                        $filename = optimizeUpload($fileItem, '../assets/uploads/', uniqid() . '_gal');
                        if ($filename) {
                            $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)")->execute([$product_id, $filename]);
                        }
                    }
                }
            }

            // Handle Variants (Refactored for Multi-Image)
            if (isset($_POST['variants'])) {
                if ($product) $pdo->prepare("DELETE FROM product_variants WHERE product_id = ?")->execute([$product_id]);
                
                foreach ($_POST['variants'] as $idx => $v_data) {
                    $v_name = $v_data['name'];
                    $v_code = $v_data['code'];
                    $main_variant_image = null;
                    
                    // Prevent empty rows
                    if (empty($v_name) || empty($v_code)) continue;

                    // 1. Handle Existing Logic (Main Image for thumbnail)
                    if (isset($v_data['existing_main_image'])) {
                        $main_variant_image = $v_data['existing_main_image'];
                    }

                    // 2. Handle File Uploads
                    // Note: $_FILES['variants'] structure is convoluted in PHP: ['name'][$idx]['images'][$fileIdx]
                    $new_images = [];
                    if (isset($_FILES['variants']['name'][$idx]['images'])) {
                        $file_names = $_FILES['variants']['name'][$idx]['images'];
                        foreach ($file_names as $fileIdx => $fName) {
                            if ($_FILES['variants']['error'][$idx]['images'][$fileIdx] === 0) {
                                $fileItem = [
                                    'name' => $_FILES['variants']['name'][$idx]['images'][$fileIdx],
                                    'type' => $_FILES['variants']['type'][$idx]['images'][$fileIdx],
                                    'tmp_name' => $_FILES['variants']['tmp_name'][$idx]['images'][$fileIdx],
                                    'error' => $_FILES['variants']['error'][$idx]['images'][$fileIdx],
                                    'size' => $_FILES['variants']['size'][$idx]['images'][$fileIdx]
                                ];
                                $uploadName = optimizeUpload($fileItem, '../assets/uploads/', uniqid() . '_vimg');
                                if ($uploadName) {
                                    $new_images[] = $uploadName;
                                }
                            }
                        }
                    }

                    // If new images uploaded, first one becomes main thumb if none existing
                    if (empty($main_variant_image) && !empty($new_images)) {
                        $main_variant_image = $new_images[0];
                    }

                    // Insert Variant
                    $stmt = $pdo->prepare("INSERT INTO product_variants (product_id, color_name, color_code, image_path) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$product_id, $v_name, $v_code, $main_variant_image]);
                    $variant_id = $pdo->lastInsertId();

                    // Insert Images into product_variant_images
                    $stmtImg = $pdo->prepare("INSERT INTO product_variant_images (product_variant_id, image_path) VALUES (?, ?)");
                    
                    // Add new images
                    foreach ($new_images as $img) {
                        $stmtImg->execute([$variant_id, $img]);
                    }
                    // Add existing images (if we were preserving them from a previous edit, which needs hidden inputs)
                    // For now, simplifiction: we only add NEW uploads to the gallery table or rely on existing rows?
                    // Implementation Detail: To edit effectively, we usually need to fetch existing `product_variant_images` and keep them.
                    // Given the complexity, let's just SAVE new ones for now. *Correction*: Users expect to KEEP old images.
                    // To keep old images, we need hidden inputs for `existing_images[]` which we don't have yet.
                    // Let's implement that in HTML below.
                    if (isset($v_data['existing_images'])) {
                        foreach ($v_data['existing_images'] as $exImg) {
                             $stmtImg->execute([$variant_id, $exImg]);
                        }
                    }
                }
            }

            redirect('products.php');
        } catch (PDOException $e) {
            $error = __('database_error', 'Database Error') . ": " . $e->getMessage();
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
    <h1 class="admin-title" style="margin-bottom: 0;">
        <?= $product ? __('edit_product', 'Edit Product') : __('add_new_product', 'Add New Product') ?>
    </h1>
    <a href="products.php" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> <?= __('back_to_list', 'Back to List') ?>
    </a>
</div>

<form method="POST" enctype="multipart/form-data" class="admin-form-layout">
    <div class="form-grid">
        <!-- Left Column: Main Info -->
        <div class="form-column-main">
            <!-- Basic Info Card -->
            <div class="card mb-4" style="margin-bottom: 1.5rem;">
                <h3 class="card-title mb-4" style="margin-bottom: 1.25rem;"><?= __('basic_info', 'Basic Information') ?></h3>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger mb-4" style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <div class="form-group mb-4">
                    <label class="form-label"><?= __('product_name_label', 'Product Name') ?></label>
                    <input type="text" name="name" class="form-control" value="<?= $product['name'] ?? '' ?>" placeholder="<?= __('product_name_placeholder', 'e.g. Wayfarer Classic') ?>" required>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label"><?= __('description_label', 'Description') ?></label>
                    <textarea name="description" class="form-control rich-editor" rows="5" placeholder="<?= __('description_placeholder', 'Tell customers about this product...') ?>"><?= $product['description'] ?? '' ?></textarea>
                    
                    <div style="margin-top: 0.75rem; display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="show_raw_html" id="show_raw_html" value="1" <?= ($product['show_raw_html'] ?? 0) ? 'checked' : '' ?>>
                        <label for="show_raw_html" style="font-size: 0.85rem; color: var(--admin-text-light); cursor: pointer;">Display raw HTML (Code View)</label>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label"><?= __('key_features_label', 'Key Features') ?></label>
                    <textarea name="key_features" class="form-control" rows="4" style="font-family: monospace;" placeholder="<?= __('key_features_placeholder', "Premium Acetate Frame\nAnti-Glare Lenses") ?>"><?= htmlspecialchars($product['key_features'] ?? '') ?></textarea>
                    <small style="color: var(--admin-text-light);"><?= __('key_features_help', 'One feature per line.') ?></small>
                </div>
            </div>

            <!-- Media Card -->
            <div class="card mb-4" style="margin-bottom: 1.5rem;">
                <h3 class="card-title mb-4" style="margin-bottom: 1.25rem;"><?= __('media', 'Media') ?></h3>
                
                <div class="form-group mb-4">
                    <label class="form-label"><?= __('main_image', 'Main Image') ?></label>
                    <div class="drag-upload-container">
                        <div id="productDragBox" class="drag-upload-box">
                            <div class="drag-upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                            <div class="drag-upload-text"><?= __('drop_image_here', 'Drop image here or click to browse') ?></div>
                            <input type="file" name="image" id="productImageInput" style="display: none;">
                        </div>
                        <div id="productPreview" class="drag-preview-container">
                            <?php if ($product && $product['image']): ?>
                                <img src="../assets/uploads/<?= $product['image'] ?>" class="drag-preview-image">
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label"><?= __('gallery_images', 'Gallery Images') ?></label>
                    <div class="drag-upload-container">
                        <div id="galleryDragBox" class="drag-upload-box" style="background: #f8fafc; border-style: dashed;">
                            <div class="drag-upload-icon" style="color: var(--admin-text-light);"><i class="fa-solid fa-images"></i></div>
                            <div class="drag-upload-text"><?= __('drop_images_here', 'Drop multiple images here') ?></div>
                            <input type="file" name="gallery[]" id="galleryInput" multiple style="display: none;">
                        </div>
                        <div id="galleryPreview" class="drag-preview-container" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem;">
                            <?php if (!empty($product_gallery)): ?>
                                <?php foreach ($product_gallery as $img): ?>
                                    <img src="../assets/uploads/<?= $img['image_path'] ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Variants Card -->
            <div class="card mb-4" style="margin-bottom: 1.5rem;">
                <h3 class="card-title mb-4" style="margin-bottom: 1.25rem;"><?= __('color_variants', 'Color Variants') ?></h3>
                
                <div class="alert alert-info mb-4" style="background: #e0f2fe; color: #0369a1; padding: 0.75rem; border-radius: 6px; font-size: 0.85rem; border: 1px solid #bae6fd;">
                    <i class="fa-solid fa-circle-info me-2"></i>
                    <strong>Note:</strong> You can now accept multiple images for each variant. The first image will be used as the main color icon.
                </div>
                
                <div id="variantContainer">
                    <?php if (!empty($product_variants)): ?>
                        <?php foreach ($product_variants as $idx => $v): 
                            $uniqueId = 'var_' . uniqid(); 
                            // Fetch sub-images
                            $v_imgs = $pdo->prepare("SELECT * FROM product_variant_images WHERE product_variant_id = ?");
                            $v_imgs->execute([$v['id']]);
                            $variant_images = $v_imgs->fetchAll();
                        ?>
                            <div class="variant-card mb-3 p-3 border rounded bg-white shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="m-0 text-muted fw-bold"><i class="fa-solid fa-swatchbook me-2"></i>Variant</h6>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-3" onclick="this.closest('.variant-card').remove()" title="Remove Variant">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                                
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <div class="form-group mb-3">
                                            <label class="form-label text-secondary small fw-bold">Color Name</label>
                                            <input type="text" name="variants[<?= $idx ?>][name]" class="form-control" value="<?= htmlspecialchars($v['color_name']) ?>" placeholder="e.g. Midnight Blue">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label text-secondary small fw-bold">Color Code</label>
                                            <div class="d-flex align-items-center gap-3">
                                                 <div class="color-picker-wrapper">
                                                    <div class="color-picker-visual" id="visual_<?= $uniqueId ?>" style="background-color: <?= $v['color_code'] ?>;"></div>
                                                    <input type="color" name="variants[<?= $idx ?>][code]" class="color-picker-input" value="<?= $v['color_code'] ?>" oninput="updateColorVisual(this, 'visual_<?= $uniqueId ?>')">
                                                </div>
                                                <input type="hidden" name="variants[<?= $idx ?>][existing_main_image]" value="<?= $v['image_path'] ?>">
                                                <span class="small text-muted">Click the circle to pick a color</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-5">
                                        <label class="form-label text-secondary small fw-bold">Variant Images</label>
                                        <div class="drag-upload-container">
                                            <div class="drag-upload-box" style="background: #f8fafc; border-style: dashed; min-height: 120px;" onclick="document.getElementById('<?= $uniqueId ?>').click()">
                                                <div class="drag-upload-icon" style="color: var(--admin-text-light);"><i class="fa-solid fa-images"></i></div>
                                                <div class="drag-upload-text" style="font-size: 0.85rem;">Drop multiple images here</div>
                                                <div style="margin-top: 0.25rem; color: #94a3b8; font-size: 0.75rem;">or click to browse</div>
                                            </div>
                                            <input type="file" name="variants[<?= $idx ?>][images][]" id="<?= $uniqueId ?>" style="display: none;" accept="image/*" multiple onchange="previewVariantImageMulti(this, 'preview_<?= $uniqueId ?>')">
                                            
                                            <div id="preview_<?= $uniqueId ?>" class="drag-preview-container" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem;">
                                                <?php if (!empty($variant_images)): ?>
                                                    <?php foreach ($variant_images as $vImg): ?>
                                                        <div style="position: relative;">
                                                            <img src="../assets/uploads/<?= $vImg['image_path'] ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;">
                                                        </div>
                                                        <input type="hidden" name="variants[<?= $idx ?>][existing_images][]" value="<?= $vImg['image_path'] ?>">
                                                    <?php endforeach; ?>
                                                <?php elseif (!empty($v['image_path'])): ?>
                                                    <!-- Fallback for only main image -->
                                                    <div style="position: relative;">
                                                        <img src="../assets/uploads/<?= $v['image_path'] ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="btn btn-sm btn-secondary w-100 mt-2" onclick="addVariantRow()">
                    <i class="fa-solid fa-plus"></i> <?= __('add_variant', 'Add Variant') ?>
                </button>
            </div>

            <!-- Specifications Card -->
             <div class="card mb-4" style="margin-bottom: 1.5rem;">
                <h3 class="card-title mb-4" style="margin-bottom: 1.25rem;"><?= __('specifications', 'Specifications') ?></h3>
                <?php $specs = isset($product['frame_specs']) ? json_decode($product['frame_specs'], true) : []; ?>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label"><?= __('lens_width', 'Lens Width') ?></label>
                        <input type="text" name="specs[lens_width]" class="form-control" value="<?= $specs['lens_width'] ?? '' ?>" placeholder="50 mm">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('bridge_width', 'Bridge Width') ?></label>
                        <input type="text" name="specs[bridge_width]" class="form-control" value="<?= $specs['bridge_width'] ?? '' ?>" placeholder="17 mm">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('temple_length', 'Temple Length') ?></label>
                        <input type="text" name="specs[temple_length]" class="form-control" value="<?= $specs['temple_length'] ?? '' ?>" placeholder="145 mm">
                    </div>
                     <div class="form-group">
                        <label class="form-label"><?= __('frame_material', 'Frame Material') ?></label>
                        <input type="text" name="specs[material]" class="form-control" value="<?= $specs['material'] ?? '' ?>" placeholder="Acetate">
                    </div>
                     <div class="form-group">
                        <label class="form-label"><?= __('shape', 'Shape') ?></label>
                        <input type="text" name="specs[shape]" class="form-control" value="<?= $specs['shape'] ?? '' ?>" placeholder="Rectangular">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= __('gender', 'Gender') ?></label>
                        <select name="specs[gender]" class="form-control">
                            <option value=""><?= __('select', 'Select...') ?></option>
                            <option value="Unisex" <?= ($specs['gender'] ?? '') == 'Unisex' ? 'selected' : '' ?>><?= __('unisex', 'Unisex') ?></option>
                            <option value="Men" <?= ($specs['gender'] ?? '') == 'Men' ? 'selected' : '' ?>><?= __('men', 'Men') ?></option>
                            <option value="Women" <?= ($specs['gender'] ?? '') == 'Women' ? 'selected' : '' ?>><?= __('women', 'Women') ?></option>
                            <option value="Kids" <?= ($specs['gender'] ?? '') == 'Kids' ? 'selected' : '' ?>><?= __('kids', 'Kids') ?></option>
                        </select>
                    </div>
                </div>
             </div>
        </div>

        <!-- Right Column: Sidebar -->
        <div class="form-column-sidebar">
            <!-- Organization Card -->
            <div class="card mb-4" style="margin-bottom: 1.5rem;">
                <h3 class="card-title mb-4" style="margin-bottom: 1.25rem;"><?= __('organization', 'Organization') ?></h3>
                
                <div class="form-group mb-0">
                    <label class="form-label"><?= __('category_label', 'Category') ?></label>
                    <select name="category_id" class="form-control" required>
                        <option value=""><?= __('select_category', 'Select Category') ?></option>
                        <?php 
                        $hierarchical_categories = getCategoriesHierarchical();
                        foreach ($hierarchical_categories as $c): 
                        ?>
                            <option value="<?= $c['id'] ?>" <?= ($product && $product['category_id'] == $c['id']) ? 'selected' : '' ?>>
                                <?= $c['parent_id'] ? '&nbsp;&nbsp;&nbsp;↳ ' : '' ?><?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Pricing & Stock Card -->
            <div class="card mb-4" style="margin-bottom: 1.5rem;">
                <h3 class="card-title mb-4" style="margin-bottom: 1.25rem;"><?= __('pricing_stock', 'Pricing & Stock') ?></h3>
                
                <div class="form-group mb-4">
                    <label class="form-label"><?= __('selling_price', 'Selling Price (₹)') ?></label>
                    <input type="number" step="0.01" name="price" class="form-control" value="<?= $product['price'] ?? '' ?>" placeholder="0.00" required>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label"><?= __('actual_price', 'M.R.P (₹)') ?></label>
                    <input type="number" step="0.01" name="actual_price" class="form-control" value="<?= $product['actual_price'] ?? '' ?>" placeholder="0.00">
                </div>

                <div class="form-group mb-4">
                    <label class="form-label"><?= __('stock_quantity_label', 'Stock Quantity') ?></label>
                    <input type="number" name="stock_quantity" class="form-control" value="<?= $product['stock_quantity'] ?? '' ?>" min="0">
                </div>

                <div class="form-group mb-0">
                    <label class="form-label"><?= __('low_stock_threshold_label', 'Low Stock Threshold') ?></label>
                    <input type="number" name="low_stock_threshold" class="form-control" value="<?= $product['low_stock_threshold'] ?? 10 ?>" min="1">
                </div>
            </div>


            <!-- Actions -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-save"></i> <?= __('save_product', 'Save Product') ?>
                </button>
            </div>
        </div>
    </div>
</form>

<style>
/* Improved Admin Styles */
/* .variant-card {
    transition: all 0.2s ease;
    border: 1px solid #e2e8f0;
}
.variant-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
} */

    

/* Color Picker */
.color-picker-wrapper {
    position: relative;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    overflow: hidden;
    cursor: pointer;
    border: 2px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.color-picker-input {
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    padding: 0;
    margin: 0;
    cursor: pointer;
    opacity: 0; /* Hide default input but keep clickable */
}
.color-picker-visual {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background-color: #000; /* Default */
    transition: background-color 0.1s;
}

/* Variant Upload Box */
    border: 2px dashed #cbd5e1 !important;
    background-color: #f8fafc;
    transition: all 0.2s ease;
}
.variant-upload-box:hover {
    border-color: #3b82f6 !important;
    background-color: #eff6ff;
}
.preview-area img {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.1s;
}
.preview-area img:hover {
    transform: scale(1.1);
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        initDragAndDrop('productDragBox', 'productImageInput', 'productPreview');
        initDragAndDrop('galleryDragBox', 'galleryInput', 'galleryPreview', true);
    });

    function addVariantRow() {
        const container = document.getElementById('variantContainer');
        const row = document.createElement('div');
        const timestamp = Date.now();
        const uniqueId = 'var_' + timestamp;
        row.className = 'variant-card mb-3 p-3 border rounded bg-white shadow-sm';
        row.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="m-0 text-muted fw-bold mb-1"><i class="fa-solid fa-swatchbook me-2">  </i> <span style="rargin-right: 10px">New Variant</span></h6>
                <button type="button" class="btn btn-primary" onclick="this.closest('.variant-card').remove()" title="Remove Variant">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
            
            <div class="row g-3">
                <div class="col-md-7">
                    <div class="form-group mb-3">
                        <label class="form-label text-secondary small fw-bold">Color Name</label>
                        <input type="text" name="variants[${timestamp}][name]" class="form-control" placeholder="e.g. Midnight Blue">
                    </div>
                    <div class="form-group">
                        <label class="form-label text-secondary small fw-bold">Color Code</label>
                        <div class="d-flex align-items-center gap-3">
                             <div class="color-picker-wrapper">
                                <div class="color-picker-visual" id="visual_${uniqueId}" style="background-color: #000000;"></div>
                                <input type="color" name="variants[${timestamp}][code]" class="color-picker-input" value="#000000" oninput="updateColorVisual(this, 'visual_${uniqueId}')">
                            </div>
                            <span class="small text-muted">Click the circle to pick a color</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-5">
                    <label class="form-label text-secondary small fw-bold">Variant Images</label>
                    <div class="drag-upload-container">
                        <div class="drag-upload-box" style="background: #f8fafc; border-style: dashed; min-height: 120px;" onclick="document.getElementById('${uniqueId}').click()">
                            <div class="drag-upload-icon" style="color: var(--admin-text-light);"><i class="fa-solid fa-images"></i></div>
                            <div class="drag-upload-text" style="font-size: 0.85rem;">Drop multiple images here</div>
                            <div style="margin-top: 0.25rem; color: #94a3b8; font-size: 0.75rem;">or click to browse</div>
                        </div>
                        <input type="file" name="variants[${timestamp}][images][]" id="${uniqueId}" style="display: none;" accept="image/*" multiple onchange="previewVariantImageMulti(this, 'preview_${uniqueId}')">
                        <div id="preview_${uniqueId}" class="drag-preview-container" style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1rem;"></div>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(row);
    }

    function updateColorVisual(input, visualId) {
        const visual = document.getElementById(visualId);
        if (visual) visual.style.backgroundColor = input.value;
    }

    function previewVariantImageMulti(input, previewId) {
        const container = document.getElementById(previewId);
        if (!container) return;
        
        // Clear container
        container.innerHTML = '';
        
        if (input.files && input.files.length > 0) {
            Array.from(input.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const wrapper = document.createElement('div');
                    wrapper.style.cssText = 'position: relative;';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.cssText = 'width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 2px solid #e2e8f0;';
                    
                    wrapper.appendChild(img);
                    container.appendChild(wrapper);
                }
                reader.readAsDataURL(file);
            });
        }
    }
</script>

<?php require_once 'footer.php'; ?>
