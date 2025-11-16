<?php
session_start();

require_once 'db_connect.php';

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
    <link href="notification.css" rel="stylesheet" >
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

<!-- New: Success Notification Area -->
<?php if (isset($_SESSION['submission_success'])): ?>
<div id="submission-success-alert" class="fixed top-24 right-5 bg-green-500 text-white py-3 px-6 rounded-lg shadow-lg z-[100]">
    <p><?php echo htmlspecialchars($_SESSION['submission_success']); ?></p>
</div>
<script>
    setTimeout(() => {
        const alert = document.getElementById('submission-success-alert');
        if (alert) {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }
    }, 4000); // The alert will disappear after 4 seconds
</script>
<?php unset($_SESSION['submission_success']); // Clear the message so it doesn't show again on refresh ?>
<?php endif; ?>

<!-- Header Carousel -->
<section class="relative w-full h-[400px] sm:h-[575px] bg-[#E4EDF3] overflow-hidden">
  <div id="carousel" class="flex h-screen transition-transform duration-700 ease-in-out">
    <!-- Slide 1 -->
    <div class="flex-shrink-0 w-full h-[400px] sm:h-[575px] relative">
      <img src="header-carousel-img1.png" alt="Slide 1" class="w-[1745px] h-[400px] sm:h-[575px] object-contain object-center mx-auto" />
      <div class="absolute inset-0 flex items-center justify-start pl-[40px] sm:pl-[360px] md:pl-[300px] pr-4 mt-[-20px]">
        <h2 class="font-[Inter] font-bold text-[14px] sm:text-[28px] md:text-[40px] lg:text-[55px] text-[#003673] max-w-[250px] sm:max-w-[500px] md:max-w-[700px] leading-tight">
          “Sustaining Excellence, Building Capabilities, Overcoming Challenges.”
        </h2>
      </div>
    </div>

    <!-- Slide 2 -->
    <div class="flex-shrink-0 w-full h-[400px] sm:h-[575px] relative">
      <img src="header-carousel-img2.png" alt="Slide 2" class="w-[1745px] h-[400px] sm:h-[575px] object-contain object-center mx-auto" />
      <div class="absolute inset-0 flex items-center justify-start pl-[40px] sm:pl-[360px] md:pl-[300px] pr-4 mt-[-20px]">
        <h2 class="font-[Inter] font-bold text-[14px] sm:text-[28px] md:text-[40px] lg:text-[55px] text-[#003673] max-w-[250px] sm:max-w-[500px] md:max-w-[700px] leading-tight">
          “Defending the Skies, Serving the Nation with Pride.”
        </h2>
      </div>
    </div>

    <!-- Slide 3 -->
    <div class="flex-shrink-0 w-full h-[400px] sm:h-[575px] relative">
      <img src="header-carousel-img3.png" alt="Slide 3" class="w-[1745px] h-[400px] sm:h-[575px] object-contain object-center mx-auto" />
      <div class="absolute inset-0 flex items-center justify-start pl-[40px] sm:pl-[360px] md:pl-[300px] pr-4 mt-[-20px]">
        <h2 class="font-[Inter] font-bold text-[14px] sm:text-[28px] md:text-[40px] lg:text-[55px] text-[#003673] max-w-[250px] sm:max-w-[500px] md:max-w-[700px] leading-tight">
          “Empowering Airmen, Advancing Air Power.”
        </h2>
      </div>
    </div>
  </div>

  <!-- Carousel Indicators -->
  <div class="absolute bottom-5 left-1/2 transform -translate-x-1/2 flex space-x-3">
    <button class="w-3 h-3 rounded-full bg-[#5E7EA2] opacity-50 hover:opacity-100" data-slide="0"></button>
    <button class="w-3 h-3 rounded-full bg-[#5E7EA2] opacity-50 hover:opacity-100" data-slide="1"></button>
    <button class="w-3 h-3 rounded-full bg-[#5E7EA2] opacity-50 hover:opacity-100" data-slide="2"></button>
  </div>
</section>

<section class="w-full h-[100px] bg-white"></section>

