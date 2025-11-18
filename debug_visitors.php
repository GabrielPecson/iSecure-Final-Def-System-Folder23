<?php
require 'php/database/db_connect.php';

try {
    echo "All visitors:\n";
    $stmt = $pdo->query('SELECT id, first_name, middle_name, last_name, status, time_in, time_out FROM visitors');
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($visitors as $v) {
        echo 'ID: ' . $v['id'] . ', Name: ' . $v['first_name'] . ' ' . $v['middle_name'] . ' ' . $v['last_name'] . ', Status: ' . $v['status'] . ', time_in: ' . $v['time_in'] . ', time_out: ' . $v['time_out'] . "\n";
    }

    echo "\nVisitation requests:\n";
    $stmt = $pdo->query('SELECT id, first_name, middle_name, last_name, status FROM visitation_requests');
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($requests as $r) {
        echo 'ID: ' . $r['id'] . ', Name: ' . $r['first_name'] . ' ' . $r['middle_name'] . ' ' . $r['last_name'] . ', Status: ' . $r['status'] . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
