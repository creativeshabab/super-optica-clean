<?php
require_once 'config/db.php';

echo "=== Variant Functionality Debug ===\n\n";

// 1. Check if product_variant_images table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'product_variant_images'");
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        echo "✓ product_variant_images table exists\n";
        
        // Check schema
        $stmt = $pdo->query("DESCRIBE product_variant_images");
        $columns = $stmt->fetchAll();
        echo "\nTable schema:\n";
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
        
        // Count records
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_variant_images");
        $count = $stmt->fetch();
        echo "\nTotal variant images: {$count['count']}\n";
        
    } else {
        echo "✗ product_variant_images table DOES NOT EXIST\n";
        echo "  → Run create_variant_images_table.php to create it\n";
    }
} catch (PDOException $e) {
    echo "✗ Error checking table: " . $e->getMessage() . "\n";
}

// 2. Check sample product with variants
echo "\n=== Sample Product Check ===\n";
try {
    $stmt = $pdo->query("SELECT * FROM products WHERE id IN (SELECT DISTINCT product_id FROM product_variants) LIMIT 1");
    $product = $stmt->fetch();
    
    if ($product) {
        echo "Product: {$product['name']} (ID: {$product['id']})\n";
        
        // Get variants
        $stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ?");
        $stmt->execute([$product['id']]);
        $variants = $stmt->fetchAll();
        
        echo "Variants: " . count($variants) . "\n";
        
        foreach ($variants as $v) {
            echo "\n  Variant: {$v['color_name']} (ID: {$v['id']})\n";
            echo "    Main image: {$v['image_path']}\n";
            
            // Check variant images
            if ($table_exists) {
                $stmt = $pdo->prepare("SELECT * FROM product_variant_images WHERE product_variant_id = ?");
                $stmt->execute([$v['id']]);
                $v_images = $stmt->fetchAll();
                echo "    Gallery images: " . count($v_images) . "\n";
                foreach ($v_images as $img) {
                    echo "      - {$img['image_path']}\n";
                }
            }
        }
        
        echo "\nTest URL: http://localhost/new/product.php?id={$product['id']}\n";
    } else {
        echo "No products with variants found\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Recommendations ===\n";
if (!$table_exists) {
    echo "1. Run: http://localhost/new/create_variant_images_table.php\n";
}
echo "2. Check browser console for JavaScript errors\n";
echo "3. Verify variant images are uploaded in admin panel\n";
?>
