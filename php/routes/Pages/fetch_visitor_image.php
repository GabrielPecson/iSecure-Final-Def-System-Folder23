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
        $full_path = '';

        // Clean the file path from the database to remove any redundant prefixes
        $cleaned_file_path = $file_path_from_db;

        // First, remove 'public/uploads/' if it exists at the beginning
        if (strpos($cleaned_file_path, 'public/uploads/') === 0) {
            $cleaned_file_path = substr($cleaned_file_path, strlen('public/uploads/'));
        }

        // Then, remove 'uploads/' if it exists at the beginning (for cases where public/ was not present)
        if (strpos($cleaned_file_path, 'uploads/') === 0) {
            $cleaned_file_path = substr($cleaned_file_path, strlen('uploads/'));
        }

        // Finally, remove 'ids/' or 'selfies/' if they exist at the beginning
        if ($type === 'id' && strpos($cleaned_file_path, 'ids/') === 0) {
            $cleaned_file_path = substr($cleaned_file_path, strlen('ids/'));
        }
        if ($type === 'selfie' && strpos($cleaned_file_path, 'selfies/') === 0) {
            $cleaned_file_path = substr($cleaned_file_path, strlen('selfies/'));
        }

        // Construct the full path
<<<<<<< HEAD
        $full_path = $base_upload_dir . $type . 's/' . $cleaned_file_path;
=======
        $full_path = $base_upload_dir . $type . 's/' . $cleaned_file_path; else {
            http_response_code(400);
            echo "Invalid image type for path construction.";
            exit;
        }
>>>>>>> 9278b8c0711da9717ed2ccd6e225ebe8332f0214

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
