<?php
require 'auth_check.php';
require 'audit_log.php';
require 'db_connect.php';
require 'encryption_key.php';

// Default fallbacks
$fullName = 'Unknown User';
$role = 'Unknown Role';

// Check if session token exists
if (!isset($_SESSION['token'])) {
    header("Location: Pages/login-page.php");
    exit;
}

// Validate token in DB
$stmt = $pdo->prepare("SELECT * FROM personnel_sessions WHERE token = :token AND expires_at > NOW()");
$stmt->execute([':token' => $_SESSION['token']]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    session_unset();
    session_destroy();
    header("Location: login-page.php");
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
        header("Location: login-page.php");
        exit;
    }
} else {
    session_unset();
    session_destroy();
    header("Location: login-page.php");
    exit;
}

// --- Fetch All Requests (not just pending) ---
$stmt = $pdo->prepare("SELECT *, CONCAT(first_name, ' ', middle_name, ' ', last_name) as visitor_name FROM visitation_requests ORDER BY created_at DESC");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Data is already in plain text, no decryption needed
foreach ($requests as &$request) {
    // Handle empty office_to_visit
    if (empty($request['office_to_visit'])) {
        $request['office_to_visit'] = 'Not specified';
    }
    // Rebuild visitor_name
    $request['visitor_name'] = trim(implode(' ', array_filter([$request['first_name'], $request['middle_name'], $request['last_name']])));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="icon" type="image/png" href="5thFighterWing-logo.png">
  <link rel="stylesheet" href="pendings.css">
  <link rel="stylesheet" href="sidebar.css">
  <title>Pendings</title>
  <style>.modal img { max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 8px; }</style>
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
      <h6 class="current-loc">Pendings</h6>
    </div>
    <div class="header-right">
      <i class="fa-regular fa-bell me-3"></i>
      <i class="fa-regular fa-message me-3"></i>
      <div class="user-info">
        <i class="fa-solid fa-user-circle fa-lg me-2" id="user-icon"></i>
        <div class="user-text">
          <span class="username"><?php echo $fullName; ?></span>
          <a id="logout-link" class="logout-link" href="logout.php">Logout</a>
        </div>
      </div>
    </div>
  </div>

 <div class="container mt-4">
  <h3><i class="fa-solid fa-clock-rotate-left"></i> Visitation Requests</h3>

  <ul class="nav nav-tabs mt-3" id="requestTabs">
    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#pendingTab">Pending</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#approvedTab">Approved</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#rejectedTab">Rejected</a></li>
    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#cancelledTab">Cancelled</a></li>
  </ul>

  <div class="tab-content mt-3">
    <!-- Pending -->
    <div class="tab-pane fade show active" id="pendingTab">
      <div class="personnel-container">
        <table class="table table-striped table-hover">
          <thead class="table-primary"><tr><th>Visitor</th><th>Contact Number</th><th>Email</th><th>Purpose of Visit</th><th>Scheduled Date</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody id="pendingTable">
        <?php
        $hasPending = false;
        foreach ($requests as $req):
          if ($req['status'] === 'Pending'):
            $hasPending = true;
        ?>
            <tr data-id="<?= $req['id'] ?>">
              <td><?= htmlspecialchars($req['visitor_name']) ?></td>
              <td><?= htmlspecialchars($req['contact_number']) ?></td>
              <td><?= htmlspecialchars($req['email']) ?></td>
              <td><?= htmlspecialchars($req['reason']) ?></td>
              <td><?= htmlspecialchars($req['visit_date']) ?></td>
              <td><span class="status-badge status-pending"><?= $req['status'] ?></span></td>
              <td>
                <button class="btn btn-primary btn-sm view-btn"
                  data-id="<?= $req['id'] ?>"
                  data-status="<?= $req['status'] ?>"
                  data-name="<?= htmlspecialchars($req['visitor_name']) ?>"
                  data-home="<?= htmlspecialchars($req['home_address']) ?>"
                  data-contact="<?= htmlspecialchars($req['contact_number']) ?>"
                  data-email="<?= htmlspecialchars($req['email']) ?>"
                  data-date="<?= $req['visit_date'] ?>"
                  data-time="<?= $req['visit_time'] ?>"
                  data-reason="<?= htmlspecialchars($req['reason']) ?>"
                  data-personnel="<?= htmlspecialchars($req['personnel_related']) ?>"
                  data-office="<?= htmlspecialchars($req['office_to_visit']) ?>"
                  data-vehicleowner="<?= htmlspecialchars($req['vehicle_owner']) ?>"
                  data-vehiclebrand="<?= htmlspecialchars($req['vehicle_brand']) ?>"
                  data-vehiclemodel="<?= htmlspecialchars($req['vehicle_model']) ?>"
                  data-vehiclecolor="<?= htmlspecialchars($req['vehicle_color']) ?>"
                  data-platenumber="<?= htmlspecialchars($req['plate_number']) ?>"
                  data-drivername="<?= htmlspecialchars($req['driver_name']) ?>"
                  data-validid="<?= htmlspecialchars($req['valid_id_path']) ?>"
                  data-selfie="<?= htmlspecialchars($req['selfie_photo_path']) ?>"
                  data-driverid="<?= htmlspecialchars($req['driver_id']) ?>"
                >View</button>
              </td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$hasPending): ?>
          <tr><td colspan="7" class="text-center">There are no current visitation request.</td></tr>
        <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Approved -->
    <div class="tab-pane fade" id="approvedTab">
      <div class="personnel-container">
        <table class="table table-striped table-hover">
          <thead class="table-primary"><tr><th>Visitor</th><th>Contact Number</th><th>Email</th><th>Purpose of Visit</th><th>Scheduled Date</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody id="approvedTable">
        <?php
        $hasApproved = false;
        foreach ($requests as $req):
          if ($req['status'] === 'Approved'):
            $hasApproved = true;
        ?>
            <tr data-id="<?= $req['id'] ?>">
              <td><?= htmlspecialchars($req['visitor_name']) ?></td>
              <td><?= htmlspecialchars($req['contact_number']) ?></td>
              <td><?= htmlspecialchars($req['email']) ?></td>
              <td><?= htmlspecialchars($req['reason']) ?></td>
              <td><?= htmlspecialchars($req['visit_date']) ?></td>
              <td><span class="status-badge status-approved"><?= $req['status'] ?></span></td>
              <td>
                <button class="btn btn-primary btn-sm view-btn"
                  data-id="<?= $req['id'] ?>"
                  data-status="<?= $req['status'] ?>"
                  data-name="<?= htmlspecialchars($req['visitor_name']) ?>"
                  data-home="<?= htmlspecialchars($req['home_address']) ?>"
                  data-contact="<?= htmlspecialchars($req['contact_number']) ?>"
                  data-email="<?= htmlspecialchars($req['email']) ?>"
                  data-date="<?= $req['visit_date'] ?>"
                  data-time="<?= $req['visit_time'] ?>"
                  data-reason="<?= htmlspecialchars($req['reason']) ?>"
                  data-personnel="<?= htmlspecialchars($req['personnel_related']) ?>"
                  data-office="<?= htmlspecialchars($req['office_to_visit']) ?>"
                  data-vehicleowner="<?= htmlspecialchars($req['vehicle_owner']) ?>"
                  data-vehiclebrand="<?= htmlspecialchars($req['vehicle_brand']) ?>"
                  data-vehiclemodel="<?= htmlspecialchars($req['vehicle_model']) ?>"
                  data-vehiclecolor="<?= htmlspecialchars($req['vehicle_color']) ?>"
                  data-platenumber="<?= htmlspecialchars($req['plate_number']) ?>"
                  data-drivername="<?= htmlspecialchars($req['driver_name']) ?>"
                  data-validid="<?= htmlspecialchars($req['valid_id_path']) ?>"
                  data-selfie="<?= htmlspecialchars($req['selfie_photo_path']) ?>"
                  data-driverid="<?= htmlspecialchars($req['driver_id']) ?>"
                >View</button>
                <button class="btn btn-danger btn-sm cancel-btn ms-1"
                  data-id="<?= $req['id'] ?>"
                  data-name="<?= htmlspecialchars($req['visitor_name']) ?>"
                >Cancel</button>
              </td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$hasApproved): ?>
          <tr><td colspan="7" class="text-center">There are no current visitation request.</td></tr>
        <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Rejected -->
    <div class="tab-pane fade" id="rejectedTab">
      <div class="personnel-container">
        <table class="table table-striped table-hover">
          <thead class="table-primary"><tr><th>Visitor</th><th>Contact Number</th><th>Email</th><th>Purpose of Visit</th><th>Scheduled Date</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody id="rejectedTable">
        <?php
        $hasRejected = false;
        foreach ($requests as $req):
          if ($req['status'] === 'Rejected'):
            $hasRejected = true;
        ?>
            <tr data-id="<?= $req['id'] ?>">
              <td><?= htmlspecialchars($req['visitor_name']) ?></td>
              <td><?= htmlspecialchars($req['contact_number']) ?></td>
              <td><?= htmlspecialchars($req['email']) ?></td>
              <td><?= htmlspecialchars($req['reason']) ?></td>
              <td><?= htmlspecialchars($req['visit_date']) ?></td>
              <td><span class="status-badge status-rejected"><?= $req['status'] ?></span></td>
              <td>
                <button class="btn btn-primary btn-sm view-btn"
                  data-id="<?= $req['id'] ?>"
                  data-status="<?= $req['status'] ?>"
                  data-name="<?= htmlspecialchars($req['visitor_name']) ?>"
                  data-home="<?= htmlspecialchars($req['home_address']) ?>"
                  data-contact="<?= htmlspecialchars($req['contact_number']) ?>"
                  data-email="<?= htmlspecialchars($req['email']) ?>"
                  data-date="<?= $req['visit_date'] ?>"
                  data-time="<?= $req['visit_time'] ?>"
                  data-reason="<?= htmlspecialchars($req['reason']) ?>"
                  data-personnel="<?= htmlspecialchars($req['personnel_related']) ?>"
                  data-office="<?= htmlspecialchars($req['office_to_visit']) ?>"
                  data-vehicleowner="<?= htmlspecialchars($req['vehicle_owner']) ?>"
                  data-vehiclebrand="<?= htmlspecialchars($req['vehicle_brand']) ?>"
                  data-vehiclemodel="<?= htmlspecialchars($req['vehicle_model']) ?>"
                  data-vehiclecolor="<?= htmlspecialchars($req['vehicle_color']) ?>"
                  data-platenumber="<?= htmlspecialchars($req['plate_number']) ?>"
                  data-drivername="<?= htmlspecialchars($req['driver_name']) ?>"
                  data-validid="<?= htmlspecialchars($req['valid_id_path']) ?>"
                  data-selfie="<?= htmlspecialchars($req['selfie_photo_path']) ?>"
                  data-driverid="<?= htmlspecialchars($req['driver_id']) ?>"
                >View</button>
              </td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$hasRejected): ?>
          <tr><td colspan="7" class="text-center">There are no current visitation request.</td></tr>
        <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Cancelled -->
    <div class="tab-pane fade" id="cancelledTab">
      <div class="personnel-container">
        <table class="table table-striped table-hover">
          <thead class="table-primary"><tr><th>Visitor</th><th>Contact Number</th><th>Email</th><th>Purpose of Visit</th><th>Scheduled Date</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody id="cancelledTable">
        <?php
        $hasCancelled = false;
        foreach ($requests as $req):
          if ($req['status'] === 'Cancelled'):
            $hasCancelled = true;
        ?>
            <tr data-id="<?= $req['id'] ?>">
              <td><?= htmlspecialchars($req['visitor_name']) ?></td>
              <td><?= htmlspecialchars($req['contact_number']) ?></td>
              <td><?= htmlspecialchars($req['email']) ?></td>
              <td><?= htmlspecialchars($req['reason']) ?></td>
              <td><?= htmlspecialchars($req['visit_date']) ?></td>
              <td><span class="status-badge status-cancelled">Cancelled</span></td>
              <td>
                <button class="btn btn-primary btn-sm view-btn"
                  data-id="<?= $req['id'] ?>"
                  data-status="<?= $req['status'] ?>"
                  data-name="<?= htmlspecialchars($req['visitor_name']) ?>"
                  data-home="<?= htmlspecialchars($req['home_address']) ?>"
                  data-contact="<?= htmlspecialchars($req['contact_number']) ?>"
                  data-email="<?= htmlspecialchars($req['email']) ?>"
                  data-date="<?= $req['visit_date'] ?>"
                  data-time="<?= $req['visit_time'] ?>"
                  data-reason="<?= htmlspecialchars($req['reason']) ?>"
                  data-personnel="<?= htmlspecialchars($req['personnel_related']) ?>"
                  data-office="<?= htmlspecialchars($req['office_to_visit']) ?>"
                  data-vehicleowner="<?= htmlspecialchars($req['vehicle_owner']) ?>"
                  data-vehiclebrand="<?= htmlspecialchars($req['vehicle_brand']) ?>"
                  data-vehiclemodel="<?= htmlspecialchars($req['vehicle_model']) ?>"
                  data-vehiclecolor="<?= htmlspecialchars($req['vehicle_color']) ?>"
                  data-platenumber="<?= htmlspecialchars($req['plate_number']) ?>"
                  data-drivername="<?= htmlspecialchars($req['driver_name']) ?>"
                  data-validid="<?= htmlspecialchars($req['valid_id_path']) ?>"
                  data-selfie="<?= htmlspecialchars($req['selfie_photo_path']) ?>"
                  data-driverid="<?= htmlspecialchars($req['driver_id']) ?>"
                >View</button>
              </td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if (!$hasCancelled): ?>
          <tr><td colspan="7" class="text-center">There are no cancelled visitation requests.</td></tr>
        <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

  <!-- Request Modal -->
  <div class="modal fade" id="requestModal" tabindex="-1">
  <div class="modal-dialog big-modal-dialog">
      <div class="modal-content big-modal-content">
        <div class="modal-header"><h5 class="modal-title">Visitation Request Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0 big-modal-body">
          <div class="table-responsive" style="max-height: 100%;">
            <table class="table table-bordered text-center mb-0" style="table-layout: fixed; word-wrap: break-word; min-width: 900px;">
              <thead class="bg-info text-white">
                <tr>
                  <th >Name</th>
                  <th >Home Address</th>
                  <th >Contact</th>
                  <th >Email</th>
                  <th >Date</th>
                  <th >Time</th>
                  <th >Reason</th>
                  <th >Personnel to Visit</th>
                  <th >Facility to Visit</th>
                  <th id="vehicleOwnerHeader">Vehicle Owner</th>
                  <th id="vehicleBrandHeader">Vehicle Brand</th>
                  <th id="vehicleModelHeader">Vehicle Model</th>
                  <th id="vehicleColorHeader">Vehicle Color</th>
                  <th id="plateNumberHeader">Plate Number</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td id="modalNameCell"></td>
                  <td id="modalHomeCell"></td>
                  <td id="modalContactCell"></td>
                  <td id="modalEmailCell"></td>
                  <td id="modalDateCell"></td>
                  <td id="modalTimeCell"></td>
                  <td id="modalReasonCell"></td>
                  <td id="modalPersonnelCell"></td>
                  <td id="modalOfficeCell"></td>
                  <td id="modalVehicleOwnerCell" class="vehicle-column"></td>
                  <td id="modalVehicleBrandCell" class="vehicle-column"></td>
                  <td id="modalVehicleModelCell" class="vehicle-column"></td>
                  <td id="modalVehicleColorCell" class="vehicle-column"></td>
                  <td id="modalPlateNumberCell" class="vehicle-column"></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="image-container d-flex justify-content-center gap-3 mt-3">
            <div class="image-box text-center">
              <strong>Valid ID</strong><br>
              <img id="modalValidId" src="" alt="Valid ID" class="uniform-image" style="max-width: 150px; max-height: 150px;">
            </div>
            <div class="image-box text-center">
              <strong>Selfie Photo</strong><br>
              <img id="modalSelfie" src="" alt="Selfie" class="uniform-image" style="max-width: 150px; max-height: 150px;">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <input type="hidden" id="modalRequestId">
          <button id="approveBtn" class="btn btn-success">Approve</button>
          <button id="rejectBtn" class="btn btn-danger">Reject</button>
          <button id="cancelBtn" class="btn btn-warning" style="display: none;">Cancel Request</button>
        </div>
      </div>
    </div>
  </div>

