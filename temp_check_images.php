<?php
require 'php/routes/Pages/db_connect.php';

$stmt = $pdo->query('SELECT id, first_name, middle_name, last_name, valid_id_path, selfie_photo_path FROM visitation_requests ORDER BY id DESC LIMIT 5');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo 'ID: ' . $row['id'] . ' - Name: ' . $row['first_name'] . ' ' . $row['last_name'] . ' - ID Path: ' . $row['valid_id_path'] . ' - Selfie Path: ' . $row['selfie_photo_path'] . "\n";
}
?>
