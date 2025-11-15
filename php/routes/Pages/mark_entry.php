<?php
require 'db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId = $_POST['id'] ?? null;

    if (!$vehicleId) {
        echo json_encode(['success' => false, 'message' => 'Missing vehicle ID.']);
        exit;
    }

    try {
        // Check if vehicle exists and is currently Expected
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ? AND status = 'Expected' LIMIT 1");
        $stmt->execute([$vehicleId]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vehicle) {
            echo json_encode(['success' => false, 'message' => 'Vehicle not found or already inside.']);
            exit;
        }

        // Update vehicle status to Inside and set entry_time
        $update = $pdo->prepare("UPDATE vehicles SET status = 'Inside', entry_time = NOW() WHERE id = ?");
        $update->execute([$vehicleId]);

        echo json_encode(['success' => true, 'message' => 'Vehicle entry confirmed.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
