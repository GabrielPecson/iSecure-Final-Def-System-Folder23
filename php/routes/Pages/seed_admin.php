<?php
require 'db_connect.php'; // this should set up $pdo (PDO connection)

try {
    // Unique ID for the new user
    $id = uniqid();

    // Default password
    $plainPassword = "admin123";
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

    // Insert admin
    $stmt = $pdo->prepare("INSERT INTO users
      (id, full_name, email, rank, role, status, password_hash)
      VALUES (:id, :full_name, :email, :rank, :role, :status, :password_hash)");

    $stmt->execute([
        ':id' => $id,
        ':full_name' => 'System Admin',
        ':email' => 'admin@isecure.com',
        ':rank' => 'Captain',
        ':role' => 'Admin',
        ':status' => 'Active',
        ':password_hash' => $hash
    ]);

    header("Location: login-page.php");
    exit;

} catch (Exception $e) {
    echo " Error: " . $e->getMessage();
}
