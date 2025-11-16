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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="config.js"></script>

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

<main class="flex flex-col items-center justify-center px-4 py-12">
  <h1 class="text-[36px] sm:text-[45px] font-[Oswald] font-semibold text-[#003673] mb-12 sm:mr-[790px] sm:text-4xl">
    Schedule A Visit with Us
  </h1>

  <!-- Notification Area for Errors -->
  <div id="notification-container" class="fixed top-20 right-5 z-[100]">
    <?php if (isset($_SESSION['submission_error'])): ?>
    <div class="notification notification-error show">
        <div class="notification-content">
            <?php echo htmlspecialchars($_SESSION['submission_error']); ?>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    <script>
        // Automatically remove after 5 seconds
        setTimeout(() => {
            document.querySelector('.notification-error')?.remove();
        }, 5000);
    </script>
    <?php unset($_SESSION['submission_error']); ?>
    <?php endif; ?>
  </div>

  <div class="bg-white w-full max-w-5xl p-8 rounded-xl shadow-[0_4px_25px_rgba(0,0,0,0.1)] border border-gray-200">
    <form class="space-y-8" id="visitationForm" action="visitation_submit.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" id="session-token" value="<?php echo htmlspecialchars($token); ?>">

      <!-- Header -->
      <div>
        <p class="text-sm text-gray-600 mb-4">
          Please complete all the required fields, information will be verified upon arrival.
        </p>
      </div>

      <!-- Personal Information -->
      <section>
        <h2 class="text-[#003673] font-semibold text-sm mb-3">Personal Information</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-semibold mb-1">Last Name <span class="text-red-500">*</span></label>
            <input type="text" name="last_name" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none" required>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">First Name <span class="text-red-500">*</span></label>
            <input type="text" name="first_name" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none" required>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Middle Name <span class="text-red-500">*</span></label>
            <input type="text" name="middle_name" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
  <!-- Valid ID Upload -->
  <div class="w-full">
    <label class="block text-sm font-semibold mb-1">
      Valid ID Image <span class="text-red-500">*</span>
    </label>
    <label
      for="valid-id"
      class="flex items-center justify-center w-full border border-gray-300 rounded-md py-3 cursor-pointer hover:border-[#003673] transition text-gray-700 font-medium"
    >
      <i class="fa-regular fa-id-card mr-2 text-gray-600"></i>
      <span>Upload Valid ID</span>
    </label>
    <input id="valid-id" name="valid_id" type="file" class="sr-only" />
  </div>

  <!-- Facial Scanning -->
  <div class="w-full">
    <label class="block text-sm font-semibold mb-1">
      Facial Capture <span class="text-red-500">*</span>
    </label>
    <button
      type="button"
      id="facial-scan-btn"
      class="flex items-center justify-center w-full border border-gray-300 rounded-md py-3 cursor-pointer hover:border-[#003673] transition text-gray-700 font-medium bg-white"
    >
      <i class="fa-solid fa-camera mr-2 text-gray-600"></i>
      <span>Start Facial Capture</span>
    </button>
    <input id="selfie-photo-path" name="selfie_photo_path" type="hidden" required />
  </div>
