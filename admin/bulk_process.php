<?php
// Start output buffering immediately to catch any accidental output/notices
ob_start();

// Custom error handler to capture PHP errors into our logs
$logs = [];
function addLog($type, $message) {
    global $logs;
    $logs[] = ['type' => $type, 'message' => $message];
}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return false;
    addLog('error', "PHP Error: [$errno] $errstr in $errfile on line $errline");
    return true;
});

require_once '../config/db.php';
require_once '../includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

try {
    if (!isAdmin()) {
        throw new Exception('Unauthorized access');
    }

// 0. Prefetch Valid Categories
$validCategories = $pdo->query("SELECT id FROM categories")->fetchAll(PDO::FETCH_COLUMN);

// Increase limits for processing
set_time_limit(600); 
ini_set('memory_limit', '512M');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

// 1. Validate Files
$csvName = '';
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    addLog('error', 'CSV file is missing or corrupted.');
} else {
    $csvName = $_FILES['csv_file']['name'];
    $csvExt = strtolower(pathinfo($csvName, PATHINFO_EXTENSION));
    if ($csvExt !== 'csv') {
        addLog('error', "Invalid file type: '$csvName'. Please upload a .csv file.");
    }
}

if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
    addLog('error', 'ZIP file is missing or corrupted.');
}

if (!empty($logs)) {
    $errorFound = false;
    foreach($logs as $l) if($l['type'] === 'error') $errorFound = true;
    if ($errorFound) {
        ob_end_clean();
        echo json_encode(['success' => false, 'logs' => $logs]);
        exit;
    }
}

// Create temporary directory for ZIP extraction
$tempDir = '../assets/uploads/temp_bulk_' . uniqid();
if (!@mkdir($tempDir, 0777, true)) {
    ob_end_clean();
    addLog('error', 'Failed to create temporary directory. check assets/uploads permissions.');
    echo json_encode(['success' => false, 'logs' => $logs]);
    exit;
}

// 2. Extract ZIP
$zipExtracted = false;
$src = realpath($_FILES['zip_file']['tmp_name']);
$dest = realpath($tempDir);

if (!$src || !$dest) {
    addLog('error', 'Critical path resolution error. Check folder permissions.');
    echo json_encode(['success' => false, 'logs' => $logs]);
    exit;
}

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive;
    if ($zip->open($src) === TRUE) {
        $zip->extractTo($dest);
        $zip->close();
        $zipExtracted = true;
        addLog('info', 'Images archive extracted successfully using ZipArchive.');
    }
} 

if (!$zipExtracted) {
    // Fallback 1: tar (Windows 10/11 native)
    $cmd = sprintf('tar -xf %s -C %s 2>&1', escapeshellarg($src), escapeshellarg($dest));
    exec($cmd, $output, $returnVar);
    if ($returnVar === 0) {
        $zipExtracted = true;
        addLog('info', 'Images archive extracted successfully using tar fallback.');
    } else {
        $tarError = implode(' ', $output);
    }
}

if (!$zipExtracted) {
    // Fallback 2: PowerShell on Windows
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $psCommand = sprintf('Expand-Archive -Path %s -DestinationPath %s -Force', escapeshellarg($src), escapeshellarg($dest));
        $psFull = "powershell -command \"$psCommand\" 2>&1";
        exec($psFull, $output, $returnVar);
        if ($returnVar === 0) {
            $zipExtracted = true;
            addLog('info', 'Images archive extracted successfully using PowerShell fallback.');
        } else {
            $psError = implode(' ', $output);
        }
    }
}

if (!$zipExtracted) {
    addLog('error', 'All extraction methods failed.');
    if (isset($tarError)) addLog('debug', "Tar Error: $tarError");
    if (isset($psError)) addLog('debug', "PS Error: $psError");
    addLog('error', 'Please enable "extension=zip" in your php.ini for best reliability.');
    deleteDirectory($tempDir);
    echo json_encode(['success' => false, 'logs' => $logs]);
    exit;
}