<section class="flex flex-col justify-center items-center w-full h-[400px] sm:h-[400px] bg-white px-4">
  <div class="text-center">
    <h1 class="font-[Oswald] font-semibold text-[24px] sm:text-[37px] text-[#003673] m-0 fade-in-up">Who we Are?</h1>
  </div>
  <div class="w-full max-w-[1230px] h-auto sm:h-[260px] flex justify-center items-center mt-4 sm:mt-[40px]">
    <p class="font-[Inter] font-normal text-[16px] sm:text-[24px] leading-[30px] sm:leading-[50px] text-center text-[#336DAF] fade-in-up px-4">
      The Philippine Air Force (PAF) (Filipino: Hukbong Himpapawid ng Pilipinas, lit. 'Air Army of the Philippines') is the aerial warfare service branch of the Armed Forces of the Philippines. Initially formed as part of the Philippine Army as the Philippine Army Air Corps (PAAC) in 1935, the PAAC eventually saw combat during World War 2 and was formally separated from the Army in 1947 as a separate service branch of the AFP under Executive Order No. 94.
    </p>
  </div>
</section>

<section class="flex flex-col items-center w-full h-auto pt-0 pl-[32px] pr-[32px] sm:pl-[128px] sm:pr-[128px] m-0 mt-[100px]">
    <div class="vision-container bg-white rounded-[46px] p-[32px] sm:p-[115px] shadow-[0_4px_83.9px_-21px_rgba(0,0,0,0.25)] w-full max-w-[1768px] h-auto flex flex-col sm:flex-row gap-8">
  <!-- Left Column -->
  <div class="vision-left flex-1 flex flex-col">
    <h2 class=" font-[Oswald] text-[24px] sm:text-[37px] text-[#003673] font-semibold mb-4">Our Vision</h2>
    <p class="font-[Inter] font-normal text-[16px] sm:text-[18px] sm:leading-[30px] text-[#336DAF] mb-6 leading-relaxed">To be a world-class air force that excels in defending the nation's sovereignty, fostering innovation, and empowering our airmen to achieve unparalleled excellence in air power.</p>
    <a href="about-us-page.php#mission-vision-section" class="bg-[#003673] text-white font-[Inter] font-semibold text-[14px] sm:text-[16px] w-[180px] sm:w-[210px] h-[45px] sm:h-[50px] rounded-lg hover:bg-[#002244] transition duration-200 inline-block text-center leading-[45px] sm:leading-[50px]">Read More</a>
  </div>
  <!-- Right Column -->
  <div class="vision-right w-full h-[300px] sm:w-[525px] sm:h-[425px] bg-gray-200 rounded-lg flex items-center justify-center">
    <span class="text-gray-500 text-[16px] sm:text-[20px]"><img src="Vision-img.png" alt="vision-img"></span>
  </div>
</div>

 <div class="mission-container bg-white rounded-[46px] mt-[174px] p-[32px] sm:p-[115px] shadow-[0_4px_83.9px_-21px_rgba(0,0,0,0.25)] w-full max-w-[1768px] h-auto flex flex-col sm:flex-row gap-8">
  <!-- Left Column -->
  <div class="mission-left w-full h-[300px] sm:w-[525px] sm:h-[425px] bg-gray-200 rounded-lg flex items-center justify-center">
    <span class="text-gray-500 text-[16px] sm:text-[20px]"><img src="Mission-img.jpg" alt="mission-img"></span>
  </div>
  <!-- Right Column -->
  <div class="mission-right flex-1 flex flex-col items-end text-right">
    <h2 class=" font-[Oswald] text-[24px] sm:text-[37px] text-[#003673] font-semibold mb-4">Our Mission</h2>
    <p class="font-[Inter] font-normal text-[16px] sm:text-[18px] sm:leading-[30px] text-[#336DAF] mb-6 leading-relaxed">To be a world-class air force that excels in defending the nation's sovereignty, fostering innovation, and empowering our airmen to achieve unparalleled excellence in air power.</p>
    <a href="about-us-page.php#mission-vision-section" class="bg-[#003673] text-white font-[Inter] font-semibold text-[14px] sm:text-[16px] w-[180px] sm:w-[210px] h-[45px] sm:h-[50px] rounded-lg hover:bg-[#002244] transition duration-200 self-end inline-block text-center leading-[45px] sm:leading-[50px]">Read More</a>
  </div>
