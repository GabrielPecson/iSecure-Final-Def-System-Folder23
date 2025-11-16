<?php
require 'db_connect.php';
require 'encryption_key.php';
header('Content-Type: application/json');

    // Note: visitors table stores data encrypted, decryption needed for display

try {
    $stmt = $pdo->prepare("
        SELECT
            id,
            first_name,
            middle_name,
            last_name,
            contact_number,
            date,
            status
        FROM visitors
        WHERE (status = 'Expected' OR status IS NULL OR status = '') AND time_in IS NULL AND status != 'Cancelled'
        ORDER BY date DESC
    ");
    $stmt->execute();
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decrypt sensitive data for display
    foreach ($visitors as &$visitor) {
        // Data is already in plain text
        // Data is already in plain text
        $visitor['status'] = 'Expected';

        // Construct full name for matching with vehicle_owner (encrypt for database query)
        $full_name = trim($visitor['first_name'] . ' ' . $visitor['middle_name'] . ' ' . $visitor['last_name']);
        $encrypted_full_name = $full_name;

        // Fetch associated vehicle if exists
        $vehicleStmt = $pdo->prepare("
            SELECT vehicle_brand, plate_number, vehicle_color, vehicle_model
            FROM vehicles
            WHERE vehicle_owner = :vehicle_owner AND status = 'Expected'
            LIMIT 1
        ");
        $vehicleStmt->execute([':vehicle_owner' => $encrypted_full_name]);
        $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);


    }

    echo json_encode($visitors);
} catch (Exception $e) {
    echo json_encode([]);
}
