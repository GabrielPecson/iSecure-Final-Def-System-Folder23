<?php
require 'auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Get POST data and sanitize
$id = $_POST['id'] ?? '';
$full_name = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$rank = $_POST['rank'] ?? '';
$role = $_POST['role'] ?? '';
$status = $_POST['status'] ?? '';

if (empty($id) || empty($full_name) || empty($email)) {
    echo json_encode(['status' => 'error', 'message' => 'Required fields missing']);
    exit;
}

// Prepare update query
$updateFields = "full_name = :full_name, email = :email, rank = :rank, role = :role, status = :status";
$params = [
    ':full_name' => $full_name,
    ':email' => $email,
    ':rank' => $rank,
    ':role' => $role,
    ':status' => $status,
    ':id' => $id
];

// If password is provided, hash and include in update
if (!empty($password)) {
    $updateFields .= ", password_hash = :password_hash";
    $params[':password_hash'] = password_hash($password, PASSWORD_DEFAULT);
}

// Update user in DB
$stmt = $pdo->prepare("UPDATE users SET $updateFields WHERE id = :id");
$success = $stmt->execute($params);

if ($success) {
    echo json_encode(['status' => 'success', 'message' => 'User updated successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update user']);
}
?>
