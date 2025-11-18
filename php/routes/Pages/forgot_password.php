<?php
require 'db_connect.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    // Find user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate reset token
        $token = bin2hex(random_bytes(32));

        // Store in password_resets table
        $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, reset_token, expires_at)
                               VALUES (:user_id, :reset_token, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
        $stmt->execute([
            ':user_id' => $user['id'],
            ':reset_token' => $token
        ]);

        // Show reset link directly (for testing only!)
        $message = "Reset link generated. <a href='reset_password.php?token=$token'>Click here to reset</a>";
        $messageType = 'success';
    } else {
        $message = "Email not found. Try again.";
        $messageType = 'danger';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password</title>
  <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="5thFighterWing-logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../stylesheet/forgot_password.css">
</head>
<body>
  <div class="reset-wrapper">
    <div class="reset-container">
      <h2>Forgot Password</h2>
      <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>" role="alert">
          <?= $message ?>
        </div>
      <?php endif; ?>
      <form action="forgot_password.php" method="POST" class="reset-form">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <button type="submit" class="reset-btn">Send Reset Link</button>
      </form>
    </div>
  </div>

  <!-- Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
