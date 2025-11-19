<?php
require 'Pages/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Missing visitation ID.']);
        exit;
    }

    try {
        // 1. Fetch visitation request
        $stmt = $pdo->prepare("SELECT * FROM visitation_requests WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            echo json_encode(['success' => false, 'message' => 'Visitation request not found.']);
            exit;
        }

        // 2. Approve request
        $pdo->prepare("UPDATE visitation_requests SET status = 'Approved' WHERE id = ?")
            ->execute([$id]);

        // 3. Insert into vehicles table if vehicle info exists
      // Insert vehicle as Expected, no entry_time yet
$insert = $pdo->prepare("
    INSERT INTO vehicles
        (visitation_id, vehicle_owner, vehicle_brand, vehicle_model, vehicle_color, plate_number, vehicle_photo_path, status)
    VALUES
        (:visitation_id, :vehicle_owner, :vehicle_brand, :vehicle_model, :vehicle_color, :plate_number, :vehicle_photo_path, 'Expected')
");
$insert->execute([
    ':visitation_id'      => $request['id'],
    ':vehicle_owner'      => $request['vehicle_owner'],
    ':vehicle_brand'      => $request['vehicle_brand'],
    ':vehicle_model'      => $request['vehicle_model'],
    ':vehicle_color'      => $request['vehicle_color'],
    ':plate_number'       => $request['plate_number'],
    ':vehicle_photo_path' => $request['vehicle_photo_path']
]);


        echo json_encode(['success' => true, 'message' => 'Request approved and vehicle added.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