// 3. Parse CSV and group by Product SKU
addLog('info', "Starting to parse '$csvName'...");
$products = [];
if (($handle = fopen($_FILES['csv_file']['tmp_name'], "r")) !== FALSE) {
    // Detect delimiter
    $firstLine = fgets($handle);
    $commaCount = substr_count($firstLine, ',');
    $semiCount = substr_count($firstLine, ';');
    $delimiter = ($semiCount > $commaCount) ? ';' : ',';
    rewind($handle);

    $headers = fgetcsv($handle, 1000, $delimiter);
    if (!$headers || count($headers) < 2) {
        addLog('error', 'CSV file headers not found or invalid format.');
        echo json_encode(['success' => false, 'logs' => $logs]);
        exit;
    }
    
    $headers = array_map('trim', $headers);
    addLog('info', "Data headers detected (Delimiter: '$delimiter'): " . implode(', ', $headers));
    
    while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
        if (count($data) < count($headers)) {
            $data = array_pad($data, count($headers), '');
        }
        $row = array_combine($headers, array_slice($data, 0, count($headers)));
        
        $pSku = trim($row['product_sku'] ?? '');
        if ($pSku === '') continue;
        
        $catId = (int)($row['category_id'] ?? 0);
        
        if (!in_array($catId, $validCategories)) {
            addLog('error', "SKU $pSku Error: Invalid Category ID ($catId). row skipped.");
            continue;
        }

        if (!isset($products[$pSku])) {
            $products[$pSku] = [
                'name' => trim($row['product_name'] ?? 'Unnamed Product'),
                'category_id' => $catId,
                'price' => (float)($row['price'] ?? 0),
                'actual_price' => (!empty($row['actual_price'])) ? (float)$row['actual_price'] : null,
                'description' => $row['product_description'] ?? '',
                'key_features' => $row['key_features'] ?? '',
                'variants' => []
            ];
        }
        
        $products[$pSku]['variants'][] = [
            'sku' => trim($row['variant_sku'] ?? $pSku . '-VAR-' . count($products[$pSku]['variants'])),
            'name' => trim($row['color_name'] ?? 'Default'),
            'code' => trim($row['color_code'] ?? '#000000'),
            'stock' => (int)($row['stock'] ?? 0),
            'threshold' => (int)($row['low_stock_threshold'] ?? 10),
            'images' => array_filter(array_map('trim', explode(',', $row['variant_images'] ?? '')))
        ];
    }
    fclose($handle);
}

// 4. Processing Loop
$successCount = 0;
$errorCount = 0;

foreach ($products as $pSku => $p) {
    try {
        $pdo->beginTransaction();
        
        // Upsert Product (Robust case-insensitive match)
        $stmt = $pdo->prepare("SELECT id FROM products WHERE LOWER(TRIM(sku)) = LOWER(TRIM(?))");
        $stmt->execute([$pSku]);
        $existing = $stmt->fetch();
        
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $p['name']))) . '-' . substr(md5($pSku), 0, 4);
        
        if ($existing) {
            $productId = $existing['id'];
            $pdo->prepare("UPDATE products SET name=?, slug=?, description=?, category_id=?, price=?, actual_price=?, key_features=? WHERE id=?")
                ->execute([$p['name'], $slug, $p['description'], $p['category_id'], $p['price'], $p['actual_price'], $p['key_features'], $productId]);
            addLog('info', "SKU $pSku: Product record updated successfully.");
        } else {
            $pdo->prepare("INSERT INTO products (sku, name, slug, description, category_id, price, actual_price, key_features) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$pSku, $p['name'], $slug, $p['description'], $p['category_id'], $p['price'], $p['actual_price'], $p['key_features']]);
            $productId = $pdo->lastInsertId();
            addLog('success', "SKU $pSku: New product created successfully.");
        }
        
        // Process Variants
        $allProductImages = [];
        foreach ($p['variants'] as $v) {
            $vSku = $v['sku'];
            $processedImages = [];
            addLog('debug', "Processing SKU $vSku with " . count($v['images']) . " image(s).");
            foreach ($v['images'] as $imgName) {
                $source = findFileRecursive($tempDir, trim($imgName));
                if ($source && file_exists($source)) {
                    addLog('debug', "Found source for $imgName at: $source");
                    $savedName = bulkProcessImage($source, $imgName);
                    if ($savedName) {
                        $processedImages[] = $savedName;
                        $allProductImages[] = $savedName;
                        addLog('debug', "Successfully processed image: $savedName");
                    } else {
                        addLog('error', "Failed to process image: $imgName");
                    }
                } else {
                    addLog('error', "Image NOT FOUND for SKU $vSku: $imgName (Checked recursively in $tempDir)");
                }
            }
            
            $mainImg = !empty($processedImages) ? $processedImages[0] : null;

            $vStmt = $pdo->prepare("SELECT id FROM product_variants WHERE LOWER(TRIM(sku)) = LOWER(TRIM(?))");
            $vStmt->execute([$vSku]);
            $existingVar = $vStmt->fetch();

            if ($existingVar) {
                $variantId = $existingVar['id'];
                $pdo->prepare("UPDATE product_variants SET product_id=?, color_name=?, color_code=?, image_path=? WHERE id=?")
                    ->execute([$productId, $v['name'], $v['code'], $mainImg, $variantId]);
                $pdo->prepare("DELETE FROM product_variant_images WHERE product_variant_id=?")->execute([$variantId]);
            } else {
                $pdo->prepare("INSERT INTO product_variants (product_id, sku, color_name, color_code, image_path) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$productId, $vSku, $v['name'], $v['code'], $mainImg]);
                $variantId = $pdo->lastInsertId();
            }
            
            $insImg = $pdo->prepare("INSERT INTO product_variant_images (product_variant_id, image_path) VALUES (?, ?)");
            foreach ($processedImages as $pi) {
                $insImg->execute([$variantId, $pi]);
            }
            
            if ($mainImg) {
                $pdo->prepare("UPDATE products SET image = ? WHERE id = ?")->execute([$mainImg, $productId]);
                addLog('info', "SKU $vSku: Variant processed with image: $mainImg");
            } else {
                addLog('info', "SKU $vSku: Variant updated (no images provided).");
            }
        }
        
        $pdo->commit();
        $successCount++;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errorCount++;
        addLog('error', "SKU $pSku Error: " . $e->getMessage());
    }
}

