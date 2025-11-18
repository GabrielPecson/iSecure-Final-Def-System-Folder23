<?php
require '../database/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId = $_POST['vehicle_id'] ?? null;

    if (!$vehicleId) {
        echo json_encode(['success' => false, 'message' => 'Missing vehicle ID.']);
        exit;
    }

    try {
        // Update status + entry_time regardless of current status
        $stmt = $pdo->prepare("UPDATE vehicles 
                               SET status = 'Inside', entry_time = NOW() 
                               WHERE id = :id");
        $stmt->execute([':id' => $vehicleId]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Vehicle entry confirmed']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Vehicle not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
