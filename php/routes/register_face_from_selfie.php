<?php
header('Content-Type: application/json');
require '../database/db_connect.php';

// --- Helper function to find Python executable ---
function find_python_executable() {
    $possible_paths = [
        realpath(__DIR__ . '/../../.venv/Scripts/python.exe'), // Virtual environment
        '/usr/bin/python3', // Linux
        '/usr/local/bin/python3', // macOS
        'C:\Python39\python.exe', // Windows example
        'C:\Python38\python.exe', // Windows example
        'C:\Python37\python.exe', // Windows example
        trim(shell_exec('where python')), // Windows command
        trim(shell_exec('which python3')), // Linux/macOS command
        trim(shell_exec('which python')), // Linux/macOS command
    ];

    foreach ($possible_paths as $path) {
        if ($path && file_exists($path) && is_executable($path)) {
            return $path;
        }
    }
    return null; // Python executable not found
}

// --- Configuration ---
$python_executable = find_python_executable();
$python_script = realpath(__DIR__ . '/../../app/services/face_recog/face_authenticator.py');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// --- Input Validation ---
$input = json_decode(file_get_contents('php://input'), true);
$visitor_id = $input['visitor_id'] ?? null;

if (!$visitor_id) {
    $response['message'] = 'Missing visitor_id.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// --- Database Query ---
try {
    $stmt = $pdo->prepare("
        SELECT 
            v.selfie_photo_path, 
            vr.first_name, 
            vr.middle_name, 
            vr.last_name 
        FROM visitors v
        LEFT JOIN visitation_requests vr ON v.first_name = vr.first_name AND v.middle_name = vr.middle_name AND v.last_name = vr.last_name
        WHERE v.id = ?
    ");
    $stmt->execute([$visitor_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $relative_selfie_path = $row['selfie_photo_path'];
        $project_root = realpath(__DIR__ . '/../../');
        $absolute_selfie_path = $project_root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_selfie_path);

        if (!file_exists($absolute_selfie_path)) {
            throw new Exception("Selfie file not found at path: " . $absolute_selfie_path);
        }

        // --- Python Script Execution ---
        if (!$python_executable) {
            throw new Exception("Python executable not found. Please ensure Python is installed and accessible.");
        }

        $full_name = trim($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']);
        $command_parts = [
            escapeshellarg($python_executable),
            escapeshellarg($python_script),
            'register',
            escapeshellarg($full_name),
            escapeshellarg($absolute_selfie_path)
        ];
        $command = implode(' ', $command_parts);

        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];

        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            // Close stdin, we are not sending any input
            fclose($pipes[0]);

            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $exitCode = proc_close($process);

            if ($exitCode !== 0) {
                $error_message = "Python script exited with error code {$exitCode}.";
                if (!empty($stderr)) {
                    $error_message .= " Stderr: " . $stderr;
                }
                throw new Exception($error_message);
            }

            $python_response = json_decode($stdout, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($python_response['success'])) {
                $response = $python_response;
                if (!$response['success']) http_response_code(400);
            } else {
                $error_message = "Failed to parse JSON from Python script output.";
                if (!empty($stdout)) {
                    $error_message .= " Stdout: " . $stdout;
                }
                if (!empty($stderr)) {
                    $error_message .= " Stderr: " . $stderr;
                }
                throw new Exception($error_message);
            }
        } else {
            throw new Exception("Failed to open process for Python script.");
        }
    } else {
        throw new Exception("Visitor not found.");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
