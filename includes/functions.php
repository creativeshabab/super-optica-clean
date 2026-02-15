<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Render product preview for listing pages — strip HTML, trim, truncate and escape.
 */
function renderProductPreview($description, $length = 140) {
    $plain = strip_tags($description ?? '');
    $plain = trim($plain);
    if (mb_strlen($plain) > $length) {
        $plain = mb_substr($plain, 0, $length) . '...';
    }
    return htmlspecialchars($plain, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sanitize allowed HTML for product detail description.
 * This removes script/style tags, strips disallowed tags, and removes inline event handlers and javascript: URIs.
 */
function sanitizeAllowedHtml($html, $allowedTags = '<p><br><strong><em><ul><li><ol><a><b><i><h1><h2><h3><h4><h5><h6>') {
    if (!$html) return '';
    // Remove script/style tags entirely
    $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
    $html = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $html);

    // Strip tags except allowed
    $html = strip_tags($html, $allowedTags);

    // Remove inline event handlers like onclick, onerror etc.
    $html = preg_replace('/(<[a-z][^>]*?)\s+on\w+\s*=\s*("|\')[^"\']*("|\')/i', '$1', $html);

    // Neutralize javascript: URIs in href/src attributes
    $html = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript:[^"\']*("|\')/i', '$1="#"', $html);

    return $html;
}

// Flash messages
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Cart functions
function addToCart($id) {
    global $pdo;
    
    // Check if product exists
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if (!$product) return false;

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['quantity']++;
    } else {
        $_SESSION['cart'][$id] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => 1
        ];
    }
    return true;
}

function getCartTotal() {
    $total = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (is_array($item) && isset($item['price'], $item['quantity'])) {
                $total += $item['price'] * $item['quantity'];
            }
        }
    }
    return $total;
}

function getCartCount() {
    $count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (is_array($item) && isset($item['quantity'])) {
                $count += $item['quantity'];
            }
        }
    }
    return $count;
}



/**
 * Get the dynamic base URL of the project
 */
function getBaseURL() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    // Adjust if in root or subfolder
    $path = rtrim($path, '/\\');
    
    // If we're in admin subfolder, go up one level
    if (basename($path) === 'admin' || strpos($path, '/admin') !== false) {
        $path = dirname($path);
    }
    
    return $protocol . '://' . $host . rtrim($path, '/\\') . '/';
}

/**
 * Generate an SEO-friendly product URL
 */
function getProductURL($product) {
    if (!$product) return '#';
    $slug = !empty($product['slug']) ? $product['slug'] : $product['id'];
    
    // Using query string format to ensure compatibility with all server states
    // (mod_rewrite was causing internal server errors)
    $url = getBaseURL() . 'product.php?slug=' . urlencode($slug);
    
    // Add category context if available (from JOIN queries)
    if (!empty($product['category_slug'])) {
        $url .= '&category=' . urlencode($product['category_slug']);
    }
    
    return $url;
}

/**
 * Get a setting value from the database
 */
function getSetting($key, $default = '') {
    global $pdo;
    static $settings = null;
    
    try {
        if ($settings === null) {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        }
    } catch (Exception $e) {
        return $default;
    }
    
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Render SEO-friendly breadcrumbs
 */
function renderBreadcrumbs($items = []) {
    echo '<nav aria-label="breadcrumb" class="breadcrumb-nav">';
    echo '<ol class="breadcrumb-list">';
    
    // Home Icon
    echo '<li>';
    echo '<a href="index.php"><i class="fa-solid fa-house"></i></a>';
    echo '</li>';
    
    foreach ($items as $label => $link) {
        echo '<span class="breadcrumb-separator"><i class="fa-solid fa-chevron-right"></i></span>';
        if ($link) {
            echo '<li><a href="' . $link . '">' . htmlspecialchars($label) . '</a></li>';
        } else {
            echo '<li class="active" aria-current="page">' . htmlspecialchars($label) . '</li>';
        }
    }
    
    echo '</ol>';
    echo '</nav>';
}

/**
 * Initialize Translation System (i18n)
 */
function initLanguage() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check for language change request
    if (isset($_GET['lang'])) {
        $allowed = ['en', 'hi'];
        if (in_array($_GET['lang'], $allowed)) {
            $_SESSION['lang'] = $_GET['lang'];
        }
    }
    
    // Default to English
    $lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
    
    // Load language file
    static $translations = null;
    if ($translations === null) {
        $path = __DIR__ . "/../languages/{$lang}.json";
        if (file_exists($path)) {
            $translations = json_decode(file_get_contents($path), true);
        } else {
            $translations = [];
        }
    }
    
    return $translations;
}

