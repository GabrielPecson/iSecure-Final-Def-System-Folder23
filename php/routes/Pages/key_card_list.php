<?php
require 'auth_check.php';
require 'db_connect.php';

// Fetch all clearance badges with visitor names
$access_logs = [];
$error_message = '';

try {
    // Fetch access logs and join with clearance badges and visitors to get the visitor's name and card name
    $stmt_logs = $pdo->query("
        SELECT
            al.timestamp,
            al.uid,
            al.door,
            al.status,
            al.reason,
            cb.card_name,
            v.first_name,
            v.last_name
        FROM access_logs al
        LEFT JOIN clearance_badges cb ON al.uid = cb.key_card_number
        LEFT JOIN visitors v ON cb.visitor_id = v.id
        ORDER BY al.timestamp DESC
        LIMIT 500
    ");
    $access_logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If a query fails (e.g., table doesn't exist), show an error instead of a 500 page.
    $error_message = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Key Cards List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="5thFighterWing-logo.png">
    <link rel="stylesheet" href="admin_maindashboard.css" />
    <link rel="stylesheet" href="sidebar.css" />
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
            <h6 class="current-loc">Key Cards List</h6>
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

    <div class="container-fluid mt-4">
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <h4>An Error Occurred</h4>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <p>Please ensure the 'access_logs', 'clearance_badges', and 'visitors' tables exist and are accessible.</p>
            </div>
        <?php else: ?>
        <h2>Key Card Access Logs</h2>
        <div class="table-responsive">
            <table class="table table-striped table-bordered key-card-list-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Visitor Name</th>
                        <th>Card Name</th>
                        <th>Card UID Used</th>
                        <th>Door</th>
                        <th>Status</th>
                        <th>Details / Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($access_logs)): ?>
                        <tr>
                            <td colspan="6" class="text-center">No key card access logs found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($access_logs as $log): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($log['timestamp']))); ?></td>
                                <td><?php echo htmlspecialchars(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? 'Unknown/Unassigned')); ?></td>
                                <td><?php echo htmlspecialchars($log['card_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($log['uid']); ?></td>
                                <td><?php echo htmlspecialchars($log['door']); ?></td>
                                <td><span class="badge bg-<?php echo $log['status'] === 'GRANTED' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($log['status']); ?></span></td>
                                <td><?php echo htmlspecialchars($log['reason']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    </div>
    </div>
</div>
<script src="sidebar.js"></script>
<script src="logout.js"></script>
</body>
</html>