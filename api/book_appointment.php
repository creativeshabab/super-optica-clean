<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON Input
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

$date = $data['date'] ?? null;
$slot = $data['time_slot'] ?? null;
$name = sanitize($data['name'] ?? '');
$phone = sanitize($data['phone'] ?? '');
$email = sanitize($data['email'] ?? '');

if (!$date || !$slot || !$name) {
    echo json_encode(['error' => 'Please complete all steps correctly.']);
    exit;
}

if (!$phone) {
    echo json_encode(['error' => 'Mobile number is mandatory.']);
    exit;
}

if (!$email) {
    echo json_encode(['error' => 'Email address is mandatory for confirmation.']);
    exit;
}

// Double Check Availability (Race Condition Prevention)
$max_slots = (int)getSetting('appointment_max_slots', '2');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND time_slot = ? AND status != 'cancelled'");
$stmt->execute([$date, $slot]);
$current_bookings = $stmt->fetchColumn();

if ($current_bookings >= $max_slots) {
    echo json_encode(['error' => 'Sorry, this slot was just booked by someone else. Please choose another.']);
    exit;
}

// Insert Appointment
try {
    $stmt = $pdo->prepare("INSERT INTO appointments (appointment_date, time_slot, name, phone, email) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$date, $slot, $name, $phone, $email]);

    // Send Confirmation Email
    require_once '../includes/order_templates.php';
    $email_subject = "Appointment Confirmation - Super Optical";
    $email_body = getAppointmentConfirmationTemplate($name, $date, $slot);
    
    // Send to the customer
    sendEmail($email, $email_subject, $email_body);

    // --- Send Notification to Owner ---
    $owner_email = "rakibsupar0786@gmail.com"; 
    $owner_subject = "New Eye Test Booking: $name";
    $owner_body = "
        <h2>New Appointment Booked</h2>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Phone:</strong> $phone</p>
        <p><strong>Date:</strong> $date</p>
        <p><strong>Slot:</strong> $slot</p>
        <p><strong>Email:</strong> $email</p>
        <br>
        <p>Please check the admin panel for more details.</p>
    ";
    sendEmail($owner_email, $owner_subject, $owner_body);
    
    echo json_encode(['success' => true, 'message' => 'Appointment booked successfully!']);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
