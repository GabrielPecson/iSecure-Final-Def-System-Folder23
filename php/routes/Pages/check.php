<?php
// check.php - unified UID handling + DB error handling + door access validation

// ========================= CONFIG =========================
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "isecure";
$TIMEZONE = "Asia/Manila";
$LOG_FILE = __DIR__ . "/debug.log";

date_default_timezone_set($TIMEZONE);

// ========================= HELPERS =========================
function log_line($msg, $LOG_FILE) {
    $ts = date("Y-m-d H:i:s");
    @file_put_contents($LOG_FILE, "[$ts] $msg\n", FILE_APPEND);
}

function normalize_uid($raw) {
    $u = strtoupper(trim($raw));
    return preg_replace('/[^0-9A-F]/i', '', $u);
}

function is_hex_uid($u) {
    return preg_match('/^[0-9A-F]+$/', $u) === 1;
}
function is_decimal_uid($u) {
    return ctype_digit($u);
}

function convertBigToLittleEndianHex($hex) {
    $hex = strtoupper(preg_replace('/[^0-9A-F]/', '', $hex));
    if (strlen($hex) % 2 !== 0) {
        $hex = "0".$hex;
    }
    $bytes = str_split($hex, 2);
    $bytes = array_reverse($bytes);
    return implode('', $bytes);
}

header('Content-Type: application/json');

// ========================= INPUT =========================
$uidRaw = $_GET['uid'] ?? '';
$door   = strtoupper(trim($_GET['door'] ?? ''));

if ($uidRaw === '' || $door === '' || !in_array($door, ['DOOR1', 'DOOR2', 'ALL'])) {
    echo json_encode(["status" => "REJECTED", "reason" => "BAD_REQUEST"]);
    exit;
}

$uid = normalize_uid($uidRaw);

// ========================= UID VARIANTS =========================
$uidsToTry = [];

if (is_hex_uid($uid)) {
    $uidsToTry[] = $uid;

    $leHex = convertBigToLittleEndianHex($uid);
    $uidsToTry[] = $leHex;

    $uidsToTry[] = (string)hexdec($leHex);
}
else if (is_decimal_uid($uid)) {
    $uidsToTry[] = $uid;

    $hex = strtoupper(dechex((int)$uid));
    if (strlen($hex) % 2 !== 0) $hex = "0$hex";
    $uidsToTry[] = $hex;

    $bytes = array_reverse(str_split($hex, 2));
    $uidsToTry[] = implode('', $bytes);
}

$uidsToTry = array_unique($uidsToTry);

// ========================= DB CONNECTION =========================
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    echo json_encode(["status" => "REJECTED", "reason" => "DB_ERROR"]);
    exit;
}

// ========================= PREPARE QUERY =========================

// Build placeholder string
$placeholders = implode(',', array_fill(0, count($uidsToTry), '?'));

$sql = "
    SELECT uid, door, valid_from, valid_to, status
    FROM registered_cards
    WHERE uid IN ($placeholders)
      AND status = 'ACTIVE'
      AND valid_from <= NOW()
      AND valid_to >= NOW()
    LIMIT 1
";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(["status" => "REJECTED", "reason" => "DB_PREPARE_ERROR"]);
    exit;
}

// Binding parameters without call_user_func_array
$types = str_repeat('s', count($uidsToTry));
$stmt->bind_param($types, ...$uidsToTry);

// ========================= EXECUTE =========================
$stmt->execute();
$res = $stmt->get_result();

$status = "REJECTED";
$reason = "NOT_FOUND_OR_INVALID";
$dbDoor = "";
$dbUID = "";

// ========================= MATCH =========================
if ($row = $res->fetch_assoc()) {
    $dbUID  = $row['uid'];
    $dbDoor = strtoupper($row['door']);

    if ($dbDoor === 'ALL') {
    $status = "GRANTED";
    $reason = "MATCHED_ALL";
}
else if ($dbDoor === "DOOR1" && ($door === "DOOR1" || $door === "DOOR1_EXIT")) {
    $status = "GRANTED";
    $reason = "MATCHED_DOOR1";
}
else if ($dbDoor === "DOOR2" && ($door === "DOOR2" || $door === "DOOR2_EXIT")) {
    $status = "GRANTED";
    $reason = "MATCHED_DOOR2";
}
else {
    $status = "REJECTED";
    $reason = "DOOR_MISMATCH";
}

}

// ========================= LOGGING =========================
$finalUID = ($dbUID !== '') ? $dbUID : $uid;

$stmtLog = $mysqli->prepare("INSERT INTO access_logs(uid, door, status, reason) VALUES (?, ?, ?, ?)");
if ($stmtLog) {
    $stmtLog->bind_param('ssss', $finalUID, $door, $status, $reason);
    $stmtLog->execute();
    $stmtLog->close();
} else {
    log_line("LOG ERROR: " . $mysqli->error, $LOG_FILE);
}


// ========================= RESPONSE =========================
echo json_encode([
    "status" => $status,
    "reason" => $reason
]);
?>