</div>
</section>

<section
  id="stats-section"
  class="relative w-full h-[600px] overflow-hidden flex items-center justify-center bg-[#003673] mt-[100px] opacity-0 translate-y-10 transition-all duration-[1200ms] ease-out"
>
  <!-- Animated Diagonal Background -->
  <div class="absolute inset-0 overflow-hidden">
    <div
      class="absolute top-0 left-[-50%] w-[250%] h-[200%] bg-[#43B0F1] opacity-25 rotate-[-25deg]"
      data-speed="0.4"
      data-direction="right"
    ></div>
    <div
      class="absolute top-[-30%] left-[-50%] w-[250%] h-[200%] bg-[#0D3648] opacity-40 rotate-[-25deg]"
      data-speed="0.6"
      data-direction="left"
    ></div>
    <div
      class="absolute top-[-60%] left-[-50%] w-[250%] h-[200%] bg-[#99EFFF] opacity-20 rotate-[-25deg]"
      data-speed="0.3"
      data-direction="right"
    ></div>
  </div>

  <!-- Stats Container -->
 <div
  class="relative z-10 grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8 px-6 sm:px-10 py-6 sm:py-12 bg-[#FEFEFE]/80 rounded-xl shadow-lg w-[95%] sm:w-[85%] md:w-[75%] max-w-[1100px]"
>

    <!-- Stat 1: Aircraft -->
    <div
      class="stat-card flex flex-col items-center justify-center text-center p-6 sm:p-8 bg-[#FEFEFE]/90 rounded-xl text-[#003673] opacity-0 translate-y-10 transition-all duration-700 hover:scale-105 hover:shadow-2xl"
      style="transition-delay: 0.2s"
    >
      <i class="fa-solid fa-jet-fighter h-14 w-14 text-[#003673] text-5xl mb-4"></i>
      <h2 class="text-3xl font-bold text-[#003673]">12</h2>
      <p class="text-[#003673] font-medium">Fighter-Trainer Aircraft</p>
    </div>

    <!-- Stat 2: Personnel -->
    <div
      class="stat-card flex flex-col items-center justify-center text-center p-6 sm:p-8 bg-[#FEFEFE]/90 rounded-xl text-[#003673] opacity-0 translate-y-10 transition-all duration-700 hover:scale-105 hover:shadow-2xl"
      style="transition-delay: 0.4s"
    >
      <i class="fa-solid fa-users h-14 w-14 text-[#003673] text-5xl mb-4"></i>
      <h2 class="text-3xl font-bold text-[#003673]">100+</h2>
      <p class="text-[#003673] font-medium">Personnel & Staff</p>
    </div>

    <!-- Stat 3: Facilities -->
    <div
      class="stat-card flex flex-col items-center justify-center text-center p-6 sm:p-8 bg-[#FEFEFE]/90 rounded-xl text-[#003673] opacity-0 translate-y-10 transition-all duration-700 hover:scale-105 hover:shadow-2xl"
      style="transition-delay: 0.6s"
    >
      <i class="fa-solid fa-building h-14 w-14 text-[#003673] text-5xl mb-4"></i>
      <h2 class="text-3xl font-bold text-[#003673]">14</h2>
      <p class="text-[#003673] font-medium">Facilities</p>
    </div>
  </div>
</section>

<!-- News and Advisory Section -->

<section
  id="news-advisory-section"
  class="relative w-full min-h-[1890px] md:h-auto overflow-hidden flex flex-col items-center justify-center bg-cover bg-center mt-[150px] mb-[150px] pt-[350px] pb-[350px]"
  style="
    background-image: url('NewsAdvisory-bg-img.png');
    -webkit-mask-image: linear-gradient(to bottom, transparent 1%, black 10%, black 85%, transparent 100%);
    mask-image: linear-gradient(to bottom, transparent 1%, black 15%, black 85%, transparent 100%);
  "