// Global translation function
function __($key) {
    static $current_translations = null;
    if ($current_translations === null) {
        $current_translations = initLanguage();
    }
    return isset($current_translations[$key]) ? $current_translations[$key] : $key;
}

// Admin Notifications
function createAdminNotification($type, $title, $message, $reference_id = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_notifications (type, title, message, reference_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$type, $title, $message, $reference_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Notification Creation Error: " . $e->getMessage());
        return false;
    }
}

// Check and create low stock/out of stock notifications
function checkStockAndNotify($product_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product || $product['stock_quantity'] === null) {
        return; // No tracking for this product
    }
    
    // Check if notification already exists for this product recently (within last hour)
    $existing = $pdo->prepare("SELECT id FROM admin_notifications WHERE reference_id = ? AND type IN ('low_stock', 'out_of_stock') AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $existing->execute([$product_id]);
    
    if ($existing->fetch()) {
        return; // Don't spam notifications
    }
    
    if ($product['stock_quantity'] == 0) {
        createAdminNotification(
            'out_of_stock',
            '⚠️ Product Out of Stock',
            $product['name'] . ' is now out of stock!',
            $product_id
        );
    } elseif ($product['stock_quantity'] <= $product['low_stock_threshold']) {
        createAdminNotification(
            'low_stock',
            '⚡ Low Stock Alert',
            $product['name'] . ' is running low (only ' . $product['stock_quantity'] . ' units left).',
            $product_id
        );
    }
}

// Category Tree Functions
function getCategoryTree() {
    global $pdo;
    
    // Get all categories
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $all_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build tree structure
    $tree = [];
    $lookup = [];
    
    // First pass: create lookup table
    foreach ($all_categories as $cat) {
        $cat['children'] = [];
        $lookup[$cat['id']] = $cat;
    }
    
    // Second pass: build tree
    foreach ($lookup as $id => $cat) {
        if ($cat['parent_id'] === null) {
            $tree[] = &$lookup[$id];
        } else {
            if (isset($lookup[$cat['parent_id']])) {
                $lookup[$cat['parent_id']]['children'][] = &$lookup[$id];
            }
        }
    }
    
    return $tree;
}

function getCategoryBreadcrumb($category_id) {
    global $pdo;
    
    $breadcrumb = [];
    $current_id = $category_id;
    
    while ($current_id) {
        $stmt = $pdo->prepare("SELECT id, name, slug, parent_id FROM categories WHERE id = ?");
        $stmt->execute([$current_id]);
        $cat = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cat) {
            array_unshift($breadcrumb, $cat);
            $current_id = $cat['parent_id'];
        } else {
            break;
        }
    }
    
    return $breadcrumb;
}

function getCategoriesHierarchical() {
    global $pdo;
    
    $categories = [];
    $stmt = $pdo->query("
        SELECT c.*, p.name as parent_name 
        FROM categories c 
        LEFT JOIN categories p ON c.parent_id = p.id 
        ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NULL DESC, c.name
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = $row;
    }
    
    return $categories;
}

function getAllChildCategoryIds($parent_id) {
    global $pdo;
    
    $ids = [$parent_id];
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE parent_id = ?");
    $stmt->execute([$parent_id]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $ids = array_merge($ids, getAllChildCategoryIds($row['id']));
    }
    
    return $ids;
}

