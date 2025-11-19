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
  <link rel="stylesheet" href="../../stylesheet/admin_maindashboard.css">
  <link rel="stylesheet" href="../../stylesheet/personnel_walkin.css">
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
          <i class="fa-solid fa-user-plus"></i>
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
        <div class="form-scroll-container">
          <div class="bg-white p-6 rounded-lg shadow-lg border">
            <form class="visitation-request-section" action="walkin_submit.php" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
              <p class="text-muted small">
                Please complete all the required fields, information will be verified upon arrival.
              </p>
            </div>

            <!-- Personal Information -->
            <section class="mb-5">
              <h3 class="text-primary fw-bold mb-3">Personal Information</h3>
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                  <input type="text" name="last_name" class="form-control" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                  <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-semibold">Middle Name</label>
                  <input type="text" name="middle_name" class="form-control">
                </div>
              </div>

              <div class="row g-3 mt-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Valid ID Image <span class="text-danger">*</span></label>
                  <label for="valid_id" class="form-control d-flex align-items-center justify-content-center border-dashed cursor-pointer">
                    <i class="fa-regular fa-id-card me-2 text-muted"></i>
                    <span>Upload Valid ID</span>
                  </label>
                  <input id="valid_id" name="valid_id" type="file" class="d-none" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Selfie Photo <span class="text-danger">*</span></label>
                  <label for="selfie-photo" class="form-control d-flex align-items-center justify-content-center border-dashed cursor-pointer">
                    <i class="fa-solid fa-camera me-2 text-muted"></i>
                    <span>Upload Selfie</span>
                  </label>
                  <input id="selfie-photo" name="selfie_photo" type="file" class="d-none" accept="image/*" required>
                </div>
              </div>
            </section>

            <!-- Contact Information -->
            <section class="mb-5">
              <h3 class="text-primary fw-bold mb-3">Contact Information</h3>
              <div class="mb-3">
                <label class="form-label fw-semibold">Home Address <span class="text-danger">*</span></label>
                <input type="text" name="home_address" class="form-control" required>
              </div>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Contact Number <span class="text-danger">*</span></label>
                  <input type="tel" name="contact_number" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Email</label>
                  <input type="email" name="email" class="form-control">
                </div>
              </div>
            </section>

            <!-- Vehicle Information -->
            <section class="mb-5">
              <h3 class="text-primary fw-bold mb-3">Vehicle Information</h3>
              <div class="mb-3">
                <p class="fw-semibold mb-2">Will you bring a vehicle? <span class="text-danger">*</span></p>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="has_vehicle" value="yes" required>
                  <label class="form-check-label">Yes</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="has_vehicle" value="no" required>
                  <label class="form-check-label">No</label>
                </div>
              </div>

              <div id="vehicle-fields" style="display: none;">
                <h4 class="text-primary fw-bold mb-3">Vehicle Details</h4>
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label fw-semibold">Vehicle Brand</label>
                    <input type="text" name="vehicle_brand" class="form-control">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold">Vehicle Type</label>
                    <input type="text" name="vehicle_type" class="form-control">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label fw-semibold">Vehicle Color</label>
                    <input type="text" name="vehicle_color" class="form-control">
                  </div>
                </div>
                <div class="mt-3">
                  <label class="form-label fw-semibold">License Plate Number</label>
                  <input type="text" name="license_plate" class="form-control">
                </div>
              </div>
            </section>

            <!-- Visit Details -->
            <section class="mb-5">
              <h3 class="text-primary fw-bold mb-3">Visit Details</h3>
              <div class="mb-3">
                <label class="form-label fw-semibold">Name of the Contact Personnel <span class="text-danger">*</span></label>
                <input type="text" name="contact_personnel" class="form-control" required>
                <small class="form-text text-muted">Write the name of the Personnel that you will meet prior to the Visit.</small>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Facility that will be Visited in the Base <span class="text-danger">*</span></label>
                <select name="office_to_visit" class="form-select" required>
                  <option value="" disabled selected>Please select a facility</option>
                  <option value="ICT Facility">ICT Facility</option>
                  <option value="Training Facility">Training Facility</option>
                  <option value="Personnels Office">Personnels Office</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Visit Date <span class="text-danger">*</span></label>
                <input type="date" name="visit_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly>
                <small class="form-text text-muted">Visit date is set to today for walk-in visits.</small>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Visit Time <span class="text-danger">*</span></label>
                <input type="time" name="visit_time" class="form-control" value="<?php echo date('H:i'); ?>" readonly>
                <small class="form-text text-muted">Visit time is set to current time for walk-in visits.</small>
              </div>
            </section>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">Submit Walk-in Request</button>

            <p class="text-muted small mt-3 text-center">
              By submitting this form, you agree to comply with all security protocols and regulations of the 5th Fighter Wing Base.<br>
              *Upon submission, the request will be properly evaluated. Once approved you will be notified through email.<br>
              *If you have a scheduled visit and will not be able to attend please submit a cancellation request in the contact us page prior to the scheduled date of visitation.
            </p>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="../../scripts/personnel_dashboard.js"></script>
<script src="../../scripts/session_check.js"></script>

<script>
// Vehicle Fields Toggle
document.querySelectorAll('input[name="has_vehicle"]').forEach(radio => {
  radio.addEventListener('change', function() {
    const vehicleFields = document.getElementById('vehicle-fields');
    if (this.value === 'yes') {
      vehicleFields.style.display = 'block';
    } else {
      vehicleFields.style.display = 'none';
    }
  });
});
</script>

<!-- Facial Scanning Modal -->
<div class="modal fade" id="facial-scan-modal" tabindex="-1" aria-labelledby="facial-scan-modal-label" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" style="background-color: #003673; color: white;">
        <h5 class="modal-title" id="facial-scan-modal-label">Facial Scanning</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <!-- Facial scanning content will be inserted here -->
        <p class="text-muted mb-4">Facial scanning interface will be loaded here.</p>
        <p class="text-sm text-muted">Please insert your Python facial scanning program in this modal.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn" style="background-color: #003673; color: white;">Complete Scan</button>
      </div>
    </div>
  </div>
</div>

<script src="../../scripts/sidebar_personnel.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
