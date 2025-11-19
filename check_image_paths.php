<?php
require 'php/routes/Pages/db_connect.php';

try {
    echo "Visitor image paths:\n";
    $stmt = $pdo->query('SELECT id, id_photo_path, selfie_photo_path FROM visitors');
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($visitors as $v) {
        echo 'ID: ' . $v['id'] . ', ID Photo: ' . ($v['id_photo_path'] ?: 'NULL') . ', Selfie: ' . ($v['selfie_photo_path'] ?: 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
