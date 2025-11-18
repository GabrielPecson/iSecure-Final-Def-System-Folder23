<?php
require 'auth_check.php';
require '../database/db_connect.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("Visitor ID missing.");
}

$stmt = $pdo->prepare("SELECT * FROM visitors WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$visitor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$visitor) {
    die("Visitor not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE visitors 
                           SET full_name = :full_name, 
                               contact_number = :contact_number, 
                               reason = :reason, 
                               date = :date,
                               time_in = :time_in,
                               time_out = :time_out,
                               status = :status
                           WHERE id = :id");
    $stmt->execute([
        ':full_name' => $_POST['full_name'],
        ':contact_number' => $_POST['contact_number'],
        ':reason' => $_POST['reason'],
        ':date' => $_POST['date'],
        ':time_in' => $_POST['time_in'],
        ':time_out' => $_POST['time_out'] ?: null,
        ':status' => $_POST['status'],
        ':id' => $id
    ]);

    header("Location: visitors.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Visitor</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="card shadow-lg rounded-3">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Edit Visitor</h5>
      </div>
      <div class="card-body">
        <form method="post">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Full Name</label>
              <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($visitor['full_name']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Contact Number</label>
              <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($visitor['contact_number']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Reason</label>
              <input type="text" name="reason" class="form-control" value="<?= htmlspecialchars($visitor['reason']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Date</label>
              <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($visitor['date']) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Time In</label>
              <input type="time" name="time_in" class="form-control" value="<?= htmlspecialchars($visitor['time_in']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Time Out</label>
              <input type="time" name="time_out" class="form-control" value="<?= htmlspecialchars($visitor['time_out']) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Status</label>
              <select name="status" class="form-select">
                <option value="Inside" <?= $visitor['status'] === 'Inside' ? 'selected' : '' ?>>Inside</option>
                <option value="Outside" <?= $visitor['status'] === 'Outside' ? 'selected' : '' ?>>Outside</option>
              </select>
            </div>
          </div>
          <div class="mt-4 d-flex justify-content-between">
            <a href="visitors.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
