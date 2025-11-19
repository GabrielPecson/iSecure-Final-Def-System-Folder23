<?php
require 'auth_check.php';
require 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$visitor_id = $data['visitor_id'] ?? null;

if (!$visitor_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Visitor ID is required.']);
    exit;
}

try {
    // Fetch the selfie path from the visitation_requests table using the visitor's ID
    $stmt = $pdo->prepare("
        SELECT vr.selfie_photo_path, vr.first_name, vr.last_name 
        FROM visitors v
        JOIN visitation_requests vr ON v.visitation_id = vr.id
        WHERE v.id = ?
    ");
    $stmt->execute([$visitor_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || empty($result['selfie_photo_path'])) {
        throw new Exception("Selfie photo path not found for visitor ID: {$visitor_id}");
    }

    $selfie_path = $result['selfie_photo_path'];
    $visitor_name = trim($result['first_name'] . ' ' . $result['last_name']);

    // Call the Python API to register this selfie
    $api_url = 'http://localhost:8000/register/from_selfie';
    $post_data = json_encode(['visitor_id' => $visitor_id, 'visitor_name' => $visitor_name, 'selfie_path' => $selfie_path]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $api_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        throw new Exception("API Error (HTTP {$http_code}): " . ($api_response ?: 'No response from server.'));
    }

    echo $api_response;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>