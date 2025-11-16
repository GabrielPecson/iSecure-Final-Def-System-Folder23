<?php
require 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_POST['vehicle_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing vehicle ID']);
    exit;
}

$vehicleId = intval($_POST['vehicle_id']);

try {
    $stmt = $pdo->prepare("
        UPDATE vehicles 
        SET status='Exited', exit_time=NOW()
        WHERE id = :id AND status='Inside'
    ");
    $stmt->execute([':id' => $vehicleId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Vehicle not found or already exited']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
}
