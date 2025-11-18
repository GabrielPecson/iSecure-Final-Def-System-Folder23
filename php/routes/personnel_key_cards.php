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

// Fetch all visitors for selection
$stmt = $pdo->query("SELECT id, first_name, last_name FROM visitors");
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Personnel Key Cards</title>
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
        <h6 class="current-loc">Key Cards</h6>
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
    <h2>Visitor Key Card Management</h2>

    <div class="mb-3">
        <label for="visitorSelect" class="form-label">Select Visitor</label>
        <select id="visitorSelect" class="form-select">
            <option value="">-- Select Visitor --</option>
            <?php foreach ($visitors as $visitor): ?>
                <option value="<?= htmlspecialchars($visitor['id']) ?>"><?= htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="badgeList" class="mb-4"></div>

    <h4 id="formTitle">Issue New Key Card</h4>
    <form id="badgeForm">
        <input type="hidden" id="badgeId" name="id" value="" />
        <div class="mb-3">
            <label for="keyCardNumber" class="form-label">Key Card Number</label>
            <input type="text" id="keyCardNumber" name="key_card_number" class="form-control" required />
        </div>
        <div class="mb-3">
            <label for="validityStart" class="form-label">Validity Start</label>
            <input type="datetime-local" id="validityStart" name="validity_start" class="form-control" required />
        </div>
        <div class="mb-3">
            <label for="validityEnd" class="form-label">Validity End</label>
            <input type="datetime-local" id="validityEnd" name="validity_end" class="form-control" required />
        </div>
        <div class="mb-3" id="statusField" style="display:none;">
            <label for="badgeStatus" class="form-label">Status</label>
            <select id="badgeStatus" name="status" class="form-control">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="terminated">Terminated</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" id="submitBtn">Issue Key Card</button>
        <button type="button" class="btn btn-danger" id="terminateBtn" style="display:none;">Terminate Key Card</button>
        <button type="button" class="btn btn-secondary" id="cancelEditBtn" style="display:none;">Cancel</button>
    </form>
</div>

<script>
document.getElementById('visitorSelect').addEventListener('change', function() {
    const visitorId = this.value;
    if (!visitorId) {
        document.getElementById('badgeList').innerHTML = '';
        return;
    }
    fetch(`clearance_badge_management.php?action=fetch&visitor_id=${visitorId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<h5>Existing Key Cards</h5><ul class="list-group">';
                data.badges.forEach(badge => {
                    html += `<li class="list-group-item">
                        <strong>Key Card #:</strong> ${badge.key_card_number} |
                        <strong>Valid:</strong> ${badge.validity_start} to ${badge.validity_end} |
                        <strong>Status:</strong> ${badge.status}
                        <button class="btn btn-sm btn-link float-end" onclick="editBadge(${badge.id}, '${badge.key_card_number}', '${badge.validity_start}', '${badge.validity_end}', '${badge.status}')">Edit</button>
                    </li>`;
                });
                html += '</ul>';
                document.getElementById('badgeList').innerHTML = html;
            } else {
                document.getElementById('badgeList').innerHTML = '<p>No badges found.</p>';
            }
        });
});

function editBadge(id, number, start, end, status) {
    document.getElementById('formTitle').textContent = 'Edit Key Card';
    document.getElementById('badgeId').value = id;
    document.getElementById('keyCardNumber').value = number;
    document.getElementById('validityStart').value = start;
    document.getElementById('validityEnd').value = end;
    document.getElementById('badgeStatus').value = status;
    document.getElementById('statusField').style.display = 'block';
    document.getElementById('terminateBtn').style.display = 'inline-block';
    document.getElementById('submitBtn').textContent = 'Update Key Card';
    document.getElementById('cancelEditBtn').style.display = 'inline-block';
}

document.getElementById('cancelEditBtn').addEventListener('click', function() {
    resetForm();
});

document.getElementById('terminateBtn').addEventListener('click', function() {
    document.getElementById('badgeStatus').value = 'terminated';
    document.getElementById('badgeForm').dispatchEvent(new Event('submit'));
});

function resetForm() {
    document.getElementById('formTitle').textContent = 'Issue New Key Card';
    document.getElementById('badgeId').value = '';
    document.getElementById('keyCardNumber').value = '';
    document.getElementById('validityStart').value = '';
    document.getElementById('validityEnd').value = '';
    document.getElementById('statusField').style.display = 'none';
    document.getElementById('terminateBtn').style.display = 'none';
    document.getElementById('submitBtn').textContent = 'Issue Key Card';
    document.getElementById('cancelEditBtn').style.display = 'none';
}

document.getElementById('badgeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const badgeId = document.getElementById('badgeId').value;
    const visitorId = document.getElementById('visitorSelect').value;
    if (!visitorId) {
        alert('Please select a visitor.');
        return;
    }
    const data = {
        visitor_id: visitorId,
        key_card_number: document.getElementById('keyCardNumber').value,
        validity_start: document.getElementById('validityStart').value,
        validity_end: document.getElementById('validityEnd').value
    };
    let action = 'issue';
    if (badgeId) {
        action = 'update';
        data.id = badgeId;
        data.status = document.getElementById('badgeStatus').value;
    }
    fetch(`clearance_badge_management.php?action=${action}`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        alert(result.message);
        if (result.success) {
            resetForm();
            document.getElementById('visitorSelect').dispatchEvent(new Event('change'));
        }
    });
});
</script>

<script src="../../scripts/sidebar_personnel.js"></script>
<script src="../../scripts/personnel_dashboard.js"></script>
<script src="../../scripts/session_check.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</div>
</div>
</div>
</body>
</html>