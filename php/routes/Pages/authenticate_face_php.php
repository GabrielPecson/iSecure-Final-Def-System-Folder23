<?php
require 'auth_check.php'; // Ensures only logged-in users can perform this action

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No image file uploaded.']);
    exit;
}

try {
    // The Python API expects a multipart/form-data request, so we'll forward it using cURL
<<<<<<< HEAD
    $api_url = 'http://localhost:8000/authenticate/face';
=======
    $api_url = 'https://isecured.online:8000/authenticate/face';
>>>>>>> 9278b8c0711da9717ed2ccd6e225ebe8332f0214

    // Create a CURLFile object to correctly handle the file upload
    $cfile = new CURLFile($_FILES['image']['tmp_name'], $_FILES['image']['type'], $_FILES['image']['name']);

    $post_data = [
        'file' => $cfile,
    ];

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

    $api_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception("API Error (HTTP {$http_code}): " . ($api_response ?: 'No response from server.') . " cURL Error: " . $curl_error);
    }

    // The Python API already returns JSON, so we can just echo it back to the frontend
    echo $api_response;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>