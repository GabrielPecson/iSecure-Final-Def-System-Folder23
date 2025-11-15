<?php
require 'auth_check.php';
require 'audit_log.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Visit Logs</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

  <!-- Fonts + Icons -->
  <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@400;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="5thFighterWing-logo.png" />

  <!-- Custom CSS -->
  <link rel="stylesheet" href="admin.css" />
  <link rel="stylesheet" href="/sidebar.css" />

  <!-- Date Picker -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />

  <!-- Export Libraries -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/docx@6.1.0/build/docx.min.js"></script>
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
          <i class="fa-solid fa-book"></i>
          <h6 class="path"> / Dashboard /</h6>
          <h6 class="current-loc">Visit Logs</h6>
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

      <!-- Date Filter -->
      <div class="date-filter-block">
        <label for="logDateInput" class="form-label">Select Date:</label>
        <input type="text" id="logDateInput" class="form-control" placeholder="Select date" />
      </div>

      <!-- Search Bar -->
      <div class="mb-3">
        <label for="searchInput" class="form-label">Search by Visitor Name:</label>
        <input type="text" id="searchInput" class="form-control" placeholder="Enter visitor name" />
      </div>

      <!-- Export Buttons -->
      <div class="mb-3">
        <button id="printBtn" class="btn btn-primary me-2">Print</button>
        <button id="exportPdfBtn" class="btn btn-danger me-2">Export PDF</button>
        <button id="exportExcelBtn" class="btn btn-success me-2">Export Excel</button>
      </div>

      <!-- Visit Logs Table -->
      <div class="table-responsive">
        <table class="table table-striped" id="visitLogsTable">
          <thead>
            <tr>
              <th>Name</th>
              <th>Contact</th>
              <th>Email</th>
              <th>Address</th>
              <th>Reason</th>
              <th>Visit Location</th>
              <th>Time In</th>
              <th>Time Out</th>
              <th>Status</th>
              <th>Vehicle Details</th>
            </tr>
          </thead>
          <tbody id="visitLogsTbody">
            <tr>
              <td colspan="10" class="text-center">Select a date to view visit logs</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="sidebar.js"></script>
<script src="session_check.js"></script>
<script>
// Date Picker
flatpickr("#logDate", {
  dateFormat: "Y-m-d",
  defaultDate: new Date(),
  onChange: function(selectedDates, dateStr) {
    fetchVisitLogs(dateStr);
  }
});

// Fetch Visit Logs
function fetchVisitLogs(date) {
  fetch(`fetch_visit_logs.php?date=${date}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        displayVisitLogs(data.data);
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => console.error('Error fetching visit logs:', error));
}

// Display Visit Logs
function displayVisitLogs(logs) {
  const tbody = document.getElementById('visitLogsTbody');
  tbody.innerHTML = '';

  if (logs.length === 0) {
    tbody.innerHTML = '<tr><td colspan="10" class="text-center">No visit logs found for this date</td></tr>';
    return;
  }

  logs.forEach(log => {
    const vehicleDetails = log.vehicle_owner ? `${log.vehicle_brand} ${log.vehicle_model} (${log.plate_number}) - ${log.vehicle_owner}` : 'No vehicle';

    const row = `
      <tr>
        <td>${log.full_name}</td>
        <td>${log.contact_number}</td>
        <td>${log.email}</td>
        <td>${log.address}</td>
        <td>${log.reason}</td>
        <td>${log.visit_location || 'N/A'}</td>
        <td>${log.time_in}</td>
        <td>${log.time_out || 'N/A'}</td>
        <td>${log.status}</td>
        <td>${vehicleDetails}</td>
      </tr>
    `;
    tbody.innerHTML += row;
  });
}

// Print Functionality - Print only the visit logs table
document.getElementById('printBtn').addEventListener('click', () => {
  const printContents = document.getElementById('visitLogsTable').outerHTML;
  const originalContents = document.body.innerHTML;

  document.body.innerHTML = `
    <html>
      <head>
        <title>Print Visit Logs</title>
        <style>
          table {
            width: 100%;
            border-collapse: collapse;
          }
          th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
          }
          th {
            background-color: #f2f2f2;
          }
        </style>
      </head>
      <body>
        <h2>Visit Logs</h2>
        ${printContents}
      </body>
    </html>
  `;

  window.print();
  document.body.innerHTML = originalContents;
  location.reload();
});

// Export to PDF as text arranged in landscape layout (no actual table)
document.getElementById('exportPdfBtn').addEventListener('click', () => {
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({ orientation: 'landscape' });

  const title = 'Visit Logs';
  doc.setFontSize(16);
  doc.text(title, 14, 20);

  const lineHeight = 10;
  let y = 30;

  const tbody = document.getElementById('visitLogsTbody');
  const trs = tbody.querySelectorAll('tr');

  const headers = ["Name", "Contact", "Email", "Address", "Reason", "Visit Location", "Time In", "Time Out", "Status", "Vehicle Details"];
  const colWidths = [30, 25, 40, 40, 30, 30, 20, 20, 20, 40];
  const startX = 10;

  // Draw headers
  let x = startX;
  doc.setFontSize(10);
  doc.setFont(undefined, 'bold');
  headers.forEach((header, i) => {
    doc.text(header, x, y);
    x += colWidths[i];
  });

  y += lineHeight;
  doc.setFont(undefined, 'normal');

  trs.forEach(tr => {
    if (tr.style.display === 'none') return; // skip hidden rows
    const tds = tr.querySelectorAll('td');
    x = startX;
    tds.forEach((td, i) => {
      let text = td.textContent.trim();
      // Truncate text if too long for column width
      const maxChars = Math.floor(colWidths[i] / 2);
      if (text.length > maxChars) {
        text = text.substring(0, maxChars - 3) + '...';
      }
      doc.text(text, x, y);
      x += colWidths[i];
    });
    y += lineHeight;
    if (y > 180) { // Add new page if needed
      doc.addPage();
      y = 20;
    }
  });

  doc.save('visit_logs.pdf');
});

// Export to Excel
document.getElementById('exportExcelBtn').addEventListener('click', () => {
  const table = document.getElementById('visitLogsTable');
  const wb = XLSX.utils.table_to_book(table);
  XLSX.writeFile(wb, 'visit_logs.xlsx');
});

// Search Functionality
document.getElementById('searchInput').addEventListener('input', (e) => {
  const searchTerm = e.target.value.toLowerCase();
  const rows = document.querySelectorAll('#visitLogsTbody tr');

  rows.forEach(row => {
    const nameCell = row.cells[0];
    if (nameCell) {
      const name = nameCell.textContent.toLowerCase();
      row.style.display = name.includes(searchTerm) ? '' : 'none';
    }
  });
});


// Load today's logs on page load
document.addEventListener('DOMContentLoaded', () => {
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('logDate').value = today;
  fetchVisitLogs(today);
});
</script>
</body>
</html>
