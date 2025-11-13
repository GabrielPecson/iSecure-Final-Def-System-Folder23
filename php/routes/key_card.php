<?php
require 'auth_check.php';
require '../database/db_connect.php';

// Fetch all visitors for selection
$stmt = $pdo->query("SELECT id, first_name, last_name FROM visitors");
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Visitor Key Card Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../../images/logo/5thFighterWing-logo.png">
    <link rel="stylesheet" href="../../stylesheet/admin_maindashboard.css" />
    <link rel="stylesheet" href="../../stylesheet/key_cards.css" />
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
                    <h6 class="path"> / Data Management /</h6>
                    <h6 class="current-loc">Key Cards</h6>
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
        <div class="container-fluid mt-4">
            <div class="key-cards-form-section">
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

            <div id="badgeList" class="key-cards-list-section" style="display:none;"></div>
        </div>

        <script>
        document.getElementById('visitorSelect').addEventListener('change', function() {
            const visitorId = this.value;
            if (!visitorId) {
                const badgeList = document.getElementById('badgeList');
                badgeList.innerHTML = '';
                badgeList.style.display = 'none';
                return;
            }
            fetch(`clearance_badge_management.php?action=fetch&visitor_id=${visitorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let html = '<h5>Existing Key Cards</h5><ul class="list-group key-cards-list">';
                        data.badges.forEach(badge => {
                            html += `<li class="list-group-item">
                                <strong>Key Card #:</strong> ${badge.key_card_number} |
                                <strong>Valid:</strong> ${badge.validity_start} to ${badge.validity_end} |
                                <strong>Status:</strong> ${badge.status}
                                <button class="btn btn-sm btn-link float-end" onclick="editBadge(${badge.id}, '${badge.key_card_number}', '${badge.validity_start}', '${badge.validity_end}', '${badge.status}')">Edit</button>
                            </li>`;
                        });
                        html += '</ul>';
                        const badgeList = document.getElementById('badgeList');
                        badgeList.innerHTML = html;
                        badgeList.style.display = 'block';
                    } else {
                        const badgeList = document.getElementById('badgeList');
                        badgeList.innerHTML = '<p>No badges found.</p>';
                        badgeList.style.display = 'block';
                    }
                });
        });

        function editBadge(id, number, start, end, status) {
            // Format date strings to 'YYYY-MM-DDTHH:MM' for datetime-local input
            function formatDateTimeLocal(dateTimeStr) {
                const dt = new Date(dateTimeStr);
                if (isNaN(dt)) return '';
                const year = dt.getFullYear();
                const month = String(dt.getMonth() + 1).padStart(2, '0');
                const day = String(dt.getDate()).padStart(2, '0');
                const hours = String(dt.getHours()).padStart(2, '0');
                const minutes = String(dt.getMinutes()).padStart(2, '0');
                return `${year}-${month}-${day}T${hours}:${minutes}`;
            }

            document.getElementById('formTitle').textContent = 'Edit Key Card';
            document.getElementById('badgeId').value = id;
            document.getElementById('keyCardNumber').value = number;
            document.getElementById('validityStart').value = formatDateTimeLocal(start);
            document.getElementById('validityEnd').value = formatDateTimeLocal(end);
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
            let validityStart = document.getElementById('validityStart').value;
            let validityEnd = document.getElementById('validityEnd').value;
            // Convert datetime-local format to MySQL datetime format
            validityStart = validityStart.replace('T', ' ') + ':00';
            validityEnd = validityEnd.replace('T', ' ') + ':00';
            const data = {
                visitor_id: visitorId,
                key_card_number: document.getElementById('keyCardNumber').value,
                validity_start: validityStart,
                validity_end: validityEnd
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
        <script src="../../scripts/sidebar.js"></script>
        </div>
    </div>
</div>
</body>
</html>