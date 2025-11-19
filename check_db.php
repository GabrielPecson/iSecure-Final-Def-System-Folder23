<?php
require 'php/routes/Pages/db_connect.php';

try {
    $stmt = $pdo->query('DESCRIBE visitors');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Visitors table columns:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . "\n";
    }

    echo "\nVehicles table columns:\n";
    $stmt = $pdo->query('DESCRIBE vehicles');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . ' - ' . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
