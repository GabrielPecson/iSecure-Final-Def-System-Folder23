<?php
require 'auth_check.php';
require '../database/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visitorId = $_POST['visitor_id'] ?? null;

    if (!$visitorId) {
        echo json_encode(['success' => false, 'message' => 'Missing visitor ID.']);
        exit;
    }

    try {
        // Fetch visitor info from visitors table
        $stmt = $pdo->prepare("SELECT * FROM visitors WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $visitorId]);
        $visitor = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$visitor) {
            echo json_encode(['success' => false, 'message' => 'Visitor not found']);
            exit;
        }

        // Update time_out, status, and optionally validity in visitors table
        $validityStart = $_POST['validity_start'] ?? null;
        $validityEnd = $_POST['validity_end'] ?? null;

        if ($validityStart && $validityEnd) {
            // Extract time from validity_end for time_out
            $timeOutFromValidity = date('H:i:s', strtotime($validityEnd));
            $stmt = $pdo->prepare("
                UPDATE visitors
                SET time_out = :timeout,
                    validity_start = :validstart,
                    validity_end = :validend
                WHERE id = :id
            ");
            $stmt->execute([
                ':timeout' => $timeOutFromValidity,
                ':validstart' => $validityStart,
                ':validend' => $validityEnd,
                ':id' => $visitorId
            ]);
        } else if ($validityStart) {
            // If only validity_start is provided, update it without changing time_out
            $stmt = $pdo->prepare("
                UPDATE visitors
                SET validity_start = :validstart
                WHERE id = :id
            ");
            $stmt->execute([
                ':validstart' => $validityStart,
                ':id' => $visitorId
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE visitors
                SET time_out = NOW()
                WHERE id = :id
            ");
            $stmt->execute([':id' => $visitorId]);
        }

        // Sync to vehicles table if visitor has a vehicle
        if (!empty($visitor['plate_number'])) {
            $stmt = $pdo->prepare("
                UPDATE vehicles
                SET exit_time = NOW()
                WHERE plate_number = :plate AND status = 'Inside'
            ");
            $stmt->execute([':plate' => $visitor['plate_number']]);
        }

        echo json_encode(['success' => true, 'message' => 'Visitor exit time updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}