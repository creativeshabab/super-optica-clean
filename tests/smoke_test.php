<?php
/**
 * Automated Smoke Test for Super Optica
 * Usage: php tests/smoke_test.php
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$tests_passed = 0;
$tests_total = 0;

function assertCheck($condition, $message) {
    global $tests_passed, $tests_total;
    $tests_total++;
    if ($condition) {
        $tests_passed++;
        echo "✅ PASS: $message\n";
    } else {
        echo "❌ FAIL: $message\n";
    }
}

echo "Starting Smoke Tests...\n\n";

// 1. Check Config Constants
assertCheck(defined('IS_PRODUCTION'), "Environment Validation (IS_PRODUCTION defined)");
assertCheck(defined('DB_HOST') || isset($host), "Database Config Loaded");

// 2. Check Database Connection
try {
    $pdo->query("SELECT 1");
    assertCheck(true, "Database Connection Successful");
} catch (PDOException $e) {
    assertCheck(false, "Database Connection Failed: " . $e->getMessage());
}

// 3. Check Critical Tables exist
$tables = ['users', 'products', 'orders', 'order_items', 'coupons'];
foreach ($tables as $table) {
    try {
        $pdo->query("SELECT 1 FROM $table LIMIT 1");
        assertCheck(true, "Table '$table' is accessible");
    } catch (PDOException $e) {
        assertCheck(false, "Table '$table' missing or verification failed");
    }
}

// 4. Check File Permissions / Existence
$dirs = ['assets/uploads', 'logs', 'config'];
foreach ($dirs as $dir) {
    $path = __DIR__ . '/../' . $dir;
    assertCheck(file_exists($path), "Directory '$dir' exists");
    if (file_exists($path)) {
        assertCheck(is_writable($path), "Directory '$dir' is writable");
    }
}

// 5. Check Critical Functions
assertCheck(function_exists('csrfField'), "Function 'csrfField' exists");
assertCheck(function_exists('validateCSRFToken'), "Function 'validateCSRFToken' exists");
assertCheck(function_exists('optimizeUpload'), "Function 'optimizeUpload' exists");

echo "\nSummary: $tests_passed / $tests_total Tests Passed.\n";

if ($tests_passed === $tests_total) {
    exit(0);
} else {
    exit(1);
}
?>
