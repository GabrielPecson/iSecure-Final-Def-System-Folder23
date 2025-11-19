<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';

    if (!empty($id)) {
        try {
            $pdo->beginTransaction();

            // Update visitation request status to 'Cancelled'
            $stmt = $pdo->prepare("UPDATE visitation_requests SET status = 'Cancelled' WHERE id = :id");
            $stmt->execute([':id' => $id]);

            // Update associated visitors to 'Cancelled' status
            $stmt = $pdo->prepare("UPDATE visitors SET status = 'Cancelled' WHERE visitation_id = :visitation_id");
            $stmt->execute([':visitation_id' => $id]);

            // Update associated vehicles to 'Cancelled' status
            $stmt = $pdo->prepare("UPDATE vehicles SET status = 'Cancelled' WHERE visitation_id = :visitation_id");
            $stmt->execute([':visitation_id' => $id]);

            // Log the cancellation, if user is logged in
            if (isset($_SESSION['user_id'])) {
                $stmt = $pdo->prepare("INSERT INTO admin_audit_logs (user_id, action, ip_address, user_agent) VALUES (:user_id, :action, :ip, :agent)");
                $stmt->execute([
                    ':user_id' => $_SESSION['user_id'],
                    ':action' => "Cancelled visitation request ID: $id",
                    ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    ':agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
            }

            $pdo->commit();

            $_SESSION['notification_message'] = "Visitation request has been cancelled.";
            $_SESSION['notification_type'] = 'warning';

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $_SESSION['notification_message'] = 'Error cancelling request: ' . $e->getMessage();
            $_SESSION['notification_type'] = 'error';
        }
    } else {
        $_SESSION['notification_message'] = 'Error: Missing request ID.';
        $_SESSION['notification_type'] = 'error';
    }
} else {
    $_SESSION['notification_message'] = 'Error: Invalid request method.';
    $_SESSION['notification_type'] = 'error';
}

header('Location: pendings.php');
exit;