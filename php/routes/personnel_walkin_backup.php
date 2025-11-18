<?php
require 'auth_check.php';
require 'audit_log.php';

// Default fallbacks
$fullName = 'Unknown User';
$role = 'Unknown Role';

// Check session
if (!isset($_SESSION['token'])) {
    header("Location: Pages/login-page.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM personnel_sessions WHERE token = :token AND expires_at > NOW()");
$stmt->execute([':token' => $_SESSION['token']]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    session_unset();
    session_destroy();
    header("Location: Pages/login-page.php");
    exit;
}

// Fetch user info
if (!empty($session['user_id'])) {
    $stmt = $pdo->prepare("SELECT full_name, role FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $session['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $fullName = htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8');
        $role = htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8');
    }
}

// Check if role is Personnel (assuming 'User' is personnel)
if ($role !== 'User') {
    echo "<script>alert('Access denied. Personnel only.'); window.location.href='Pages/login-page.php';</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Personnel Walk-in Visit</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../../images/logo/5thFighterWing-logo.png">
  <link rel="stylesheet" href="../../stylesheet/personnel_dashboard.css">
  <link rel="stylesheet" href="../../stylesheet/sidebar.css">
</head>
<body>

<div class="body">
  <div class="left-panel">
    <div id="sidebar-container"></div>
  </div>

  <div class="right-panel">
    <div class="main-content">
      <div class="main-header">
        <div class="header-left">
          <i class="fa-solid fa-home"></i>
          <h6 class="path"> / Dashboard /</h6>
          <h6 class="current-loc">Walk-in Visit</h6>
        </div>
        <div class="header-right">
          <div class="notification-dropdown">
            <i class="fa-regular fa-bell me-3" id="notification-bell"></i>
            <div class="notification-menu" id="notification-menu">
              <div class="notification-header">Notifications</div>
              <div id="notification-list">
                <!-- Notifications will be loaded here -->
              </div>
            </div>
          </div>
          <i class="fa-regular fa-message me-3"></i>
          <div class="user-info">
            <i class="fa-solid fa-user-circle fa-lg me-2"></i>
            <div class="user-text">
              <span class="username"><?php echo $fullName; ?></span>
              <a id="logout-link" class="logout-link" href="logout.php">Logout</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Walk-in Visit Form -->
      <div class="walkin-form-section">
        <h4>Walk-in Visit Request</h4>
<form class="space-y-8" action="walkin_submit.php" method="POST" enctype="multipart/form-data">
<div>
<p class="text-sm text-gray-600 mb-4">
Please complete all the required fields, information will be verified upon arrival.
</p>
</div>
          <div class="container">
            <div class="row">
              <div class="col-md-6">
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
            <label class="block text-sm font-semibold mb-1">Middle Name</label>
            <input type="text" name="middle_name" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mt-4">
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
    <input id="valid-id" name="valid_id" type="file" class="hidden" required />
  </div>

  <!-- Selfie Upload -->
  <div class="w-full">
    <label class="block text-sm font-semibold mb-1">
      Selfie Photo <span class="text-red-500">*</span>
    </label>
    <label
      for="selfie-photo"
      class="flex items-center justify-center w-full border border-gray-300 rounded-md py-3 cursor-pointer hover:border-[#003673] transition text-gray-700 font-medium"
    >
      <i class="fa-solid fa-camera mr-2 text-gray-600"></i>
      <span>Upload Selfie</span>
    </label>
    <input id="selfie-photo" name="selfie_photo" type="file" class="hidden" required />
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
          <input type="text" id="visit-date" name="visit_date" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-[#003673] focus:outline-none" value="<?php echo date('Y-m-d'); ?>" readonly>
          <p class="text-xs text-gray-500 mt-1">
            This is automatically set to today's date for walk-in visits.
          </p>
        </div>
      </section>
              </div>

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
            </div>
          </div>
        </form>
      </div>

      <!-- Submit Button -->
      <button type="submit" class="w-full bg-[#003673] text-white font-semibold py-3 rounded-md hover:bg-[#002a59] transition">
        Submit Walk-in Request
      </button>

      <p class="text-[12px] text-gray-500 leading-relaxed mt-4 text-center">
        By submitting this form, you agree to comply with all security protocols and regulations of the 5th Fighter Wing Base.<br>
        *Upon submission, the request will be properly evaluated. Once approved you will be notified through email.<br>
        *If you have a scheduled visit and will not be able to attend please submit a cancellation request in the contact us page prior to the scheduled date of visitation.
      </p>

    </div>
  </div>
</div>

<script src="../../scripts/personnel_dashboard.js"></script>
<script src="../../scripts/session_check.js"></script>
<script>
document.querySelectorAll('input[name="has_vehicle"]').forEach(radio => {
  radio.addEventListener('change', function() {
    const vehicleFields = document.getElementById('vehicle-fields');
    if (this.value === 'yes') {
      vehicleFields.classList.remove('hidden');
    } else {
      vehicleFields.classList.add('hidden');
    }
  });
});
</script>


<script src="../../scripts/sidebar_personnel.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>