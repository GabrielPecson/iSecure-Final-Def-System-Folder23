<?php
require 'auth_check.php';
require '../database/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['section_type'])) {
    header("Location: customizelanding.php");
    exit;
}

$sectionType = $_POST['section_type'];

// Reset the section to empty (no hard delete, as sections are fixed)
$stmt = $pdo->prepare("UPDATE landing_sidebar_sections SET title = '', content = '', image_path = NULL WHERE section_type = :type");
$stmt->execute([':type' => $sectionType]);

$activeTab = $_POST['active_tab'] ?? 'sidebar';
header("Location: customizelanding.php?active_tab=" . urlencode($activeTab));
exit;
?>
