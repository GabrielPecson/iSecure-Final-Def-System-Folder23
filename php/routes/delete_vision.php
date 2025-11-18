<?php
require 'auth_check.php';
require '../database/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: customizelanding.php");
    exit;
}

$id = intval($_POST['id']);

$stmt = $pdo->prepare("DELETE FROM landing_vision_mission WHERE id = :id");
$stmt->execute([':id' => $id]);

$activeTab = $_POST['active_tab'] ?? 'vision';
header("Location: customizelanding.php?active_tab=" . urlencode($activeTab));
exit;
?>
