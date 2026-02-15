<?php
require_once 'config/db.php';

echo "<h1>🔧 Add image_path to product_variants Table</h1>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;} .success{color:#22c55e;} .error{color:#ef4444;} pre{background:#f8f8f8;padding:10px;border-radius:4px;}</style>";

try {
    echo "<h2>Step 1: Check if column exists</h2>";
    $stmt = $pdo->query("DESCRIBE product_variants");
    $columns = $stmt->fetchAll();
    
    $has_image_path = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'image_path') {
            $has_image_path = true;
            break;
        }
    }
    
    if ($has_image_path) {
        echo "<p class='success'>✓ Column already exists. No fix needed.</p>";
    } else {
        echo "<p>Adding image_path column to product_variants table...</p>";
        
        $pdo->exec("ALTER TABLE product_variants ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER color_code");
        
        echo "<p class='success'>✓ Column added successfully!</p>";
    }
    
    echo "<h2>Step 2: Verify schema</h2>";
    $stmt = $pdo->query("DESCRIBE product_variants");
    $columns = $stmt->fetchAll();
    
    echo "<pre>";
    printf("%-25s %-20s\n", "Column", "Type");
    echo str_repeat("-", 50) . "\n";
    foreach ($columns as $col) {
        printf("%-25s %-20s\n", $col['Field'], $col['Type']);
    }
    echo "</pre>";
    
    echo "<h2 class='success'>✅ SUCCESS!</h2>";
    echo "<p>The product_variants table now has the image_path column.</p>";
    echo "<p><strong>You can now upload variant images without errors!</strong></p>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Go to Admin → Products</li>";
    echo "<li>Edit a product with variants</li>";
    echo "<li>Upload multiple images for each variant</li>";
    echo "<li>Save and test</li>";
    echo "</ol>";
    
    echo "<p class='error'>⚠️ Delete this file after running for security.</p>";
    
} catch (PDOException $e) {
    echo "<h2 class='error'>❌ Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
