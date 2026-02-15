<?php
// CRASH DEBUGGER V3
// Mocks Admin Session to test full header execution.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (ob_get_level()) ob_end_clean();

session_start();
// MOCK ADMIN USER
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['email'] = 'debug@test.com';

echo "start...<br>"; flush();

// 1. DB
echo "1. Requiring db.php... "; flush();
require_once '../config/db.php';
echo "✅ OK<br>"; flush();

// 2. Functions
echo "2. Requiring functions.php... "; flush();
require_once '../includes/functions.php';
echo "✅ OK<br>"; flush();

// 3. Admin Header (With fake session)
echo "3. Requiring admin/header.php (Fake Admin Session Active)... "; flush();
try {
    require_once 'header.php'; 
    echo "<br>✅ OK - Admin Header loaded completely.<br>"; flush();
} catch (Throwable $e) {
    echo "<br>❌ CRASH in admin/header.php: " . $e->getMessage() . "<br>";
    echo "Stack Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<br>🏁 FINISHED V3.";
?>
