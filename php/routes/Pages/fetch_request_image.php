<?php
require 'db_connect.php';

if (!isset($_GET['request_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing request_id parameter']);
    exit;
}

$request_id = intval($_GET['request_id']);
$type = $_GET['type'] ?? 'id'; // Default to 'id', can be 'selfie'

if ($type === 'selfie') {
    $stmt = $pdo->prepare("SELECT selfie_photo_path FROM visitation_requests WHERE id = ?");
} else {
    $stmt = $pdo->prepare("SELECT valid_id_path FROM visitation_requests WHERE id = ?");
}
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    http_response_code(404);
    echo json_encode(['error' => 'Request not found']);
    exit;
}

$photo_path = $type === 'selfie' ? $request['selfie_photo_path'] : $request['valid_id_path'];
$photo_path = $photo_path ?: 'sample_id.png';

// Check if photo_path is base64 encoded image data
if (preg_match('/^data:image\/(\w+);base64,/', $photo_path, $matches)) {
    $data = substr($photo_path, strpos($photo_path, ',') + 1);
    $data = base64_decode($data);
    if ($data === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Base64 decode failed']);
        exit;
    }
    $mime_type = 'image/' . $matches[1];
    header('Content-Type: ' . $mime_type);
    echo $data;
    exit;
}

// Extract filename from path if it contains directory prefixes
$filename = basename($photo_path);

// Construct the file path relative to the current directory (php/routes/Pages/)
if ($type === 'selfie') {
    $file_path = __DIR__ . 'var/www/html/iSecure-Final-Def-System-Folder/php/Routes/Pages/uploads/selfies/' . $filename;
} else {
    $file_path = __DIR__ . 'var/www/html/iSecure-Final-Def-System-Folder/php/Routes/Pages/uploads/ids/' . $filename;
}

// Check if the file exists
if (!file_exists($file_path)) {
    // If no image found, serve a placeholder
    $placeholder_path = __DIR__ . 'sample_id.png'; // Adjust path as needed
    if (file_exists($placeholder_path)) {
        $file_path = $placeholder_path;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Image file not found in any expected location']);
        exit;
    }
}

$mime_type = mime_content_type($file_path);
header('Content-Type: ' . $mime_type);
readfile($file_path);
exit;
?>
