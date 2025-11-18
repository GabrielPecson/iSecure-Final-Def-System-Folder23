<?php
require 'auth_check.php';
require '../database/db_connect.php';
require 'audit_log.php';

// File upload function
function uploadFile($fileInput, $uploadDir = "uploads/") {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES[$fileInput]["name"]);
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES[$fileInput]["tmp_name"], $targetFile)) {
        return $targetFile;
    }
    return null;
}

// File upload function for IDs
function uploadIdFile($fileInput, $uploadDir = __DIR__ . "/uploads/ids/") {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . "_id." . pathinfo($_FILES[$fileInput]["name"], PATHINFO_EXTENSION);
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES[$fileInput]["tmp_name"], $targetFile)) {
        return $fileName; // Return filename only for database
    }
    return null;
}

// File upload function for selfies
function uploadSelfieFile($fileInput, $uploadDir = __DIR__ . "/uploads/selfies/") {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . "_selfie." . pathinfo($_FILES[$fileInput]["name"], PATHINFO_EXTENSION);
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES[$fileInput]["tmp_name"], $targetFile)) {
        return $fileName; // Return filename for database
    }
    return null;
}

// Collect form inputs
$first_name         = $_POST['first_name'] ?? null;
$last_name          = $_POST['last_name'] ?? null;
$middle_name        = $_POST['middle_name'] ?? null;
$home_address       = $_POST['home_address'] ?? null;
$contact_number     = $_POST['contact_number'] ?? null;
$email              = $_POST['email'] ?? null;
$vehicle_brand      = $_POST['vehicle_brand'] ?? null;
$vehicle_type       = $_POST['vehicle_type'] ?? null;
$vehicle_color      = $_POST['vehicle_color'] ?? null;
$license_plate      = $_POST['license_plate'] ?? null;
$contact_personnel  = $_POST['contact_personnel'] ?? null;
$office_to_visit    = $_POST['office_to_visit'] ?? null;
$visit_date         = date('Y-m-d'); // Fixed to today
$visit_time         = date('H:i:s'); // Set to current time

// Store data in plain text
$first_name_enc     = $first_name;
$middle_name_enc    = $middle_name;
$last_name_enc      = $last_name;
$home_address_enc   = $home_address;
$contact_number_enc = $contact_number;
$email_enc          = $email;
$contact_personnel_enc = $contact_personnel;
$office_to_visit_enc = $office_to_visit;

// Upload files
$valid_id_path      = uploadIdFile("valid_id");
$selfie_photo_path = uploadSelfieFile("selfie_photo");

// Insert into visitation_requests
$stmt = $pdo->prepare("
    INSERT INTO visitation_requests
    (first_name, middle_name, last_name, home_address, contact_number, email, valid_id_path, selfie_photo_path,
     vehicle_brand, vehicle_color, plate_number, vehicle_model,
     personnel_related, office_to_visit, visit_date, visit_time, reason, status)
    VALUES (:first_name, :middle_name, :last_name, :home_address, :contact_number, :email, :valid_id_path, :facial_photos,
            :vehicle_brand, :vehicle_color, :plate_number, :vehicle_type,
            :contact_personnel, :office_to_visit, :visit_date, :visit_time, 'Walk-in Visit', 'Pending')
");

$success = $stmt->execute([
    ':first_name'        => $first_name_enc,
    ':middle_name'       => $middle_name_enc,
    ':last_name'         => $last_name_enc,
    ':home_address'      => $home_address_enc,
    ':contact_number'    => $contact_number_enc,
    ':email'             => $email_enc,
    ':valid_id_path'     => $valid_id_path,
    ':selfie_photo_path' => $selfie_photo_path,
    ':vehicle_brand'     => $vehicle_brand,
    ':vehicle_color'     => $vehicle_color,
    ':plate_number'      => $license_plate,
    ':vehicle_type'      => $vehicle_type,
    ':contact_personnel' => $contact_personnel_enc,
    ':office_to_visit'   => $office_to_visit_enc,
    ':visit_date'        => $visit_date,
    ':visit_time'        => $visit_time
]);

if ($success) {
    // Get the new visitation ID
    $visitationId = $pdo->lastInsertId();

    // If a vehicle was included, insert it properly now
    if (!empty($license_plate)) {
        $vehicle_owner = trim($first_name . ' ' . $middle_name . ' ' . $last_name);
        $stmt = $pdo->prepare("
            INSERT INTO vehicles
            (visitation_id, vehicle_owner, vehicle_brand, vehicle_model, vehicle_color, plate_number, status)
            VALUES (:visitation_id, :vehicle_owner, :vehicle_brand, :vehicle_model, :vehicle_color, :plate_number, 'Expected')
        ");
        $stmt->execute([
            ':visitation_id'      => $visitationId,
            ':vehicle_owner'      => $vehicle_owner,
            ':vehicle_brand'      => $vehicle_brand,
            ':vehicle_model'      => $vehicle_type,
            ':vehicle_color'      => $vehicle_color,
            ':plate_number'       => $license_plate
        ]);
    }

    // Log action
    $user_id = $_SESSION['user_id'] ?? null;
    $visitor_name = trim($first_name . ' ' . $middle_name . ' ' . $last_name);
    log_admin_action($pdo, $user_id, "Submitted walk-in visitation request for $visitor_name");

    echo "<script>alert('Walk-in visitation request submitted successfully!'); window.location.href='personnel_dashboard.php';</script>";
} else {
    echo "<script>alert('Error saving request. Please try again.'); window.history.back();</script>";
}