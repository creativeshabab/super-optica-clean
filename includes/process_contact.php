<?php
/**
 * Contact Form Processing Script
 * Handles contact form submissions from contact.php
 */

require_once '../config/db.php';
require_once 'functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request method');
    redirect('../contact.php');
}

// Sanitize and validate input
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
} elseif (strlen($name) < 2) {
    $errors[] = 'Name must be at least 2 characters';
} elseif (strlen($name) > 255) {
    $errors[] = 'Name is too long';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address';
}

if (empty($subject)) {
    $errors[] = 'Subject is required';
} elseif (strlen($subject) < 3) {
    $errors[] = 'Subject must be at least 3 characters';
} elseif (strlen($subject) > 500) {
    $errors[] = 'Subject is too long';
}

if (empty($message)) {
    $errors[] = 'Message is required';
} elseif (strlen($message) < 10) {
    $errors[] = 'Message must be at least 10 characters';
} elseif (strlen($message) > 5000) {
    $errors[] = 'Message is too long';
}

// If there are validation errors, redirect back with errors
if (!empty($errors)) {
    setFlash('error', implode('<br>', $errors));
    redirect('../contact.php');
}

// Rate limiting - prevent spam (max 3 submissions per hour from same IP)
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));

$rate_check = $pdo->prepare("SELECT COUNT(*) FROM contact_messages WHERE ip_address = ? AND created_at > ?");
$rate_check->execute([$ip_address, $one_hour_ago]);
$recent_count = $rate_check->fetchColumn();

if ($recent_count >= 3) {
    setFlash('error', 'Too many submissions. Please try again later.');
    redirect('../contact.php');
}

// Get user agent
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Insert into database
try {
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (name, email, subject, message, ip_address, user_agent, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'new')
    ");
    
    $stmt->execute([
        htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
        $ip_address,
        substr($user_agent, 0, 500)
    ]);
    
    // Optional: Send email notification to admin
    // You can uncomment and configure this section if you want email notifications
    /*
    $to = 'info@superoptical.in';
    $email_subject = 'New Contact Form Submission: ' . $subject;
    $email_body = "Name: $name\n";
    $email_body .= "Email: $email\n";
    $email_body .= "Subject: $subject\n\n";
    $email_body .= "Message:\n$message\n";
    $headers = "From: noreply@superoptical.in\r\n";
    $headers .= "Reply-To: $email\r\n";
    
    mail($to, $email_subject, $email_body, $headers);
    */
    
    setFlash('success', 'Thank you for contacting us! We will get back to you soon.');
    redirect('../contact.php');
    
} catch (PDOException $e) {
    error_log('Contact form error: ' . $e->getMessage());
    setFlash('error', 'An error occurred while sending your message. Please try again later.');
    redirect('../contact.php');
}
