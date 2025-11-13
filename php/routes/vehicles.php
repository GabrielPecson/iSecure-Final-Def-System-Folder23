<?php
require 'auth_check.php';
require '../config/encryption_key.php';

// Default fallbacks
$fullName = 'Unknown User';
$role = 'Unknown Role';

// Check if session token exists
if (!isset($_SESSION['token'])) {
    header("Location: Pages/login-page.php");
    exit;
}

// Validate token in DB
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../../images/logo/5thFighterWing-logo.png">
  <link rel="stylesheet" href="../../stylesheet/vehicles.css">
  <link rel="stylesheet" href="../../stylesheet/sidebar.css">
  <link rel="stylesheet" href="../../stylesheet/notification.css">
  <title>Vehicles</title>
</head>
<body>
<div class="body">
  <div class="left-panel">
    <div id="sidebar-container"></div>
  </div>

  <div class="right-panel">
    <div class="main-content">
      <div class="main-header d-flex justify-content-between align-items-center">
        <div class="header-left d-flex align-items-center">
          <i class="fa-solid fa-home me-2"></i>
          <h6 class="path mb-0"> / Dashboard /</h6>
          <h6 class="current-loc mb-0 ms-1">Vehicles</h6>
        </div>
        <div class="header-right d-flex align-items-center">
          <i class="fa-regular fa-bell me-3"></i>
          <i class="fa-regular fa-message me-3"></i>
          <div class="user-info d-flex align-items-center">
            <i class="fa-solid fa-user-circle fa-lg me-2"></i>
            <div class="user-text">
              <span class="username"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></span>
              <a id="logout-link" class="logout-link" href="logout.php">Logout</a>
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

      <!-- ==== Expected Vehicles Table ==== -->
      <div class="vehicles-container mb-4">
        <h5 class="table-title">Expected Vehicles</h5>
        <div class="table-responsive">
          <table id="expectedVehiclesTable" class="table table-striped">
            <thead>
              <tr>
                <th>Driver</th>
                <th>Brand</th>
                <th>Model</th>
                <th>Color</th>
                <th>Plate No.</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="6" class="text-center">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ==== Inside Vehicles Table ==== -->
      <div class="vehicles-container mb-4">
        <h5 class="table-title">Inside Vehicles</h5>
        <div class="table-responsive">
          <table id="insideVehiclesTable" class="table table-striped">
            <thead>
              <tr>
                <th>Driver</th>
                <th>Brand</th>
                <th>Model</th>
                <th>Color</th>
                <th>Plate No.</th>
                <th>Entry Time</th>
                <th>Exit Time</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="8" class="text-center">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    <!-- ==== Exited Vehicles Table ==== -->
 <div class="vehicles-container mb-4">
  <h5 class="table-title">Exited Vehicles</h5>
  <div class="table-responsive">
    <table id="exitedVehiclesTable" class="table table-striped">
      <thead>
        <tr>
          <th>Driver</th>
          <th>Brand</th>
          <th>Model</th>
          <th>Color</th>
          <th>Plate No.</th>
          <th>Entry Time</th>
          <th>Exit Time</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td colspan="8" class="text-center">Loading...</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>


    </div>
  </div>
</div>
<div id="notification-container"></div>

<script src="../../scripts/sidebar.js"></script>
<script src="../../scripts/vehicles.js"></script>
<script src="../../scripts/session_check.js"></script>
<script src="../../scripts/notification.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>