</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script>
const requestModal = new bootstrap.Modal(document.getElementById("requestModal"));

        document.querySelectorAll(".view-btn").forEach(btn => {
          btn.addEventListener("click", () => {
            document.getElementById("modalNameCell").textContent = btn.dataset.name;
            document.getElementById("modalHomeCell").textContent = btn.dataset.home;
            document.getElementById("modalContactCell").textContent = btn.dataset.contact;
            document.getElementById("modalEmailCell").textContent = btn.dataset.email;
            document.getElementById("modalDateCell").textContent = btn.dataset.date;
            document.getElementById("modalTimeCell").textContent = btn.dataset.time;
            document.getElementById("modalReasonCell").textContent = btn.dataset.reason;
            document.getElementById("modalPersonnelCell").textContent = btn.dataset.personnel;
            document.getElementById("modalOfficeCell").textContent = btn.dataset.office;
            document.getElementById("modalVehicleOwnerCell").textContent = btn.dataset.vehicleowner;
            document.getElementById("modalVehicleBrandCell").textContent = btn.dataset.vehiclebrand;
            document.getElementById("modalVehicleModelCell").textContent = btn.dataset.vehiclemodel;
            document.getElementById("modalVehicleColorCell").textContent = btn.dataset.vehiclecolor;
            document.getElementById("modalPlateNumberCell").textContent = btn.dataset.platenumber;
            document.getElementById("modalValidId").src = "../../php/routes/fetch_request_image.php?request_id=" + btn.dataset.id + "&type=id";
            document.getElementById("modalSelfie").src = "../../php/routes/fetch_request_image.php?request_id=" + btn.dataset.id + "&type=selfie";
            document.getElementById("modalRequestId").value = btn.dataset.id;

    // Hide approve/reject buttons if not pending
    const approveBtn = document.getElementById("approveBtn");
    const rejectBtn = document.getElementById("rejectBtn");
    const cancelBtn = document.getElementById("cancelBtn");
    if (btn.dataset.status === "Pending") {
      approveBtn.style.display = "inline-block";
      rejectBtn.style.display = "inline-block";
      cancelBtn.style.display = "none";
    } else if (btn.dataset.status === "Approved") {
      approveBtn.style.display = "none";
      rejectBtn.style.display = "none";
      cancelBtn.style.display = "inline-block";
    } else {
      approveBtn.style.display = "none";
      rejectBtn.style.display = "none";
      cancelBtn.style.display = "none";
    }

    // Hide vehicle columns if no vehicle
    const vehicleColumns = document.querySelectorAll(".vehicle-column");
    const vehicleHeaders = ["vehicleOwnerHeader", "vehicleBrandHeader", "vehicleModelHeader", "vehicleColorHeader", "plateNumberHeader"];
    const hasVehicle = btn.dataset.vehicleowner && btn.dataset.vehicleowner.trim() !== "";
    vehicleColumns.forEach(col => {
      col.style.display = hasVehicle ? "table-cell" : "none";
    });
    vehicleHeaders.forEach(headerId => {
      const header = document.getElementById(headerId);
      if (header) header.style.display = hasVehicle ? "table-cell" : "none";
    });

    requestModal.show();
  });
});

