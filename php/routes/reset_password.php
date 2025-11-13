<?php
require '../database/db_connect.php';

$token = $_GET['token'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE reset_token = :reset_token AND expires_at > NOW()");
$stmt->execute([':reset_token' => $token]);
$reset = $stmt->fetch();

$valid = $reset ? true : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password</title>
  <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="./images/logo/5thFighterWing-logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../stylesheet/reset_password.css">
</head>
<body>
  <div class="reset-wrapper">
    <div class="reset-container">
      <h2>Reset Password</h2>
      <?php if (!$valid): ?>
        <div class="alert alert-danger" role="alert">
          Invalid or expired reset link.
        </div>
      <?php else: ?>
        <form autocomplete="off" action="reset_password_submit.php" method="POST" class="reset-form">

          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

          <label for="password">New Password:</label>
          <div class="password-wrapper">
            <input type="password" id="password" name="password" required>
            <i class="fas fa-eye" id="togglePassword"></i>
          </div>

          <label for="confirm_password">Confirm Password:</label>
          <div class="password-wrapper">
            <input type="password" id="confirm_password" name="confirm_password" required>
            <i class="fas fa-eye" id="toggleConfirmPassword"></i>
          </div>

          <button type="submit" class="reset-btn">Reset Password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Password Toggle Script -->
  <script>
    document.getElementById('togglePassword').addEventListener('click', function () {
      const password = document.getElementById('password');
      const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
      password.setAttribute('type', type);
      this.classList.toggle('fa-eye-slash');
    });
    document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
      const confirmPassword = document.getElementById('confirm_password');
      const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
      confirmPassword.setAttribute('type', type);
      this.classList.toggle('fa-eye-slash');
    });
  </script>
</body>
</html>
