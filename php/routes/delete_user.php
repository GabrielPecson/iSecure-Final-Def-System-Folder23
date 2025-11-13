<?php
require '../database/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$id = $_POST['id'] ?? '';

if (empty($id)) {
    echo json_encode(["success" => false, "message" => "Missing user ID"]);
    exit;
}

try {
    // 1. Get the user first
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([":id" => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["success" => false, "message" => "User not found"]);
        exit;
    }

    // 2. Copy into deleted_users
    $stmt = $pdo->prepare("
        INSERT INTO deleted_users 
        (id, full_name, email, rank, status, role, password_hash, joined_date, last_active, deleted_at)
        VALUES (:id, :full_name, :email, :rank, :status, :role, :password_hash, :joined_date, :last_active, NOW())
    ");
    $stmt->execute([
        ":id"            => $user['id'],             // encrypted ID
        ":full_name"     => $user['full_name'],
        ":email"         => $user['email'],
        ":rank"          => $user['rank'],
        ":status"        => $user['status'],
        ":role"          => $user['role'],
        ":password_hash" => $user['password_hash'],  
        ":joined_date"   => $user['joined_date'],
        ":last_active"   => $user['last_active']
    ]);

    // 3. Delete from users
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([":id" => $id]);

    echo json_encode(["success" => true, "message" => "User deleted and moved to archive"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "DB error: " . $e->getMessage()]);
}