</div>
      </section>

      <!-- Contact Information -->
      <section>
        <h2 class="text-[#003673] font-semibold text-sm mb-3">Contact Information</h2>
        <div class="mb-4">
          <label class="block text-sm font-semibold mb-1">Home Address <span class="text-red-500">*</span></label>
          <input type="text" name="home_address" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none" required>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-semibold mb-1">Contact Number <span class="text-red-500">*</span></label>
            <input type="tel" name="contact_number" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none" required>
          </div>
          <div>
            <label class="block text-sm font-semibold mb-1">Email</label>
            <input type="email" name="email" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none">
          </div>
        </div>
      </section>

      <!-- Vehicle Information -->
      <section>
        <h2 class="text-[#003673] font-semibold text-sm mb-3">Vehicle Information</h2>
        <div class="mb-4">
          <p class="text-sm font-semibold mb-1">Will you bring a vehicle? <span class="text-red-500">*</span></p>
          <div class="flex space-x-6 text-sm">
            <label class="flex items-center space-x-2">
            <input type="radio" name="has_vehicle" value="yes" class="text-[#003673]" required>
              <span>Yes</span>
            </label>
            <label class="flex items-center space-x-2">
              <input type="radio" name="has_vehicle" value="no" class="text-[#003673]" required>
              <span>No</span>
            </label>
          </div>
        </div>

        <div id="vehicle-fields" class="hidden">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-semibold mb-1">Vehicle Brand</label>
              <input type="text" name="vehicle_brand" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none">
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1">Vehicle Type</label>
              <input type="text" name="vehicle_type" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none">
            </div>
            <div>
              <label class="block text-sm font-semibold mb-1">Vehicle Color</label>
              <input type="text" name="vehicle_color" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none">
            </div>
          </div>

          <div class="mt-4">
            <label class="block text-sm font-semibold mb-1">License Plate Number</label>
            <input type="text" name="license_plate" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none">
          </div>
        </div>
      </section>

      <!-- Visit Details -->
      <section>
        <h2 class="text-[#003673] font-semibold text-sm mb-3">Visit Details</h2>
        <div class="mb-4">
          <label class="block text-sm font-semibold mb-1">Name of the Contact Personnel <span class="text-red-500">*</span></label>
          <input type="text" name="contact_personnel" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none" required>
          <p class="text-xs text-gray-500 mt-1">Write the name of the Personnel that you will meet prior to the Visit.</p>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-semibold mb-1">Facility that will be Visited in the Base <span class="text-red-500">*</span></label>
          <select name="office_to_visit" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none" required>
            <option value="" disabled selected>Please select a facility</option>
            <option value="ICT Facility">ICT Facility</option>
            <option value="Training Facility">Training Facility</option>
            <option value="Personnels Office">Personnels Office</option>
          </select>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-semibold mb-1">Visit Date <span class="text-red-500">*</span></label>
          <input type="text" id="visit-date" name="visit_date" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none" placeholder="Select a date" required>
          <p class="text-xs text-gray-500 mt-1">
            Below are the list of dates highligted in green that are available for a scheduled visitation.
          </p>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-semibold mb-1">Visit Time <span class="text-red-500">*</span></label>
          <input type="text" id="visit-time" name="visit_time" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none" placeholder="Select a time" required>
          <p class="text-xs text-gray-500 mt-1">
            The only available time for visitation is from 7:00 AM to 7:00 PM.
          </p>
        </div>
      </section>

      <!-- Submit Button -->
      <button type="submit" id="submit-request-btn" class="w-full bg-[#003673] text-white font-semibold py-3 rounded-md hover:bg-[#002a59]">
        Submit Visitation Request
      </button>

      <p class="text-[12px] text-gray-500 leading-relaxed mt-4 text-center">
        By submitting this form, you agree to comply with all security protocols and regulations of the 5th Fighter Wing Base.<br>
        *Upon submission, the request will be properly evaluated. Once approved you will be notified through email.<br>
        *If you have a scheduled visit and will not be able to attend please submit a cancellation request in the contact us page prior to the scheduled date of visitation.
      </p>
    </form>
  </div>
</main>


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



<!-- Facial Scanning Modal -->
<div id="facial-scan-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
  <div class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
      <div class="flex justify-between items-center p-6 border-b">
        <h3 class="text-xl font-semibold text-[#003673]">Facial Scanning</h3>
        <button id="close-facial-modal" class="text-gray-500 hover:text-gray-700">
          <i class="fas fa-times text-2xl"></i>
        </button>
      </div>
      <div class="p-6">
        <!-- Facial scanning content will be inserted here -->
        <div id="facial-scan-content" class="text-center">
          <video id="webcam-feed" class="w-full max-w-md mx-auto rounded-lg shadow-md" autoplay playsinline></video>
          <canvas id="captured-canvas" class="w-full max-w-md mx-auto rounded-lg shadow-md" style="display: none;"></canvas>
          <p id="scan-instructions" class="text-gray-600 my-4">Position your face in the center of the frame.</p>
          <button type="button" id="capture-photo-btn" class="px-4 py-2 bg-[#003673] text-white rounded-md hover:bg-[#002a59] transition mt-4">
            Capture Photo
          </button>
        </div>
      </div>
      <div class="flex justify-end space-x-3 p-6 border-t">
        <button id="cancel-facial-scan" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition">
          Cancel
        </button>
        <button id="complete-facial-scan" class="px-4 py-2 bg-[#003673] text-white rounded-md hover:bg-[#002a59] transition">
          Complete Facial Capture
        </button>
      </div>
    </div>
  </div>
</div>