>
  <div class="relative z-999 w-full max-w-7xl px-6 md:px-10 py-12 text-center">
  <!-- Latest News Section -->
  <h2 class="font-[Oswald] text-[37px] md:text-[37px] font-semibold text-[#003673] mt-[55px] mb-[62px] fade-in">
    Latest News Highlights
  </h2>

  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 justify-items-center mb-2">
    <!-- News Card 1 -->
    <div class="news-card bg-white rounded-xl shadow-lg overflow-hidden w-full max-w-sm">
      <img src="newshighlight-img1.jpg" alt="News 1" class="w-full h-60 object-cover" />
      <div class="p-4 flex justify-start">
        <a href="news-page.php#news-cards-container" class="text-[#003673] font-semibold hover:underline">VIEW ➤</a>
      </div>
    </div>

    <!-- News Card 2 -->
    <div class="news-card bg-white rounded-xl shadow-lg overflow-hidden w-full max-w-sm">
      <img src="newshighlight-img2.jpg" alt="News 2" class="w-full h-60 object-cover" />
      <div class="p-4 flex justify-start">
        <a href="news-page.php#news-cards-container" class="text-[#003673] font-semibold hover:underline">VIEW ➤</a>
      </div>
    </div>

    <!-- News Card 3 -->
    <div class="news-card bg-white rounded-xl shadow-lg overflow-hidden w-full max-w-sm">
      <img src="newshighlight-img3.jpg" alt="News 3" class="w-full h-60 object-cover" />
      <div class="p-4 flex justify-start">
        <a href="news-page.php#news-cards-container" class="text-[#003673] font-semibold hover:underline">VIEW ➤</a>
      </div>
    </div>
  </div>

 <a href="news-page.php" class="mt-[34px] bg-[#003673] text-white font-[Inter] font-semibold text-[14px] sm:text-[16px] w-[180px] sm:w-[210px] h-[45px] sm:h-[50px] rounded-lg hover:bg-[#002244] transition duration-200 inline-block text-center leading-[45px] sm:leading-[50px] fade-in">More News</a>

  <!-- Latest Advisories Section -->
    <h2 class="font-[Oswald] text-[37px] md:text-[37px] font-semibold text-[#003673] mt-[131px] mb-[34px] fade-in">
    Latest Advisories
  </h2>

  <div class="grid gap-6 sm:grid-cols-2 justify-items-center mb-6 h-[540px]">
    <!-- Advisory Card 1 -->
    <div class="advisory-card relative bg-[#022b6d] text-white rounded-xl shadow-lg w-full max-w-md flex flex-col items-center justify-center py-16 px-4">
      <img
        src="5thFighterWing-logo.png"
        alt="5th Fighter Wing Logo"
        class="absolute -top-8 w-24 h-24 object-contain"
      />
      <h3 class="text-lg font-semibold mt-8">5th Fighter Wing Advisory</h3>
    </div>

    <!-- Advisory Card 2 -->
    <div class="advisory-card relative bg-[#022b6d] text-white rounded-xl shadow-lg w-full max-w-md flex flex-col items-center justify-center py-16 px-4">
      <img
        src="5thFighterWing-logo.png"
        alt="5th Fighter Wing Logo"
        class="absolute -top-8 w-24 h-24 object-contain"
      />
      <h3 class="text-lg font-semibold mt-8">5th Fighter Wing Advisory</h3>
    </div>
  </div>

<a href="advisory-page.php" class="mt-[25px] bg-[#003673] text-white font-[Inter] font-semibold text-[14px] sm:text-[16px] w-[180px] sm:w-[210px] h-[45px] sm:h-[50px] rounded-lg hover:bg-[#002244] transition duration-200 inline-block text-center leading-[45px] sm:leading-[50px] fade-in">More Advisories</a>

</div>

</section>

<footer class="bg-[#003366] text-white h-[395px] flex items-center border-t border-white/10 mt-auto">
  <div class="container bg-[#003366] mx-auto px-[75px] flex flex-col md:flex-row items-center md:items-center justify-between text-center md:text-left gap-10 w-full p-[20px]">
    
    <!-- Left Section -->
    <div class="flex flex-col items-center md:items-start space-y-3">
      <div class="flex space-x-3">
        <img src="5thFighterWing-logo.png" alt="Logo 1" class="h-[70px] w-auto">
        <img src="BP-logo.png" alt="Logo 2" class="h-[70px] w-auto">
        <img src="AFP-logo.png" alt="Logo 3" class="h-[70px] w-auto">
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
<script src="notification.js"></script>
</html>
