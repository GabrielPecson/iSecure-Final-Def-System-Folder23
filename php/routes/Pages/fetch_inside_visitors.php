<?php
require 'db_connect.php';
require 'encryption_key.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            first_name,
            middle_name,
            last_name,
            contact_number,
            key_card_number,
            time_in,
            time_out,
            status
        FROM visitors
        WHERE time_in IS NOT NULL AND time_out IS NULL AND (status != 'Cancelled' OR status IS NULL OR status = '')
    ");
    $stmt->execute();
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decrypt sensitive data for display
    foreach ($visitors as &$visitor) {
        // Data is already in plain text
        // Data is already in plain text

        // Construct full name for matching with vehicle_owner
        $full_name = trim($visitor['first_name'] . ' ' . $visitor['middle_name'] . ' ' . $visitor['last_name']);

        // Fetch associated vehicle if exists
        $vehicleStmt = $pdo->prepare("
            SELECT vehicle_brand, plate_number, vehicle_color, vehicle_model
            FROM vehicles
            WHERE vehicle_owner = :vehicle_owner AND status = 'Inside'
            LIMIT 1
        ");
        $vehicleStmt->execute([':vehicle_owner' => $full_name]);
        $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);

        if ($vehicle) {
            $visitor['vehicle'] = $vehicle;
        } else {
            $visitor['vehicle'] = null;
        }
    }

    echo json_encode($visitors);
} catch (Exception $e) {
    error_log("Error in fetch_inside_visitors.php: " . $e->getMessage());
    echo json_encode([]);
}
