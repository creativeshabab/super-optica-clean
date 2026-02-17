<?php
// Bulk Image Optimizer
// Usage: php utils/optimize_images.php [wet|dry]

$mode = $argv[1] ?? 'dry';
$do_optimize = ($mode === 'wet');

echo "Starting Image Optimization (Mode: " . strtoupper($mode) . ")...\n";

$dir = __DIR__ . '/../assets/uploads/';
$files = glob($dir . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);

$total_saved = 0;
$count = 0;

foreach ($files as $file) {
    if ($count > 50) break; // Limit to 50 for safety in this run

    $size = filesize($file);
    if ($size < 200 * 1024) continue; // Skip small files (<200KB)

    $info = getimagesize($file);
    if (!$info) continue;

    $width = $info[0];
    $mime = $info['mime'];
    
    // Criteria: > 1920px width OR > 500KB
    if ($width <= 1920 && $size < 500 * 1024) continue;

    echo "Optimizing " . basename($file) . " (" . round($size/1024) . "KB, {$width}px)... ";

    if ($do_optimize) {
        switch ($mime) {
            case 'image/jpeg': 
                $img = imagecreatefromjpeg($file); 
                break;
            case 'image/png': 
                $img = imagecreatefrompng($file); 
                break;
            case 'image/webp':
                $img = imagecreatefromwebp($file);
                break;
            default: continue 2;
        }

        if ($width > 1920) {
            $img = imagescale($img, 1920);
        }

        // Save back with compression
        if ($mime === 'image/jpeg') imagejpeg($img, $file, 80);
        elseif ($mime === 'image/png') imagepng($img, $file, 8); // 0-9 for PNG
        elseif ($mime === 'image/webp') imagewebp($img, $file, 80);
        
        imagedestroy($img);
        clearstatcache();
        $new_size = filesize($file);
        $saved = $size - $new_size;
        $total_saved += $saved;
        echo "Reduced to " . round($new_size/1024) . "KB (-" . round($saved/1024) . "KB)\n";
    } else {
        echo "[Would Optimize]\n";
    }
    $count++;
}

if ($do_optimize) {
    echo "\nTotal Space Saved: " . round($total_saved / 1024 / 1024, 2) . " MB\n";
} else {
    echo "\nRun with 'wet' argument to apply changes.\n";
}
?>
