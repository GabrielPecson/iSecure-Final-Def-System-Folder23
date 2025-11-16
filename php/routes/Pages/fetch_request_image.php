<?php
require 'auth_check.php'; // Ensures only logged-in users can access

$request_id = $_GET['request_id'] ?? null;
$type = $_GET['type'] ?? null;

if (!$request_id || !$type) {
    http_response_code(400);
    echo "Missing parameters.";
    exit;
}

// Determine which column to fetch based on the 'type' parameter
$column = '';
if ($type === 'id') {
    $column = 'valid_id_path';
} elseif ($type === 'selfie') {
    $column = 'selfie_photo_path';
} else {
    http_response_code(400);
    echo "Invalid image type specified.";
    exit;
}

$stmt = $pdo->prepare("SELECT {$column} FROM visitation_requests WHERE id = ?");
$stmt->execute([$request_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$image_path = $result[$column] ?? null;

// --- Path Correction ---
// __DIR__ is ".../php/routes/Pages". We need to go up three levels to get to the project root.
// The $image_path from the DB is something like 'php/routes/uploads/ids/...'
// We will construct the path from the project root to ensure it's always correct.
$project_root = realpath(__DIR__ . '/../../..'); 
$full_path = $project_root . DIRECTORY_SEPARATOR . $image_path;

// Log the paths for debugging. You can check this in your server's error log.
error_log("Attempting to load image. DB Path: {$image_path}, Full Constructed Path: {$full_path}");

if ($image_path && file_exists($full_path)) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($full_path);
    header('Content-Type: ' . $mime_type);
    readfile($full_path);
    exit;
} else {
    http_response_code(404);
    // You can optionally return a placeholder image here
    echo "Image not found.";
    error_log("Image not found at path: {$full_path}"); // Log the failure
}
?>