// async function handleAction(id, action) {
//   try {
//     const res = await fetch("approve_visitation_request.php", {
//       method: "POST",
//       headers: {"Content-Type": "application/x-www-form-urlencoded"},
//       body: new URLSearchParams({ id: id, action: action })
//     });
//     const data = await res.json();
//     if (data.success) {
//       // Move row to correct tab
//       const row = document.querySelector(`tr[data-id='${id}']`);
//       if (row) {
//         row.remove();
//         row.querySelector("td:nth-child(4)").innerHTML = 
//           `<span class="badge ${action==='approve'?'bg-success':'bg-danger'}">${action==='approve'?'Approved':'Rejected'}</span>`;
//         document.getElementById(action==='approve'?'approvedTable':'rejectedTable').appendChild(row);
//       }
//       requestModal.hide();
//     } else {
//       alert("Error: " + data.message);
//     }
//   } catch (err) {
//     console.error(err);
//     alert("Something went wrong.");
//   }
// }

document.getElementById("approveBtn").addEventListener("click", () => {
  handleDecision("approve");
});
document.getElementById("rejectBtn").addEventListener("click", () => {
  handleDecision("reject");
});
document.getElementById("cancelBtn").addEventListener("click", () => {
  handleCancel();
});

function handleDecision(action) {
  const id = document.getElementById("modalRequestId").value;

  fetch("approve_visitation_request.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `id=${encodeURIComponent(id)}&action=${action}`
  })
  .then(res => res.json())
  .then(data => {
  if (data.success) {
    const row = document.querySelector(`.view-btn[data-id="${id}"]`).closest("tr");
    if (row) {
      // Update status badge
      let badgeClass = "bg-warning text-dark";
      if (data.status === "Approved") badgeClass = "bg-success";
      if (data.status === "Rejected") badgeClass = "bg-danger";
      row.querySelector("td:nth-child(4)").innerHTML =
        `<span class="badge ${badgeClass}">${data.status}</span>`;

      // Move row to correct tab
      if (data.status === "Approved") {
        document.querySelector("#approvedTab tbody").appendChild(row);
      } else if (data.status === "Rejected") {
        document.querySelector("#rejectedTab tbody").appendChild(row);
      }
    }
    requestModal.hide();

    // 🔹 NEW: refresh vehicles tables if available
    if (typeof window.loadExpectedVehicles === "function") {
      window.loadExpectedVehicles();
    }
    if (typeof window.loadInsideVehicles === "function") {
      window.loadInsideVehicles();
    }

  } else {
    alert("Error: " + data.error);
  }
})

  .catch(err => alert("Request failed: " + err));
}

