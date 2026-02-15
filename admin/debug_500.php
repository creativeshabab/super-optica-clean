<?php
// Prevent 500 generic error by forcing output
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Turn off output buffering to see progress immediately
if (ob_get_level()) ob_end_clean();

echo "<h1>🔍 Deep Level Debugger</h1>";
echo "Timestamp: " . date('Y-m-d H:i:s') . "<br>";
flush();

// STEP 1: Check File Existence
echo "<h2>1. File System Check</h2>";
$files = [
    '../config/db.php',
    '../config/database.production.php',
    '../includes/functions.php',
    '../includes/IntegrationManager.php',
    '../includes/shiprocket/ShiprocketAPI.php'
];

foreach ($files as $f) {
    if (file_exists($f)) {
        echo "✅ Found: $f (Size: " . filesize($f) . " bytes)<br>";
    } else {
        echo "❌ <strong style='color:red'>MISSING: $f</strong><br>";
    }
}
flush();

// STEP 2: Manual DB Connection (Bypassing db.php)
echo "<h2>2. Direct Database Connection Logic</h2>";
echo "Attempting to connect with Production credentials directly...<br>";

// HARDCODED CREDENTIALS (FROM database.production.php)
$host = 'localhost';
$db_name = 'superopt1_db';
$username = 'superopt1_user1';
$password = 'Saima@143143';

try {
    $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ <strong style='color:green'>Database Connection SUCCESSFUL!</strong><br>";
} catch (PDOException $e) {
    echo "❌ <strong style='color:red'>Database Connection FAILED</strong><br>";
    echo "Error: " . $e->getMessage() . "<br>";
    die("Stopping here due to DB Connection failure.");
}
flush();

// STEP 3: Test Tables
echo "<h2>3. Table Existence Check</h2>";
$tables = ['orders', 'users', 'shipping_methods', 'service_integrations'];
foreach ($tables as $table) {
    try {
        $pdo->query("SELECT 1 FROM $table LIMIT 1");
        echo "✅ Table '$table' exists.<br>";
    } catch (PDOException $e) {
        echo "❌ <strong style='color:red'>Table '$table' is MISSING or Error: " . $e->getMessage() . "</strong><br>";
    }
}
flush();

// STEP 4: Test problematic Query
echo "<h2>4. Testing Order View Query (ID: 23)</h2>";
$sql = "
    SELECT o.*, u.name, u.email, sm.name as shipping_method_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id 
    WHERE o.id = 23
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($order) {
        echo "✅ Query Successful. Order found.<br>";
        echo "Shipping Method in DB: " . ($order['shipping_method_name'] ?? 'NULL') . "<br>";
    } else {
        echo "⚠️ Query ran, but no order found with ID 23.<br>";
    }
} catch (PDOException $e) {
    echo "❌ <strong style='color:red'>Query FAILED:</strong> " . $e->getMessage() . "<br>";
}
flush();

// STEP 5: Test PHP Include (Only if we get this far)
echo "<h2>5. Testing Includes (Potential Crash Point)</h2>";
echo "Attempting to include config/db.php... ";
flush();

// We use try/catch just in case, but parse errors won't be caught
try {
    include('../config/db.php'); 
    echo "✅ Success.<br>";
} catch (Throwable $t) {
    echo "❌ CRASHED: " . $t->getMessage() . "<br>";
}

echo "<h3>Debug Complete.</h3>";
?>
