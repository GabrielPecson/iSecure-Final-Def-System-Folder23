<?php
session_start();
require_once 'audit_log.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | iSecure</title>
  <link rel="icon" type="image/png" href="5thFighterWing-logo.png">
  <link href="output.css" rel="stylesheet">
  <link href="login-page.css" rel="stylesheet">

</head>

<body id="login-bg" class="h-screen flex justify-center items-center overflow-hidden">

  <div class="container flex relative justify-center items-center">
    
    <!-- Blue Panel -->
    <div id="blue-panel"
    class="blue-panel bg-[#006682] h-[500px] flex flex-col justify-center items-center shadow-[0_5px_45px_21px_rgba(0,0,0,0.5)] z-20">
      <img id="logo" 
      src="5thFighterWing-logo.png" alt="5thfighterwinglogo" 
      class="h-[180px] w-[180px] mb-4 opacity-0">
      <h1 id="welcome" 
      class="welcome-text text-white text-3xl font-bold fade-in opacity-0">Welcome to iSecure,</h1>
      <p id="subtext" 
      class="sub-text text-white text-lg fade-in opacity-0">Please Login</p>
    </div>

    <!-- White Panel -->
    <div id="white-panel" 
    class="white-panel absolute bg-white w-[650px] h-[500px] rounded-[45px] flex flex-col justify-center items-center shadow-lg opacity-0 pl-[155px]">
      <h2 
      class="text-[#006682] text-3xl font-bold mb-6">
      Login
      </h2>
      <form action="login.php" method="POST"
      class="login-form flex flex-col w-3/4 space-y-4 fade-in opacity-0">
        <label
        class="text-sm text-[#006682] font-semibold">
        Email:
        </label>
        <input type="text" name="email" required
        class="border border-[#006682] rounded-md px-2 py-1 focus:outline-none">
        <label class="text-sm text-[#006682] font-semibold">
          Password:
        </label>
        <div class="relative">
          <input type="password" id="password" name="password" class="border border-[#006682] rounded-md px-2 py-1 focus:outline-none w-full" required>
          <button type="button" id="togglePassword" class="absolute right-2 top-1/2 transform -translate-y-1/2 flex items-center text-[#006682] hover:text-[#00506a]">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
          </button>
        </div>
        <a href="#" id="forgotPasswordLink" class="text-xs text-[#006682] text-right mt-[25px] hover:underline">
          Forgot Password?
        </a>
        <button type="submit" class="bg-[#006682] text-white py-2 rounded-md hover:bg-[#00506a] transition">
          Login
        </button>
      </form>

    </div>

  </div>

  <!-- Loader Overlay -->
  <div id="loader-overlay" class="loader-overlay" style="display: none;">
    <div class="loader-spinner"></div>
    <div id="loader-text" class="loader-text">Loading...</div>
  </div>

  <!-- Login Error Modal -->
  <div id="loginErrorModal" class="custom-modal" style="display: none;">
    <div class="custom-modal-overlay" id="errorModalOverlay"></div>
    <div class="custom-modal-content">
      <div class="custom-modal-header" style="background-color: #dc3545; color: white;">
        <h5 class="custom-modal-title">Login Failed</h5>
        <button type="button" class="custom-modal-close" id="closeErrorModal">&times;</button>
      </div>
      <div class="custom-modal-body">
        <p id="errorMessage">Invalid email or password!</p>
      </div>
      <div class="custom-modal-footer" style="padding: 10px; text-align: right;">
        <button type="button" class="btn btn-secondary" id="closeErrorBtn">Close</button>
      </div>
    </div>
  </div>

  <!-- Forgot Password Modal -->
      <div id="forgotPasswordModal" class="custom-modal" style="display: none;">
        <div class="custom-modal-overlay" id="modalOverlay"></div>
        <div class="custom-modal-content">
          <div class="custom-modal-header" style="background-color: #006682; color: white;">
            <h5 class="custom-modal-title">Forgot Password</h5>
            <button type="button" class="custom-modal-close" id="closeModal">&times;</button>
          </div>
          <div class="custom-modal-body">
            <form action="forgot_password.php" method="POST">
              <div class="form-group">
                <label for="resetEmail">Enter your email address</label>
                <input type="email" id="resetEmail" name="email" required>
              </div>
              <button type="submit" class="custom-modal-submit" style="background-color: #006682; border-color: #006682;">Send Reset Link</button>
            </form>
          </div>
        </div>
      </div>
      
  <!-- Login Error Modal -->
  <?php if (!empty($_SESSION['login_error'])): ?>
    <div class="modal fade" id="loginErrorModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header custom-modal-header text-white">
            <h5 class="modal-title">Login Failed</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <?= htmlspecialchars($_SESSION['login_error']); ?>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <?php unset($_SESSION['login_error']); ?>
  <?php endif; ?>


  <script src="login-page.js"></script>
  <script src="loginpage.js"></script>

</body>
</html>
