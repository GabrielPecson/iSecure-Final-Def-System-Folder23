<?php
session_start();
require 'Pages/db_connect.php';

if (!isset($_SESSION['token']) || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$token = $_SESSION['token'];
$userId = $_SESSION['user_id'];

// Validate token in DB and enforce single active session per user
$stmt = $pdo->prepare("
    SELECT * FROM personnel_sessions 
    WHERE token = :token AND user_id = :user_id AND expires_at > NOW()
    ORDER BY created_at DESC LIMIT 1
");
$stmt->execute([
    ':token'   => $token,
    ':user_id' => $userId
]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    // Session invalid, force logout
    session_unset();
    session_destroy();
    http_response_code(401);
    echo json_encode(["error" => "Session expired or invalid"]);
    exit;
}

// ✅ Optional: refresh expiry on activity
$stmt = $pdo->prepare("UPDATE personnel_sessions SET expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = :id");
$stmt->execute([':id' => $session['id']]);
?>
