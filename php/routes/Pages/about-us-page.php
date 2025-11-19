<?php
session_start();
require 'db_connect.php';

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
    <link rel="icon" type="image/png" href="5thFighterWing-logo.png">
    <title>5th Fighter Wing</title>
    <link href="output.css" rel="stylesheet" >
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
        <img src="5thFighterWing-logo.png" alt="5th Fighter Wing Logo"
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
      <img src="PAF-logo.png" alt="PAF Logo" class="w-[65px] h-[65px] object-contain" />
      <img src="TS-logo.png" alt="TS Logo" class="w-[65px] h-[65px] object-contain" />
      <img src="BP-logo.png" alt="BP Logo" class="w-[65px] h-[65px] object-contain" />
    </div>
  </div>

  <!-- Desktop Navbar -->
  <nav class="hidden sm:flex justify-center items-center w-full h-[75px] bg-[#F8FAFC] border-y border-[#E4E4E4]">
    <ul class="flex space-x-[40px]">
      <li><a href="home-page.php" class="text-[20px] text-[#5E7EA2] font-medium transition-all duration-200 hover:text-[#003673] hover:text-[23px]">HOME</a></li>
      <li><a href="about-us-page.php" class="text-[20px] text-[#5E7EA2] font-medium transition-all duration-200 hover:text-[#003673] hover:text-[23px]">ABOUT US</a></li>
      <li><a href="news-page.php" class="text-[20px] text-[#5E7EA2] font-medium transition-all duration-200 hover:text-[#003673] hover:text-[23px]">NEWS</a></li>
      <li><a href="advisory-page.php" class="text-[20px] text-[#5E7EA2] font-medium transition-all duration-200 hover:text-[#003673] hover:text-[23px]">ADVISORY</a></li>
      <li><a href="visit-page.php" class="text-[20px] text-[#5E7EA2] font-medium transition-all duration-200 hover:text-[#003673] hover:text-[23px]">VISIT US</a></li>
      <li><a href="contact-page.php" class="text-[20px] text-[#5E7EA2] font-medium transition-all duration-200 hover:text-[#003673] hover:text-[23px]">CONTACT US</a></li>
    </ul>
  </nav>

  <!-- Mobile Navbar (Dropdown Modal Style) -->
  <nav
    id="mobile-menu"
    class="absolute top-full left-0 w-full bg-[#F8FAFC] border-y border-[#E4E4E4] hidden opacity-0 translate-y-[-10px] transition-all duration-300 ease-in-out"
  >
    <ul class="flex flex-col items-center py-5 space-y-5">
      <li><a href="home-page.php" class="text-[18px] text-[#5E7EA2] font-medium hover:text-[#003673] transition">HOME</a></li>
      <li><a href="about-us-page.php" class="text-[18px] text-[#5E7EA2] font-medium hover:text-[#003673] transition">ABOUT US</a></li>
      <li><a href="news-page.php" class="text-[18px] text-[#5E7EA2] font-medium hover:text-[#003673] transition">NEWS</a></li>
      <li><a href="advisory-page.php" class="text-[18px] text-[#5E7EA2] font-medium hover:text-[#003673] transition">ADVISORY</a></li>
      <li><a href="visit-page.php" class="text-[18px] text-[#5E7EA2] font-medium hover:text-[#003673] transition">VISIT US</a></li>
      <li><a href="contact-page.php" class="text-[18px] text-[#5E7EA2] font-medium hover:text-[#003673] transition">CONTACT US</a></li>
    </ul>
  </nav>
</header>

