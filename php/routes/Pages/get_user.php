<?php
require '../database/db_connect.php';

define('ENC_KEY', 'your-32-character-secret-key');

/**
 * Try to decrypt if data is encrypted, otherwise return as-is.
 */
function tryDecrypt($data) {
    if (!$data) return '';

    $decoded = base64_decode($data, true);
    if ($decoded === false) return $data;

    if (strlen($decoded) <= 16) return $data; // too short

    $iv = substr($decoded, 0, 16);
    $cipher = substr($decoded, 16);

    $decrypted = openssl_decrypt($cipher, 'AES-256-CBC', ENC_KEY, OPENSSL_RAW_DATA, $iv);
    return $decrypted !== false ? $decrypted : $data;
}

// Ensure ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'User ID missing']);
    exit;
}

$id = $_GET['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');

    if ($user) {
        echo json_encode([
            'id'        => $user['id'],
            'full_name' => tryDecrypt($user['full_name']),
            'email'     => tryDecrypt($user['email']),
            'rank'      => tryDecrypt($user['rank']),
            'role'      => tryDecrypt($user['role']),
            'status'    => tryDecrypt($user['status']),
            'joined_date' => $user['joined_date'],
            'last_active' => $user['last_active']
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
