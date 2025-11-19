<?php
require 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Not a POST request, redirect away
    header('Location: personnelaccounts.php');
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
    $_SESSION['notification_message'] = 'Required fields missing.';
    $_SESSION['notification_type'] = 'error';
    header('Location: personnelaccounts.php');
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
    $_SESSION['notification_message'] = 'User updated successfully.';
    $_SESSION['notification_type'] = 'success';
} else {
    $_SESSION['notification_message'] = 'Failed to update user.';
    $_SESSION['notification_type'] = 'error';
}

$redirect_to = $_POST['redirect_to'] ?? 'personnelaccounts.php'; // Default fallback
header('Location: ' . $redirect_to);
exit;
?>
