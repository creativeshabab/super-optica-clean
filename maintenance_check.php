<?php
/**
 * Maintenance Health Check Script
 * URL: /maintenance_check.php?key=YOUR_SECRET_KEY
 * Intended for Uptime Monitors or Cron Jobs
 */

require_once 'config/db.php';

// Simple API Key Protection (Change this in production)
$secret = 'super_optica_health';
if (($_GET['key'] ?? '') !== $secret && php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Access Denied');
}

$status = [];
$errors = [];

// 1. Database Check
try {
    $start = microtime(true);
    $pdo->query("SELECT 1");
    $duration = round((microtime(true) - $start) * 1000, 2);
    $status['database'] = "OK ({$duration}ms)";
} catch (PDOException $e) {
    $status['database'] = "FAIL";
    $errors[] = "DB: " . $e->getMessage();
}

// 2. Disk Space Check (Drive C: or /)
$free_space = disk_free_space(".");
$total_space = disk_total_space(".");
$percent_free = ($free_space / $total_space) * 100;

$status['disk_free'] = round($free_space / 1024 / 1024 / 1024, 2) . " GB";
$status['disk_percent'] = round($percent_free, 2) . "%";

if($percent_free < 10) {
    $errors[] = "Disk space critically low (<10%)";
}

// 3. Error Log Check
$log_file = ini_get('error_log');
if ($log_file && file_exists($log_file)) {
    $size = filesize($log_file);
    $status['error_log_size'] = round($size / 1024 / 1024, 2) . " MB";
    if ($size > 100 * 1024 * 1024) { // 100MB
        $errors[] = "Error log file is too large (>100MB)";
    }
} else {
    $status['error_log'] = "Not Found / Not Set";
}

// Response
header('Content-Type: application/json');
$response = [
    'timestamp' => date('c'),
    'system' => 'Super Optica',
    'status' => empty($errors) ? 'HEALTHY' : 'CRITICAL',
    'checks' => $status,
    'issues' => $errors
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
