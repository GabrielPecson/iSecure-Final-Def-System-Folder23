<?php
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

// --- Input Validation ---
if (!isset($_FILES['image'])) {
    $response['message'] = 'No image file uploaded.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$image_file = $_FILES['image'];

// --- File Handling ---
if ($image_file['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'File upload error: ' . $image_file['error'];
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$tmp_dir = sys_get_temp_dir();
$tmp_file_path = tempnam($tmp_dir, 'ocr_id_') . '.' . pathinfo($image_file['name'], PATHINFO_EXTENSION);

if (!move_uploaded_file($image_file['tmp_name'], $tmp_file_path)) {
    $response['message'] = 'Failed to move uploaded file to temporary location.';
    http_response_code(500);
    echo json_encode($response);
    exit;
}

// Ensure temporary file is removed on script end (successful or error)
register_shutdown_function(function () use ($tmp_file_path) {
    if (!empty($tmp_file_path) && file_exists($tmp_file_path)) {
        @unlink($tmp_file_path);
    }
});

// --- Python Script Execution ---

// Helper: find a usable python executable (checks venv locations first, then system fallbacks)
function find_python_executable(): ?string {
	$baseDir = realpath(__DIR__ . '/../../');
	$candidates = [
		// project venv common locations
		$baseDir . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe',
		$baseDir . DIRECTORY_SEPARATOR . '.venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python',
		$baseDir . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'Scripts' . DIRECTORY_SEPARATOR . 'python.exe',
		$baseDir . DIRECTORY_SEPARATOR . 'venv' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'python',
		// system locations
		'/usr/bin/python3',
		'/usr/bin/python',
		'python3',
		'python'
	];

	foreach ($candidates as $p) {
		if (strpos($p, DIRECTORY_SEPARATOR) !== false) {
			if (file_exists($p) && is_executable($p)) {
				return $p;
			}
		} else {
			if (stripos(PHP_OS_FAMILY, 'Windows') !== false) {
				$which = @shell_exec('where ' . escapeshellarg($p) . ' 2>NUL');
			} else {
				$which = @shell_exec('command -v ' . escapeshellarg($p) . ' 2>/dev/null');
			}
			if ($which && trim($which) !== '') {
				$resolved = explode(PHP_EOL, trim($which))[0];
				return $resolved ?: $p;
			}
		}
	}

	return null;
}

// Locate python
$python = find_python_executable();
if ($python === null) {
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'No Python executable found. Please install Python or activate your virtual environment.'
	]);
	exit;
}

// Path to the Python OCR script
$scriptPath = realpath(__DIR__ . '/../../app/services/ocr/id_scanner.py');
if ($scriptPath === false || !file_exists($scriptPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Python OCR script not found.'
    ]);
    exit;
}

// Build secure command: pass image path and --json-output flag
$pythonCmd = escapeshellcmd($python);
$scriptArg = escapeshellarg($scriptPath);
$imageArg = escapeshellarg($tmp_file_path);
$jsonOutputFlag = '--json-output';

$cmd = $pythonCmd . ' ' . $scriptArg . ' --file ' . $imageArg . ' ' . $jsonOutputFlag;

$descriptors = [
	0 => ["pipe", "r"],
	1 => ["pipe", "w"],
	2 => ["pipe", "w"]
];

$process = @proc_open($cmd, $descriptors, $pipes, null, null);

if (!is_resource($process)) {
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'Failed to start Python process.'
	]);
	exit;
}

// Read output
$stdout = stream_get_contents($pipes[1]);
fclose($pipes[1]);

$stderr = stream_get_contents($pipes[2]);
fclose($pipes[2]);

// Close process and get exit code
$returnCode = proc_close($process);

// Try to decode JSON output from stdout
$decoded_output = json_decode($stdout, true);

if (json_last_error() === JSON_ERROR_NONE && isset($decoded_output['success'])) {
    // Python script returned valid JSON
    http_response_code(200);
    echo json_encode($decoded_output);
} else {
    // Error: invalid or missing JSON from Python script
    $errMsg = 'Python script did not return valid JSON.';
    if ($returnCode !== 0) {
        $errMsg .= " Exit code: {$returnCode}.";
    }
    if (!empty($stdout)) {
        $snippet = substr($stdout, 0, 1000);
        $errMsg .= " Stdout: " . $snippet;
    }
    if (!empty($stderr)) {
        $snippet = substr($stderr, 0, 1000);
        $errMsg .= " Stderr: " . $snippet;
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $errMsg
    ]);
}

// Cleanup is handled by register_shutdown_function
exit;

?>