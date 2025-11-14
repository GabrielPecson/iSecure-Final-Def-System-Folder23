<?php
require 'db_connect.php';

function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // in case of proxy, take first IP
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    }
}

function log_admin_action($pdo, $user_id, $action) {
    $ip = get_client_ip();
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    $stmt = $pdo->prepare("INSERT INTO admin_audit_logs (user_id, action, ip_address, user_agent) 
                           VALUES (:user_id, :action, :ip, :agent)");
    $stmt->execute([
        ':user_id' => $user_id,
        ':action'  => $action,
        ':ip'      => $ip,
        ':agent'   => $agent
    ]);
}

function log_landing_action($pdo, $user_id, $action) {
    // 1. Check if session exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM visitor_sessions WHERE user_token = ?");
    $stmt->execute([$user_id]);
    
    if ($stmt->fetchColumn() == 0) {
        // 2. Session doesn't exist → create it
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // adjust expiry as needed
        $insertSession = $pdo->prepare("INSERT INTO visitor_sessions (user_token, created_at, expires_at) VALUES (?, CURRENT_TIMESTAMP(), ?)");
        $insertSession->execute([$user_id, $expires_at]);
    }

    // 3. Log the landing action
    $ip = get_client_ip();
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    $stmtLog = $pdo->prepare("INSERT INTO landing_audit_logs (user_id, action, ip_address, user_agent) 
                              VALUES (:user_id, :action, :ip, :agent)");
    $stmtLog->execute([
        ':user_id' => $user_id,
        ':action'  => $action,
        ':ip'      => $ip,
        ':agent'   => $agent
    ]);
}

?>


