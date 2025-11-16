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
    // Assign key card
    $keyCardNumber = 'KC-' . strtoupper(substr(md5(uniqid()), 0, 8));

    // Update visitor with key card and status
    $updateStmt = $pdo->prepare("UPDATE visitors SET time_in = NOW(), status = 'Inside', key_card_number = ? WHERE id = ?");
    $updateStmt->execute([$keyCardNumber, $visitorId]);

    // Get visitor name from visitor_id
    $visitorStmt = $pdo->prepare("SELECT first_name, middle_name, last_name FROM visitors WHERE id = :vid");
    $visitorStmt->execute([':vid' => $visitorId]);
    $visitorData = $visitorStmt->fetch(PDO::FETCH_ASSOC);

    if ($visitorData) {
        $visitorName = trim($visitorData['first_name'] . ' ' . $visitorData['middle_name'] . ' ' . $visitorData['last_name']);

        // Update vehicles table status to 'Inside' when visitor is inside, matching by vehicle_owner
        $stmt = $pdo->prepare("UPDATE vehicles SET entry_time = NOW(), status = 'Inside' WHERE vehicle_owner = :visitor_name AND status = 'Expected'");
        $stmt->execute([':visitor_name' => $visitorName]);
    }

    // Insert into clearance_badges
    $badgeStmt = $pdo->prepare("INSERT INTO clearance_badges (visitor_id, clearance_level, key_card_number, validity_start, validity_end, status) VALUES (?, 'Visitor', ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), 'Active')");
    $badgeStmt->execute([$visitorId, $keyCardNumber]);

    // Removed transfer to inside_vehicles table as per user feedback
    // $transferStmt = $pdo->prepare("
    //     INSERT INTO inside_vehicles (visitation_id, vehicle_owner, vehicle_brand, vehicle_model, vehicle_color, plate_number, vehicle_photo_path, entry_time, status)
    //     SELECT visitation_id, vehicle_owner, vehicle_brand, vehicle_model, vehicle_color, plate_number, vehicle_photo_path, entry_time, status
    //     FROM vehicles
    //     WHERE visitation_id = :vid AND status = 'Inside'
    // ");
    // $transferStmt->execute([':vid' => $visitationId]);

    // Fetch visitor's phone number
    $phoneStmt = $pdo->prepare("SELECT phone_number FROM visitors WHERE id = ?");
    $phoneStmt->execute([$visitorId]);
    $visitorPhone = $phoneStmt->fetchColumn();

    if ($visitorPhone) {
        // Send SMS
        // Corrected the path to sms_module.php. Since it's in the same directory,
        // a direct include is sufficient. Using __DIR__ makes it more robust.
        require_once __DIR__ . 'sms_module.php';
        $message = "Welcome to Basa Air Base. Please be advised that you need to leave the premises before 7:00 PM.";
        send_sms($visitorPhone, $message);
    }

    echo json_encode(['success' => true, 'message' => 'Visitor marked as Inside']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}