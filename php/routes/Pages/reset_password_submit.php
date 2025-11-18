<?php
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';

    // Find reset request
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE reset_token = :reset_token AND expires_at > NOW()");
    $stmt->execute([':reset_token' => $token]);
    $reset = $stmt->fetch();

    if ($reset) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Update password in users
        $stmt = $pdo->prepare("UPDATE users SET password_hash = :password WHERE id = :id");
        $stmt->execute([
            ':password' => $hashed,
            ':id' => $reset['user_id']
        ]);

        // Delete reset token
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE reset_token = :reset_token");
        $stmt->execute([':reset_token' => $token]);

        header("Location: Pages\login-page.php?reset=success");
        log_admin_action($pdo, $email, "User Resets Password");
        exit;
    } else {
        die("Invalid or expired reset request.");
    }
}