/**
 * Send Email using PHPMailer
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email HTML body
 * @param bool $debug Enable debug mode (default: false)
 * @return bool True on success, false on failure
 */
function sendEmail($to, $subject, $body, $debug = false) {
    // Load Configuration
    $config = require __DIR__ . '/../config/email.php';
    
    // Load PHPMailer manually (if composer not used)
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Enable debug mode if requested
        if ($debug) {
            $mail->SMTPDebug = 2; // Verbose debug output
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug [$level]: $str");
            };
        }
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['encryption']; // ssl or tls
        $mail->Port       = $config['port'];
        $mail->CharSet    = 'UTF-8';

        // Fix for XAMPP SSL Issues
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        
        // Log successful email
        error_log("Email sent successfully to: $to | Subject: $subject");
        
        return true;
    } catch (Exception $e) {
        // Detailed error logging
        $error_details = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'subject' => $subject,
            'error' => $mail->ErrorInfo,
            'exception' => $e->getMessage(),
            'smtp_host' => $config['host'],
            'smtp_port' => $config['port'],
            'smtp_encryption' => $config['encryption']
        ];
        
        // Log to PHP error log
        error_log("=== EMAIL SEND FAILURE ===");
        error_log("Timestamp: " . $error_details['timestamp']);
        error_log("To: " . $error_details['to']);
        error_log("Subject: " . $error_details['subject']);
        error_log("PHPMailer Error: " . $error_details['error']);
        error_log("Exception: " . $error_details['exception']);
        error_log("SMTP Config: " . $error_details['smtp_host'] . ":" . $error_details['smtp_port'] . " (" . $error_details['smtp_encryption'] . ")");
        error_log("========================");
        
        // Store last error in session for debugging
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['last_email_error'] = $error_details;
        }
        
        return false;
    }
}

/**
 * Get the last email error details
 * 
 * @return array|null Error details or null if no error
 */
function getLastEmailError() {
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['last_email_error'])) {
        return $_SESSION['last_email_error'];
    }
    return null;
}

/**
 * Clear the last email error
 */
function clearLastEmailError() {
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['last_email_error'])) {
        unset($_SESSION['last_email_error']);
    }
}

/**
 * Advanced Image Upload with Optimization
 * - Enforces max width of 1920px to save space
 * - Creates multiple size variants (thumbnail, medium, large)
 * - Converts to WebP for better compression
 * - Maintains original format as fallback
 * - Automatic quality optimization
 * 
 * @param array $file The $_FILES['input_name'] array
 * @param string $targetDir The directory to save the file to (e.g. '../assets/uploads/')
 * @param string|null $customName Optional custom filename (without extension)
 * @param array $sizes Array of sizes to generate ['thumb' => 150, 'medium' => 600]
 * @param int $quality JPEG/WebP quality (0-100, default 80)
 * @return string|false The saved filename on success, false on failure
 */
