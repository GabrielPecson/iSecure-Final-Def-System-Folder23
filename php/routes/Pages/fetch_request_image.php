<?php
session_start();
require __DIR__ . '/db_connect.php'; // Database connection

// Basic validation for required parameters
if ((!isset($_GET['request_id']) && !isset($_GET['visitor_id'])) || !isset($_GET['type'])) {
    http_response_code(400); // Bad Request
    echo "Error: Missing required parameters.";
    exit;
}

// Determine if we are fetching for a request or an existing visitor
$isRequest = isset($_GET['request_id']);
$id = $isRequest ? $_GET['request_id'] : $_GET['visitor_id'];
$imageType = $_GET['type'];

// Validate image type to prevent security issues
$allowedTypes = ['id', 'selfie'];
if (!in_array($imageType, $allowedTypes)) {
    http_response_code(400);
    echo "Error: Invalid image type specified.";
    exit;
}

if ($isRequest) {
    // For visitation_requests table
    $table = 'visitation_requests';
    $column = ($imageType === 'id') ? 'valid_id_path' : 'selfie_photo_path';
} else {
    // For visitors table
    $table = 'visitors';
    $column = ($imageType === 'id') ? 'id_photo_path' : 'selfie_photo_path';
}

try {
    // Prepare and execute the query to get the image path
    $stmt = $pdo->prepare("SELECT {$column} FROM {$table} WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || empty($result[$column])) {
        http_response_code(404);
        echo "Error: Image record not found in the database.";
        exit;
    }

    // Construct the full, absolute path to the image file
    // The base path is the project root.
    $basePath = dirname(__FILE__, 4); // Go up 4 levels from /php/routes/Pages/ to the root
    // Use the file path directly from the database
    $filePath = $basePath . DIRECTORY_SEPARATOR . $result[$column];

    if (!file_exists($filePath)) {
        http_response_code(404);
        echo "Error: Image file not found on the server at path: " . htmlspecialchars($filePath);
        exit;
    }

    // Serve the image
    $mimeType = mime_content_type($filePath);
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    // In a production environment, you might log this error instead of displaying it
    echo "Database error: " . $e->getMessage();
    exit;
}
?>