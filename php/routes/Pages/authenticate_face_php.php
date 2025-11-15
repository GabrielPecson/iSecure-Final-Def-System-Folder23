<?php
header('Content-Type: application/json');

// --- Configuration ---
// prefer the actual project script location (app/face_authenticator.py)
$python_executable = realpath(__DIR__ . '/../../../.venv/Scripts/python.exe');
// $python_script (old) pointed to services/face_recog; prefer the actual script in /app
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
if ($image_file['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'File upload error: ' . $image_file['error'];
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$tmp_dir = sys_get_temp_dir();
$tmp_file_path = tempnam($tmp_dir, 'face_auth_') . '.jpg';

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

// Basic validation
if (empty($visitor_id)) {
	http_response_code(400);
	echo json_encode([
		'success' => false,
		'message' => 'Missing visitor_id parameter'
	]);
	exit;
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

// --- UPDATED: detect the correct Python script (require app/face_authenticator.py) ---
$scriptPath = realpath(__DIR__ . '/../../app/services/face_recog/face_authenticator.py');
if ($scriptPath === false || !file_exists($scriptPath)) {
    // Requirement: return this exact JSON error when script is missing
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Python authentication script not found.'
    ]);
    exit;
}

// Build secure command: pass image path and visitor_id
$pythonCmd = escapeshellcmd($python);
$scriptArg = escapeshellarg($scriptPath);
$imageArg = escapeshellarg($tmp_file_path);
$visitorArg = escapeshellarg($visitor_id);

// Use proc_open to capture stdout & stderr separately
$descriptors = [
	0 => ["pipe", "r"],
	1 => ["pipe", "w"],
	2 => ["pipe", "w"]
];

$cmd = $pythonCmd . ' ' . $scriptArg . ' authenticate ' . $imageArg;
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

// Prefer stdout but if empty include stderr
$rawOutput = trim($stdout !== '' ? $stdout : $stderr);

// Try to extract JSON object from output (robust: find first { and last })
$jsonStr = null;
$start = strpos($rawOutput, '{');
$end = strrpos($rawOutput, '}');
if ($start !== false && $end !== false && $end > $start) {
	$jsonStr = substr($rawOutput, $start, $end - $start + 1);
}

// Validate JSON
if ($jsonStr !== null) {
	$decoded = json_decode($jsonStr, true);
	if (json_last_error() === JSON_ERROR_NONE) {
		// successful result from Python script
		http_response_code(200);
		echo json_encode($decoded);
		exit;
	}
}

// Error: invalid or missing JSON
$errMsg = 'Python script did not return valid JSON.';
if ($returnCode !== 0) {
	$errMsg .= " Exit code: {$returnCode}.";
}
if (!empty($rawOutput)) {
	$snippet = substr($rawOutput, 0, 1000);
	$errMsg .= " Output: " . $snippet;
}

http_response_code(500);
echo json_encode([
	'success' => false,
	'message' => $errMsg
]);
exit;

// --- Cleanup ---
if (file_exists($tmp_file_path)) {
    unlink($tmp_file_path);
}

echo json_encode($response);