function optimizeUpload($file, $targetDir, $customName = null, $sizes = null, $quality = 80) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // Default sizes if not provided
    if ($sizes === null) {
        $sizes = [
            'thumb' => 150,
            'medium' => 600,
            'large' => 1200
        ];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        return false; // Invalid file type
    }

    // Generate filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $baseName = $customName ? $customName : uniqid();
    $filename = $baseName . '.' . $ext;
    $targetPath = rtrim($targetDir, '/') . '/' . $filename;

    // Check if GD is enabled
    if (!extension_loaded('gd')) {
        // Fallback: Just move the file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $filename;
        }
        return false;
    }

    // Load Image
    $image = null;
    switch ($mimeType) {
        case 'image/jpeg': 
        case 'image/jpg':
            $image = @imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/webp':
            $image = @imagecreatefromwebp($file['tmp_name']);
            break;
        default:
            return false;
    }

    if (!$image) return false;

    // Get original dimensions
    $origWidth = imagesx($image);
    $origHeight = imagesy($image);

    // Enforce Max Width (1920px) for "Original" to save space
    $maxWidth = 1920;
    if ($origWidth > $maxWidth) {
        $ratio = $maxWidth / $origWidth;
        $newHeight = floor($origHeight * $ratio);
        
        $resizedMain = imagecreatetruecolor($maxWidth, $newHeight);
        
        // Preserve transparency
        if ($mimeType == 'image/png' || $mimeType == 'image/webp') {
            imagealphablending($resizedMain, false);
            imagesavealpha($resizedMain, true);
        }
        
        imagecopyresampled($resizedMain, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $origWidth, $origHeight);
        
        // Replace original resource with resized one
        imagedestroy($image);
        $image = $resizedMain;
        
        // Update dimensions
        $origWidth = $maxWidth;
        $origHeight = $newHeight;
    }

    // Save "Original" (Optimized & Resized)
    $saved = saveImageVariant($image, $targetPath, $mimeType, $quality, $origWidth, $origHeight);
    
    // Create WebP version of Original
    if (function_exists('imagewebp')) {
        $webpPath = rtrim($targetDir, '/') . '/' . $baseName . '.webp';
        saveImageVariant($image, $webpPath, 'image/webp', 80, $origWidth, $origHeight);
    }

    // Generate size variants
    foreach ($sizes as $sizeName => $msgWidth) {
        if ($origWidth > $msgWidth) {
            $ratio = $msgWidth / $origWidth;
            $newWidth = $msgWidth;
            $newHeight = floor($origHeight * $ratio);

            // Create resized image
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency
            if ($mimeType == 'image/png' || $mimeType == 'image/webp') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

            // Save variant in original format
            $variantPath = rtrim($targetDir, '/') . '/' . $baseName . '-' . $sizeName . '.' . $ext;
            saveImageVariant($resized, $variantPath, $mimeType, $quality, $newWidth, $newHeight);

            // Save variant in WebP
            if (function_exists('imagewebp')) {
                $webpVariantPath = rtrim($targetDir, '/') . '/' . $baseName . '-' . $sizeName . '.webp';
                saveImageVariant($resized, $webpVariantPath, 'image/webp', 80, $newWidth, $newHeight);
            }

            imagedestroy($resized);
        }
    }

    imagedestroy($image);

    return $saved ? $filename : false;
}

/**
 * Get category hierarchy path (root -> child)
 * @param int $category_id
 * @return array [[id, name, slug], ...]
 */
function getCategoryHierarchy($category_id) {
    global $pdo;
    $path = [];
    $currentId = $category_id;
    
    while ($currentId) {
        $stmt = $pdo->prepare("SELECT id, name, slug, parent_id FROM categories WHERE id = ?");
        $stmt->execute([$currentId]);
        $cat = $stmt->fetch();
        
        if ($cat) {
            array_unshift($path, $cat); // Add to beginning of array
            $currentId = $cat['parent_id'];
        } else {
            break;
        }
    }
    
    return $path;
}
function saveImageVariant($image, $path, $mimeType, $quality, $width, $height) {
    if (!$image) return false;
    
    switch ($mimeType) {
        case 'image/jpeg': 
        case 'image/jpg':
            return imagejpeg($image, $path, $quality); 
        case 'image/png':
            // PNG quality is 0-9 (compression level), mapped from 0-100
            $qt = 9 - round(($quality / 100) * 9); 
            // Ensure bounds
            $qt = max(0, min(9, $qt));
            // Create proper PNG resource options
            imagealphablending($image, false);
            imagesavealpha($image, true);
            return imagepng($image, $path, $qt); 
        case 'image/webp':
            if (function_exists('imagewebp')) {
                return imagewebp($image, $path, $quality);
            }
            break;
    }
    
    return false;
}

/**
 * Get optimized image path (WebP if available, fallback to original)
 */
