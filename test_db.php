<?php
include 'php/database/db_connect.php';
try {
    $stmt = $pdo->prepare('SELECT id, first_name, selfie_photo_path, id_photo_path FROM visitors LIMIT 1');
    $stmt->execute();
    $visitor = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($visitor) {
        echo 'Visitor ID: ' . $visitor['id'] . PHP_EOL;
        echo 'Selfie Path: ' . ($visitor['selfie_photo_path'] ?: 'NULL') . PHP_EOL;
        echo 'ID Path: ' . ($visitor['id_photo_path'] ?: 'NULL') . PHP_EOL;
    } else {
        echo 'No visitors found.' . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
