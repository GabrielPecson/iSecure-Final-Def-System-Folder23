<?php
session_start();
require 'db_connect.php'; // Assuming this file provides a $pdo object

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// --- 1. Data Validation ---
$required_fields = ['first_name', 'last_name', 'contact_number', 'email', 'address', 'personnel_to_visit', 'office_to_visit', 'date', 'time_in'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Field '{$field}' is required."]);
        exit;
    }
}

if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
    exit;
}

// --- 2. File Upload Handling ---
function handle_upload($file_key, $upload_dir) {
    if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Failed to upload file for '{$file_key}'. Please try again.");
    }

    $file = $_FILES[$file_key];
    $max_size = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $max_size) {
        throw new RuntimeException("File '{$file_key}' is too large. Maximum size is 5MB.");
    }

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($file['tmp_name']);
    if (!in_array($mime_type, $allowed_types)) {
        throw new RuntimeException("Invalid file type for '{$file_key}'. Only JPG, PNG, and GIF are allowed.");
    }

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safe_filename = uniqid(preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($_POST['first_name'] . '_' . $_POST['last_name'])) . '_', true) . '.' . $extension;
    $destination = $upload_dir . $safe_filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException("Failed to move uploaded file '{$file_key}'.");
    }

    return $destination;
}

try {
    $id_photo_path = handle_upload('id_photo', 'Pages/uploads/ids/');
    $selfie_photo_path = handle_upload('selfie_photo', 'Pages/uploads/selfies/');
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

// --- 3. Database Insertion ---
try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO visitors (
                first_name, middle_name, last_name, contact_number, email, address, 
                personnel_to_visit, office_to_visit, date, time_in, 
                vehicle_owner, vehicle_brand, vehicle_model, vehicle_color, plate_number, 
                id_photo_path, selfie_photo_path, status
            ) VALUES (
                :first_name, :middle_name, :last_name, :contact_number, :email, :address, 
                :personnel_to_visit, :office_to_visit, :date, :time_in, 
                :vehicle_owner, :vehicle_brand, :vehicle_model, :vehicle_color, :plate_number, 
                :id_photo_path, :selfie_photo_path, 'Pending'
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':first_name' => $_POST['first_name'],
        ':middle_name' => $_POST['middle_name'] ?? null,
        ':last_name' => $_POST['last_name'],
        ':contact_number' => $_POST['contact_number'],
        ':email' => $_POST['email'],
        ':address' => $_POST['address'],
        ':personnel_to_visit' => $_POST['personnel_to_visit'],
        ':office_to_visit' => $_POST['office_to_visit'],
        ':date' => $_POST['date'],
        ':time_in' => $_POST['time_in'],
        ':vehicle_owner' => $_POST['vehicle_owner'] ?? null,
        ':vehicle_brand' => $_POST['vehicle_brand'] ?? null,
        ':vehicle_model' => $_POST['vehicle_model'] ?? null,
        ':vehicle_color' => $_POST['vehicle_color'] ?? null,
        ':plate_number' => $_POST['plate_number'] ?? null,
        ':id_photo_path' => $id_photo_path,
        ':selfie_photo_path' => $selfie_photo_path
    ]);

    $visitor_id = $pdo->lastInsertId();

    // --- 4. API Call to Register Face ---
    // Generate a unique token for this visit session
    $session_token = bin2hex(random_bytes(32));

    $api_url = 'https://isecured.online:8000/register/face';
    $post_data = [
        'session_token' => $session_token,
        'file' => new CURLFile(realpath($selfie_photo_path))
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // On a live server with valid SSL, you should not disable verification.
    // If you have issues, ensure your server's CA bundle is up to date.
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $api_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception("API Error: Failed to register face. Server responded with code {$http_code}. Details: {$api_response} Curl Error: {$curl_error}");
    }

    // If everything is successful, commit the transaction
    $pdo->commit();

    // Set a session variable for the success page
    $_SESSION['registration_success'] = true;
    $_SESSION['visitor_name'] = htmlspecialchars($_POST['first_name'] . ' ' . $_POST['last_name']);

    echo json_encode(['success' => true, 'message' => 'Visitation request submitted successfully. Please wait for approval.']);

} catch (Exception $e) {
    // If anything fails, roll back the database transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>