function handleCancel() {
  const id = document.getElementById("modalRequestId").value;

  if (confirm("Are you sure you want to cancel this approved visitation request? This will remove the visitor and vehicle entries from the system.")) {
    fetch("cancel_visitation_request.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `id=${encodeURIComponent(id)}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const row = document.querySelector(`.view-btn[data-id="${id}"]`).closest("tr");
        if (row) {
          // Update status badge
          row.querySelector("td:nth-child(6)").innerHTML =
            `<span class="badge bg-secondary">Cancelled</span>`;

          // Move row to cancelled tab
          document.querySelector("#cancelledTab tbody").appendChild(row);
          // Update status badge
          row.querySelector("td:nth-child(6)").innerHTML = '<span class="status-badge status-cancelled">Cancelled</span>';
        }
        requestModal.hide();

        // Refresh visitor and vehicle tables
        if (typeof window.loadExpectedVisitors === "function") {
          window.loadExpectedVisitors();
        }
        if (typeof window.loadInsideVisitors === "function") {
          window.loadInsideVisitors();
        }
        if (typeof window.loadExpectedVehicles === "function") {
          window.loadExpectedVehicles();
        }
        if (typeof window.loadInsideVehicles === "function") {
          window.loadInsideVehicles();
        }

      } else {
        alert("Error: " + data.message);
      }
    })
    .catch(err => alert("Request failed: " + err));
  }
}

