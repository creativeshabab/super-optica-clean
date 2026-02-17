<?php
// Simple CSS Minifier
// Usage: php utils/minify_assets.php

echo "Starting Asset Minification...\n";

$cssDir = __DIR__ . '/../assets/css/';
$files = glob($cssDir . '*.css');

foreach ($files as $file) {
    if (strpos($file, '.min.css') !== false) continue;

    $filename = basename($file);
    echo "Minifying $filename... ";

    $css = file_get_contents($file);
    
    // 1. Remove comments
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
    
    // 2. Remove tabs, spaces, newlines, etc.
    $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
    
    // 3. Save as .min.css
    $minFile = str_replace('.css', '.min.css', $file);
    file_put_contents($minFile, $css);
    
    echo "Done! (" . round(filesize($minFile)/1024, 2) . "KB)\n";
}

echo "\nMinification Complete.\n";
?>
