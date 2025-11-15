<?php
session_start();
require 'db_connect.php';

// Basic security check: ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    // For testing purposes, allow access if no session (but this should be removed in production)
    // http_response_code(403);
    // echo "Access denied.";
    // exit;
}

// Validate input
$visitor_id = filter_input(INPUT_GET, 'visitor_id', FILTER_VALIDATE_INT);
$type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING);

if (!$visitor_id || !$type || !in_array($type, ['selfie', 'id'])) {
    http_response_code(400);
    echo "Invalid request.";
    exit;
}

// Determine which column to fetch
$column = ($type === 'selfie') ? 'selfie_photo_path' : 'id_photo_path';

try {
    $stmt = $pdo->prepare("SELECT $column FROM visitors WHERE id = ?");
    $stmt->execute([$visitor_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $file_path_from_db = $row[$column];
        $base_upload_dir = __DIR__ . '/../uploads/'; // This resolves to php/routes/uploads/

        if ($type === 'id') {
            // Assuming id_photo_path in DB is like 'ids/filename.png'
            $full_path = $base_upload_dir . $file_path_from_db;
        } elseif ($type === 'selfie') {
            // Assuming selfie_photo_path in DB is like 'uploads/selfies/filename.jpg'
            // We need to remove the 'uploads/' prefix from $file_path_from_db
            $cleaned_file_path = str_replace('uploads/', '', $file_path_from_db);
            $full_path = $base_upload_dir . $cleaned_file_path;
        } else {
            http_response_code(400);
            echo "Invalid image type for path construction.";
            exit;
        }

        // error_log("Trying to load: " . $full_path);
        // error_log("File exists: " . (file_exists($full_path) ? 'yes' : 'no'));
        if (file_exists($full_path)) {
            // Set the content type header and output the file
            $mime_type = mime_content_type($full_path);
            header("Content-Type: $mime_type");
            readfile($full_path);
            exit;
        } else {
            http_response_code(404);
            echo "File not found on server.";
        }
    } else {
        http_response_code(404);
        echo "Visitor record not found.";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Server error: " . $e->getMessage();
}
