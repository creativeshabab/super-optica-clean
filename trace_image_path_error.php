<?php
require_once 'config/db.php';

echo "<h1>🔍 Trace image_path Error</h1>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;} .success{color:#22c55e;} .error{color:#ef4444;} .warning{color:#f59e0b;} pre{background:#f8f8f8;padding:10px;border-radius:4px;overflow-x:auto;}</style>";

echo "<h2>Checking ALL tables for image_path column</h2>";

$tables = ['products', 'product_variants', 'product_variant_images', 'product_images'];

foreach ($tables as $table) {
    echo "<h3>Table: $table</h3>";
    
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if (!$stmt->fetch()) {
            echo "<p class='warning'>⚠️ Table does not exist</p>";
            continue;
        }
        
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll();
        
        echo "<pre>";
        printf("%-25s %-20s\n", "Column", "Type");
        echo str_repeat("-", 50) . "\n";
        
        $has_image_path = false;
        foreach ($columns as $col) {
            printf("%-25s %-20s\n", $col['Field'], $col['Type']);
            if ($col['Field'] === 'image_path') {
                $has_image_path = true;
            }
        }
        echo "</pre>";
        
        if ($has_image_path) {
            echo "<p class='success'>✓ Has image_path column</p>";
        } else {
            echo "<p class='warning'>✗ No image_path column</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Testing Queries</h2>";

// Test 1: product_variant_images
echo "<h3>Test 1: SELECT from product_variant_images</h3>";
try {
    $stmt = $pdo->query("SELECT id, product_variant_id, image_path FROM product_variant_images LIMIT 1");
    echo "<p class='success'>✓ Query works! Table has image_path column.</p>";
} catch (PDOException $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

// Test 2: product_variants
echo "<h3>Test 2: SELECT from product_variants</h3>";
try {
    $stmt = $pdo->query("SELECT id, product_id, color_name, image_path FROM product_variants LIMIT 1");
    echo "<p class='success'>✓ Query works! Table has image_path column.</p>";
} catch (PDOException $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p class='warning'>This might be the source of your error!</p>";
}

echo "<h2>Recommendation</h2>";
echo "<p>If Test 2 failed, the <code>product_variants</code> table is missing the <code>image_path</code> column.</p>";
echo "<p>This column should exist for backward compatibility (stores the main variant thumbnail).</p>";
?>
