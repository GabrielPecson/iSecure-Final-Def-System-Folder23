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


    }

    echo json_encode($visitors);
} catch (Exception $e) {
    echo json_encode([]);
}
