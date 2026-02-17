<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    die('Unauthorized');
}

$filename = "bulk_product_template_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// CSV Headers
fputcsv($output, [
    'product_sku', 
    'product_name', 
    'category_id', 
    'price', 
    'actual_price', 
    'stock', 
    'low_stock_threshold',
    'variant_sku', 
    'color_name', 
    'color_code', 
    'variant_images', 
    'product_description',
    'key_features'
]);

// Fetch actual categories for sample data
$cats = $pdo->query("SELECT id FROM categories LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
$cat1 = $cats[0] ?? '1';
$cat2 = $cats[1] ?? $cat1;

// Sample Rows for a Product with 2 Variants
fputcsv($output, [
    'SKU-SUN-001', 
    'Classic Aviator', 
    $cat1, 
    '1299', 
    '1999', 
    '50', 
    '10',
    'VAR-SUN-001-GOLD', 
    'Gold Frame', 
    '#D4AF37', 
    'aviator_gold_front.jpg, aviator_gold_side.jpg, aviator_gold_wear.jpg', 
    'Classic aviator sunglasses with polarized lenses.',
    "Polarized Lenses\nUV400 Protection\nHigh-Quality Metal Frame"
]);

fputcsv($output, [
    'SKU-SUN-001', 
    'Classic Aviator', 
    $cat1, 
    '1299', 
    '1999', 
    '30', 
    '10',
    'VAR-SUN-001-BLACK', 
    'Matte Black', 
    '#000000', 
    'aviator_black_front.jpg, aviator_black_side.jpg', 
    'Classic aviator sunglasses with polarized lenses.',
    "Polarized Lenses\nUV400 Protection\nHigh-Quality Metal Frame"
]);

// Sample Row for another product
fputcsv($output, [
    'SKU-WAY-002', 
    'Wayfarer Pro', 
    $cat2, 
    '2499', 
    '3499', 
    '20', 
    '5',
    'VAR-WAY-002-BLUE', 
    'Blue Tortoise', 
    '#2563EB', 
    'wayfarer_blue.jpg', 
    'Modern wayfarer design with flexible hinges.',
    "Lightweight Frame\nScratch Resistant Lenses"
]);

fclose($output);
exit;
