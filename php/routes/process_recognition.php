<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$mode = $_POST['mode'] ?? '';
$image = $_FILES['image'] ?? null;

if (!$image || $image['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'No image uploaded']);
    exit;
}

$api_base = 'http://localhost:8000';
$endpoint = '';

switch ($mode) {
    case 'face':
        $endpoint = '/authenticate/face';
        break;
    case 'vehicle':
        $endpoint = '/recognize/vehicle';
        break;
    case 'ocr':
        $endpoint = '/ocr/id';
        break;
    default:
        echo json_encode(['error' => 'Invalid mode']);
        exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_base . $endpoint);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'file' => new CURLFile($image['tmp_name'], $image['type'], $image['name'])
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    echo json_encode(['error' => 'API request failed', 'code' => $http_code]);
    exit;
}

$data = json_decode($response, true);

if ($mode === 'face') {
    // Face already has recognized, id, type, name
    echo json_encode($data);
} elseif ($mode === 'ocr') {
    // For OCR, assume id_number is used to search visitors or personnel
    // For simplicity, return extracted info; extend with DB search if needed
    echo json_encode([
        'recognized' => !empty($data['id_number']),
        'type' => 'visitor',
        'id' => $data['id_number'] ?? null,
        'extracted' => $data
    ]);
} elseif ($mode === 'vehicle') {
    // For vehicle, search DB for plate match
    include '../database/db_connect.php';
    $stmt = $pdo->prepare("SELECT id, plate_number, color FROM vehicles WHERE plate_number = ? LIMIT 1");
    $stmt->execute([$data['plate_number']]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($vehicle) {
        echo json_encode([
            'recognized' => true,
            'type' => 'vehicle',
            'id' => $vehicle['id'],
            'plate_number' => $data['plate_number'],
            'color' => $data['color']
        ]);
    } else {
        echo json_encode([
            'recognized' => false,
            'plate_number' => $data['plate_number'],
            'color' => $data['color']
        ]);
    }
}
?>
