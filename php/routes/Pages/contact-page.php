<?php
session_start();
require '../../database/db_connect.php';

function generateRandomToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

$token = $_SESSION['user_token'] ?? null;

if (!$token) {
    $token = generateRandomToken(64);
    $_SESSION['user_token'] = $token;

    $expiry = date("Y-m-d H:i:s", strtotime("+45 minutes"));
    $stmt = $pdo->prepare("INSERT INTO visitor_sessions (user_token, created_at, expires_at) VALUES (?, CURRENT_TIMESTAMP(), ?)");
    $stmt->execute([$token, $expiry]);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../../images/logo/5thFighterWing-logo.png">
    <title>5th Fighter Wing</title>
    <link href="../../../src/output.css" rel="stylesheet" >
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

</head>

<body class="min-h-screen flex flex-col">
<header class="w-full bg-white border-b border-[#E4E4E4] relative z-50">
    <div class="flex flex-col sm:flex-row justify-between items-center px-6 sm:px-[119px] py-[27px]">
    <!-- Left Side: Logo + Title + Menu Button -->
    <div class="flex items-center justify-between w-full sm:w-auto">
      <!-- 5th Fighter Wing Logo -->
      <div class="flex items-center">
        <img src="../../../images/logo/5thFighterWing-logo.png" alt="5th Fighter Wing Logo"
          class="w-[65px] h-[65px] object-contain mr-5 sm:mr-8" />
      </div>

      <!-- Title + Menu Button -->
      <div class="flex items-center space-x-6">
        <h1
        class="font-[Oswald] font-semibold text-[26px] sm:text-[37px] text-[#003673] whitespace-nowrap leading-none tracking-wide drop-shadow-[0_2px_4px_rgba(0,0,0,0.25)]">
        5TH FIGHTER WING
        </h1>

        <!-- Hamburger Button -->
        <button id="menu-btn" class="sm:hidden flex flex-col space-y-1.5 ml-3 focus:outline-none relative z-50">
          <span class="block w-6 h-0.5 bg-[#003673] transition-all duration-300"></span>
          <span class="block w-6 h-0.5 bg-[#003673] transition-all duration-300"></span>
          <span class="block w-6 h-0.5 bg-[#003673] transition-all duration-300"></span>
        </button>
      </div>
    </div>

    <!-- Right Logos (includes PAF) -->
    <div class="flex items-center space-x-3 mt-4 sm:mt-0">
      <img src="../../../images/logo/PAF-logo.png" alt="PAF Logo" class="w-[65px] h-[65px] object-contain" />
      <img src="../../../images/logo/TS-logo.png" alt="TS Logo" class="w-[65px] h-[65px] object-contain" />
      <img src="../../../images/logo/BP-logo.png" alt="BP Logo" class="w-[65px] h-[65px] object-contain" />
    </div>
  </div>

 <!-- Desktop Navbar -->
  <nav class="hidden sm:flex justify-center items-center w-full h-[75px] bg-[#F8FAFC] border-y border-[#E4E4E4]">
    <ul class="flex space-x-[40px]">
      <li><a href="../../../php/routes/Pages/home-page.php" class="text-[20px] text-[#5E7EA2] font-medium transition-all duration-200 hover:text-[#003673] hover:text-[23px]">HOME</a></li>
      <li><a href="../../../php/routes/Pages/about-us-page.php" class="text-[20px] text-[#5E7EA2] font-medium transition-all duration-200 hover:text-[#003673] hover:text-[23px]">ABOUT US</a></li>
      <li><a href="../../../php/routes/Pages/news-page.php" class="text-[20px] text-[#5E7EA2] font-medium transition-all duration-200 hover:text-[#003673] hover:text-[23px]">NEWS</a></li>
      <li><a href="../../../php/routes/Pages/advisory-page.php" class="text-[20px] text-[#5E7EA2] font-medium transition-all duration-200 hover:text-[#003673] hover:text-[23px]">ADVISORY</a></li>
      <li><a href="../../../php/routes/Pages/visit-page.php" class="text-[20px] text-[#5E7EA2] font-medium transition-all duration-200 hover:text-[#003673] hover:text-[23px]">VISIT US</a></li>
      <li><a href="../../../php/routes/Pages/contact-page.php" class="text-[20px] text-[#5E7EA2] font-medium transition-all duration-200 hover:text-[#003673] hover:text-[23px]">CONTACT US</a></li>
    </ul>
  </nav>

  <!-- Mobile Navbar (Dropdown Modal Style) -->
  <nav
    id="mobile-menu"
    class="absolute top-full left-0 w-full bg-[#F8FAFC] border-y border-[#E4E4E4] hidden opacity-0 translate-y-[-10px] transition-all duration-300 ease-in-out"
  >
    <ul class="flex flex-col items-center py-5 space-y-5">
      <li><a href="../../../php/routes/Pages/home-page.php" class="text-[18px] text-[#5E7EA2] font-medium hover:text-[#003673] transition">HOME</a></li>
      <li><a href="../../../php/routes/Pages/about-us-page.php" class="text-[18px] text-[#5E7EA2] font-medium hover:text-[#003673] transition">ABOUT US</a></li>
      <li><a href="../../../php/routes/Pages/news-page.php" class="text-[18px] text-[#5E7EA2] font-medium hover:text-[#003673] transition">NEWS</a></li>
      <li><a href="../../../php/routes/Pages/advisory-page.php" class="text-[18px] text-[#5E7EA2] font-medium hover:text-[#003673] transition">ADVISORY</a></li>
      <li><a href="../../../php/routes/Pages/visit-page.php" class="text-[18px] text-[#5E7EA2] font-medium hover:text-[#003673] transition">VISIT US</a></li>
      <li><a href="../../../php/routes/Pages/contact-page.php" class="text-[18px] text-[#5E7EA2] font-medium hover:text-[#003673] transition">CONTACT US</a></li>
    </ul>
  </nav>
</header>


<section class="relative w-full min-h-screen mx-auto py-16 px-6 sm:px-[120px] pb-[200px]">
  <div class="flex flex-col w-full h-auto">
    <div class="flex-shrink-0 w-full h-auto sm:h-[575px] relative">

      <!-- Title -->
      <h1 class="text-[36px] sm:text-[45px] font-[Oswald] font-semibold text-[#003673] mb-12">
        Contact Us
      </h1>

      <!-- Contact Container -->
      <div class="contact-container bg-white rounded-[12px] shadow-[0_4px_83.9px_-21px_rgba(0,0,0,0.25)] p-4 sm:p-[48px] w-full max-w-[1768px] h-auto flex flex-col gap-8">
        
        <!-- Contact Info -->
        <div class="contact-info flex-1">
          
          <h1 class="text-[#003673] text-[31px] font-[Oswald] font-bold text-left mb-[45px]">
            Basa Air Base, 5th Fighter Wing
          </h1>

          <h2 class="font-[Inter] font-light text-[21px] text-[#003673] mb-7 flex items-center gap-3">
            <i class="fa-solid fa-envelope p-[5px] text-[22px] bg-[#8FAEDD] rounded-3xl h-[35px] w-[38px] flex items-center justify-center"></i>
            5thfighterwing@mil.ph
          </h2>

          <h2 class="font-[Inter] font-light text-[21px] text-[#003673] mb-7 flex items-center gap-3">
            <i class="fa-solid fa-location-dot p-[5px] text-[22px] bg-[#8FAEDD] rounded-3xl h-[35px] w-[38px] flex items-center justify-center"></i>
            Basa Air Base, Florida, Blanca
          </h2>

          <h2 class="font-[Inter] font-light text-[21px] text-[#003673] mb-7 flex items-center gap-3">
            <i class="fa-solid fa-phone p-[5px] text-[22px] bg-[#8FAEDD] rounded-3xl h-[35px] w-[38px] flex items-center justify-center"></i>
            0915-5322-241
          </h2>

          <!-- Concern Section -->
          <h1 class="text-[#003673] text-[31px] font-[Oswald] font-bold text-left mt-[45px] mb-[45px]">
            Report a Concern
          </h1>

          <textarea 
            class="text-[21px] rounded-[10px] border border-[#1F4F85] w-full h-[143px] p-2 focus:outline-none focus:ring-0 focus:border-[#1F4F85] text-[#1F4F85]" 
            placeholder="Please state your concern"></textarea>

          <!-- Name -->
          <h1 class="text-[#003673] text-[28px] font-[Oswald] font-medium text-left mt-[45px] mb-[15px]">
            Name
          </h1>
          <input 
            type="text" 
            placeholder="Write your Full Name" 
            class="text-[21px] rounded-[10px] border border-[#1F4F85] sm:w-[600px] w-full h-[52px] p-2 focus:outline-none focus:ring-0 focus:border-[#1F4F85] text-[#1F4F85]" 
            required>

          <!-- Email -->
          <h1 class="text-[#003673] text-[28px] font-[Oswald] font-medium text-left mt-[45px] mb-[15px]">
            Email
          </h1>
          <input 
            type="text" 
            placeholder="Write your Email" 
            class="text-[21px] rounded-[10px] border border-[#1F4F85] sm:w-[600px] w-full h-[52px] p-2 focus:outline-none focus:ring-0 focus:border-[#1F4F85] text-[#1F4F85]" 
            required>

          <!-- Submit Button -->
          <div class="flex justify-end">
            <button 
              type="submit" 
              class="rounded-[5px] bg-[#1F4F85] h-[45px] w-[180px] p-[5px] mt-[25px] font-[Inter] font-bold text-white text-[20px] hover:bg-[#003673] transition-colors">
              Send Concern
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>
</section>



<footer class="bg-[#003366] text-white h-[395px] flex items-center border-t border-white/10 mt-[250px] sm:mt-[450px]">
  <div class="container bg-[#003366] mx-auto px-[75px] flex flex-col md:flex-row items-center md:items-center justify-between text-center md:text-left gap-10 w-full p-[20px]">
    
    <!-- Left Section -->
    <div class="flex flex-col items-center md:items-start space-y-3">
      <div class="flex space-x-3">
        <img src="../../../images/logo/5thFighterWing-logo.png" alt="Logo 1" class="h-[70px] w-auto">
        <img src="../../../images/logo/BP-logo.png" alt="Logo 2" class="h-[70px] w-auto">
        <img src="../../../images/logo/PAF-logo.png" alt="Logo 3" class="h-[70px] w-auto">
      </div>
      <p class="text-sm leading-tight mt-2">
        Copyright © Basa Air Base 5th Fighter Wing.<br>
        All Rights Reserved
      </p>
    </div>

    <!-- Center Section -->
    <div class="flex flex-col items-center space-y-3">
      <p class="text-base font-medium">Follow our Socials:</p>
      <div class="flex space-x-5 text-[30px]">
        <a href="#" class="hover:text-gray-300"><i class="fab fa-facebook"></i></a>
        <a href="#" class="hover:text-gray-300"><i class="fab fa-instagram"></i></a>
        <a href="#" class="hover:text-gray-300"><i class="fab fa-x-twitter"></i></a>
        <a href="#" class="hover:text-gray-300"><i class="fab fa-youtube"></i></a>
      </div>
    </div>

    <!-- Right Section -->
    <div class="flex flex-col items-center md:items-end space-y-3">
      <p class="text-base font-medium">DEVELOPED BY:</p>
      <div class="flex items-center space-x-3">
        <img src="../../../images/logo/PAMSU-logo.png" alt="PSU Logo 1" class="h-[70px] w-auto">
        <img src="../../../images/logo/CCS-logo.png" alt="PSU Logo 2" class="h-[70px] w-auto">
      </div>
      <p class="text-sm leading-tight text-center md:text-right">
        CCS Students of<br>Pampanga State University
      </p>
    </div>

  </div>
</footer>



</body>
<!-- <script src="https://cdn.tailwindcss.com"></script> -->
<script src="../../../scripts/landingpage.js"></script>
</html>