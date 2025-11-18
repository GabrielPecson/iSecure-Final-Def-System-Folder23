<?php
require 'auth_check.php';
require '../database/db_connect.php';

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

// Fetch all clearance badges with visitor names
$stmt = $pdo->query("SELECT cb.*, v.first_name, v.last_name FROM clearance_badges cb JOIN visitors v ON cb.visitor_id = v.id ORDER BY cb.issued_at DESC");
$badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Personnel Key Cards List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../../images/logo/5thFighterWing-logo.png">
    <link rel="stylesheet" href="../../stylesheet/personnel_dashboard.css" />
    <link rel="stylesheet" href="../../stylesheet/sidebar.css" />
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
            <i class="fa-solid fa-id-badge"></i>
            <h6 class="path"> / Dashboard /</h6>
            <h6 class="current-loc">Key Cards List</h6>
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
    <div class="container-fluid mt-4">
        <h2>All Issued Key Cards</h2>
        <div class="table-responsive">
            <table class="table table-striped table-bordered key-card-list-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Key Card Number</th>
                        <th>Validity Start</th>
                        <th>Validity End</th>
                        <th>Status</th>
                        <th>Issued At</th>
                        <th>Updated At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($badges)): ?>
                        <tr>
                            <td colspan="9" class="text-center">No key cards found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($badges as $badge): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($badge['id']); ?></td>
                                <td><?php echo htmlspecialchars($badge['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($badge['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($badge['key_card_number']); ?></td>
                                <td><?php echo date('Y-m-d h:i:s A', strtotime($badge['validity_start'])); ?></td>
                                <td><?php echo date('Y-m-d h:i:s A', strtotime($badge['validity_end'])); ?></td>
                                <td><?php echo htmlspecialchars($badge['status']); ?></td>
                                <td><?php echo date('Y-m-d h:i:s A', strtotime($badge['issued_at'])); ?></td>
                                <td><?php echo date('Y-m-d h:i:s A', strtotime($badge['updated_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
    </div>
</div>
<script src="../../scripts/sidebar_personnel.js"></script>
<script src="../../scripts/personnel_dashboard.js"></script>
<script src="../../scripts/session_check.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>