// Final Cleanup
if (isset($tempDir)) deleteDirectory($tempDir);

// Re-enable output buffering to clear any garbage
    echo json_encode([
        'success' => $errorCount === 0 && $successCount > 0,
        'logs' => $logs
    ]);

} catch (Throwable $e) {
    // Catch-all for both Exceptions and Fatal Errors (PHP 7+)
    ob_get_clean(); // Discard any partial output
    
    // Add the error to logs
    addLog('error', "Critical System Error: " . $e->getMessage());
    addLog('debug', "File: " . $e->getFile() . " Line: " . $e->getLine());
    
    // Return a clean JSON response
    echo json_encode([
        'success' => false,
        'logs' => $logs
    ]);
}

function bulkProcessImage($source, $originalName) {
    $targetDir = '../assets/uploads/';
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $base = 'bulk_' . uniqid() . '_' . substr(md5($originalName), 0, 6);
    $filename = $base . '.' . $ext;
    $targetPath = $targetDir . $filename;
    
    if (!extension_loaded('gd')) {
        if (copy($source, $targetPath)) {
            addLog('debug', "GD not loaded. Copied file directly to: $targetPath");
            return $filename;
        } else {
            addLog('error', "Failed to copy file from $source to $targetPath");
            return false;
        }
    }

    $mime = mime_content_type($source);
    $img = null;
    switch ($mime) {
        case 'image/jpeg': $img = @imagecreatefromjpeg($source); break;
        case 'image/png': $img = @imagecreatefrompng($source); break;
        case 'image/webp': $img = @imagecreatefromwebp($source); break;
    }

    if (!$img) {
        copy($source, $targetPath);
        return $filename;
    }

    $w = imagesx($img);
    $h = imagesy($img);
    $max = 1920;
    
    if ($w > $max) {
        $ratio = $max / $w;
        $nw = $max;
        $nh = floor($h * $ratio);
        $res = imagecreatetruecolor($nw, $nh);
        if ($mime == 'image/png' || $mime == 'image/webp') {
            imagealphablending($res, false);
            imagesavealpha($res, true);
        }
        imagecopyresampled($res, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($img);
        $img = $res;
        $w = $nw; $h = $nh;
    }

    $saved = saveImageVariant($img, $targetPath, $mime, 80, $w, $h);
    if ($saved) {
        addLog('debug', "Saved processed image: $targetPath");
    } else {
        addLog('error', "Failed to save processed image: $targetPath");
    }
    
    if (function_exists('imagewebp')) {
        $webpSaved = saveImageVariant($img, $targetDir . $base . '.webp', 'image/webp', 80, $w, $h);
        if ($webpSaved) addLog('debug', "Saved WebP variant: " . $base . ".webp");
    }

    imagedestroy($img);
    return $saved ? $filename : false;
}

function deleteDirectory($dir) {
    if (!file_exists($dir)) return true;
    if (!is_dir($dir)) return unlink($dir);
    
    // Clear status cache for directory
    clearstatcache(true, $dir);
    
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') continue;
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
    }
    
    // Attempt multiple times for Windows file lock delays
    $attempts = 0;
    while ($attempts < 3) {
        if (@rmdir($dir)) return true;
        $attempts++;
        usleep(100000); // Wait 100ms
        clearstatcache(true, $dir);
    }
    return false;
}

function findFileRecursive($dir, $filename) {
    if (!is_dir($dir)) return null;
    $files = scandir($dir);
    
    // 1. Try exact match first
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            $res = findFileRecursive($path, $filename);
            if ($res) return $res;
        } elseif (strtolower($file) === strtolower($filename)) {
            return $path;
        }
    }

    // 2. Try fuzzy match (if no exact match, check if filename is part of a file with more extensions)
    // e.g. "aviator.jpg" matching "aviator.jpg.png"
    foreach ($files as $file) {
        if ($file === '.' || $file === '..' || is_dir($dir . DIRECTORY_SEPARATOR . $file)) continue;
        if (stripos($file, $filename) === 0) {
            return $dir . DIRECTORY_SEPARATOR . $file;
        }
    }

    return null;
}
