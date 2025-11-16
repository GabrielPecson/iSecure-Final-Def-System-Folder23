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
        
        // Construct the command
        // Ensure the 'python' command is in your system's PATH
        $command = 'python ' . $pythonScriptPath . ' ' . $imagePathForScript;

        // Execute the command and capture its output
        $output = shell_exec($command);

        // The Python script prints JSON, so we can directly echo it
        echo $output;

        // Clean up the uploaded file
        unlink($uploadFile);

    } else {
        echo json_encode(['error' => 'Failed to save uploaded image.']);
    }
} else {
    echo json_encode(['error' => 'No image was uploaded.']);
}
?>
