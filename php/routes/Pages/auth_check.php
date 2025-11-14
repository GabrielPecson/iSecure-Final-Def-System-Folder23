<?php
session_start();
require '../database/db_connect.php';

// Default fallbacks
$fullName = 'Unknown User';
$role     = 'Unknown Role';

// ✅ 1. Check if session token exists
if (empty($_SESSION['token'])) {
    header("Location: ../../php/routes/Pages/login-page.php?not_logged_in=1");
    exit;
}

// ✅ 2. Validate token in DB (must exist + not expired)
$stmt = $pdo->prepare("
    SELECT ps.user_id, u.full_name, u.role 
    FROM personnel_sessions ps
    JOIN users u ON ps.user_id = u.id
    WHERE ps.token = :token AND ps.expires_at > NOW()
    LIMIT 1
");
$stmt->execute([':token' => $_SESSION['token']]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    // Token missing or expired (user logged in elsewhere or timed out)
    session_unset();
    session_destroy();
    header("Location: ../../php/routes/Pages/login-page.php?expired=1");
    exit;
}

// ✅ 3. Set user_id in session for later use
$_SESSION['user_id'] = $session['user_id'];

// ✅ 4. Sanitize output for use in UI
$fullName = htmlspecialchars($session['full_name'] ?? 'Unknown User', ENT_QUOTES, 'UTF-8');
$role     = htmlspecialchars($session['role'] ?? 'Unknown Role', ENT_QUOTES, 'UTF-8');
