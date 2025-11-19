<?php
require 'auth_check.php';
require 'audit_log.php';

// Default fallbacks
$fullName = 'Unknown User';
$role = 'Unknown Role';

// Check session
if (!isset($_SESSION['token'])) {
    header("Location: Pages/login-page.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM personnel_sessions WHERE token = :token AND expires_at > NOW()");
$stmt->execute([':token' => $_SESSION['token']]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    session_unset();
    session_destroy();
    header("Location: Pages/login-page.php");
    exit;
}

// Fetch user info
if (!empty($session['user_id'])) {
    $stmt = $pdo->prepare("SELECT full_name, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $session['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $fullName = htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8');
        $role = htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8');
    }
}

// Check if role is Personnel (assuming 'User' is personnel)
if ($role !== 'User') {
    echo "<script>alert('Access denied. Personnel only.'); window.location.href='Pages/login-page.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Personnel Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../../images/logo/5thFighterWing-logo.png">
  <link rel="stylesheet" href="../../stylesheet/personnel_dashboard.css">
  <link rel="stylesheet" href="../../stylesheet/admin_maindashboard.css">
  <link rel="stylesheet" href="../../stylesheet/sidebar.css">
</head>
<body>

<div class="body">
  <div class="left-panel">
    <div id="sidebar-container"></div>
  </div>

  <div class="right-panel">
    <div class="main-content">
      <div class="main-header">
        <div class="header-left">
          <i class="fa-solid fa-home"></i>
          <h6 class="path"> / Dashboard /</h6>
          <h6 class="current-loc">Personnel Dashboard</h6>
        </div>
        <div class="header-right">
          <div class="notification-dropdown">
            <i class="fa-regular fa-bell me-3" id="notification-bell"></i>
            <div class="notification-menu" id="notification-menu">
              <div class="notification-header">Notifications</div>
              <div id="notification-list">
                <!-- Notifications will be loaded here -->
              </div>
            </div>
          </div>
          <i class="fa-regular fa-message me-3"></i>
          <div class="user-info">
            <i class="fa-solid fa-user-circle fa-lg me-2"></i>
            <div class="user-text">
              <span class="username"><?php echo $fullName; ?></span>
              <a id="logout-link" class="logout-link" href="logout.php">Logout</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Confirm Modal -->
      <div id="confirmModal" class="modal">
        <div class="modal-content">
          <p id="confirmMessage"></p>
          <div class="modal-actions">
            <button id="confirmYes" class="btn btn-danger">Yes</button>
            <button id="confirmNo" class="btn btn-secondary">No</button>
          </div>
        </div>
      </div>

      <div class="welcome-section">
        <h4>Welcome to Personnel Dashboard</h4>
        <p>Use the sidebar to navigate to different sections: Walk-in Visit, View Notifications, Visitors Inside, Key Cards, and Key Card List.</p>
      </div>


    </div>
  </div>
</div>

<script src="../../scripts/sidebar_personnel.js"></script>
<script src="../../scripts/personnel_dashboard.js"></script>
<script src="../../scripts/session_check.js"></script>
<script src="Pages/logout.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>