<?php
require 'auth_check.php';
require 'db_connect.php';
require 'encryption_key.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if ($id) {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Update visitation request status to 'Rejected'
            $stmt = $pdo->prepare("UPDATE visitation_requests SET status = 'Rejected' WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Update associated visitors to 'Rejected' status
            $stmt = $pdo->prepare("UPDATE visitors SET status = 'Rejected' WHERE visitation_id = :visitation_id");
            $stmt->execute([':visitation_id' => $id]);

            // Update associated vehicles to 'Rejected' status
            $stmt = $pdo->prepare("UPDATE vehicles SET status = 'Rejected' WHERE visitation_id = :visitation_id");
            $stmt->execute([':visitation_id' => $id]);

            // Commit transaction
            $pdo->commit();

            // Log the cancellation
            $stmt = $pdo->prepare("INSERT INTO admin_audit_logs (user_id, action, ip_address, user_agent) VALUES (:user_id, :action, :ip, :agent)");
            $stmt->execute([
                ':user_id' => $_SESSION['user_id'],
                ':action' => "Rejected visitation request ID: $id",
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            echo json_encode(['success' => true, 'status' => 'Rejected']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing request ID']);
    }
}
