<?php
require 'auth_check.php';
require '../database/db_connect.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'issue':
        issueClearanceBadge($pdo);
        break;
    case 'update':
        updateClearanceBadge($pdo);
        break;
    case 'fetch':
        fetchClearanceBadge($pdo);
        break;
    case 'check_validity':
        checkBadgeValidity($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function issueClearanceBadge($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $visitorId = $data['visitor_id'] ?? null;
    $personnelId = $data['personnel_id'] ?? null;
    $keyCardNumber = $data['key_card_number'] ?? null;
    $validityStart = $data['validity_start'] ?? null;
    $validityEnd = $data['validity_end'] ?? null;

    if ((!$visitorId && !$personnelId) || !$keyCardNumber || !$validityStart || !$validityEnd) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        return;
    }

    // Check if key card number is already in use by an active badge
    $stmt = $pdo->prepare("SELECT id FROM clearance_badges WHERE key_card_number = :key_card_number AND status = 'active'");
    $stmt->execute([':key_card_number' => $keyCardNumber]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Key card number is already in use by an active badge']);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO clearance_badges (visitor_id, personnel_id, key_card_number, validity_start, validity_end, status, issued_at, updated_at) VALUES (:visitor_id, :personnel_id, :key_card_number, :validity_start, :validity_end, 'active', NOW(), NOW())");
        $stmt->execute([
            ':visitor_id' => $visitorId,
            ':personnel_id' => $personnelId,
            ':key_card_number' => $keyCardNumber,
            ':validity_start' => $validityStart,
            ':validity_end' => $validityEnd
        ]);
        echo json_encode(['success' => true, 'message' => 'Key card issued successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateClearanceBadge($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    $badgeId = $data['id'] ?? null;
    $validityStart = $data['validity_start'] ?? null;
    $validityEnd = $data['validity_end'] ?? null;
    $status = $data['status'] ?? null;

    if (!$badgeId) {
        echo json_encode(['success' => false, 'message' => 'Missing badge ID']);
        return;
    }

    try {
        $fields = [];
        $params = [':id' => $badgeId];

        if ($validityStart !== null) {
            $fields[] = "validity_start = :validity_start";
            $params[':validity_start'] = $validityStart;
        }
        if ($validityEnd !== null) {
            $fields[] = "validity_end = :validity_end";
            $params[':validity_end'] = $validityEnd;
        }
        if ($status !== null) {
            $fields[] = "status = :status";
            $params[':status'] = $status;
        }

        if (empty($fields)) {
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }

        $fields[] = "updated_at = NOW()";

        $sql = "UPDATE clearance_badges SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['success' => true, 'message' => 'Key card updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function fetchClearanceBadge($pdo) {
    $visitorId = $_GET['visitor_id'] ?? null;
    if (!$visitorId) {
        echo json_encode(['success' => false, 'message' => 'Missing visitor ID']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM clearance_badges WHERE visitor_id = :visitor_id ORDER BY issued_at DESC");
        $stmt->execute([':visitor_id' => $visitorId]);
        $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'badges' => $badges]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function checkBadgeValidity($pdo) {
    $badgeId = $_GET['badge_id'] ?? null;
    if (!$badgeId) {
        echo json_encode(['success' => false, 'message' => 'Missing badge ID']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT validity_end, status FROM clearance_badges WHERE id = :id");
        $stmt->execute([':id' => $badgeId]);
        $badge = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$badge) {
            echo json_encode(['success' => false, 'message' => 'Badge not found']);
            return;
        }

        $now = new DateTime();
        $validityEnd = new DateTime($badge['validity_end']);
        $status = $badge['status'];

        if ($status !== 'active' || $now > $validityEnd) {
            echo json_encode(['success' => false, 'message' => 'Badge expired or inactive']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Badge is valid']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>