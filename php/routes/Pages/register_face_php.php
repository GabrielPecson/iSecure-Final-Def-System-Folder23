<?php
header('Content-Type: application/json');

// --- Configuration ---
// Path to the python executable inside your virtual environment
$python_executable = realpath(__DIR__ . '/../../../.venv/Scripts/python.exe');
// Path to the face authenticator script
$python_script = realpath(__DIR__ . '/../../../app/services/face_recog/face_authenticator.py');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// --- Input Validation ---
if (!isset($_POST['visitor_id']) || !isset($_FILES['image'])) {
    $response['message'] = 'Missing visitor_id or image file.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$visitor_id = $_POST['visitor_id'];
$image_file = $_FILES['image'];

// --- File Handling ---
// Check for upload errors
if ($image_file['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'File upload error: ' . $image_file['error'];
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Create a temporary file to store the upload
$tmp_dir = sys_get_temp_dir();
$tmp_file_path = tempnam($tmp_dir, 'face_reg_') . '.jpg';

if (!move_uploaded_file($image_file['tmp_name'], $tmp_file_path)) {
    $response['message'] = 'Failed to move uploaded file to temporary location.';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

// --- Python Script Execution ---
if (!file_exists($python_executable)) {
    $response['message'] = 'Python executable not found at: ' . $python_executable;
    http_response_code(500);
} elseif (!file_exists($python_script)) {
    $response['message'] = 'Python script not found at: ' . $python_script;
    http_response_code(500);
} else {
    // Build the command
    $command = sprintf(
        '%s %s register --id %s --image %s',
        escapeshellarg($python_executable),
        escapeshellarg($python_script),
        escapeshellarg($visitor_id),
        escapeshellarg($tmp_file_path)
    );

    // Execute the command
    $output = shell_exec($command . ' 2>&1'); // Capture both stdout and stderr

    // --- Response Handling ---
    // The python script is designed to print a single line of JSON
    $python_response = json_decode(trim($output), true);

    if (json_last_error() === JSON_ERROR_NONE && isset($python_response['success'])) {
        $response['success'] = $python_response['success'];
        $response['message'] = $python_response['message'];
        if (!$python_response['success']) {
            http_response_code(400); // Bad request if python script reports failure
        }
    } else {
        // This happens if the python script crashes and doesn't print JSON
        $response['message'] = 'Failed to execute Python script or parse its output.';
        $response['debug_output'] = $output; // Include raw output for debugging
        http_response_code(500);
    }
}

// --- Cleanup ---
// Delete the temporary file
if (file_exists($tmp_file_path)) {
    unlink($tmp_file_path);
}

// --- Final Output ---
echo json_encode($response);
