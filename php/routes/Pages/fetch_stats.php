<?php
require 'db_connect.php';
header('Content-Type: application/json');

try {
    // Current visitors (Inside)
    $stmt = $pdo->query("SELECT COUNT(*) FROM visitors WHERE status = 'Inside'");
    $visitors = $stmt->fetchColumn();

    // Current vehicles (Inside)
    $stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'Inside'");
    $vehicles = $stmt->fetchColumn();

    // Pendings
    $stmt = $pdo->query("SELECT COUNT(*) FROM visitation_requests WHERE status = 'Pending'");
    $pendings = $stmt->fetchColumn();

    // Door entries (leave 0 for now)
    $entries = 0;

    echo json_encode([
        'visitors' => $visitors,
        'vehicles' => $vehicles,
        'pendings' => $pendings,
        'entries' => $entries
    ]);
} catch (Exception $e) {
    echo json_encode(['visitors' => 0, 'vehicles' => 0, 'pendings' => 0, 'entries' => 0]);
}