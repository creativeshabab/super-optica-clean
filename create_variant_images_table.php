<?php
require_once 'config/db.php';

try {
    $pdo->exec("DROP TABLE IF EXISTS product_variant_images");

    // Create table without FK first to avoid 150 error
    $sql = "CREATE TABLE product_variant_images (
        id INT(11) NOT NULL AUTO_INCREMENT,
        product_variant_id INT(11) NOT NULL,
        image_path VARCHAR(255) NOT NULL,
        PRIMARY KEY (id),
        KEY product_variant_id_idx (product_variant_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Table 'product_variant_images' created successfully (no FK).\n";
    
    // Verify
    $stmt = $pdo->query("DESCRIBE product_variant_images");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