<!-- Mandate, Powers and Functions Section -->
<section id="mandate-section" class="w-full bg-[#F8FAFC] py-12">
  <div class="mx-auto px-6 sm:px-[119px] max-w-6xl">
    <!-- Section Title -->
    <div class="mb-8 text-center sm:text-left">
      <h2 class="font-[Oswald] font-bold text-3xl sm:text-4xl text-[#003673]">
        Mandate, Powers and Functions
      </h2>
      <p class="mt-3 font-inter font-light text-sm sm:text-base text-[#374151]">
        Click a section to expand for more details.
      </p>
    </div>

    <!-- Accordion Container -->
    <div id="mandate-accordion" class="space-y-4">
      <!-- Item 1 -->
      <div class="accordion-item bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
        <button
          type="button"
          class="accordion-btn w-full flex justify-between items-center px-5 py-4 text-left focus:outline-none"
          aria-expanded="false"
        >
          <span class="font-[Oswald] font-bold text-lg text-[#003673]">Mandate / Role</span>
          <svg class="accordion-icon w-5 h-5 transition-transform duration-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div class="accordion-content px-5 pb-5 pt-0 max-h-0 overflow-hidden transition-all duration-300 ease-in-out opacity-0">
          <div class="pt-4 font-inter font-light text-sm text-[#374151]">
            <!-- Replace below with real content -->
            <p class="mb-3">The 5th Fighter Wing is mandated to provide air defense and conduct operations in support of national security and territorial integrity. It performs tactical air operations, surveillance, and provides rapid reaction capability.</p>
            <ul class="list-disc pl-5 space-y-2">
              <li>Provide air superiority within assigned airspace.</li>
              <li>Conduct offensive and defensive air operations.</li>
              <li>Support joint operations with other service branches.</li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Item 2 -->
      <div class="accordion-item bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
        <button
          type="button"
          class="accordion-btn w-full flex justify-between items-center px-5 py-4 text-left focus:outline-none"
          aria-expanded="false"
        >
          <span class="font-[Oswald] font-bold text-lg text-[#003673]">Powers and Functions</span>
          <svg class="accordion-icon w-5 h-5 transition-transform duration-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <div class="accordion-content px-5 pb-5 pt-0 max-h-0 overflow-hidden transition-all duration-300 ease-in-out opacity-0">
          <div class="pt-4 font-inter font-light text-sm text-[#374151]">
            <!-- Replace below with real content -->
            <p class="mb-3">The powers and functions include but are not limited to:</p>
            <ol style="list-style-type: upper-roman;" class="pl-5 space-y-2">
              <li>Air Defense and Air Interception</li>
              <p>
              -Intercept and respond to aerial threats to national sovereignty. <br>
              -Maintain Combat Air Patrol (CAP) and defend airspace. 
              </p>
              <li>Offensive and Defensive Air Operations</li>
              <p>
              -Conduct offensive counter-air and defensive counter-air missions.  <br>
              -Air-to-air and air-to-ground missions, including interdiction and ground support.
              </p>
              <li>Training and Readiness</li>
              <p>
              -Keep fighter pilots, crews, and supporting elements trained and ready through exercises (e.g. “Sanay Sibat”) to maintain tactical proficiency.  <br>
              -Integrate doctrine, tactics, procedures for multi-role fighter operations.  
              </p>
              <li>Operational Integration</li>
              <p>
              -Coordinate with other Air Force units (e.g. Aircraft Control and Warning Wings) for command & control, early warning, etc.  <br>
              -Work in joint operations with the AFP (Armed Forces of the Philippines), and with allied forces in exercises and through interoperability. 
              </p>
              <li>Force Modernization and Capability Development</li>
              <p>
              -Acquire, maintain, and upgrade aircraft and fighter-related systems.  <br>
              -Enhance technical & tactical capability to handle new aircraft types and mission sets. 
              </p>
              <li>Support to National Defense Policy</li>
              <p>
              -Implement directives and policies from the Department of National Defense and from PAF command regarding air defense, readiness, and force posture.  <br>
              -Contribute to the AFP’s overall mission of maintaining sovereignty, territorial integrity, and internal security. 
              </p>
              <li>Participation in Humanitarian Assistance, Disaster Response (inferred)</li>
              <p>
              -While not its primary function, the 5FW can be mobilized for disaster relief, rescue, transport, or other related tasks (especially as air assets are often used in these roles).
              </p>
            </ol>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="mission-vision-section" class="w-full bg-[#F8FAFC] py-12">
  <div class="mx-auto px-6 sm:px-[119px] max-w-6xl">
     <div class="mb-8 text-center sm:text-left">
      <h2 class="font-[Oswald] font-bold text-3xl sm:text-4xl text-[#003673]">
        Mission and Vision
      </h2>
    </div>

    <!-- Two-Column Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

      <!-- Mission Column -->
      <div class="mission-container bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <div class="mission-left mb-4">
          <h3 class="font-[Oswald] font-bold text-2xl text-[#003673] mb-4">Mission</h3>
          <blockquote class="font-inter font-light text-lg text-[#374151] italic mb-4 border-l-4 border-[#003673] pl-4">
            “To defend the Philippine skies with excellence, courage, and dedication.”
          </blockquote>
          <p class="font-inter font-medium text-base text-[#003673] mb-2">The 5th Fighter Wing commits to:</p>
          <ul class="font-inter font-light text-sm text-[#374151] list-disc pl-5 space-y-1">
            <li>Protect and secure the sovereignty of the Philippine airspace</li>
            <li>Maintain a high level of combat readiness and operational capability</li>
            <li>Support the Armed Forces of the Philippines in joint operations for national defense and internal security</li>
            <li>Provide rapid air response to emerging threats and contingencies</li>
            <li>Uphold professionalism, discipline, and service excellence in every mission</li>
          </ul>
        </div>
      </div>

      <!-- Vision Column -->
      <div class="vision-container bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
        <div class="vision-left mb-4">
          <h3 class="font-[Oswald] font-bold text-2xl text-[#003673] mb-4">Vision</h3>
          <blockquote class="font-inter font-light text-lg text-[#374151] italic mb-4 border-l-4 border-[#003673] pl-4">
            “A premier fighter unit of the Philippine Air Force — highly capable, mission-ready, and respected for its valor, discipline, and excellence in air defense operations.”
          </blockquote>
          <p class="font-inter font-medium text-base text-[#003673] mb-2">The 5th Fighter Wing envisions itself as:</p>
          <ul class="font-inter font-light text-sm text-[#374151] list-disc pl-5 space-y-1">
            <li>The leading force in sustaining air superiority and national security</li>
            <li>A reliable partner in peacekeeping, humanitarian, and disaster response operations</li>
            <li>A symbol of honor, integrity, and dedication in serving the Filipino people</li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</section>