// Handle cancel button clicks from table
document.addEventListener("click", (e) => {
  if (e.target.classList.contains("cancel-btn")) {
    const id = e.target.dataset.id;
    const name = e.target.dataset.name;

    if (confirm(`Are you sure you want to cancel the visitation request for ${name}? This will remove the visitor and vehicle entries from the system.`)) {
      fetch("cancel_visitation_request.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `id=${encodeURIComponent(id)}`
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const row = e.target.closest("tr");
          if (row) {
            // Update status badge
            row.querySelector("td:nth-child(6)").innerHTML =
              `<span class="badge bg-secondary">Cancelled</span>`;

            // Move row to cancelled tab
            document.querySelector("#cancelledTab tbody").appendChild(row);
            // Update status badge
            row.querySelector("td:nth-child(6)").innerHTML = '<span class="status-badge status-cancelled">Cancelled</span>';
          }

          // Refresh visitor and vehicle tables
          if (typeof window.loadExpectedVisitors === "function") {
            window.loadExpectedVisitors();
          }
          if (typeof window.loadInsideVisitors === "function") {
            window.loadInsideVisitors();
          }
          if (typeof window.loadExpectedVehicles === "function") {
            window.loadExpectedVehicles();
          }
          if (typeof window.loadInsideVehicles === "function") {
            window.loadInsideVehicles();
          }

        } else {
          alert("Error: " + data.message);
        }
      })
      .catch(err => alert("Request failed: " + err));
    }
  }
});

</script>
<script src="sidebar.js"></script>
</body>
</html>