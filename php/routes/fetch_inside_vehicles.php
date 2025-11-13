<?php
require 'auth_check.php';
require '../database/db_connect.php';
require '../config/encryption_key.php';

header('Content-Type: application/json');

try {
    // Make status comparison case-insensitive
    $stmt = $pdo->prepare("SELECT id, vehicle_owner AS driver_name, vehicle_brand, vehicle_model, vehicle_color, plate_number, entry_time, exit_time, status
                           FROM vehicles
                           WHERE status = 'Inside'
                           ORDER BY entry_time DESC");
    $stmt->execute();
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decrypt vehicle data for display
    foreach ($vehicles as &$vehicle) {
        $vehicle['driver_name'];
        $vehicle['vehicle_brand'];
        $vehicle['vehicle_model'];
        $vehicle['vehicle_color'];
        $vehicle['plate_number'];
    }

    echo json_encode($vehicles);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