function getOptimizedImage($imagePath, $size = null) {
    if (!$imagePath) return '';
    
    $pathInfo = pathinfo($imagePath);
    $baseName = $pathInfo['filename'];
    $dir = $pathInfo['dirname'];
    
    // Build WebP path
    $webpName = $size ? $baseName . '-' . $size . '.webp' : $baseName . '.webp';
    $webpPath = $dir . '/' . $webpName;
    
    // Check if WebP exists
    if (file_exists($webpPath)) {
        return $webpPath;
    }
    
    // Fallback to original or sized version
    if ($size) {
        $sizedPath = $dir . '/' . $baseName . '-' . $size . '.' . $pathInfo['extension'];
        if (file_exists($sizedPath)) {
            return $sizedPath;
        }
    }
    
    return $imagePath;
}

/**
 * Generate responsive srcset attribute
 */
function generateSrcset($imagePath, $sizes = ['thumb', 'medium', 'large']) {
    if (!$imagePath) return '';
    
    $pathInfo = pathinfo($imagePath);
    $baseName = $pathInfo['filename'];
    $ext = $pathInfo['extension'];
    $dir = $pathInfo['dirname'];
    
    $srcset = [];
    $sizeWidths = ['thumb' => '150w', 'medium' => '600w', 'large' => '1200w'];
    
    foreach ($sizes as $size) {
        $webpPath = $dir . '/' . $baseName . '-' . $size . '.webp';
        $fallbackPath = $dir . '/' . $baseName . '-' . $size . '.' . $ext;
        
        if (file_exists($webpPath)) {
            $srcset[] = $webpPath . ' ' . $sizeWidths[$size];
        } elseif (file_exists($fallbackPath)) {
            $srcset[] = $fallbackPath . ' ' . $sizeWidths[$size];
        }
    }
    
    return implode(', ', $srcset);
}

/**
 * Cleanup old image variants when deleting
 */
function deleteImageVariants($imagePath) {
    if (!$imagePath || !file_exists($imagePath)) return false;
    
    $pathInfo = pathinfo($imagePath);
    $baseName = $pathInfo['filename'];
    $dir = $pathInfo['dirname'];
    
    // Delete original
    @unlink($imagePath);
    
    // Delete WebP version
    @unlink($dir . '/' . $baseName . '.webp');
    
    // Delete all size variants
    $sizes = ['thumb', 'medium', 'large'];
    $extensions = ['jpg', 'jpeg', 'png', 'webp'];
    
    foreach ($sizes as $size) {
        foreach ($extensions as $ext) {
            @unlink($dir . '/' . $baseName . '-' . $size . '.' . $ext);
        }
    }
    
    return true;
}
/**
 * Calculate average rating from reviews
 */
function getAverageRating() {
    global $pdo;
    static $avg = null;
    
    if ($avg !== null) return $avg;
    
    try {
        $stmt = $pdo->query("SELECT AVG(rating) as average, COUNT(*) as count FROM reviews");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || $result['count'] == 0) {
            $avg = ['score' => 0, 'count' => 0];
        } else {
            $avg = [
                'score' => round($result['average'], 1),
                'count' => $result['count']
            ];
        }
    } catch (Exception $e) {
        $avg = ['score' => 0, 'count' => 0];
    }
    
    return $avg;
}

// Hex to RGB Helper
function hex2rgb($hex) {
    $hex = str_replace("#", "", $hex);
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    return "$r, $g, $b";
}

/**
 * Render a premium Lenskart-style product card
 */
