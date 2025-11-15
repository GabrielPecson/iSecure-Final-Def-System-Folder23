<?php
// --- TEMPORARY DEBUGGING ---
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain'); // Set content type to plain text for debugging

require 'db_connect.php';

if (!isset($_GET['request_id'])) {
    http_response_code(400);
    echo "DEBUG: Error - Missing request_id parameter.";
    exit;
}

$request_id = intval($_GET['request_id']);
echo "DEBUG: Received request_id = " . $request_id . "\n";

$type = $_GET['type'] ?? 'id';
echo "DEBUG: Received type = " . $type . "\n";

if ($type === 'selfie') {
    $stmt = $pdo->prepare("SELECT selfie_photo_path FROM visitation_requests WHERE id = ?");
} else {
    $stmt = $pdo->prepare("SELECT valid_id_path FROM visitation_requests WHERE id = ?");
}
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    http_response_code(404);
    echo "DEBUG: Error - No record found in 'visitation_requests' table for id = " . $request_id . "\n";
    exit;
}

echo "DEBUG: Database query successful. Found record.\n";
$photo_path = $type === 'selfie' ? $request['selfie_photo_path'] : $request['valid_id_path'];
echo "DEBUG: Photo path from DB = " . ($photo_path ?: '[EMPTY]') . "\n";

$photo_path = $photo_path ?: 'sample_id.png';
echo "DEBUG: Effective photo path = " . $photo_path . "\n";


// Check if photo_path is base64 encoded image data
if (preg_match('/^data:image\/(\w+);base64,/', $photo_path, $matches)) {
    echo "DEBUG: Path appears to be a base64 string. Attempting to decode and serve.\n";
    $data = substr($photo_path, strpos($photo_path, ',') + 1);
    $data = base64_decode($data);
    if ($data === false) {
        http_response_code(500);
        echo "DEBUG: Error - Base64 decode failed.\n";
        exit;
    }
    $mime_type = 'image/' . $matches[1];
    // Temporarily disable actual image output for debugging
    // header('Content-Type: ' . $mime_type);
    // echo $data;
    echo "DEBUG: Base64 decoded successfully. Would have served the image.\n";
    exit;
}

// Construct the file path by prepending the uploads directory based on type, using basename to get the filename
if ($type === 'selfie') {
    $file_path = __DIR__ . '/uploads/selfies/' . basename($photo_path);
} else {
    $file_path = __DIR__ . '/uploads/ids/' . basename($photo_path);
}
echo "DEBUG: Constructed file path = " . $file_path . "\n";


// Check if the file exists
if (!file_exists($file_path)) {
    echo "DEBUG: Image file not found at constructed path. Trying placeholder.\n";
    // If no image found, serve a placeholder
    $placeholder_path = __DIR__ . '/sample_id.png';
    echo "DEBUG: Placeholder path = " . $placeholder_path . "\n";

    if (file_exists($placeholder_path)) {
        $file_path = $placeholder_path;
        echo "DEBUG: Placeholder found. Will serve placeholder.\n";
    } else {
        http_response_code(404);
        echo "DEBUG: Error - Image file not found AND placeholder not found.\n";
        exit;
    }
} else {
    echo "DEBUG: Image file found at constructed path. Will serve image.\n";
}

// Temporarily disable actual image output for debugging
// $mime_type = mime_content_type($file_path);
// header('Content-Type: ' . $mime_type);
// readfile($file_path);
exit;
?>