<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/functions.php';

// disable error display in API
ini_set('display_errors', 0);
error_reporting(0);

$date = $_GET['date'] ?? null;

if (!$date) {
    echo json_encode(['error' => 'Date is required']);
    exit;
}

// Validate date format
if (!DateTime::createFromFormat('Y-m-d', $date)) {
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

// Check if Date is in the past
if (strtotime($date) < strtotime(date('Y-m-d'))) {
    echo json_encode(['error' => 'Cannot book in the past']);
    exit;
}

// 1. Get Settings
$start_time = getSetting('appointment_start', '10:00');
$end_time = getSetting('appointment_end', '20:00');
$duration = (int)getSetting('appointment_duration', '30');
$max_slots = (int)getSetting('appointment_max_slots', '2');
$closed_days = json_decode(getSetting('appointment_closed_days', '[]'), true);

// 2. Check if Day is Closed
$dayOfWeek = date('l', strtotime($date));
if (in_array($dayOfWeek, $closed_days)) {
    echo json_encode(['slots' => [], 'message' => 'Closed on ' . $dayOfWeek]);
    exit;
}

// 3. Generate All Possible Slots
$slots = [];
$current = strtotime($date . ' ' . $start_time);
$end = strtotime($date . ' ' . $end_time);

// 4. Get Existing Bookings for this Date
$stmt = $pdo->prepare("SELECT time_slot, COUNT(*) as count FROM appointments WHERE appointment_date = ? AND status != 'cancelled' GROUP BY time_slot");
$stmt->execute([$date]);
$bookings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // ['10:00 AM - 10:30 AM' => 1]

while ($current < $end) {
    $slot_start = date('h:i A', $current);
    $next = $current + ($duration * 60);
    
    if ($next > $end) break;
    
    $slot_end = date('h:i A', $next);
    $slot_label = "$slot_start - $slot_end";
    
    // Check Availability
    $booked_count = $bookings[$slot_label] ?? 0;
    
    if ($booked_count < $max_slots) {
        $slots[] = [
            'time' => $slot_label,
            'available' => $max_slots - $booked_count
        ];
    }

    $current = $next;
}

echo json_encode(['slots' => $slots]);
?>