<section id="agencies-section" class="w-full bg-[#F8FAFC] py-12 flex justify-center items-center mb-[50px]">
  <div class="mx-auto px-6 sm:px-[119px] max-w-6xl w-full">
    <div class="mb-8 text-left">
      <h2 class="font-[Oswald] font-bold text-3xl sm:text-4xl text-[#003673]">
        Related Government Agencies
      </h2>
    </div>

    <!-- Agencies Container -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 bg-white rounded-[17px] border border-[#003673] shadow-lg p-8 sm:p-12 w-full max-w-6xl mx-auto place-items-center">
      <!-- PAF -->
      <a href="https://www.paf.mil.ph/" target="_blank" class="flex flex-col items-center justify-center bg-white rounded-2xl shadow-sm border border-gray-100 text-center hover:shadow-2xl transition-shadow duration-300 p-6 w-full max-w-[400px]">
        <img src="PAF-logo.png" alt="Philippine Air Force Logo" class="w-28 h-28 object-contain mb-4" />
        <p class="font-[Inter] font-semibold text-lg text-[#003673]">Philippine Air Force</p>
      </a>

      <!-- DND -->
      <a href="https://www.dnd.gov.ph/" target="_blank" class="flex flex-col items-center justify-center bg-white rounded-2xl shadow-sm border border-gray-100 text-center hover:shadow-2xl transition-shadow duration-300 p-6 w-full max-w-[400px]">
        <img src="DND-logo.png" alt="Department of National Defense Logo" class="w-28 h-28 object-contain mb-4" />
        <p class="font-[Inter] font-semibold text-lg text-[#003673]">Department of National Defense</p>
      </a>

      <!-- AFP -->
      <a href="https://www.afp.mil.ph/" target="_blank" class="flex flex-col items-center justify-center bg-white rounded-2xl shadow-sm border border-gray-100 text-center hover:shadow-2xl transition-shadow duration-300 p-6 w-full max-w-[400px]">
        <img src="AFP-logo.png" alt="Armed Forces of the Philippines Logo" class="w-28 h-28 object-contain mb-4" />
        <p class="font-[Inter] font-semibold text-lg text-[#003673]">Armed Forces of the Philippines</p>
      </a>
    </div>
  </div>
</section>


<footer class="bg-[#003366] text-white h-[395px] flex items-center border-t border-white/10 mt-auto">
  <div class="container bg-[#003366] mx-auto px-[75px] flex flex-col md:flex-row items-center md:items-center justify-between text-center md:text-left gap-10 w-full p-[20px]">
    
    <!-- Left Section -->
    <div class="flex flex-col items-center md:items-start space-y-3">
      <div class="flex space-x-3">
        <img src="5thFighterWing-logo.png" alt="Logo 1" class="h-[70px] w-auto">
        <img src="BP-logo.png" alt="Logo 2" class="h-[70px] w-auto">
        <img src="PAF-logo.png" alt="Logo 3" class="h-[70px] w-auto">
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
        <img src="PAMSU-logo.png" alt="PSU Logo 1" class="h-[70px] w-auto">
        <img src="CCS-logo.png" alt="PSU Logo 2" class="h-[70px] w-auto">
      </div>
      <p class="text-sm leading-tight text-center md:text-right">
        CCS Students of<br>Pampanga State University
      </p>
    </div>

  </div>
</footer>

</body>
<!-- <script src="https://cdn.tailwindcss.com"></script> -->
<script src="landingpage.js"></script>
</html>