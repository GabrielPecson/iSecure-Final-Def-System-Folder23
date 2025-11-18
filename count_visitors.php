<?php
require 'php/database/db_connect.php';

try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM visitors WHERE status = \'Expected\' AND time_in IS NULL');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Expected visitors count: ' . $result['count'] . PHP_EOL;

    $stmt = $pdo->query('SELECT COUNT(*) as count FROM visitors WHERE time_in IS NOT NULL AND time_out IS NULL AND status != \'Rejected\'');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Inside visitors count: ' . $result['count'] . PHP_EOL;

    $stmt = $pdo->query('SELECT COUNT(*) as count FROM visitors WHERE time_in IS NOT NULL AND time_out IS NOT NULL AND status != \'Rejected\'');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Exited visitors count: ' . $result['count'] . PHP_EOL;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
