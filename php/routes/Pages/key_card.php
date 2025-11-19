<?php
require 'auth_check.php';
require 'db_connect.php'; // Your PDO connection

// ---------------------------------------------------------------------
// HEX / DECIMAL Conversion (from admin_register.php)
// ---------------------------------------------------------------------
function decToLEHex($dec){
    $h=strtoupper(dechex((int)$dec));
    if(strlen($h)%2)$h='0'.$h;
    return implode('', array_reverse(str_split($h,2)));
}

function hexToLE($hex){
    $hex=strtoupper(preg_replace('/[^0-9A-F]/','',$hex));
    if(strlen($hex)%2)$hex='0'.$hex;
    return implode('', array_reverse(str_split($hex,2)));
}

// ---------------------------------------------------------------------
// Audit Logging
// ---------------------------------------------------------------------
function log_audit($pdo, $user_id, $username, $action, $details) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO access_log (uid, door, status, reason, timestamp)
            VALUES (?, 'ADMIN', 'SUCCESS', ?, NOW())
        ");
        $reason = $action . ': ' . $details;
        $stmt->execute([$user_id, $reason]);
    } catch (PDOException $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

// ---------------------------------------------------------------------
// API Router  (register / update / fetch)
// ---------------------------------------------------------------------
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    $admin_user_id = $_SESSION['user_id'] ?? 0;
    $admin_username = $_SESSION['username'] ?? 'admin';

    // ---------------------------------------------------------------
    // Fetch all badges for a visitor
    // ---------------------------------------------------------------
    if ($action === 'fetch') {
        $visitor_id = $_GET['visitor_id'] ?? 0;
        if (!$visitor_id) {
            echo json_encode(['success' => false, 'message' => 'Visitor ID is required.']);
            exit;
        }
        $stmt = $pdo->prepare("SELECT * FROM clearance_badges WHERE visitor_id = ? ORDER BY issued_at DESC");
        $stmt->execute([$visitor_id]);
        echo json_encode(['success' => true, 'badges' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    // ---------------------------------------------------------------
    // Register a NEW Key Card (Merged with admin_register logic)
    // ---------------------------------------------------------------
// ---------------------------------------------------------------
// Register a NEW Key Card (Fixed: saves UID as LITTLE-ENDIAN HEX)
// ---------------------------------------------------------------
if ($action === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['card_uid']) || empty($data['card_name'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Card UID and Card Name are required.']);
        exit;
    }

    // RAW UID from USB Reader
    $raw_uid = trim($data['card_uid']);

    // ----------------------------------------------
    // FIX: AUTO-CONVERT UID TO LITTLE-ENDIAN HEX
    // ----------------------------------------------
    if (ctype_digit($raw_uid)) {
        // UID is DECIMAL → convert to little-endian HEX
        $key_card_uid = decToLEHex($raw_uid);
    } else {
        // UID already HEX → normalize to little-endian HEX
        $key_card_uid = hexToLE($raw_uid);
    }

    // Check if UID already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clearance_badges WHERE key_card_number = ?");
    $stmt->execute([$key_card_uid]);
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['notification_message'] = 'This card UID is already registered.';
        $_SESSION['notification_type'] = 'error';
        header('Location: key_card.php');
        exit;
    }

    try {
        // Save card into main table
        $stmt = $pdo->prepare("
            INSERT INTO clearance_badges (key_card_number, card_name, status, clearance_level, visitor_id)
            VALUES (?, ?, 'unassigned', 'none', NULL)
        ");
        $stmt->execute([$key_card_uid, $data['card_name']]);

        // Save also into doorlock system
        $doorlock_db = new PDO("mysql:host=localhost;dbname=isecure", "root", "");
        $doorlock_stmt = $doorlock_db->prepare("
            INSERT INTO registered_cards(uid, status)
            VALUES(?, 'ACTIVE')
            ON DUPLICATE KEY UPDATE status = 'ACTIVE'
        ");
        $doorlock_stmt->execute([$key_card_uid]);

        // Log audit
        log_audit(
            $pdo,
            $admin_user_id,
            $admin_username,
            "CARD_REGISTER",
            "Registered card '{$data['card_name']}' UID: $key_card_uid"
        );

        $_SESSION['notification_message'] = 'Key card registered successfully.';
        $_SESSION['notification_type'] = 'success';

    } catch (PDOException $e) {
        $_SESSION['notification_message'] = 'Database error: ' . $e->getMessage();
        $_SESSION['notification_type'] = 'error';
    }

    header('Location: key_card.php');
    exit;
}


    // ---------------------------------------------------------------
    // Assign Key Card to Visitor (Merged admin_assign logic)
    // ---------------------------------------------------------------
    if ($action === 'update') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id']) || empty($data['visitor_id']) ||
            empty($data['validity_start']) || empty($data['validity_end'])) {

            $_SESSION['notification_message'] = 'Missing required fields.';
            $_SESSION['notification_type'] = 'error';
            header('Location: key_card.php');
            exit;
        }

        // Check if visitor exists
        $visitor_id = (int)$data['visitor_id'];
        if ($visitor_id <= 0) {
            $_SESSION['notification_message'] = 'Invalid visitor ID.';
            $_SESSION['notification_type'] = 'error';
            header('Location: key_card.php');
            exit;
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE id = ?");
        $stmt->execute([$visitor_id]);
        if ($stmt->fetchColumn() == 0) {
            $_SESSION['notification_message'] = 'Invalid visitor ID.';
            $_SESSION['notification_type'] = 'error';
            header('Location: key_card.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE clearance_badges
                SET visitor_id = ?, validity_start = ?, validity_end = ?, door = ?, status = 'active', clearance_level = 'visitor'
                WHERE id = ?
            ");
            $stmt->execute([
                $data['visitor_id'],
                $data['validity_start'],
                $data['validity_end'],
                $data['door'],
                $data['id']
            ]);

            if ($stmt->rowCount() > 0) {
                // Get visitor details for card_holder table
                $visitor_stmt = $pdo->prepare("SELECT first_name, last_name FROM visitors WHERE id = ?");
                $visitor_stmt->execute([$data['visitor_id']]);
                $visitor = $visitor_stmt->fetch(PDO::FETCH_ASSOC);

                if ($visitor) {
                    // Insert into card_holder table
                    $holder_stmt = $pdo->prepare("
                        INSERT INTO card_holders (holder_id, first_name, last_name)
                        VALUES (?, ?, ?)
                    ");
                    $holder_stmt->execute([$data['visitor_id'], $visitor['first_name'], $visitor['last_name']]);
                }

                log_audit($pdo, $admin_user_id, $admin_username, "CARD_ASSIGN",
                    "Assigned card ID {$data['id']} to visitor {$data['visitor_id']}");

                $_SESSION['notification_message'] = 'Key card assigned successfully.';
                $_SESSION['notification_type'] = 'success';
            } else {
                $_SESSION['notification_message'] = 'Failed to assign card.';
                $_SESSION['notification_type'] = 'error';
            }

        } catch (PDOException $e) {
            $_SESSION['notification_message'] = 'Database error: ' . $e->getMessage();
            $_SESSION['notification_type'] = 'error';
        }
        header('Location: key_card.php');
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// ---------------------------------------------------------------------
// Render UI Page
// ---------------------------------------------------------------------
$visitors = $pdo->query("SELECT id, first_name, last_name FROM visitors")->fetchAll(PDO::FETCH_ASSOC);

$all_cards_for_assign = $pdo->query("
    SELECT cb.id, cb.card_name, cb.key_card_number, cb.status,
           CONCAT(v.first_name, ' ', v.last_name) as holder_name
    FROM clearance_badges cb
    LEFT JOIN visitors v ON cb.visitor_id = v.id
    ORDER BY cb.card_name
")->fetchAll(PDO::FETCH_ASSOC);

$all_cards = $pdo->query("
    SELECT
        cb.id, cb.key_card_number, cb.card_name, cb.status,
        cb.validity_start, cb.validity_end, cb.door,
        v.first_name, v.last_name
    FROM clearance_badges cb
    LEFT JOIN visitors v ON cb.visitor_id = v.id
    ORDER BY issued_at DESC
")->fetchAll(PDO::FETCH_ASSOC);



?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Key Cards List</title>
    <link rel="icon" type="image/png" href="5thFighterWing-logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin_maindashboard.css" />
    <link rel="stylesheet" href="key_cards.css" />
    <link rel="stylesheet" href="sidebar.css" />
    <link rel="stylesheet" href="personnels.css" />
</head>

<body>
<?php require_once 'notification_component.php'; ?>
<div class="body">
    <div class="left-panel">
        <div id="sidebar-container"></div>
    </div>

    <div class="right-panel">
        <div class="main-content">

            <!-- HEADER -->
            <div class="main-header">
                <div class="header-left">
                    <i class="fa-solid fa-id-badge"></i>
                    <h6 class="current-loc">Key Cards</h6>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <i class="fa-solid fa-user-circle fa-lg me-2"></i>
                        <div class="user-text">
                            <span class="username"><?= htmlspecialchars($fullName) ?></span>
                            <a id="logout-link" href="logout.php" class="logout-link">Logout</a>
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


            <!-- CONTENT -->
            <div class="container-fluid mt-4">
                <div class="row">

                    <!-- REGISTER NEW CARD -->
                    <div class="col-md-5">
                        <div class="key-cards-form-section">
                            <h4>Register New Key Card</h4>
                            <form id="registerCardForm" action="key_card.php?action=register" method="POST">
                                <label>Card Name</label>
                                <input type="text" id="card_name" class="form-control" required>

                                <label>New Card UID (Decimal or HEX)</label>
                                <input type="text" id="card_uid" class="form-control" required>

                                <button class="btn btn-info mt-2">Register Card</button>
                            </form>
                        </div>
                    </div>

                    <!-- ASSIGN / EDIT CARD -->
                <div class="col-md-7">
                    <div class="key-cards-form-section">
                        <h4 id="formTitle">Assign Key Card to Visitor</h4>

                        <form id="badgeForm" action="key_card.php?action=update" method="POST">

                            <!-- Store badge ID when editing -->
                            <input type="hidden" id="badgeId">

                            <!-- Visitor Selector -->
                            <label class="mt-2">Select Visitor</label>
                            <select id="visitorSelect" class="form-select" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($visitors as $v): ?>
                                    <option value="<?= $v['id'] ?>">
                                        <?= $v['first_name'] . " " . $v['last_name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <!-- Badge List for Selected Visitor -->
                            <div id="badgeList" class="mb-4"></div>

                            <!-- Card Selector (Assign Mode Only) -->
                            <div id="assignCardField">
                                <label class="mt-2">Select Card</label>
                                <select id="keyCardId" class="form-select">
                                    <option value="">-- Select --</option>
                                    <?php foreach ($all_cards_for_assign as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= $c['status'] === 'active' ? 'disabled' : '' ?>>
                                            <?= htmlspecialchars($c['card_name']) ?> (<?= $c['key_card_number'] ?>) - <?= $c['status'] === 'active' ? 'Assigned to: ' . htmlspecialchars($c['holder_name'] ?? 'Unknown') : 'Available' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- UID (READ-ONLY) – Edit mode only -->
                            <div id="uidField" style="display:none;">
                                <label class="mt-2">Key Card UID</label>
                                <input type="text" id="keyCardNumber" class="form-control" readonly>
                            </div>

                            <!-- Validity -->
                            <label class="mt-2">Validity Start</label>
                            <input type="datetime-local" id="validityStart" class="form-control" required>

                            <label class="mt-2">Validity End</label>
                            <input type="datetime-local" id="validityEnd" class="form-control" required>

                            <!-- Door Access -->
                            <label for="doorAccess" class="mt-2">Door Access</label>
                            <select id="doorAccess" class="form-select" required>
                                <option value="ALL">All Doors</option>
                                <option value="DOOR1">Door 1 Only</option>
                                <option value="DOOR2">Door 2 Only</option>
                            </select>

                            <!-- STATUS DROPDOWN (Edit Mode Only) -->
                            <div id="statusField" class="mt-2" style="display:none;">
                                <label>Status</label>
                                <select id="badgeStatus" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="terminated">Terminated</option>
                                </select>
                            </div>

                            <!-- Buttons -->
                            <div class="mt-3">

                                <!-- Submit = Assign (default) or Update (edit mode) -->
                                <button id="submitBtn" type="submit" class="btn btn-success">
                                    Assign Key Card
                                </button>

                                <!-- Terminate Button -->
                                <button id="terminateBtn" type="button" class="btn btn-danger" style="display:none;">
                                    Terminate
                                </button>

                                <!-- Cancel Edit -->
                                <button id="cancelEditBtn" type="button" class="btn btn-secondary" style="display:none;">
                                    Cancel Edit
                                </button>

                            </div>

                        </form>
                    </div>
                </div>

                </div>

                <!-- ALL CARDS TABLE -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="personnel-container mt-4">
                            <h4>All Registered Cards</h4>
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Card Name</th>
                                        <th>UID</th>
                                        <th>Holder</th>
                                        <th>Door</th>
                                        <th>Valid From</th>
                                        <th>Valid To</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($all_cards as $card): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($card['card_name']) ?></td>
                                        <td><?= $card['key_card_number'] ?></td>
                                        <td><?= ($card['first_name'] ?? 'Unassigned') . " " . ($card['last_name'] ?? '') ?></td>
                                        <td><?= $card['door'] ?? 'N/A' ?></td>
                                        <td><?= $card['validity_start'] ?: 'N/A' ?></td>
                                        <td><?= $card['validity_end'] ?: 'N/A' ?></td>
                                        <td><?= ucfirst($card['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <script src="key_card_manager.js"></script>
        <script src="sidebar.js"></script>
        <script src="logout.js"></script>
    </div>
</div>
</body>
</html>