<?php
require 'auth_check.php';
require 'db_connect.php';
require 'encryption_key.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $action = $_POST['action'] ?? 'approve'; // Default to approve

    if ($id) {
        try {
            // Mark visitation request as approved or rejected based on action
            $status = ($action === 'reject') ? 'Rejected' : 'Approved';
            $stmt = $pdo->prepare("UPDATE visitation_requests SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $status, ':id' => $id]);

            // Fetch visitation request details
            $stmt = $pdo->prepare("SELECT * FROM visitation_requests WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($request) {
                // Visitor data for processing (needed for both approve and reject) - use directly from database
                $first_name = $request['first_name'];
                $middle_name = $request['middle_name'];
                $last_name = $request['last_name'];
                $contact_number = $request['contact_number'];
                $email = $request['email'];
                $home_address = $request['home_address'];
                $visitor_name = trim($first_name . ' ' . $middle_name . ' ' . $last_name);

                if ($action === 'approve') {
                // Insert or update vehicle status to Expected only if vehicle data exists
                if (!empty($request['plate_number'])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO vehicles
                            (visitation_id, vehicle_owner, vehicle_brand, vehicle_model, vehicle_color, plate_number, vehicle_photo_path, entry_time, exit_time, status)
                        VALUES
                            (:visitation_id, :vehicle_owner, :vehicle_brand, :vehicle_model, :vehicle_color, :plate_number, :vehicle_photo_path, NULL, NULL, 'Expected')
                        ON DUPLICATE KEY UPDATE status = 'Expected'
                    ");
                    $stmt->execute([
                        ':visitation_id'      => $request['id'],
                        ':vehicle_owner'      => $visitor_name, // Plain text vehicle_owner
                        ':vehicle_brand'      => $request['vehicle_brand'], // Plain text in visitation_requests
                        ':vehicle_model'      => $request['vehicle_model'], // Plain text in visitation_requests
                        ':vehicle_color'      => $request['vehicle_color'], // Plain text in visitation_requests
                        ':plate_number'       => $request['plate_number'], // Plain text in visitation_requests
                        ':vehicle_photo_path' => $request['vehicle_photo_path'] ?? null
                    ]);
                }

                // Store data in plain text
                $first_name_enc = $first_name;
                $middle_name_enc = $middle_name;
                $last_name_enc = $last_name;
                $contact_number_enc = $contact_number;
                $email_enc = $email;
                $home_address_enc = $home_address;
                $office_to_visit_enc = $request['office_to_visit'];
                $personnel_related_enc = $request['personnel_related'];

                // Insert into visitors table (data stored encrypted)
                $stmt = $pdo->prepare("
                    INSERT INTO visitors
                        (visitation_id, first_name, middle_name, last_name, contact_number, email, address, reason, id_photo_path, selfie_photo_path, date, time_in, status, office_to_visit, personnel_related)
                    VALUES
                        (:visitation_id, :first_name, :middle_name, :last_name, :contact_number, :email, :address, :reason, :id_photo, :selfie, :visit_date, NULL, 'Expected', :office_to_visit, :personnel_related)
                ");
                $stmt->execute([
                    ':visitation_id' => $request['id'],
                    ':first_name'     => $first_name_enc,
                    ':middle_name'    => $middle_name_enc,
                    ':last_name'      => $last_name_enc,
                    ':contact_number' => $contact_number_enc,
                    ':email'          => $email_enc,
                    ':address'        => $home_address_enc,
                    ':reason'         => $request['reason'],
                    ':id_photo'       => $request['valid_id_path'] ?? null,
                    ':selfie'         => $request['selfie_photo_path'] ?? null,
                    ':visit_date'     => $request['visit_date'] ?? date('Y-m-d'),
                    ':office_to_visit' => $office_to_visit_enc,
                    ':personnel_related' => $personnel_related_enc
                ]);
                }

                // Insert notification for personnel user
                $stmt = $pdo->prepare("
                    SELECT id FROM users WHERE full_name = :full_name LIMIT 1
                ");
                $stmt->execute([':full_name' => $request['personnel_related']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    $actionText = ($action === 'reject') ? 'rejected' : 'approved';
                    $notificationMessage = "Your visitation request for " . htmlspecialchars($visitor_name) . " has been " . $actionText . ".";
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, message, created_at, read_status)
                        VALUES (:user_id, :message, NOW(), 'Unread')
                    ");
                    $stmt->execute([
                        ':user_id' => $user['id'],
                        ':message' => $notificationMessage
                    ]);
                }

                // Send SMS to visitor
                if (!empty($contact_number)) {
                    require_once __DIR__ . '/sms_module.php';
                    if ($action === 'approve') {
                        $smsMessage = "Your visitation request to Basa Air Base has been approved. Please arrive on time for your visit.";
                    } else {
                        $smsMessage = "Your visitation request to Basa Air Base has been rejected. Please contact the office for more details.";
                    }
                    send_sms($contact_number, $smsMessage);
                }
            }

            echo json_encode(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing request ID']);
    }
<<<<<<< HEAD
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
=======
}
>>>>>>> 9278b8c0711da9717ed2ccd6e225ebe8332f0214