</body>
<!-- <script src="https://cdn.tailwindcss.com"></script> -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="landingpage.js"></script>
<script src="notification.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const facialScanBtn = document.getElementById('facial-scan-btn');
    const facialScanModal = document.getElementById('facial-scan-modal');
    const closeFacialModalBtn = document.getElementById('close-facial-modal');
    const cancelFacialScanBtn = document.getElementById('cancel-facial-scan');
    const completeFacialScanBtn = document.getElementById('complete-facial-scan');
    const webcamFeed = document.getElementById('webcam-feed');
    const capturedCanvas = document.getElementById('captured-canvas');
    const capturePhotoBtn = document.getElementById('capture-photo-btn');
    const selfiePhotoPathInput = document.getElementById('selfie-photo-path');
    const scanInstructions = document.getElementById('scan-instructions');
    const submitRequestBtn = document.getElementById('submit-request-btn');

    let stream; // To store the webcam stream

    // --- New: Custom notification function ---
    const showNotification = (message, isError = false) => {
      const notification = document.createElement('div');
      notification.textContent = message;
      notification.style.position = 'fixed';
      notification.style.top = '20px';
      notification.style.right = '20px';
      notification.style.padding = '15px';
      notification.style.borderRadius = '8px';
      notification.style.color = 'white';
      notification.style.backgroundColor = isError ? '#ef4444' : '#22c55e';
      notification.style.zIndex = '1000';
      notification.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
      document.body.appendChild(notification);
      setTimeout(() => {
        notification.remove();
      }, 3000);
    };

    // Function to open the modal and start the webcam
    const startFacialScan = async () => {
      facialScanModal.classList.remove('hidden');
      webcamFeed.style.display = 'block';
      capturedCanvas.style.display = 'none';
      capturePhotoBtn.style.display = 'block';
      completeFacialScanBtn.style.display = 'none';
      scanInstructions.textContent = 'Position your face in the center of the frame and click "Capture Photo".';

      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: true });
        webcamFeed.srcObject = stream;
      } catch (err) {
        console.error("Error accessing webcam: ", err);
        scanInstructions.textContent = 'Error: Could not access webcam. Please grant camera permissions.';
        capturePhotoBtn.style.display = 'none';
      }
    };

    // Function to stop the webcam
    const stopWebcam = () => {
      if (stream) {
        stream.getTracks().forEach(track => track.stop());
        webcamFeed.srcObject = null;
      }
    };

    // Event listeners for opening and closing the modal
    facialScanBtn.addEventListener('click', startFacialScan);
    closeFacialModalBtn.addEventListener('click', () => {
      facialScanModal.classList.add('hidden');
      stopWebcam();
    });
    cancelFacialScanBtn.addEventListener('click', () => {
      facialScanModal.classList.add('hidden');
      stopWebcam();
    });

    // Event listener for capturing the photo
    capturePhotoBtn.addEventListener('click', () => {
      capturedCanvas.width = webcamFeed.videoWidth;
      capturedCanvas.height = webcamFeed.videoHeight;
      const context = capturedCanvas.getContext('2d');
      context.drawImage(webcamFeed, 0, 0, capturedCanvas.width, capturedCanvas.height);
      
      webcamFeed.style.display = 'none';
      capturedCanvas.style.display = 'block';
      capturePhotoBtn.style.display = 'none';
      completeFacialScanBtn.style.display = 'block';
      scanInstructions.textContent = 'Photo captured. Click "Complete Scan" to save.';
      stopWebcam();
    });

    // Event listener for completing the scan and uploading the image
    completeFacialScanBtn.addEventListener('click', () => {
      // --- New: Add loading state ---
      completeFacialScanBtn.disabled = true;
      completeFacialScanBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

      capturedCanvas.toBlob(async (blob) => {
        const formData = new FormData();
        formData.append('file', blob, 'selfie.jpg');
        const sessionToken = document.getElementById('session-token').value;
        formData.append('session_token', sessionToken);

        try {
          // --- Updated: Use API_BASE_URL from config.js ---
          const response = await fetch(`${API_BASE_URL}/register/face`, {
            method: 'POST',
            body: formData
          });
          const result = await response.json();

          if (response.ok) {
            selfiePhotoPathInput.value = result.file_path;
            
            // --- New: Update UI to show success ---
            showNotification('Facial scan completed successfully!');
            facialScanBtn.innerHTML = '<span>Scan Complete ✔</span>';
            facialScanBtn.classList.remove('hover:border-[#003673]', 'bg-white');
            facialScanBtn.classList.add('bg-green-500', 'text-white', 'border-green-500');
            
            facialScanModal.classList.add('hidden');
          } else {
            showNotification('Error: ' + (result.detail || 'Unknown error occurred.'), true);
          }
        } catch (error) {
          console.error('Error uploading image:', error);
          showNotification('An error occurred. Please check the console for details.', true);
        } finally {
          // --- New: Reset button state ---
          completeFacialScanBtn.disabled = false;
          completeFacialScanBtn.innerHTML = 'Complete Scan';
        }
      }, 'image/jpeg');
    });
  });
</script>
<!-- The visit-form-handler.js is no longer needed for this submission method -->
</html>
