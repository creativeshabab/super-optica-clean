<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_SESSION['applied_coupon'])) {
        unset($_SESSION['applied_coupon']);
        echo json_encode(['success' => true, 'message' => 'Coupon removed successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No coupon applied.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
