<?php
require '../database/db_connect.php';
header('Content-Type: application/json');

$date = $_GET['date'] ?? null;
if (!$date) {
    echo json_encode(['success' => false, 'message' => 'Missing date parameter']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            v.id,
            CONCAT(v.first_name, ' ', v.last_name) AS full_name,
            v.contact_number,
            v.email,
            v.address,
            v.reason,
            v.date,
            v.time_in,
            v.time_out,
            v.status,
            COALESCE(vr.office_to_visit, v.office_to_visit) AS visit_location,
            veh.vehicle_owner,
            veh.vehicle_brand,
            veh.vehicle_model,
            veh.vehicle_color,
            veh.plate_number,
            veh.vehicle_photo_path
        FROM visitors v
        LEFT JOIN visitation_requests vr
            ON CONCAT(vr.first_name, ' ', vr.last_name) = CONCAT(v.first_name, ' ', v.last_name)
           AND vr.visit_date = v.date
        LEFT JOIN vehicles veh
            ON veh.visitation_id = vr.id
        WHERE v.date = :date
        ORDER BY v.time_in ASC
    ");
    $stmt->execute([':date' => $date]);
    $visit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $visit_logs]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Query error: ' . $e->getMessage()]);
}
?>
