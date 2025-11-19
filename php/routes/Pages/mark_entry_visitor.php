<?php
require 'auth_check.php';
require 'db_connect.php';
require 'encryption_key.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['notification_message'] = 'Error: Invalid request method.';
    $_SESSION['notification_type'] = 'error';
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$visitorId = $_POST['visitor_id'] ?? null;
if (!$visitorId) {
    $_SESSION['notification_message'] = 'Error: Missing visitor ID.';
    $_SESSION['notification_type'] = 'error';
    echo json_encode(['success' => false, 'message' => 'Missing visitor ID.']);
    exit;
}

try {
    // Update visitor status
    $updateStmt = $pdo->prepare("UPDATE visitors SET time_in = NOW(), status = 'Inside' WHERE id = ?");
    $updateStmt->execute([$visitorId]);

    // Get visitor name from visitor_id
    $visitorStmt = $pdo->prepare("SELECT first_name, middle_name, last_name FROM visitors WHERE id = :vid");
    $visitorStmt->execute([':vid' => $visitorId]);
    $visitorData = $visitorStmt->fetch(PDO::FETCH_ASSOC);

    $visitorName = 'Unknown Visitor';
    if ($visitorData) {
        $visitorName = trim($visitorData['first_name'] . ' ' . $visitorData['middle_name'] . ' ' . $visitorData['last_name']);

        // Update vehicles table status to 'Inside' when visitor is inside, matching by vehicle_owner
        $stmt = $pdo->prepare("UPDATE vehicles SET entry_time = NOW(), status = 'Inside' WHERE vehicle_owner = :visitor_name AND status = 'Expected'");
        $stmt->execute([':visitor_name' => $visitorName]);
    }

    // Fetch visitor's phone number (using contact_number from visitors table)
    $phoneStmt = $pdo->prepare("SELECT contact_number FROM visitors WHERE id = ?");
    $phoneStmt->execute([$visitorId]);
    $visitorPhone = $phoneStmt->fetchColumn();

    if ($visitorPhone) {
        require_once __DIR__ . '/sms_module.php';
        $message = "Welcome to Basa Air Base. Please be advised that you need to leave the premises before 7:00 PM.";
        send_sms($visitorPhone, $message);
    }

    $_SESSION['notification_message'] = htmlspecialchars($visitorName) . " has been marked as Inside.";
    $_SESSION['notification_type'] = 'success';
    echo json_encode(['success' => true, 'message' => 'Visitor marked as Inside']);
} catch (Exception $e) {
    $_SESSION['notification_message'] = 'Error marking visitor as Inside: ' . $e->getMessage();
    $_SESSION['notification_type'] = 'error';
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}