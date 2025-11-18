<?php
require 'auth_check.php';

// SSE headers
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");
header("Connection: keep-alive");

// Allow immediate flush
while (ob_get_level() > 0) {
    ob_end_flush();
}
ob_implicit_flush(true);

if (!isset($_SESSION['token'])) {
    echo "event: logout\n";
    echo "data: Session missing\n\n";
    flush();
    exit;
}

$token = $_SESSION['token'];

// Loop and check session validity
while (true) {
    // ⚡ Prevent PHP session lock issues
    session_write_close();

    $stmt = $pdo->prepare("SELECT 1 FROM personnel_sessions 
                           WHERE token = :token AND expires_at > NOW() LIMIT 1");
    $stmt->execute([':token' => $token]);
    $valid = $stmt->fetchColumn();

    if (!$valid) {
        echo "event: logout\n";
        echo "data: Your session has been terminated\n\n";
        flush();
        break; // stop streaming
    }

    echo "event: keepalive\n";
    echo "data: still-valid\n\n";
    flush();

    sleep(5); // check every 5 seconds
}
