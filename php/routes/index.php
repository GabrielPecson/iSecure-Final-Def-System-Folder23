<?php
session_start();
require 'Pages/db_connect.php';
// Check if any admin user exists
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'Admin'");
$stmt->execute();
$adminCount = $stmt->fetchColumn();

// If no admin exists, redirect to seed_admin.php
if ($adminCount == 0) {
    header("Location: Pages/seed_admin.php");
    exit;
}

// If admin already exists, redirect to login page
header("Location: Pages/home-page.php");
exit;
?>
