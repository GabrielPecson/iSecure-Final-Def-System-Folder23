<?php
 require 'auth_check.php';
 require 'audit_log.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Main Dashboard</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Fonts + Icons -->
  <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="../../images/logo/5thFighterWing-logo.png">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="../../stylesheet/admin.css">
  <link rel="stylesheet" href="../../stylesheet/admin_maindashboard.css">
  <link rel="stylesheet" href="../../stylesheet/sidebar.css">
  <link rel="stylesheet" href="../../stylesheet/notification.css">
</head>
<body>
<div class="body">

  <!-- Sidebar -->
  <div class="left-panel">
    <div id="sidebar-container"></div>
  </div>

  <!-- Main content -->
  <div class="right-panel">
    <div class="main-content">

      <!-- Header -->
      <div class="main-header">
        <div class="header-left">
          <i class="fa-solid fa-home"></i>
          <h6 class="path"> / Dashboard /</h6>
          <h6 class="current-loc">Main Dashboard</h6>
        </div>
        <div class="header-right">
          <i class="fa-regular fa-bell me-3"></i>
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

      <!-- Confirm Modal -->
      <div id="confirmModal" class="modal">
        <div class="modal-content">
          <p id="confirmMessage"></p>
          <div class="modal-actions">
            <button id="confirmYes" class="btn btn-danger">Yes</button>
            <button id="confirmNo" class="btn btn-secondary">No</button>
          </div>
        </div>
      </div>

     <!-- Visitor Details Modal -->
<div class="modal fade" id="visitorDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Visitor Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p><strong>Name:</strong> <span id="visitorName"></span></p>
        <p><strong>Contact:</strong> <span id="visitorContact"></span></p>
        <p><strong>Email:</strong> <span id="visitorEmail"></span></p>
        <p><strong>Address:</strong> <span id="visitorAddress"></span></p>
        <p><strong>Reason:</strong> <span id="visitorReason"></span></p>
        <p><strong>Time In:</strong> <span id="visitorTimeIn"></span></p>
        <p><strong>Time Out:</strong> <span id="visitorTimeOut"></span></p>
        <p><strong>ID Photo:</strong><br><img id="visitorIDPhoto" src="" alt="ID Photo" style="width:100%;max-width:200px;"></p>
        <p><strong>Selfie:</strong><br><img id="visitorSelfie" src="" alt="Selfie Photo" style="width:100%;max-width:200px;"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


      <!-- Stats cards -->
    <div class="status-containers">
      <div class="stats-cards">
        <div class="stats-card-icon">
          <i class="fa-solid fa-users"></i>
        </div>
        <div class="stats-card-content">
          <div class="card-value" id="visitorsCount">0</div>
          <div class="card-label">Current Visitors</div>
          <div class="updated-time">updated 21hrs ago</div>
        </div>
      </div>
      <div class="stats-cards">
        <div class="stats-card-icon">
          <i class="fa-solid fa-car"></i>
        </div>
        <div class="stats-card-content">
          <div class="card-value" id="vehiclesCount">0</div>
          <div class="card-label">Current Vehicles</div>
          <div class="updated-time">updated 21hrs ago</div>
        </div>
      </div>
      <div class="stats-cards">
        <div class="stats-card-icon">
          <i class="fa-solid fa-list-check"></i>
        </div>
        <div class="stats-card-content">
          <div class="card-value" id="pendingCount">0</div>
          <div class="card-label">Pendings</div>
          <div class="updated-time">updated 21hrs ago</div>
        </div>
      </div>
      <div class="stats-cards">
        <div class="stats-card-icon">
          <i class="fa-solid fa-clock"></i>
        </div>
        <div class="stats-card-content">
          <div class="card-value" id="entryCount">0</div>
          <div class="card-label">Access Control</div>
          <div class="updated-time">updated 21hrs ago</div>
        </div>
      </div>
    </div>


      <!-- Chart + Widgets -->
      <div class="dashboard-grid">
        <div>
          <!-- Daily Visits -->
          <div id="daily-visits-widget" class="chart-card widget-card">
            <div class="widget-header">
              <h4>Daily Visits Analysis</h4>
              <div class="widget-actions">
                <button class="minimize-btn"><i class="fa-solid fa-minimize"></i></button>
                <button class="remove-btn"><i class="fa-solid fa-x"></i></button>
              </div>
            </div>
            <div class="widget-body">
              <canvas id="visitsChart" height="140"></canvas>
            </div>
          </div>

          <!-- Visitor Status Widget -->
          <div id="visitor-status-widget" class="visitor-status widget-card">
            <div class="widget-header">
              <h4>Visitor Status</h4>
              <div class="widget-actions">
                <button class="minimize-btn"><i class="fa-solid fa-minimize"></i></button>
                <button class="remove-btn"><i class="fa-solid fa-x"></i></button>
              </div>
            </div>
            <div class="widget-body">
              <table>
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="visitorStatusTbody">
                  <tr>
                    <td colspan="5" class="text-center">Loading visitors...</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div>
          <!-- Latest Vehicle -->
          <div id="latest-vehicle-widget" class="widget-box widget-card">
            <div class="widget-header">
              <h4>Latest Vehicle Entry</h4>
              <div class="widget-actions">
                <button class="minimize-btn"><i class="fa-solid fa-minimize"></i></button>
                <button class="remove-btn"><i class="fa-solid fa-x"></i></button>
              </div>
            </div>
            <div class="widget-body">
              <ul class="widget-list" id="latestVehicleList">
                <li>TOYOTA FORTUNER - John Doe</li>
                <li>HONDA CIVIC - Jane Smith</li>
                <li>NISSAN NV - Visitor A</li>
              </ul>
            </div>
            <div class="widget-footer"><a href="vehicles.php">View full information</a></div>
          </div>

          <!-- User Activity -->
          <div id="user-activity-widget" class="widget-box widget-card">
            <div class="widget-header">
              <h4>User Activity</h4>
              <div class="widget-actions">
                <button class="minimize-btn"><i class="fa-solid fa-minimize"></i></button>
                <button class="remove-btn"><i class="fa-solid fa-x"></i></button>
              </div>
            </div>
            <div class="widget-body">
              <ul class="widget-list" id="userActivityList">
                <li><strong>John Doe</strong> - Logged in</li>
                <li><strong>Jane Smith</strong> - Added visitor</li>
                <li><strong>Admin</strong> - Updated landing page</li>
              </ul>
            </div>
            <div class="widget-footer"><a href="audit_log.php">View all activity</a></div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<div id="notification-container"></div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../../scripts/sidebardashboard.js"></script>
<script src="../../scripts/admin.js"></script>
<script src="../../scripts/notification.js"></script>
</body>
</html>