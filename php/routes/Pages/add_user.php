<?php
require 'db_connect.php';
require 'encryption_key.php';

$data = $_POST;

if (!$data || empty($data['full_name']) || empty($data['email']) || empty($data['password']) || empty($data['rank']) || empty($data['status']) || empty($data['role'])) {
    echo json_encode(["success" => false, "message" => "Missing required fields"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (id, full_name, email, rank, status, password_hash, role, joined_date, last_active)
        VALUES (UUID(), :full_name, :email, :rank, :status, :password_hash, :role, NOW(), NOW())
    ");
    $stmt->execute([
        ":full_name"     => $data['full_name'],
        ":email"         => $data['email'],
        ":rank"          => $data['rank'],
        ":status"        => $data['status'],
        ":password_hash" => password_hash($data['password'], PASSWORD_DEFAULT),
        ":role"          => $data['role'],
    ]);

    echo json_encode(["success" => true, "message" => "User added successfully"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "DB error: " . $e->getMessage()]);
}
