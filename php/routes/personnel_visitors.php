<?php
require 'auth_check.php';
require 'audit_log.php';
require 'Pages/db_connect.php';
require '../config/encryption_key.php';

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
        $fullName = htmlspecialchars($user['full_name'] ?? 'Unknown User', ENT_QUOTES, 'UTF-8');
        $role = htmlspecialchars($user['role'] ?? 'Unknown Role', ENT_QUOTES, 'UTF-8');
    } else {
        session_unset();
        session_destroy();
        header("Location: Pages/login-page.php");
        exit;
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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Personnel Visitors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../../images/logo/5thFighterWing-logo.png">
    <link rel="stylesheet" href="../../stylesheet/personnel_dashboard.css" />
    <link rel="stylesheet" href="../../stylesheet/visitors.css" />
    <link rel="stylesheet" href="../../stylesheet/sidebar.css" />
</head>
<body>
<div class="body">
    <div class="left-panel">
        <div id="sidebar-container"></div>
    </div>
    
<div class="right-panel">
<div class="main-content">
      <div class="main-header d-flex justify-content-between align-items-center">
        <div class="header-left d-flex align-items-center">
          <i class="fa-solid fa-home me-2"></i>
          <h6 class="path mb-0"> / Dashboard /</h6>
          <h6 class="current-loc mb-0 ms-1">Visitors</h6>
        </div>
        <div class="header-right d-flex align-items-center">
          <i class="fa-regular fa-bell me-3"></i>
          <i class="fa-regular fa-message me-3"></i>
          <div class="user-info d-flex align-items-center">
            <i class="fa-solid fa-user-circle fa-lg me-2"></i>
            <div class="user-text">
              <span class="username"><?php echo $fullName; ?></span>
              <a id="logout-link" class="logout-link" href="logout.php">Logout</a>
              <div id="confirmModal" class="modal">
                <div class="modal-content">
                  <p id="confirmMessage"></p>
                  <div class="modal-actions">
                    <button id="confirmYes" class="btn btn-danger">Yes</button>
                    <button id="confirmNo" class="btn btn-secondary">No</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>



      <!-- ==== Expected Visitors Table ==== -->
      <div class="vehicles-container mb-4">
        <h5 class="table-title">Expected Visitors</h5>
        <div class="table-responsive">
          <table id="expectedVisitorsTable" class="table table-striped">
            <thead>
              <tr>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Last Name</th>
                <th>Contact</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="7" class="text-center">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ==== Inside Visitors Table ==== -->
      <div class="vehicles-container mb-4">
        <h5 class="table-title">Inside Visitors</h5>
        <div class="table-responsive">
          <table id="insideVisitorsTable" class="table table-striped">
            <thead>
              <tr>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Last Name</th>
                <th>Contact</th>
                <th>Key Card Number</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="9" class="text-center">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ==== Exited Visitors Table ==== -->
      <div class="vehicles-container mb-4">
        <h5 class="table-title">Exited Visitors</h5>
        <div class="table-responsive">
          <table id="exitedVisitorsTable" class="table table-striped">
            <thead>
              <tr>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Last Name</th>
                <th>Contact</th>
                <th>Key Card Number</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <tr><td colspan="9" class="text-center">Loading...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

</div>
</div>
</div>

<!-- Modals -->
<div class="modal fade" id="editTimeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Visitor Time</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="editTimeForm">
          <input type="hidden" id="editVisitorId" name="visitor_id">
          <div class="mb-3">
            <label for="editTimeIn" class="form-label">Time In</label>
            <input type="datetime-local" id="editTimeIn" name="time_in" class="form-control">
          </div>
          <div class="mb-3">
            <label for="editTimeOut" class="form-label">Time Out</label>
            <input type="datetime-local" id="editTimeOut" name="time_out" class="form-control">
          </div>
          <button type="submit" class="btn btn-primary">Save</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Updated modal to match requested style with verification tabs -->
<div class="modal fade" id="visitorDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width: 95vw;">
    <div class="modal-content" style="background-color: #ffffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); height: auto; max-height: 90vh;">
      <div class="modal-header" style="border-bottom: none; padding-bottom: 0.5rem;">
        <h5 class="modal-title">Visitor Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
        <div class="modal-body" style="background-color: white; border-radius: 12px; padding: 1rem; overflow-x: auto;">
          <ul class="nav nav-tabs mt-4" id="visitorTab" role="tablist" style="border-bottom: none;">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="true">Details</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="verify-tab" data-bs-toggle="tab" data-bs-target="#verify" type="button" role="tab" aria-controls="verify" aria-selected="false">Verify</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="facial-tab" data-bs-toggle="tab" data-bs-target="#facial" type="button" role="tab" aria-controls="facial" aria-selected="false">Facial</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="vehicle-tab" data-bs-toggle="tab" data-bs-target="#vehicle" type="button" role="tab" aria-controls="vehicle" aria-selected="false">Vehicle</button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="id-tab" data-bs-toggle="tab" data-bs-target="#id" type="button" role="tab" aria-controls="id" aria-selected="false">ID</button>
            </li>
          </ul>
          <div id="visitorDetailsSection">
            <div class="table-responsive" style="overflow-x: auto; max-width: 100%; max-height: 400px;">
              <table class="table table-bordered text-center mb-0" style="table-layout: auto; white-space: nowrap; min-width: 1200px;">
                <thead class="bg-info text-white">
                  <tr>
                    <th>Name</th>
                    <th>Home Address</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Personnel to Visit</th>
                    <th>Facility to Visit</th>
                    <th id="visitorVehicleOwnerHeader">Vehicle Owner</th>
                    <th id="visitorVehicleBrandHeader">Vehicle Brand</th>
                    <th id="visitorVehicleModelHeader">Vehicle Model</th>
                    <th id="visitorVehicleColorHeader">Vehicle Color</th>
                    <th id="visitorPlateNumberHeader">Plate Number</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td id="visitorNameCell" style="font-weight: 600;"></td>
                    <td id="visitorAddressCell"></td>
                    <td id="visitorContactCell"></td>
                    <td id="visitorEmailCell"></td>
                    <td id="visitorDateCell"></td>
                    <td id="visitorTimeCell"></td>
                    <td id="visitorPersonnelCell"></td>
                    <td id="visitorOfficeCell"></td>
                    <td id="vehicleOwnerCell" class="visitor-vehicle-column"></td>
                    <td id="vehicleBrandCell" class="visitor-vehicle-column"></td>
                    <td id="vehicleModelCell" class="visitor-vehicle-column"></td>
                    <td id="vehicleColorCell" class="visitor-vehicle-column"></td>
                    <td id="plateNumberCell" class="visitor-vehicle-column"></td>
                  </tr>
                </tbody>
              </table>
            </div>
          <div class="d-flex justify-content-center gap-4 mt-4">
            <div class="text-center">
              <strong>Valid ID</strong><br>
              <img id="visitorIDPhoto" src="" alt="Valid ID" style="max-width: 150px; max-height: 100px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
            </div>
            <div class="text-center">
              <strong>Selfie Photo</strong><br>
              <img id="visitorSelfie" src="" alt="Selfie" style="max-width: 150px; max-height: 150px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
            </div>
          </div>
        </div>

        <div class="tab-content" id="visitorTabContent" style="margin-top: 10px;">
          <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
            <!-- Details tab content can be repeated or customized if needed -->
            <button id="nextToVerify" class="btn btn-primary float-end">Verify</button>
          </div>
          <div class="tab-pane fade" id="verify" role="tabpanel" aria-labelledby="verify-tab">
            <div>
              <button id="nextToFacial" class="btn btn-primary float-end">Next</button>
            </div>
          </div>
          <div class="tab-pane fade" id="facial" role="tabpanel" aria-labelledby="facial-tab">
            <div id="facialRecognitionContainer" style="min-height: 200px; border: 1px solid #ccc; border-radius: 8px; margin-bottom: 15px; padding: 15px;">
              <div id="facialResult" style="margin-bottom: 15px;"></div>
              <div class="row">
                <div class="col-md-6">
                  <h5>Visitor Selfie</h5>
                  <img id="facialSelfie" src="" alt="Selfie" style="width: 100%; height: 300px; object-fit: cover; border: 1px solid #ccc; border-radius: 8px;">
                </div>
                <div class="col-md-6">
                  <h5>Live Camera</h5>
                  <div style="position: relative; width: 100%; height: 300px; border: 1px solid #ccc; border-radius: 8px; overflow: hidden;">
                    <img id="facialCamera" src="http://isecured.online:8000/camera/facial/frame" alt="Live Camera" style="width: 100%; height: 100%; object-fit: cover;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 200px; height: 250px; border: 3px solid red; border-radius: 8px; pointer-events: none;"></div>
                  </div>
                </div>
              </div>
              <div class="d-flex justify-content-end mt-3">
                <button id="recognizeFaceBtn" class="btn btn-success">Recognize Face</button>
              </div>
            </div>
            <button id="nextToVehicle" class="btn btn-primary float-end">Next</button>
          </div>
          <div class="tab-pane fade" id="vehicle" role="tabpanel" aria-labelledby="vehicle-tab">
            <div id="vehicleVerificationContainer" style="min-height: 200px; border: 1px solid #ccc; border-radius: 8px; margin-bottom: 15px; padding: 15px;">
              <div class="row">
                <div class="col-md-6">
                  <h5>Expected Plate Number</h5>
                  <p id="expectedPlateNumberDisplay" style="font-size: 24px; font-weight: bold; text-align: center; padding: 20px; border: 2px solid #007bff; border-radius: 8px; background-color: #f8f9fa;"></p>
                </div>
                <div class="col-md-6">
                  <h5>Live Camera</h5>
                  <div class="camera-feed-container" style="position: relative; width: 100%; height: 240px; border: 1px solid #ccc; border-radius: 8px; overflow: hidden;">
                    <img id="cameraFeed" src="https://isecured.online:8000/camera/vehicle/frame" alt="Camera Feed" style="width: 100%; height: 100%; object-fit: cover;">
                  </div>
                </div>
              </div>
              <div class="text-center mt-3">
                <p>Recognized Plate: <strong id="recognizedPlateDisplay">N/A</strong></p>
                <p>Status: <strong id="verificationStatus" class="text-muted">Awaiting scan...</strong></p>
              </div>
              <div class="d-flex justify-content-center">
                <button id="scanPlateBtn" class="btn btn-primary">Scan Plate</button>
              </div>
            </div>
            <button id="skipVehicle" class="btn btn-secondary float-start">Skip</button>
            <button id="nextToId" class="btn btn-primary float-end">Next</button>
          </div>
          <div class="tab-pane fade" id="id" role="tabpanel" aria-labelledby="id-tab">
            <div class="row">
              <div class="col-md-6">
                <div class="text-center">
                  <h5>ID Image</h5>
                  <img id="idTabImage" src="" alt="ID Image" style="max-width: 100%; max-height: 300px; border: 1px solid #ccc; border-radius: 8px;">
                </div>
              </div>
              <div class="col-md-6">
                <div id="ocrResults" style="min-height: 200px; border: 1px solid #ccc; border-radius: 8px; padding: 15px; background-color: #f8f9fa;">
                  <h5>Extracted ID Details</h5>
                  <div id="ocrContent">
                    <p class="text-muted">Processing ID image for OCR...</p>
                  </div>
                </div>
              </div>
            </div>
            <button id="markEntryBtn" class="btn btn-success float-end">Mark Entry</button>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="border-top: none;">
        <!-- Add any footer buttons if needed -->
      </div>
    </div>
  </div>
</div>

<script src="../../scripts/sidebar_personnel.js"></script>
<script src="../../scripts/visitors.js"></script>
<script src="../../scripts/session_check.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>