<?php
require 'auth_check.php';
require '../database/db_connect.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT id, vehicle_owner AS owner_name, vehicle_brand, vehicle_model, vehicle_color, plate_number, entry_time, exit_time, status
        FROM vehicles
        WHERE status = 'Inside'
        ORDER BY entry_time DESC
    ");
    $stmt->execute();
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($vehicles);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}