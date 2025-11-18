



document.addEventListener("DOMContentLoaded", () => {
  /* ---- Logout Modal ---- */
  const logoutLink = document.getElementById("logout-link");
  if (logoutLink) {
    logoutLink.addEventListener("click", (ev) => {
      ev.preventDefault();
      const modal = document.getElementById("confirmModal");
      const msgEl = document.getElementById("confirmMessage");
      const yes = document.getElementById("confirmYes");
      const no = document.getElementById("confirmNo");

      msgEl.textContent = "Are you sure you want to log out?";
      modal.classList.add("show");

      yes.onclick = () => {
        // Show loader
        const loaderOverlay = document.createElement('div');
        loaderOverlay.id = 'logout-loader-overlay';
        loaderOverlay.style.cssText = `
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.5);
          display: flex;
          flex-direction: column;
          justify-content: center;
          align-items: center;
          z-index: 9999;
        `;
        loaderOverlay.innerHTML = `
          <div style="
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #006682;
            border-radius: 50%;
            animation: spin 1s linear infinite;
          "></div>
          <div style="
            margin-top: 20px;
            color: white;
            font-size: 18px;
            font-weight: bold;
          ">Logging out...</div>
        `;
        document.body.appendChild(loaderOverlay);

        // Add spin animation CSS
        const style = document.createElement('style');
        style.textContent = `
          @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
          }
        `;
        document.head.appendChild(style);

        // Send AJAX request
        fetch(logoutLink.href, {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Update loader text
            loaderOverlay.querySelector('div:last-child').textContent = 'Logging out...';
            setTimeout(() => {
              window.location.href = data.redirect;
            }, 8000); // 8 second delay before redirect
          }
        })
        .catch(error => {
          console.error('Logout error:', error);
          // Fallback to normal redirect
          window.location.href = logoutLink.href;
        });
      };
      no.onclick = () => { modal.classList.remove("show"); };
    });
  }

  /* ---- Helper ---- */
  function escapeHtml(s) {
    if (!s) return "";
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  /* ---- Visitor Status Widget ---- */
  const visitorStatusTbody = document.querySelector("#visitor-status-widget tbody");

async function loadVisitorStatus() {
  try {
    const res = await fetch("fetch_visitors.php");
    let data = await res.json();

    visitorStatusTbody.innerHTML = "";

    if (!Array.isArray(data) || data.length === 0) {
      visitorStatusTbody.innerHTML = `<tr><td colspan="5" class="text-center">No visitors found</td></tr>`;
      return;
    }

    // Sort by time_in (descending), fallback to date
    data.sort((a, b) => {
      const timeA = a.time_in ? new Date(a.time_in) : new Date(a.date);
      const timeB = b.time_in ? new Date(b.time_in) : new Date(b.date);
      return timeB - timeA;
    });

    // Limit to 4 latest visitors
    data = data.slice(0, 4);

    data.forEach(v => {
      const timeIn = v.time_in ? `<span style="color:green">${v.time_in}</span>` : "Not yet entered";
      const timeOut = v.time_out ? `<span style="color:red">${v.time_out}</span>` : "Still Inside";

      // Action buttons: View always, Mark Exit only if Inside
      const actionButtons = v.status === "Inside"
        ? `<button class="btn btn-info btn-sm view-btn" data-id="${v.id}">View</button>
           <button class="btn btn-danger btn-sm exit-btn" data-id="${v.id}">Mark Exit</button>`
        : `<button class="btn btn-info btn-sm view-btn" data-id="${v.id}">View</button>`;

      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${escapeHtml(v.full_name)}</td>
        <td>${timeIn}</td>
        <td>${timeOut}</td>
        <td>${escapeHtml(v.date)}</td>
        <td>${actionButtons}</td>
      `;
      visitorStatusTbody.appendChild(tr);
    });
  } catch (err) {
    console.error(err);
    visitorStatusTbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Failed to load visitors</td></tr>`;
  }
}

/* ---- Latest Vehicle Entry Widget ---- */
async function loadLatestVehicles() {
  try {
    const res = await fetch("fetch_latest_vehicles.php");
    const vehicles = await res.json();

    const list = document.getElementById("latestVehicleList");
    list.innerHTML = "";

    if (!Array.isArray(vehicles) || vehicles.length === 0) {
      list.innerHTML = `<li>No vehicles currently inside</li>`;
      return;
    }

    vehicles.forEach(v => {
      const li = document.createElement("li");
      li.innerHTML = `
        <i class="fa-solid fa-car-side" style="margin-right: 8px;"></i>
        <span style="font-weight:500;">${escapeHtml(v.vehicle_brand || "")} ${escapeHtml(v.vehicle_model || "")}</span> -
        <span style="font-weight:500;">${escapeHtml(v.owner_name || "Unknown")}</span> -
        <span style="font-weight:300; color:#36C3EF;">Time: ${escapeHtml(v.entry_time || "N/A")}</span>
      `;
      list.appendChild(li);
    });
  } catch (err) {
    console.error("Failed to load latest vehicles:", err);
    document.getElementById("latestVehicleList").innerHTML =
      `<li class="text-danger">Failed to load vehicles</li>`;
  }
}







// Initial load + refresh every 30s
loadLatestVehicles();
setInterval(loadLatestVehicles, 30000);


  /* ---- Load User Activity ---- */
async function loadUserActivity() {
  try {
    const res = await fetch("fetch_user_activity.php");
    const logs = await res.json();

    const list = document.getElementById("userActivityList");
    list.innerHTML = "";

    if (!Array.isArray(logs) || logs.length === 0) {
      list.innerHTML = `<li>No recent activity</li>`;
      return;
    }

    logs.forEach(log => {
      const li = document.createElement("li");
      li.innerHTML = `<strong>${log.full_name || 'Unknown'}</strong> - ${log.action} <br>
                      <small>${new Date(log.created_at).toLocaleString()}</small>`;
      list.appendChild(li);
    });
  } catch (err) {
    console.error("Failed to load user activity:", err);
    document.getElementById("userActivityList").innerHTML =
      `<li class="text-danger">Failed to load activity</li>`;
  }
}


// Load on page load and refresh every 30s
loadUserActivity();
setInterval(loadUserActivity, 30000);


// Load on page load
loadUserActivity();

// Optionally refresh every 30s
setInterval(loadUserActivity, 30000);



  /* ---- Visitor Action Buttons ---- */
  visitorStatusTbody.addEventListener("click", async (e) => {
    const btn = e.target.closest("button");
    if (!btn) return;
    const visitorId = btn.dataset.id;

    // View
    if (btn.classList.contains("view-btn")) {
      try {
        const res = await fetch(`fetch_visitor_details.php?id=${encodeURIComponent(visitorId)}`);
        const visitor = await res.json();
        if (!visitor.success) return showNotification("Visitor data not found", "error");
        document.getElementById("visitorContact").textContent = visitor.data.contact_number;
        document.getElementById("visitorEmail").textContent = visitor.data.email;
        document.getElementById("visitorAddress").textContent = visitor.data.home_address;
        document.getElementById("visitorReason").textContent = visitor.data.reason;
        document.getElementById("visitorIDPhoto").src = visitor.data.valid_id_path;
        document.getElementById("visitorSelfie").src = visitor.data.selfie_photo_path;
        document.getElementById("visitorTimeIn").textContent = visitor.data.time_in;
        document.getElementById("visitorTimeOut").textContent = visitor.data.time_out || "Still Inside";

        new bootstrap.Modal(document.getElementById("visitorDetailsModal")).show();
      } catch (err) { console.error(err); showNotification("Failed to fetch visitor details.", "error"); }
    }

    // Edit
    else if (btn.classList.contains("edit-btn")) {
      try {
        const res = await fetch(`fetch_visitor_details.php?id=${encodeURIComponent(visitorId)}`);
        const visitor = await res.json();
        if (!visitor.success) return showNotification("Visitor data not found", "error");

        document.getElementById("editVisitorId").value = visitor.data.id;
        document.getElementById("editTimeOut").value = visitor.data.time_out || "";
        new bootstrap.Modal(document.getElementById("editTimeModal")).show();
      } catch (err) { console.error(err); showNotification("Failed to load visitor data for editing.", "error"); }
    }

    // Exit
    else if (btn.classList.contains("exit-btn")) {
      const confirmModal = document.getElementById("confirmModal");
      const confirmMessage = document.getElementById("confirmMessage");
      const confirmYes = document.getElementById("confirmYes");
      const confirmNo = document.getElementById("confirmNo");

      confirmMessage.textContent = "Mark this visitor as exited?";
      confirmModal.classList.add("show");

      confirmYes.onclick = async () => {
        confirmModal.classList.remove("show");
      };

      confirmNo.onclick = () => {
        confirmModal.classList.remove("show");
      };

      try {
        const formData = new URLSearchParams();
        formData.append("visitor_id", visitorId);

        const res = await fetch("mark_exit_visitor.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: formData
        });

        const result = await res.json();
        if (result.success) {
          showNotification("Visitor exit marked and synced with vehicles!", "success");
          loadVisitorStatus();
        } else showNotification("Error: " + result.message, "error");
      } catch (err) { console.error(err); showNotification("Request failed.", "error"); }
    }
  });

  /* ---- Initial Load ---- */
  loadVisitorStatus();
  setInterval(loadVisitorStatus, 30000);

async function loadDailyVisitsChart() {
  try {
    const res = await fetch("fetch_visitors.php");
    const data = await res.json();

    // Count visits per date
    const counts = {};
    data.forEach(v => {
      counts[v.date] = (counts[v.date] || 0) + 1;
    });

    const sortedDates = Object.keys(counts).sort();
    const visits = sortedDates.map(d => counts[d]);

    // Convert dates → weekday names for label
    const labels = sortedDates.map(d => {
      const dateObj = new Date(d);
      return dateObj.toLocaleDateString("en-US", { month: 'short', day: 'numeric' }); // e.g., Sep 25
    });

    const ctx = document.getElementById("visitsChart").getContext("2d");

    // Gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, "rgba(0, 191, 255, 0.9)");
    gradient.addColorStop(1, "rgba(30, 144, 255, 0.9)");

    if (window.visitsChartInstance) window.visitsChartInstance.destroy();

    window.visitsChartInstance = new Chart(ctx, {
      type: "bar",
      data: {
        labels: labels,
        datasets: [{
          label: "Visitors",
          data: visits,
          backgroundColor: gradient,
          borderRadius: 8,
          barThickness: 40
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { backgroundColor: "rgba(0,0,0,0.8)", titleColor: "#fff", bodyColor: "#fff" }
        },
        scales: {
          x: { title: { display: true, text: "Date", font: { weight: "bold" } }, ticks: { color: "#333" }, grid: { display: false } },
          y: { title: { display: true, text: "Visits", font: { weight: "bold" } }, ticks: { color: "#333" }, grid: { color: "rgba(0,0,0,0.05)" }, beginAtZero: true }
        }
      }
    });
  } catch (err) {
    console.error("Failed to load daily visits chart:", err);
  }
}

// Initial load + refresh every hour
loadDailyVisitsChart();
setInterval(loadDailyVisitsChart, 3600 * 1000); // 3600s = 1 hour

  loadDailyVisitsChart();
  setInterval(loadDailyVisitsChart, 60000);

  
async function loadStats() {
  try {
    const res = await fetch("fetch_stats.php");
    const stats = await res.json();

    document.getElementById("visitorsCount").textContent = stats.visitors;
    document.getElementById("vehiclesCount").textContent = stats.vehicles;
    document.getElementById("pendingCount").textContent = stats.pendings;
    document.getElementById("entryCount").textContent = stats.entries;
  } catch (err) {
    console.error("Failed to load stats:", err);
  }
}

// Load once when page loads
loadStats();

// Optional: refresh every 30s
setInterval(loadStats, 30000);



});