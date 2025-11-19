<?php
require 'db_connect.php';
require 'auth_check.php'; // Ensure session is started here

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['notification_message'] = "Invalid request method.";
    $_SESSION['notification_type'] = "error";
    header('Location: personnelaccounts.php');
    exit;
}

$id = $_POST['id'] ?? '';

if (empty($id)) {
    $_SESSION['notification_message'] = "Missing user ID for deletion.";
    $_SESSION['notification_type'] = "error";
    header('Location: personnelaccounts.php');
    exit;
}

try {
    // 1. Get the user first
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([":id" => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['notification_message'] = "User not found for deletion.";
        $_SESSION['notification_type'] = "error";
        header('Location: personnelaccounts.php');
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

    $_SESSION['notification_message'] = "User deleted and moved to archive successfully.";
    $_SESSION['notification_type'] = "success";
} catch (Exception $e) {
    $_SESSION['notification_message'] = "DB error during user deletion: " . $e->getMessage();
    $_SESSION['notification_type'] = "error";
}

$redirect_to = $_POST['redirect_to'] ?? 'personnelaccounts.php'; // Default fallback
header("Location: " . $redirect_to);
exit;
