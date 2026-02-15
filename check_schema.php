<?php
require_once 'config/db.php';

try {
    echo "Product Variants:\n";
    $stmt = $pdo->query("DESCRIBE product_variants");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    echo "\nProduct Images:\n";
    $stmt = $pdo->query("DESCRIBE product_images");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
