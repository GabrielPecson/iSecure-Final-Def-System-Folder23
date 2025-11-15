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

    // Update clearance_badges status to terminated for the visitor's key card
    $stmt = $pdo->prepare("UPDATE clearance_badges SET status = 'terminated' WHERE visitor_id = :vid AND status = 'active'");
    $stmt->execute([':vid' => $visitorId]);

    echo json_encode(['success' => true, 'message' => 'Visitor marked as exited']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}