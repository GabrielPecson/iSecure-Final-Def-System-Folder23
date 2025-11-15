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
    $file_path_from_db = $result[$column];
    error_log("DEBUG: file_path_from_db = " . $file_path_from_db);
    error_log("DEBUG: __DIR__ = " . __DIR__);
    $base_upload_dir = __DIR__ . '/../uploads/'; // This resolves to php/routes/uploads/
    error_log("DEBUG: resolved base_upload_dir = " . $base_upload_dir);
    $full_path = '';

    // Clean the file path from the database to remove any redundant prefixes
    $cleaned_file_path = $file_path_from_db;
    error_log("DEBUG: cleaned_file_path (before cleaning) = " . $cleaned_file_path);

    // First, remove 'public/uploads/' if it exists at the beginning
    if (strpos($cleaned_file_path, 'public/uploads/') === 0) {
        $cleaned_file_path = substr($cleaned_file_path, strlen('public/uploads/'));
        error_log("DEBUG: cleaned_file_path (after public/uploads/ removal) = " . $cleaned_file_path);
    }

    // Then, remove 'uploads/' if it exists at the beginning (for cases where public/ was not present)
    if (strpos($cleaned_file_path, 'uploads/') === 0) {
        $cleaned_file_path = substr($cleaned_file_path, strlen('uploads/'));
        error_log("DEBUG: cleaned_file_path (after uploads/ removal) = " . $cleaned_file_path);
    }

    // Finally, remove 'ids/' or 'selfies/' if they exist at the beginning
    if ($imageType === 'id' && strpos($cleaned_file_path, 'ids/') === 0) {
        $cleaned_file_path = substr($cleaned_file_path, strlen('ids/'));
        error_log("DEBUG: cleaned_file_path (after ids/ removal) = " . $cleaned_file_path);
    }
    if ($imageType === 'selfie' && strpos($cleaned_file_path, 'selfies/') === 0) {
        $cleaned_file_path = substr($cleaned_file_path, strlen('selfies/'));
        error_log("DEBUG: cleaned_file_path (after selfies/ removal) = " . $cleaned_file_path);
    }
    error_log("DEBUG: cleaned_file_path (final) = " . $cleaned_file_path);
    error_log("DEBUG: imageType = " . $imageType);

    // Construct the full path
    $full_path = $base_upload_dir . $imageType . 's/' . $cleaned_file_path;
    error_log("DEBUG: full_path = " . $full_path);

    $filePath = $full_path; // Assign to $filePath for consistency with existing code

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
