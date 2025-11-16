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

// Construct the full, absolute path to the image file
$full_path = realpath(__DIR__ . '/../' . $image_path);

if ($image_path && file_exists($full_path)) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime_type = $finfo->file($full_path);
    header('Content-Type: ' . $mime_type);
    readfile($full_path);
} else {
    http_response_code(404);
    // You can optionally return a placeholder image here
    echo "Image not found.";
}
?>