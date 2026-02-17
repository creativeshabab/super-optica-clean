<?php
// Environment Detection
// START: Automatic Environment Detection
$is_cli = (php_sapi_name() === 'cli');
$is_localhost = false;

if (isset($_SERVER['HTTP_HOST'])) {
    if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1' || strpos($_SERVER['HTTP_HOST'], '192.168') !== false) {
        $is_localhost = true;
    }
}

if ($is_cli || $is_localhost) {
    define('IS_PRODUCTION', false);
} else {
    define('IS_PRODUCTION', true);
}
// END: Automatic Environment Detection

// Load Configuration
if (IS_PRODUCTION) {
    if (file_exists(__DIR__ . '/database.production.php')) {
        require_once __DIR__ . '/database.production.php';
    } else {
        die("Production configuration file missing.");
    }
} else {
    if (file_exists(__DIR__ . '/database.local.php')) {
        require_once __DIR__ . '/database.local.php';
    } else {
        // Fallback or error if local config is missing
        die("Local configuration file missing. Please create config/database.local.php");
    }
}

if (IS_PRODUCTION) {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

try {
    // Added charset to connection string as per best practice
    $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    
    // Set Error Mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set timezone to Indian Standard Time (IST)
    date_default_timezone_set('Asia/Kolkata');
    $pdo->exec("SET time_zone = '+05:30'");
    
    // echo "DB Connected Successfully"; // Verified
    
} catch(PDOException $e) {
    if (IS_PRODUCTION) {
        // Log error secretly if needed
        error_log($e->getMessage());
        die("Site is undergoing maintenance. Please try again later.");
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}
?>
