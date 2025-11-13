<?php
// audit_log.php - shows all RFID access activity
date_default_timezone_set('Asia/Manila');

// Database connection
$mysqli = new mysqli("localhost", "root", "", "doorlock");
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// Fetch logs (latest first)
$result = $mysqli->query("
  SELECT a.*, CONCAT(h.first_name, ' ', h.last_name) AS full_name
  FROM access_logs a
  LEFT JOIN registered_cards c ON a.uid = c.uid
  LEFT JOIN card_holders h ON c.holder_id = h.holder_id
  ORDER BY a.log_id DESC
  LIMIT 100
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>RFID Audit Logs</title>
<meta http-equiv="refresh" content="5"> <!-- Auto refresh every 5 sec -->
<style>
body {
  font-family: Arial, sans-serif;
  margin: 20px;
  background: #f5f6f7;
}
h1 {
  color: #333;
  margin-bottom: 10px;
}
table {
  border-collapse: collapse;
  width: 100%;
  background: white;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
th, td {
  padding: 8px 10px;
  border: 1px solid #ccc;
  text-align: left;
}
th {
  background: #ff7b9c;
  color: white;
}
.status-granted { color: green; font-weight: bold; }
.status-rejected { color: red; font-weight: bold; }
.timestamp { font-size: 0.9em; color: #666; }
</style>
</head>
<body>
<h1>RFID Access Logs (Live Audit)</h1>
<p>Automatically updates every 5 seconds.</p>

<table>
  <tr>
    <th>Log ID</th>
    <th>Holder</th>
    <th>Card UID</th>
    <th>Door</th>
    <th>Status</th>
    <th>Reason</th>
    <th>Timestamp</th>
  </tr>

  <?php while($row = $result->fetch_assoc()): ?>
  <tr>
    <td><?php echo $row['log_id']; ?></td>
    <td><?php echo htmlspecialchars($row['full_name'] ?? 'Unassigned'); ?></td>
    <td><?php echo htmlspecialchars($row['uid']); ?></td>
    <td><?php echo htmlspecialchars($row['door']); ?></td>
    <td class="<?php echo ($row['status']=='GRANTED'?'status-granted':'status-rejected'); ?>">
      <?php echo htmlspecialchars($row['status']); ?>
    </td>
    <td><?php echo htmlspecialchars($row['reason']); ?></td>
    <td class="timestamp"><?php echo htmlspecialchars($row['timestamp']); ?></td>
  </tr>
  <?php endwhile; ?>
</table>

</body>
</html>
