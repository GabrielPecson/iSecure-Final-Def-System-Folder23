<?php
session_start();

header('Content-Type: application/json'); // Always JSON for AJAX

try {
    require_once __DIR__ . '/db_connect.php';
    require_once __DIR__ . '/audit_log.php';

    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Lookup user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        log_admin_action($pdo, $email, "Failed login attempt");
        echo json_encode(['error' => 'Invalid email or password!']);
        exit;
    }

    log_admin_action($pdo, $user['id'], "User logged in");

    // Remove old sessions
    $stmt = $pdo->prepare("DELETE FROM personnel_sessions WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user['id']]);

    $token = bin2hex(random_bytes(32));
    $sessionId = uniqid();

    $stmt = $pdo->prepare("
        INSERT INTO personnel_sessions (id, user_id, token, expires_at)
        VALUES (:id, :user_id, :token, DATE_ADD(NOW(), INTERVAL 1 HOUR))
    ");
    $stmt->execute([
        ':id' => $sessionId,
        ':user_id' => $user['id'],
        ':token' => $token
    ]);

    session_regenerate_id(true);
    $_SESSION['token'] = $token;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];

    $redirect = $user['role'] === 'Admin' ? 'maindashboard.php' : 'personnel_dashboard.php';

    echo json_encode(['redirect' => $redirect]);
    exit;

} catch (\Throwable $e) {
    // Catch all errors and send JSON
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
}
