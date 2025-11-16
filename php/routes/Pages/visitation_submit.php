<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();
session_start();
require 'db_connect.php';
require 'encryption_key.php';
require 'notify.php';
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
function uploadIdFile($fileInput) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Define the relative path for both saving and database storage.
    // This will create 'uploads/ids/' inside the current 'Pages' directory.
    $relativeUploadDir = 'uploads/ids/';
    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . $relativeUploadDir;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . "_id." . pathinfo($_FILES[$fileInput]["name"], PATHINFO_EXTENSION);
    $targetFile = $uploadDir . $fileName;
    if (move_uploaded_file($_FILES[$fileInput]["tmp_name"], $targetFile)) {
        // Return the full relative path from the project root for consistency.
        // This matches the format used by the Python API for selfies.
        return 'php/routes/Pages/' . $relativeUploadDir . $fileName;
    }
    return null;
}

// File upload function for selfies
function uploadSelfieFile($fileInput, $uploadDir = __DIR__ . "/../uploads/selfies/") {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = time() . "_selfie." . pathinfo($_FILES[$fileInput]["name"], PATHINFO_EXTENSION);
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES[$fileInput]["tmp_name"], $targetFile)) {
        // Return the relative path from the project root, not just the filename.
        // This makes it consistent with how selfie paths are stored.
        return 'uploads/ids/' . $fileName;
    }
    return null;
}

// Collect form inputs
$first_name         = $_POST['first_name'] ?? null;
$middle_name        = $_POST['middle_name'] ?? null;
$last_name          = $_POST['last_name'] ?? null;

$home_address       = $_POST['home_address'] ?? null;
$contact_number     = $_POST['contact_number'] ?? null;
$email              = $_POST['email'] ?? null;
$has_vehicle        = $_POST['has_vehicle'] ?? 'no';
$reason             = $_POST['reason'] ?? 'Visitation';
$personnel_related  = $_POST['contact_personnel'] ?? null;
$office_to_visit    = $_POST['office_to_visit'] ?? null;
$visit_date         = $_POST['visit_date'] ?? null;
$visit_time         = $_POST['visit_time'] ?? null;

// --- FIX: Define the user_token from the session ---
$user_token = $_SESSION['user_token'] ?? null;

// Ensure office_to_visit has a value if not selected
if (empty($office_to_visit)) {
    $office_to_visit = 'Not specified';
}

// Store data in plain text
$first_name_enc     = $first_name;
$middle_name_enc    = $middle_name;
$last_name_enc      = $last_name;
$home_address_enc   = $home_address;
$contact_number_enc = $contact_number;
$email_enc          = $email;
$personnel_related_enc = $personnel_related;
$office_to_visit_enc = $office_to_visit; // Plain text

// Build visitor name for vehicle owner if needed (encrypt later)
$visitor_name = trim(implode(' ', array_filter([$first_name, $middle_name, $last_name])));

// Handle vehicle fields based on has_vehicle
if ($has_vehicle === 'yes') {
    $vehicle_owner      = $visitor_name; // Plain text vehicle_owner
    $vehicle_brand      = $_POST['vehicle_brand'] ?? null;
    $plate_number       = $_POST['license_plate'] ?? null;
    $vehicle_color      = $_POST['vehicle_color'] ?? null;
    $vehicle_type       = $_POST['vehicle_type'] ?? null;
    $vehicle_photo_path = null; // No vehicle photo in form
} else {
    $vehicle_owner      = null;
    $vehicle_brand      = null;
    $plate_number       = null;
    $vehicle_color      = null;
    $vehicle_type       = null;
    $vehicle_photo_path = null;
}

// Upload files
$valid_id_path      = uploadIdFile("valid_id");
$selfie_photo_path = $_POST['selfie_photo_path'] ?? null; // Get from POST if available

if (!$selfie_photo_path && isset($_SESSION['user_token'])) { // Only check session if not in POST
    $stmt_session = $pdo->prepare("SELECT selfie_photo_path FROM visitor_sessions WHERE user_token = ?");
    $stmt_session->execute([$user_token]);
    $session_data = $stmt_session->fetch(PDO::FETCH_ASSOC);
    if ($session_data && $session_data['selfie_photo_path']) {
        $selfie_photo_path = $session_data['selfie_photo_path'];
    }
}

// Insert into visitation_requests
$stmt = $pdo->prepare("
    INSERT INTO visitation_requests
    (first_name, middle_name, last_name, home_address, contact_number, email, valid_id_path, selfie_photo_path,
     vehicle_owner, vehicle_brand, plate_number, vehicle_color, vehicle_model, vehicle_photo_path,
     reason, personnel_related, office_to_visit, visit_date, visit_time, status, user_token)
    VALUES (:first_name, :middle_name, :last_name, :home_address, :contact_number, :email, :valid_id_path, :selfie_photo_path,
            :vehicle_owner, :vehicle_brand, :plate_number, :vehicle_color, :vehicle_model, :vehicle_photo_path,
            :reason, :personnel_related, :office_to_visit, :visit_date, :visit_time, 'Pending', :user_token)
");

try {
    $success = $stmt->execute([
        ':first_name'        => $first_name_enc,
        ':middle_name'       => $middle_name_enc,
        ':last_name'         => $last_name_enc,
        ':home_address'      => $home_address_enc,
        ':contact_number'    => $contact_number_enc,
        ':email'             => $email_enc,
        ':valid_id_path'     => $valid_id_path,
        ':selfie_photo_path' => $selfie_photo_path,
        ':vehicle_owner'     => $vehicle_owner,
        ':vehicle_brand'     => $vehicle_brand,
        ':plate_number'      => $plate_number,
        ':vehicle_color'     => $vehicle_color,
        ':vehicle_model'     => $vehicle_type, // Corrected placeholder
        ':vehicle_photo_path'=> $vehicle_photo_path,
        ':reason'            => $reason,
        ':personnel_related' => $personnel_related_enc,
        ':office_to_visit'   => $office_to_visit_enc,
        ':visit_date'        => $visit_date,
        ':visit_time'        => $visit_time,
        ':user_token'        => $user_token
    ]);

    if ($success) {
        // Log action
        log_landing_action($pdo, $user_token, "Submitted visitation request form");

        // Notify admins/personnel if visitor has history
        notify_admin_about_visitor_history($pdo, [
            'first_name' => $first_name,
            'middle_name' => $middle_name,
            'last_name' => $last_name,
            'email' => $email,
            'contact_number' => $contact_number
        ]);

        // Set a success message in the session to display on the homepage
        $_SESSION['submission_success'] = 'Visitation request submitted successfully!';
        // Clean output buffer and redirect to the homepage
        ob_end_clean();
        header('Location: home-page.php');
        exit;
    } else {
        // Log the actual database error for debugging
        error_log("Visitation submission failed: " . implode(" - ", $stmt->errorInfo()));

        // Set an error message and redirect back to the form page
        $_SESSION['submission_error'] = 'Error saving request. Please try again.';
        // Clean output buffer and redirect back to the visit page to show the error
        ob_end_clean();
        header('Location: visit-page.php');
        exit;
    }
} catch (PDOException $e) {
    // Log the exception for debugging
    error_log("Visitation submission failed with exception: " . $e->getMessage());

    // Set an error message and redirect back to the form page
    $_SESSION['submission_error'] = 'Error saving request. Please try again.';
    // Clean output buffer and redirect back to the visit page to show the error
    ob_end_clean();
    header('Location: visit-page.php');
    exit;
}
