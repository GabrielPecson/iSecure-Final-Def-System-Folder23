<?php
require 'db_connect.php';
require 'auth_check.php';

$data = $_POST;

if (!$data || empty($data['full_name']) || empty($data['email']) || empty($data['password']) || empty($data['rank']) || empty($data['status']) || empty($data['role'])) {
    $_SESSION['notification_message'] = 'Missing required fields.';
    $_SESSION['notification_type'] = 'error';
    header('Location: personnelaccounts.php');
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

    $_SESSION['notification_message'] = 'User added successfully.';
    $_SESSION['notification_type'] = 'success';
} catch (Exception $e) {
    $_SESSION['notification_message'] = 'DB error: ' . $e->getMessage();
    $_SESSION['notification_type'] = 'error';
}

header('Location: personnelaccounts.php');
exit;
?>
