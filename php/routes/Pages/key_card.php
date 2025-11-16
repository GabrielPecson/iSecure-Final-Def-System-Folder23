<?php
require 'auth_check.php';
require 'db_connect.php';

/**
 * Logs an action to the audit_log table.
 */
function log_audit($pdo, $user_id, $username, $action, $details) {
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_audit_logs (user_id, action, details) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $username, $action, $details]);
    } catch (PDOException $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

// --- API Logic Handler ---
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];
    $admin_user_id = $_SESSION['user_id'];
    $admin_username = $_SESSION['username'] ?? 'admin';

    switch ($action) {
        case 'fetch':
            $visitor_id = $_GET['visitor_id'] ?? 0;
            if (!$visitor_id) {
                echo json_encode(['success' => false, 'message' => 'Visitor ID is required.']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT * FROM clearance_badges WHERE visitor_id = ? ORDER BY issued_at DESC");
            $stmt->execute([$visitor_id]);
            $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'badges' => $badges]);
            break;

        case 'register':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['card_uid']) || empty($data['card_name'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Card UID and Card Name are required.']);
                exit;
            }

            try {
                // Insert a new card record with a status of 'unassigned' and no visitor.
                $stmt = $pdo->prepare(
                    "INSERT INTO clearance_badges (key_card_number, card_name, status) VALUES (?, ?, 'unassigned')"
                );
                $stmt->execute([$data['card_uid'], $data['card_name']]);

                $details = "Registered new key card '{$data['card_name']}' (UID: {$data['card_uid']}).";
                log_audit($pdo, $admin_user_id, $admin_username, 'CARD_REGISTER', $details);

                echo json_encode(['success' => true, 'message' => 'Key card registered successfully.']);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            break;

        case 'update':
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['id']) || empty($data['visitor_id']) || empty($data['validity_start']) || empty($data['validity_end'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Card ID, Visitor ID, and validity dates are required.']);
                exit;
            }

            try {
                // Update the existing card record to assign it to a visitor and set it as 'active'.
                $stmt = $pdo->prepare(
                    "UPDATE clearance_badges SET visitor_id = ?, validity_start = ?, validity_end = ?, status = 'active' WHERE id = ?"
                );
                $stmt->execute([
                    $data['visitor_id'],
                    $data['validity_start'],
                    $data['validity_end'],
                    $data['id']
                ]);

                if ($stmt->rowCount() > 0) {
                    $details = "Assigned card ID {$data['id']} to visitor ID {$data['visitor_id']}.";
                    log_audit($pdo, $admin_user_id, $admin_username, 'CARD_ASSIGN', $details);
                    echo json_encode(['success' => true, 'message' => 'Key card assigned successfully.']);
                } else {
                    // This can happen if the card ID is invalid or already assigned.
                    echo json_encode(['success' => false, 'message' => 'Failed to assign key card. It may not exist or is not unassigned.']);
                }

            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
            break;
    }
    exit; // Stop script execution after handling the API request
}

// --- HTML Rendering Logic ---

// Fetch all visitors for selection
$stmt = $pdo->query("SELECT id, first_name, last_name FROM visitors");
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unassigned key cards for the assignment dropdown
$unassigned_stmt = $pdo->query("SELECT id, card_name, key_card_number FROM clearance_badges WHERE status = 'unassigned' OR status IS NULL");
$unassigned_cards = $unassigned_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch ALL key cards for the main list view, joining with visitors to get holder name
$all_cards_stmt = $pdo->query("
    SELECT 
        cb.id, cb.key_card_number, cb.card_name, cb.status, cb.validity_start, cb.validity_end,
        v.first_name, v.last_name
    FROM clearance_badges cb
    LEFT JOIN visitors v ON cb.visitor_id = v.id
    ORDER BY cb.issued_at DESC
");
$all_cards = $all_cards_stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="icon" type="image/png" href="5thFighterWing-logo.png">
    <link rel="stylesheet" href="admin_maindashboard.css" />
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
            <div class="row d-flex align-items-stretch">
                <!-- Registration Form -->
                <div class="col-md-5">
                    <div class="key-cards-form-section h-100">
                        <h4>Register New Key Card</h4>
                        <p class="text-muted small">Add a new key card UID to the system to make it available for assignment.</p>
                        <form id="registerCardForm">
                             <div class="mb-3">
                                <label for="card_name" class="form-label">Card Name</label>
                                <input type="text" id="card_name" class="form-control" placeholder="e.g., 'Main Gate Card 01'" required />
                            </div>
                            <div class="mb-3">
                                <label for="card_uid" class="form-label">New Card UID</label>
                                <input type="text" id="card_uid" class="form-control" placeholder="Scan or enter new card UID" required />
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-info">Register Card</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Assignment Form -->
                <div class="col-md-7">
                    <div class="key-cards-form-section h-100">
                        <h4 id="formTitle">Assign Key Card to Visitor</h4>
                        <p class="text-muted small">Select a visitor and an unassigned card to grant access for a specific time.</p>
                        <form id="badgeForm">
                            <input type="hidden" id="badgeId" name="id" value="" />
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="visitorSelect" class="form-label">Select Visitor</label>
                                    <select id="visitorSelect" class="form-select">
                                        <option value="">-- Select Visitor --</option>
                                        <?php foreach ($visitors as $visitor): ?>
                                            <option value="<?= htmlspecialchars($visitor['id']) ?>"><?= htmlspecialchars($visitor['first_name'] . ' ' . $visitor['last_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                     <label for="keyCardId" class="form-label">Select Unassigned Card</label>
                                     <select id="keyCardId" name="key_card_id" class="form-select" required>
                                        <option value="">-- Select a card --</option>
                                        <?php foreach ($unassigned_cards as $card): ?>
                                            <option value="<?= htmlspecialchars($card['id']) ?>">
                                                <?= htmlspecialchars($card['card_name']) ?> (<?= htmlspecialchars($card['key_card_number']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                     </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="validityStart" class="form-label">Validity Start</label>
                                    <input type="datetime-local" id="validityStart" name="validity_start" class="form-control" required />
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="validityEnd" class="form-label">Validity End</label>
                                    <input type="datetime-local" id="validityEnd" name="validity_end" class="form-control" required />
                                </div>
                            </div>
                            <div class="mb-3" id="statusField" style="display:none;">
                                <label for="badgeStatus" class="form-label">Status</label>
                                <select id="badgeStatus" name="status" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="terminated">Terminated</option>
                                </select>
                            </div>
                            <div class="d-flex justify-content-end mt-3">
                                <button type="button" class="btn btn-secondary me-2" id="cancelEditBtn" style="display:none;">Cancel</button>
                                <button type="button" class="btn btn-danger me-2" id="terminateBtn" style="display:none;">Terminate Key Card</button>
                                <button type="submit" class="btn btn-info" id="submitBtn">Assign Key Card</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Table of All Registered Cards -->
            <div class="key-cards-list-section mt-5" id="all-cards-table">
                <h4>All Registered Cards</h4>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped key-card-list-table">
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
                                <td><?= htmlspecialchars($card['key_card_number']) ?></td>
                                <td><?= htmlspecialchars(($card['first_name'] ?? '') . ' ' . ($card['last_name'] ?? '')) ?: 'Unassigned' ?></td>
                                <td><?= $card['validity_start'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($card['validity_start']))) : 'N/A' ?></td>
                                <td><?= $card['validity_end'] ? htmlspecialchars(date('Y-m-d H:i', strtotime($card['validity_end']))) : 'N/A' ?></td>
                                <td>
                                    <span class="badge bg-<?= $card['status'] === 'active' ? 'success' : ($card['status'] === 'unassigned' ? 'warning text-dark' : 'secondary') ?>">
                                        <?= htmlspecialchars(ucfirst($card['status'] ?? '')) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="badgeList" class="key-cards-list-section" style="display:none;"></div>
        </div>

        <script src="key_card_manager.js"></script>
        <script src="sidebar.js"></script>
        </div>
    </div>
</div>
</body>
</html>