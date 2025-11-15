<?php
require 'auth_check.php';
require 'db_connect.php';
require 'encryption_key.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM visitation_requests ORDER BY created_at DESC");
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decrypt sensitive data for display
    foreach ($requests as &$request) {
        $request['first_name'];
        $request['middle_name'];
        $request['last_name'];
        $request['home_address'];
        $request['contact_number'];
        $request['email'];
        $request['personnel_related'];
        $request['office_to_visit'];
        // Handle empty office_to_visit
        if (empty($request['office_to_visit'])) {
            $request['office_to_visit'] = 'Not specified';
        }
        $request['vehicle_owner'];
        $request['vehicle_brand'];
        $request['plate_number'];
        $request['vehicle_color'];
        $request['vehicle_model'];
    }

    echo json_encode($requests);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
