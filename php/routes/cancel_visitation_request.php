<?php
require 'auth_check.php';
require '../database/db_connect.php';
require '../config/encryption_key.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if ($id) {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Update visitation request status to Cancelled
            $stmt = $pdo->prepare("UPDATE visitation_requests SET status = 'Cancelled' WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Update associated visitors to Cancelled status
            $stmt = $pdo->prepare("UPDATE visitors SET status = 'Cancelled' WHERE visitation_id = :visitation_id");
            $stmt->execute([':visitation_id' => $id]);

            // Update associated vehicles to Cancelled status
            $stmt = $pdo->prepare("UPDATE vehicles SET status = 'Cancelled' WHERE visitation_id = :visitation_id");
            $stmt->execute([':visitation_id' => $id]);

            // Commit transaction
            $pdo->commit();

            // Log the cancellation
            $stmt = $pdo->prepare("INSERT INTO admin_audit_logs (user_id, action, ip_address, user_agent) VALUES (:user_id, :action, :ip, :agent)");
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':action' => "Cancelled visitation request ID: $id",
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            echo json_encode(['success' => true, 'status' => 'Cancelled']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing request ID']);
    }
}
