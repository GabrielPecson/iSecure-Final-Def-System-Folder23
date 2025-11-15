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
        $file_path = $row[$column];

        // Construct the file path relative to the current directory (php/routes/Pages/)
        if ($type === 'id') {
            $full_path = __DIR__ . 'www.isecured.online/iSecure-Final-Def-System-Folder/php/Routes/Pages/uploads/ids/' . $file_path;
        } else {
            $full_path = __DIR__ . 'www.isecured.online/iSecure-Final-Def-System-Folder/php/Routes/Pages/uploads/selfies/' . $file_path;
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
