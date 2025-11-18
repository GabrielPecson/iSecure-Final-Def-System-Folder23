<?php
require '../database/db_connect.php';
header('Content-Type: application/json');

// Get daily visit counts for the past 7 days
$stmt = $pdo->prepare("
    SELECT DATE(date) AS visit_date, COUNT(*) AS visits
    FROM visitation_requests
    WHERE date >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(date)
    ORDER BY DATE(date) ASC
");
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fill missing days with 0
$days = [];
$visits = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $days[] = $day;
    $found = false;
    foreach ($data as $row) {
        if ($row['visit_date'] === $day) {
            $visits[] = (int)$row['visits'];
            $found = true;
            break;
        }
    }
    if (!$found) $visits[] = 0;
}

echo json_encode(['labels' => $days, 'visits' => $visits]);
