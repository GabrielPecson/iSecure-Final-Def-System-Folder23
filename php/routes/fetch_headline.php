<?php
require 'auth_check.php';
require 'Pages/db_connect.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No ID provided']);
    exit;
}

$id = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT * FROM news_headlines WHERE id = :id");
$stmt->execute([':id' => $id]);
$headline = $stmt->fetch(PDO::FETCH_ASSOC);

if ($headline) {
    echo json_encode($headline);
} else {
    echo json_encode(['error' => 'Headline not found']);
}
?>