<?php
require 'auth_check.php';
require 'Pages/db_connect.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Visitor ID missing.");
}

$stmt = $pdo->prepare("DELETE FROM visitors WHERE id = :id");
$stmt->execute([':id' => $id]);

header("Location: visitors.php");
exit;
