<?php
session_start();
require_once 'db_connect.php'; // Database connection

// Validate required parameters
if ((!isset($_GET['request_id']) && !isset($_GET['visitor_id'])) || !isset($_GET['type'])) {
    http_response_code(400); // Bad Request
    echo "Error: Missing required parameters.";
    exit;
}

// Determine if fetching a request or a visitor
$isRequest = isset($_GET['request_id']);
$id = $isRequest ? $_GET['request_id'] : $_GET['visitor_id'];
$imageType = $_GET['type'];

// Validate image type
$allowedTypes = ['id', 'selfie'];
if (!in_array($imageType, $allowedTypes)) {
    http_response_code(400);
    echo "Error: Invalid image type specified.";
    exit;
}

// Determine table and column
if ($isRequest) {
    $table = 'visitation_requests';
    $column = ($imageType === 'id') ? 'valid_id_path' : 'selfie_photo_path';
} else {
    $table = 'visitors';
    $column = ($imageType === 'id') ? 'id_photo_path' : 'selfie_photo_path';
}

try {
    // Fetch image path from DB
    $stmt = $pdo->prepare("SELECT {$column} FROM {$table} WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || empty($result[$column])) {
        http_response_code(404);
        echo "Error: Image record not found in the database.";
        exit;
    }

    // Use the path from the database directly
    $filePath = $result[$column];

    // Optional: If DB stores relative paths, uncomment below
    // $filePath = __DIR__ . DIRECTORY_SEPARATOR . $result[$column];

    // Debugging
    error_log("DEBUG: Using filePath: " . $filePath);
    error_log("DEBUG: file_exists('$filePath'): " . (file_exists($filePath) ? 'true' : 'false'));

    // Check if file exists
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
    error_log("Database error: " . $e->getMessage());
    echo "Database error occurred.";
    exit;
}
?>
