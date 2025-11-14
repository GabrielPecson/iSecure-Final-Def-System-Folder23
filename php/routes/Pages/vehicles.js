document.addEventListener("DOMContentLoaded", () => {
  const expectedTbody = document.querySelector("#expectedVehiclesTable tbody");
  const insideTbody = document.querySelector("#insideVehiclesTable tbody");
  const exitedTbody = document.querySelector("#exitedVehiclesTable tbody");
function escapeHtml(s) {
  if (!s) return "";
  return String(s)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}


   /* ---- Logout modal ---- */
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

      yes.onclick = () => { window.location.href = logoutLink.href; };
      no.onclick = () => { modal.classList.remove("show"); };
    });
  }
  
  // ---- Load Expected Vehicles ----
  async function loadExpectedVehicles() {
    try {
      const res = await fetch("fetch_expected_vehicles.php");
      const data = await res.json();
      console.log("Expected vehicles:", data);

      expectedTbody.innerHTML = "";
      if (!Array.isArray(data) || data.length === 0) {
        expectedTbody.innerHTML =
 `<tr><td colspan="6" class="text-center">No expected vehicles</td></tr>`;
        return;
      }

     data.forEach(v => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
    <td>${escapeHtml(v.vehicle_owner)}</td>
    <td>${escapeHtml(v.vehicle_brand)}</td>
    <td>${escapeHtml(v.vehicle_model)}</td>
    <td>${escapeHtml(v.vehicle_color)}</td>
    <td>${escapeHtml(v.plate_number)}</td>
    <td>${escapeHtml(v.status)}</td>
`;
  expectedTbody.appendChild(tr);
});

    } catch (err) {
      console.error("Error loading expected vehicles:", err);
    }
  }
  window.loadExpectedVehicles = loadExpectedVehicles;

  // ---- Load Inside Vehicles ----
  async function loadInsideVehicles() {
    try {
      const res = await fetch("fetch_inside_vehicles.php");
      const data = await res.json();
      console.log("Inside vehicles:", data);

      insideTbody.innerHTML = "";
      if (!Array.isArray(data) || data.length === 0) {
        insideTbody.innerHTML =
          `<tr><td colspan="8" class="text-center">No vehicles inside</td></tr>`;
        return;
      }

    data.forEach(v => {
  const tr = document.createElement("tr");
  tr.innerHTML = `
    <td>${escapeHtml(v.driver_name)}</td>
    <td>${escapeHtml(v.vehicle_brand)}</td>
    <td>${escapeHtml(v.vehicle_model)}</td>
    <td>${escapeHtml(v.vehicle_color)}</td>
    <td>${escapeHtml(v.plate_number)}</td>
    <td>${escapeHtml(v.entry_time || "")}</td>
    <td>${v.exit_time ? escapeHtml(v.exit_time) : "Still Inside"}</td>
    <td>${escapeHtml(v.status || "Inside")}</td>
  `;
  insideTbody.appendChild(tr);
});

   } catch (err) {
      console.error("Error loading inside vehicles:", err);
    }
  }
  window.loadInsideVehicles = loadInsideVehicles;

 

// ---- Load Exited Vehicles ----
async function loadExitedVehicles() {
  try {
    const res = await fetch("fetch_exited_vehicles.php");
    const data = await res.json();
    console.log("Exited vehicles:", data);

    exitedTbody.innerHTML = "";
    if (!Array.isArray(data) || data.length === 0) {
      exitedTbody.innerHTML =
        `<tr><td colspan="8" class="text-center">No exited vehicles</td></tr>`;
      return;
    }

    data.forEach(v => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${escapeHtml(v.driver_name)}</td>
        <td>${escapeHtml(v.vehicle_brand)}</td>
        <td>${escapeHtml(v.vehicle_model)}</td>
        <td>${escapeHtml(v.vehicle_color)}</td>
        <td>${escapeHtml(v.plate_number)}</td>
        <td>${escapeHtml(v.entry_time || "")}</td>
        <td>${escapeHtml(v.exit_time || "")}</td>
        <td>${escapeHtml(v.status || "Exited")}</td>
      `;
      exitedTbody.appendChild(tr);
    });

  } catch (err) {
    console.error("Error loading exited vehicles:", err);
  }
}
window.loadExitedVehicles = loadExitedVehicles;


  // ---- Handle Exit Button ----
  insideTbody.addEventListener("click", e => {
    const btn = e.target.closest(".exit-btn");
    if (btn) {
      const id = btn.dataset.id;
      const confirmModal = document.getElementById("confirmModal");
      const confirmMessage = document.getElementById("confirmMessage");
      const confirmYes = document.getElementById("confirmYes");
      const confirmNo = document.getElementById("confirmNo");

      confirmMessage.textContent = "Mark this vehicle as exited?";
      confirmModal.classList.add("show");

      confirmYes.onclick = async () => {
        confirmModal.classList.remove("show");
        fetch("mark_exit.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "vehicle_id=" + encodeURIComponent(id)
        })
          .then(res => res.json())
          .then(result => {
            if (result.success) {
              showNotification("🚗 Vehicle exit marked!", "success");
              loadInsideVehicles();
            } else {
              showNotification("Error: " + result.message, "error");
            }
          })
          .catch(err => {
            console.error(err);
            showNotification("Request failed.", "error");
          });
      };

      confirmNo.onclick = () => {
        confirmModal.classList.remove("show");
      };
    }
  });

  // ---- Auto refresh ----
  loadExpectedVehicles();
  loadInsideVehicles();
  loadExitedVehicles();
  setInterval(() => {
    loadExpectedVehicles();
    loadInsideVehicles();
    loadExitedVehicles();
  }, 30000);
});