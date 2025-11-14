<?php
require 'db_connect.php';
require 'encryption_key.php';

try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY joined_date DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $users = [];
    foreach ($rows as $r) {
        $users[] = [
            "id"          => $r["id"],
            "full_name"   => $r["full_name"],
            "email"       => $r["email"],
            "rank"        => $r["rank"],
            "status"      => $r["status"],
            "role"        => $r["role"],
            "joined_date" => $r["joined_date"],
            "last_active" => $r["last_active"]
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($users);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
