<?php
session_start();
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
            INSERT INTO audit_log (user_id, username, action, details)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $username, $action, $details]);
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
    if ($action === 'register') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['card_uid']) || empty($data['card_name'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Card UID and Card Name are required.']);
            exit;
        }

        // Convert input (supports raw decimal or hex)
        $raw = trim($data['card_uid']);
        if (ctype_digit($raw)) {
            $key_card_uid = decToLEHex($raw);
        } else {
            $key_card_uid = hexToLE($raw);
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO clearance_badges (key_card_number, card_name, status)
                VALUES (?, ?, 'unassigned')
            ");
            $stmt->execute([$key_card_uid, $data['card_name']]);

            log_audit($pdo, $admin_user_id, $admin_username, "CARD_REGISTER",
                "Registered card '{$data['card_name']}' UID: $key_card_uid");

            echo json_encode(['success' => true, 'message' => 'Key card registered successfully.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    // ---------------------------------------------------------------
    // Assign Key Card to Visitor (Merged admin_assign logic)
    // ---------------------------------------------------------------
    if ($action === 'update') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id']) || empty($data['visitor_id']) ||
            empty($data['validity_start']) || empty($data['validity_end'])) {

            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE clearance_badges
                SET visitor_id = ?, validity_start = ?, validity_end = ?, status = 'active'
                WHERE id = ?
            ");
            $stmt->execute([
                $data['visitor_id'],
                $data['validity_start'],
                $data['validity_end'],
                $data['id']
            ]);

            if ($stmt->rowCount() > 0) {
                log_audit($pdo, $admin_user_id, $admin_username, "CARD_ASSIGN",
                    "Assigned card ID {$data['id']} to visitor {$data['visitor_id']}");

                echo json_encode(['success' => true, 'message' => 'Key card assigned successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to assign card.']);
            }

        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// ---------------------------------------------------------------------
// Render UI Page
// ---------------------------------------------------------------------
$visitors = $pdo->query("SELECT id, first_name, last_name FROM visitors")->fetchAll(PDO::FETCH_ASSOC);

$unassigned_cards = $pdo->query("
    SELECT id, card_name, key_card_number 
    FROM clearance_badges
    WHERE status='unassigned' OR status IS NULL
")->fetchAll(PDO::FETCH_ASSOC);

$all_cards = $pdo->query("
    SELECT 
        cb.id, cb.key_card_number, cb.card_name, cb.status,
        cb.validity_start, cb.validity_end,
        v.first_name, v.last_name
    FROM clearance_badges cb
    LEFT JOIN visitors v ON cb.visitor_id = v.id
    ORDER BY issued_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$fullName = $_SESSION['full_name'] ?? "Admin";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visitor Key Card Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="key_cards.css" />
    <link rel="stylesheet" href="sidebar.css" />
</head>

<body>
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
                    <h6 class="path"> / Data Management / </h6>
                    <h6 class="current-loc">Key Cards</h6>
                </div>
                <div class="header-right">
                    <div class="user-info">
                        <i class="fa-solid fa-user-circle fa-lg"></i>
                        <span class="username"><?= htmlspecialchars($fullName) ?></span>
                        <a href="logout.php" class="logout-link">Logout</a>
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
                            <form id="registerCardForm">
                                <label>Card Name</label>
                                <input type="text" id="card_name" class="form-control" required>

                                <label>New Card UID (Decimal or HEX)</label>
                                <input type="text" id="card_uid" class="form-control" required>

                                <button class="btn btn-info mt-2">Register Card</button>
                            </form>
                        </div>
                    </div>

                    <!-- ASSIGN CARD -->
                    <div class="col-md-7">
                        <div class="key-cards-form-section">
                            <h4>Assign Key Card to Visitor</h4>

                            <form id="badgeForm">
                                <input type="hidden" id="badgeId">

                                <label>Select Visitor</label>
                                <select id="visitorSelect" class="form-select">
                                    <option value="">-- Select --</option>
                                    <?php foreach ($visitors as $v): ?>
                                        <option value="<?= $v['id'] ?>">
                                            <?= $v['first_name'] . " " . $v['last_name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <label>Select Card</label>
                                <select id="keyCardId" class="form-select mt-2">
                                    <option value="">-- Select --</option>
                                    <?php foreach ($unassigned_cards as $c): ?>
                                        <option value="<?= $c['id'] ?>">
                                            <?= $c['card_name'] ?> (<?= $c['key_card_number'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <label class="mt-2">Validity Start</label>
                                <input type="datetime-local" id="validityStart" class="form-control">

                                <label class="mt-2">Validity End</label>
                                <input type="datetime-local" id="validityEnd" class="form-control">

                                <button class="btn btn-success mt-3">Assign Key Card</button>
                            </form>
                        </div>
                    </div>

                </div>

                <!-- ALL CARDS TABLE -->
                <div class="mt-4">
                    <h4>All Registered Cards</h4>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>UID</th>
                                <th>Holder</th>
                                <th>Valid From</th>
                                <th>Valid To</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($all_cards as $card): ?>
                            <tr>
                                <td><?= $card['key_card_number'] ?></td>
                                <td><?= ($card['first_name'] ?? 'Unassigned') . " " . ($card['last_name'] ?? '') ?></td>
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

        <script src="key_card_manager.js"></script>
        <script src="sidebar.js"></script>
    </div>
</div>
</body>
</html>