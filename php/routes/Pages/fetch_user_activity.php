<?php
require 'db_connect.php';

header('Content-Type: application/json');

try {
    // Join with users table to get full_name
    $stmt = $pdo->prepare("
        SELECT a.action, a.created_at, u.full_name
        FROM admin_audit_logs a
        LEFT JOIN users u ON u.id = a.user_id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set full_name or fallback to 'Unknown'
    foreach ($logs as &$log) {
        $log['full_name'] = $log['full_name'] ?: 'Unknown';
    }

    echo json_encode($logs);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}