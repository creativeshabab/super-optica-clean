<?php
require_once 'config/db.php';

echo "<h1>🔧 Variant Images Table - Complete Fix</h1>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;} .success{color:#22c55e;} .error{color:#ef4444;} .info{color:#3b82f6;} pre{background:#f8f8f8;padding:10px;border-radius:4px;}</style>";

try {
    echo "<h2>Step 1: Drop existing table (if any)</h2>";
    $pdo->exec("DROP TABLE IF EXISTS product_variant_images");
    echo "<p class='success'>✓ Dropped old table</p>";
    
    echo "<h2>Step 2: Create table with correct schema</h2>";
    $sql = "CREATE TABLE product_variant_images (
        id INT(11) NOT NULL AUTO_INCREMENT,
        product_variant_id INT(11) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY product_variant_id_idx (product_variant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    echo "<p class='success'>✓ Created table with correct schema</p>";
    
    echo "<h2>Step 3: Verify schema</h2>";
    $stmt = $pdo->query("DESCRIBE product_variant_images");
    $columns = $stmt->fetchAll();
    
    echo "<pre>";
    echo "Table: product_variant_images\n";
    echo str_repeat("-", 60) . "\n";
    printf("%-25s %-20s %-10s\n", "Column", "Type", "Null");
    echo str_repeat("-", 60) . "\n";
    
    $has_image_path = false;
    foreach ($columns as $col) {
        printf("%-25s %-20s %-10s\n", $col['Field'], $col['Type'], $col['Null']);
        if ($col['Field'] === 'image_path') {
            $has_image_path = true;
        }
    }
    echo "</pre>";
    
    if ($has_image_path) {
        echo "<h2 class='success'>✅ SUCCESS!</h2>";
        echo "<p>The table is now correctly configured with the <strong>image_path</strong> column.</p>";
        echo "<h3>Next Steps:</h3>";
        echo "<ol>";
        echo "<li>Go to <strong>Admin → Products</strong></li>";
        echo "<li>Edit any product with variants</li>";
        echo "<li>Upload multiple images for each variant</li>";
        echo "<li>Save and test on the product page</li>";
        echo "</ol>";
        echo "<p class='info'>⚠️ <strong>IMPORTANT:</strong> Delete this file (fix_variant_images_table.php) after running for security.</p>";
    } else {
        echo "<h2 class='error'>❌ ERROR</h2>";
        echo "<p>The image_path column is still missing. Please contact support.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2 class='error'>❌ Database Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Manual Fix:</h3>";
    echo "<p>Run this SQL in phpMyAdmin:</p>";
    echo "<pre>";
    echo "DROP TABLE IF EXISTS product_variant_images;\n\n";
    echo "CREATE TABLE product_variant_images (\n";
    echo "  id INT(11) NOT NULL AUTO_INCREMENT,\n";
    echo "  product_variant_id INT(11) NOT NULL,\n";
    echo "  image_path VARCHAR(255) NOT NULL,\n";
    echo "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
    echo "  PRIMARY KEY (id),\n";
    echo "  KEY product_variant_id_idx (product_variant_id)\n";
    echo ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n";
    echo "</pre>";
}
?>
