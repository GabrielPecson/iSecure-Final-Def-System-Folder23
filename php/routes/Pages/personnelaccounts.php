<?php
require 'auth_check.php';
require 'audit_log.php';

// Default fallbacks so template never sees an undefined variable
$fullName = 'Unknown User';
$role = 'Unknown Role';

// Check if session token exists
if (!isset($_SESSION['token'])) {
    header("Location: login-page.php");
    exit;
}

// Validate token in DB and get session row
$stmt = $pdo->prepare("SELECT * FROM personnel_sessions WHERE token = :token AND expires_at > NOW()");
$stmt->execute([':token' => $_SESSION['token']]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    // Session expired or invalid
    session_unset();
    session_destroy();
    header("Location: login-page.php");
    exit;
}

// Fetch user info using the user_id from the personnel_sessions row
if (!empty($session['user_id'])) {
    $stmt = $pdo->prepare("SELECT full_name, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $session['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // always sanitize output
        $fullName = htmlspecialchars($user['full_name'] ?? 'Unknown User', ENT_QUOTES, 'UTF-8');
        $role = htmlspecialchars($user['role'] ?? 'Unknown Role', ENT_QUOTES, 'UTF-8');
    } else {
        // user record missing — log out to be safe
        session_unset();
        session_destroy();
        header("Location: login-page.php");
        exit;
    }
} else {
    // weird session row with no user_id — destroy and redirect
    session_unset();
    session_destroy();
    header("Location: login-page.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnel Accounts</title>

    <!-- Bootstrap + Fonts + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../../images/logo/5thFighterWing-logo.png">
    <!-- Your existing CSS -->
    <link rel="stylesheet" href="personnelaccounts.css">
    <link rel="stylesheet" href="sidebar.css">
    <link rel="stylesheet" href="notification.css">
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
            <h6 class="current-loc">Personnel Accounts</h6>
        </div>

        <div class="header-right">
            <i class="fa-regular fa-bell me-3"></i>
            <i class="fa-regular fa-message me-3"></i>

            <div class="user-info">
            <i class="fa-solid fa-user-circle fa-lg me-2"></i>
            <div class="user-text">
            <span class="username"><?php echo $fullName; ?></span>
            <a id="logout-link" class="logout-link" href="logout.php">Logout</a>
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
            </div>
  </div>
  
        </div>
    </div>

    <div class="personnel-container">
      <h3 class="title-perso"><i class="fa-solid fa-users"></i> Personnel Accounts</h3>
    <div class="personnel-topbar">
        <input class="search-bar" type="text" name="search" id="search" placeholder="Search by name or email">
        <i id="search-icon" class="fa-solid fa-search"></i>

        <div class="dropdown">
          <button class="role-btn"><i class="fa-solid fa-user"></i> Roles <i class="fa-solid fa-caret-down"></i></button>
          <div id="roleDropdown" class="dropdown-content">
            <a href="#" data-role="all">All Roles</a>
            <a href="#" data-role="Admin">Admin</a>
            <a href="#" data-role="User">User</a>
          </div>
        </div>
        <?php if ($role === 'Admin'): ?>
        <button id="addPersonnelBtn" class="add-btn">+ Add Personnel</button>
        <?php endif; ?>
    </div>

    <!-- Users Table -->
    <table id="userTable">
  <thead>
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
  <tbody id="usersTbody"></tbody>
</table>
  </div>

<!-- Add / Edit Personnel Modal -->
<div id="addUserModal" class="modal" aria-hidden="true">
  <div class="modal-content form-modal">
    <span class="close-modal" id="closeAddModal">&times;</span>
    <h2 id="addModalTitle">Create Personnel Account</h2>

    <form autocomplete="off" id="addUserForm" novalidate>
      <input type="hidden" id="editing_id" name="id" value="">

      <label for="full_name">Full Name</label>
      <input id="full_name" name="full_name" type="text" placeholder="Enter full name" required>

      <label for="email">Email</label>
      <input id="email" name="email" type="email" placeholder="Enter email" required>

      <label for="password">Password</label>
      <input id="password" name="password" type="password" placeholder="Enter password">

      <label for="password_confirm">Confirm Password</label>
      <input id="password_confirm" name="password_confirm" type="password" placeholder="Re-enter password">

      <label for="rank">Rank</label>
      <select id="rank" name="rank">
        <option value="Private">Private</option>
        <option value="Corporal">Corporal</option>
        <option value="Sergeant">Sergeant</option>
        <option value="Lieutenant">Lieutenant</option>
        <option value="Captain">Captain</option>
        <option value="Major">Major</option>
        <option value="Colonel">Colonel</option>
        <option value="General">General</option>
      </select>

      <label for="role">Role</label>
      <select id="roleSelect" name="role">
        <option value="User">User</option>
        <option value="Admin">Admin</option>
      </select>

      <label for="status">Status</label>
      <select id="status" name="status">
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
        <option value="Banned">Banned</option>
        <option value="Pending">Pending</option>
        <option value="Suspended">Suspended</option>
      </select>

      <div class="modal-actions">
        <button class="confirm" type="submit" id="saveUserBtn">Create Account</button>
        <button class="cancel" type="button" id="cancelAddBtn">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="modal" aria-hidden="true">
  <div class="modal-content form-modal">
    <span class="close-modal" id="closeEditModal">&times;</span>
    <h2>Edit Personnel Account</h2>

    <form autocomplete="off" id="editUserForm" novalidate>
      <input type="hidden" id="edit_user_id" name="id" value="">

      <label for="edit_full_name">Full Name</label>
      <input id="edit_full_name" name="full_name" type="text" placeholder="Enter full name" required>

      <label for="edit_email">Email</label>
      <input id="edit_email" name="email" type="email" placeholder="Enter email" required>

      <label for="edit_password">Password</label>
      <div class="password-container">
        <input id="edit_password" name="password" type="password" placeholder="Enter new password (leave empty to keep current)">
        <i class="fa-solid fa-eye" id="toggleEditPassword"></i>
      </div>

      <label for="edit_rank">Rank</label>
      <select id="edit_rank" name="rank">
        <option value="Private">Private</option>
        <option value="Corporal">Corporal</option>
        <option value="Sergeant">Sergeant</option>
        <option value="Lieutenant">Lieutenant</option>
        <option value="Captain">Captain</option>
        <option value="Major">Major</option>
        <option value="Colonel">Colonel</option>
        <option value="General">General</option>
      </select>

      <label for="edit_role">Role</label>
      <select id="edit_role" name="role">
        <option value="User">User</option>
        <option value="Admin">Admin</option>
      </select>

      <label for="edit_status">Status</label>
      <select id="edit_status" name="status">
        <option value="Active">Active</option>
        <option value="Inactive">Inactive</option>
        <option value="Banned">Banned</option>
        <option value="Pending">Pending</option>
        <option value="Suspended">Suspended</option>
      </select>

      <div class="modal-actions">
        <button class="confirm" type="submit" id="updateUserBtn"> Update Account</button>
        <button class="cancel" type="button" id="cancelEditBtn"> Cancel</button>
      </div>
    </form>
  </div>
</div>


<!-- Confirm + Notify Modals reuse your existing markup -->
<div id="confirmModal" class="modal">
  <div class="modal-content">
    <p id="confirmMessage"></p>
    <div class="modal-actions">
      <button id="confirmYes" class="btn btn-danger">Yes</button>
      <button id="confirmNo" class="btn btn-secondary">No</button>
    </div>
  </div>
</div>

<div id="notifyModal" class="custom-modal">
  <div class="custom-modal-content">
    <p id="notifyMessage">Action completed successfully!</p>
    <div class="modal-actions">
      <button id="notifyOk" class="btn ok-btn">OK</button>
    </div>
  </div>
</div>

</div>
</div>
</div>
<div id="notification-container"></div>

<!-- Scripts -->
<script src="sidebar.js"></script>
<script src="personnelaccount.js"></script>
<script src="session_check.js"></script>
<script src="notification.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>