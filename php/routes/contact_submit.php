<?php
session_start();
require 'Pages/db_connect.php';

// Create table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Collect form inputs
$name = $_POST['name'] ?? null;
$email = $_POST['email'] ?? null;
$message = $_POST['message'] ?? null;

// Validate required fields
if (!$name || !$message) {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Name and message are required.']);
    } else {
        echo "<script>alert('Name and message are required.'); window.history.back();</script>";
    }
    exit;
}

// Insert into database
$stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message) VALUES (:name, :email, :message)");
$dbSuccess = $stmt->execute([
    ':name' => $name,
    ':email' => $email,
    ':message' => $message
]);

if ($dbSuccess) {
    // Send email
    $to = 'j.c.boadilla2024@gmail.com';
    $subject = 'New Contact Message from Landing Page';
    $body = "Name: $name\nEmail: $email\n\nMessage:\n$message";
    $headers = "From: noreply@yourdomain.com\r\n";

    mail($to, $subject, $body, $headers);

    if ($isAjax) {
        echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);
    } else {
        echo "<script>alert('Message is sent'); window.location.href='landingpage.php';</script>";
    }
} else {
    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => 'Error sending message. Please try again.']);
    } else {
        echo "<script>alert('Error sending message. Please try again.'); window.history.back();</script>";
    }
}
?>
