<?php
header('Content-Type: application/json');

if (isset($_FILES['image'])) {
    // Use a more robust temporary directory
    $uploadDir = sys_get_temp_dir() . '/vehicle_recog_uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate a unique filename to prevent collisions
    $fileName = uniqid('plate_', true) . '.jpg';
    $uploadFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
        // Get absolute paths for reliability
        $imagePathForScript = escapeshellarg(realpath($uploadFile));
        $pythonScriptPath = escapeshellarg(realpath(__DIR__ . '/../../../app/services/vehicle_recog/license_scanner.py'));
        // --- Path Correction ---
        // Provide the absolute path to the Python executable in your virtual environment
        $pythonExecutable = escapeshellarg(realpath(__DIR__ . '/../../../app/venv/bin/python'));
        
        // Construct the command
        // The "2>&1" at the end redirects stderr to stdout, so we can capture Python errors.
        $command = $pythonExecutable . ' ' . $pythonScriptPath . ' ' . $imagePathForScript . ' 2>&1';

        // Execute the command and capture its output
        $output = shell_exec($command);

        // --- New: Validate the output ---
        // Check if the output is valid JSON. If not, it's probably a Python error.
        json_decode($output);
        if (json_last_error() === JSON_ERROR_NONE) {
            // The output is valid JSON, so echo it directly.
            echo $output;
        } else {
            // The output is not JSON, so wrap the error in a valid JSON structure.
            echo json_encode(['error' => 'The recognition script failed.', 'details' => $output]);
        }

        // Clean up the uploaded file
        unlink($uploadFile);

    } else {
        echo json_encode(['error' => 'Failed to save uploaded image.']);
    }
} else {
    echo json_encode(['error' => 'No image was uploaded.']);
}
?>