function renderProductCard($p) {
    $url = getProductURL($p);
    $price = number_format($p['price'], 0);
    $actual_price = isset($p['actual_price']) ? $p['actual_price'] : null;
    $description = $p['description'] ?? 'Premium quality eyewear designed for durability and comfort.';
    
    // Calculate Discount
    $discount = 0;
    if ($actual_price && $actual_price > $p['price']) {
        $discount = round((($actual_price - $p['price']) / $actual_price) * 100);
    }
    
    // Rating (Fallback for demo if no reviews yet)
    $rating_score = '4.8'; 
    $rating_count = '2.4k';
    
    // Labels/Badges (Lenskart labels are often "Trending" or "New")
    $label = isset($p['label']) ? $p['label'] : 'Trending';
    ?>
    <div class="product-card-premium group relative h-full">
        <div class="card-inner bg-white border border-gray-100 rounded-2xl overflow-hidden transition-all duration-300 hover:shadow-xl flex flex-col h-[450px]">
        
            <!-- Product Image (40% Height) -->
            <a href="<?= $url ?>" class="block relative h-[40%] bg-white p-0 overflow-hidden">
                <?php if($p['image']): ?>
                    <img src="assets/uploads/<?= $p['image'] ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="w-full h-full object-cover transform transition-transform duration-500 group-hover:scale-110">
                <?php else: ?>
                    <img src="https://i.ibb.co/3sxh1gV/glass-placeholder.png" class="w-full h-full object-cover opacity-50" alt="Product">
                <?php endif; ?>
            </a>

            <!-- Card Body (60% Height) -->
            <div class="card-body p-6 pt-0 flex flex-col h-[60%]">

                <!-- Product Info -->
                <a href="<?= $url ?>" class="block mb-2">
                    <h3 class="product-brand text-gray-900 font-black text-xl leading-tight mb-1 truncate"><?= htmlspecialchars($p['name']) ?></h3>
                    <p class="text-gray-500 text-sm mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= htmlspecialchars(strip_tags($description)) ?></p>
                </a>

                <!-- Price Block -->
                <div class="price-container flex items-center gap-3">
                    <span class="final-price text-indigo-900 font-black text-2xl tracking-tighter">₹<?= $price ?></span>
                    <?php if($discount > 0): ?>
                        <span class="old-price text-gray-300 line-through font-bold text-lg">₹<?= number_format($actual_price, 0) ?></span>
                        <span class="discount-tag text-cyan-400 font-black text-lg">(<?= $discount ?>% OFF)</span>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons (Push to bottom) -->
                <div class="mt-auto flex gap-2 pt-4">
                     <button onclick="window.location.href='cart.php?add=<?= $p['id'] ?>&redirect=checkout.php'" class="btn btn-primary flex-1 text-sm py-2">
                        Buy Now
                    </button>
                    <button onclick="addToCart(<?= $p['id'] ?>)" class="btn btn-outline flex-1 text-sm py-2">
                        Add Cart
                    </button>
                </div>

            </div>
        </div>
    </div>
    <?php
}

/**
 * Render a premium blog card consistent with the homepage
 */
function renderBlogCard($post) {
    if (!$post) return '';
    $date = date('M d', strtotime($post['created_at']));
    $imageHtml = '';
    
    if ($post['image']) {
        $imageHtml = '
        <div class="aspect-video overflow-hidden relative">
            <img src="assets/uploads/' . htmlspecialchars($post['image']) . '" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" alt="' . htmlspecialchars($post['title']) . '">
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300"></div>
        </div>';
    } else {
        $imageHtml = '
        <div class="aspect-video overflow-hidden relative bg-gray-100 flex items-center justify-center">
            <i class="fa-solid fa-newspaper text-4xl text-gray-300"></i>
        </div>';
    }

    echo '
    <div class="blog-card-premium h-full group">
        <div class="card-inner h-full flex flex-col bg-surface rounded-2xl overflow-hidden shadow-sm transition-all duration-300 hover:shadow-xl">
            ' . $imageHtml . '
            <div class="p-6 flex flex-col flex-1">
                <div class="flex items-center gap-2 mb-3">
                    <span class="px-2 py-1 bg-gray-100 text-xs font-bold uppercase tracking-wider text-gray-500 rounded-md">
                        ' . $date . '
                    </span>
                </div>
                <h3 class="text-xl font-black text-gray-800 mb-3 line-clamp-2 leading-tight group-hover:text-primary transition-colors">
                    <a href="blog.php?id=' . $post['id'] . '">' . htmlspecialchars($post['title']) . '</a>
                </h3>
                <div class="mt-auto pt-4 border-t border-gray-100">
                    <a href="blog.php?id=' . $post['id'] . '" class="text-primary font-bold text-sm hover:underline inline-flex items-center gap-1">
                        ' . __('read_more') . ' <i class="fa-solid fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>';
}
