<?php
require 'auth_check.php';
require 'db_connect.php';

/**
 * Logs an action to the audit_log table.
 */
function log_audit($pdo, $user_id, $username, $action, $details) {
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, username, action, details) VALUES (?, ?, ?, ?)");
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

        case 'issue': // This is now the "assign" function
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data['visitor_id']) || empty($data['key_card_number']) || empty($data['validity_start']) || empty($data['validity_end'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields for assignment.']);
                exit;
            }

            try {
                // This logic assumes you are adding a new record for assignment.
                // A more robust system might update an existing "unassigned" card record.
                $stmt = $pdo->prepare(
                    "INSERT INTO clearance_badges (visitor_id, key_card_number, validity_start, validity_end, status) VALUES (?, ?, ?, ?, 'active')"
                );
                $stmt->execute([$data['visitor_id'], $data['key_card_number'], $data['validity_start'], $data['validity_end']]);

                $details = "Assigned key card #{$data['key_card_number']} to visitor ID {$data['visitor_id']}.";
                log_audit($pdo, $admin_user_id, $admin_username, 'CARD_ASSIGN', $details);

                echo json_encode(['success' => true, 'message' => 'Key card assigned successfully.']);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
            break;

        case 'update':
            // This logic remains for editing an already-assigned card
            $data = json_decode(file_get_contents('php://input'), true);
            // ... (update logic as before) ...
            break;

        // You would add a 'register' case here if you create a separate table for unassigned cards.
        // For now, we will handle it via the "Assign" form.

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

// Fetch unassigned key cards (This is a conceptual query. You'll need a table for this)
// For now, we'll imagine a table `registered_cards` with `card_uid` and `status` columns.
/*
$unassigned_stmt = $pdo->query("SELECT card_uid FROM registered_cards WHERE status = 'unassigned'");
$unassigned_cards = $unassigned_stmt->fetchAll(PDO::FETCH_ASSOC);
*/
// Since the table doesn't exist, we'll keep the text input for now but change the UI to reflect the new workflow.

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
            <div class="row">
                <!-- Registration Form -->
                <div class="col-md-5">
                    <div class="key-cards-form-section">
                        <h4>Register New Key Card</h4>
                        <p class="text-muted small">Add a new key card UID to the system to make it available for assignment.</p>
                        <form id="registerCardForm">
                            <div class="mb-3">
                                <label for="newCardUid" class="form-label">New Card UID</label>
                                <input type="text" id="newCardUid" class="form-control" placeholder="Scan or enter new card UID" required />
                            </div>
                            <button type="submit" class="btn btn-info">Register Card</button>
                        </form>
                    </div>
                </div>

                <!-- Assignment Form -->
                <div class="col-md-7">
                    <div class="key-cards-form-section">
                        <h4 id="formTitle">Assign Key Card to Visitor</h4>
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
                                     <label for="keyCardNumber" class="form-label">Key Card Number</label>
                                     <!-- This would be a dropdown if you had a table of unassigned cards -->
                                    <input type="text" id="keyCardNumber" name="key_card_number" class="form-control" placeholder="Enter UID of a registered card" required />
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
                            <button type="submit" class="btn btn-primary" id="submitBtn">Assign Key Card</button>
                            <button type="button" class="btn btn-danger" id="terminateBtn" style="display:none;">Terminate Key Card</button>
                            <button type="button" class="btn btn-secondary" id="cancelEditBtn" style="display:none;">Cancel</button>
                        </form>
                    </div>
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