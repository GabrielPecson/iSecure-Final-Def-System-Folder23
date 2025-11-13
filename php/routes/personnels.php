<?php
require 'auth_check.php';
require 'audit_log.php';

// Default fallbacks so template never sees an undefined variable
$fullName = 'Unknown User';
$role = 'Unknown Role';

// Check if session token exists
if (!isset($_SESSION['token'])) {
    header("Location: Pages/login-page.php");
    exit;
}

// Validate token
$stmt = $pdo->prepare("SELECT * FROM personnel_sessions WHERE token = :token AND expires_at > NOW()");
$stmt->execute([':token' => $_SESSION['token']]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    session_unset();
    session_destroy();
    header("Location: Pages/login-page.php");
    exit;
}

// Fetch logged-in user info
if (!empty($session['user_id'])) {
    $stmt = $pdo->prepare("SELECT full_name, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $session['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $fullName = htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8');
        $role = htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Personnels</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../../images/logo/5thFighterWing-logo.png">
  <link rel="stylesheet" href="../../stylesheet/personnels.css">
    <link rel="stylesheet" href="../../stylesheet/sidebar.css">
    <link rel="stylesheet" href="../../stylesheet/notification.css">
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
          <h6 class="current-loc">Personnels</h6>
        </div>
        <div class="header-right">
          <i class="fa-regular fa-bell me-3"></i>
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

      <!-- Personnel Table -->
      <div class="personnel-container">
        <h4 class="mb-3">All Personnel Users</h4>
        <table class="table table-striped table-hover">
          <thead class="table-primary">
            <tr>
              <th>Full Name</th>
              <th>Email</th>
              <th>Rank</th>
              <th>Status</th>
              <th>Role</th>
              <th>Joined</th>
              <th>Last Active</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="personnelsTbody"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div id="editUserModal" class="modal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="editUserForm">
          <input type="hidden" name="id" id="edit_id">
          <div class="mb-3">
            <label>Full Name</label>
            <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
          </div>
          <div class="mb-3">
            <label>Email</label>
            <input type="email" class="form-control" id="edit_email" name="email" required>
          </div>
          <div class="mb-3">
            <label>Rank</label>
            <input type="text" class="form-control" id="edit_rank" name="rank">
          </div>
          <div class="mb-3">
            <label>Status</label>
            <select class="form-control" id="edit_status" name="status">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
              <option value="Banned">Banned</option>
              <option value="Pending">Pending</option>
              <option value="Suspended">Suspended</option>
            </select>
          </div>
          <div class="mb-3">
            <label>Role</label>
            <select class="form-control" id="edit_role" name="role">
              <option value="Admin">Admin</option>
              <option value="User">User</option>
              <option value="Moderator">Moderator</option>
              <option value="Guest">Guest</option>
            </select>
          </div>
          <button type="submit" class="btn btn-success">Save Changes</button>
        </form>
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
<div id="notification-container"></div>

<script src="../../scripts/sidebar.js"></script>
<script src="../../scripts/personnels.js"></script>
<script src="../../scripts/session_check.js"></script>
<script src="../../scripts/notification.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
