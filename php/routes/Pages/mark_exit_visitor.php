<?php
require 'auth_check.php';
require 'db_connect.php';
require 'encryption_key.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$visitorId = $_POST['visitor_id'] ?? null;
if (!$visitorId) {
    echo json_encode(['success' => false, 'message' => 'Missing visitor ID.']);
    exit;
}

try {
    // Update time_out and status
    $stmt = $pdo->prepare("UPDATE visitors SET time_out = NOW(), status = 'Exited' WHERE id = :id");
    $stmt->execute([':id' => $visitorId]);

    // Get visitor name from visitor_id
    $visitorStmt = $pdo->prepare("SELECT first_name, middle_name, last_name FROM visitors WHERE id = :vid");
    $visitorStmt->execute([':vid' => $visitorId]);
    $visitorData = $visitorStmt->fetch(PDO::FETCH_ASSOC);

    $visitorName = 'Unknown Visitor';
    if ($visitorData) {
        $visitorName = trim($visitorData['first_name'] . ' ' . $visitorData['middle_name'] . ' ' . $visitorData['last_name']);

        // Update vehicles table status to 'Exited' when visitor exits, matching by vehicle_owner
        $stmt = $pdo->prepare("UPDATE vehicles SET exit_time = NOW(), status = 'Exited' WHERE vehicle_owner = :visitor_name AND status = 'Inside'");
        $stmt->execute([':visitor_name' => $visitorName]);
    }

    // Removed transfer to exited_vehicles table as per user feedback
    // $transferStmt = $pdo->prepare("
    //     INSERT INTO exited_vehicles (visitation_id, vehicle_owner, vehicle_brand, vehicle_model, vehicle_color, plate_number, vehicle_photo_path, entry_time, exit_time, status)
    //     SELECT visitation_id, vehicle_owner, vehicle_brand, vehicle_model, vehicle_color, plate_number, vehicle_photo_path, entry_time, exit_time, status
    //     FROM vehicles
    //     WHERE visitation_id = :vid AND status = 'Exited'
    // ");
    // $transferStmt->execute([':vid' => $visitationId]);

    // Unassign the key card from the visitor, making it available again
    $stmt = $pdo->prepare("UPDATE clearance_badges SET status = 'unassigned', visitor_id = NULL WHERE visitor_id = :vid AND status = 'active'");
    $stmt->execute([':vid' => $visitorId]);

    // Also clear the key card number from the visitor's own record
    $stmt = $pdo->prepare("UPDATE visitors SET key_card_number = NULL WHERE id = :vid");
    $stmt->execute([':vid' => $visitorId]);

    // Send exit SMS to visitor
    $phoneStmt = $pdo->prepare("SELECT contact_number FROM visitors WHERE id = ?");
    $phoneStmt->execute([$visitorId]);
    $visitorPhone = $phoneStmt->fetchColumn();

    if ($visitorPhone) {
        require_once __DIR__ . '/sms_module.php';
        $message = "Thank you for visiting Basa Air Base. You have been successfully checked out. Safe travels!";
        send_sms($visitorPhone, $message);
    }

    $_SESSION['notification_message'] = htmlspecialchars($visitorName) . " has been marked as Exited.";
    $_SESSION['notification_type'] = 'success';
    echo json_encode(['success' => true, 'message' => 'Visitor marked as exited']);
} catch (Exception $e) {
    $_SESSION['notification_message'] = 'Error marking visitor as Exited: ' . $e->getMessage();
    $_SESSION['notification_type'] = 'error';
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
