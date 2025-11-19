document.addEventListener("DOMContentLoaded", () => {

  // ----- Buttons & Elements -----
  const nextToVerifyBtn = document.getElementById("nextToVerify");
  const nextToFacialBtn = document.getElementById("nextToFacial");
  const nextToVehicleBtn = document.getElementById("nextToVehicle");
  const nextToIdBtn = document.getElementById("nextToId");
  const skipVehicleBtn = document.getElementById("skipVehicle");
  const markEntryBtn = document.getElementById("markEntryBtn");
  const logoutLink = document.getElementById("logout-link");
  const idTabImage = document.getElementById("idTabImage");
  const ocrContent = document.getElementById("ocrContent");
  const expectedVisitorsTbody = document.querySelector("#expectedVisitorsTable tbody");
  const insideVisitorsTbody = document.querySelector("#insideVisitorsTable tbody");
  const exitedVisitorsTbody = document.querySelector("#exitedVisitorsTable tbody");

  // Facial authentication elements
  const authVideoFeed = document.getElementById("auth-video-feed");
  const authenticateBtn = document.getElementById("authenticate-btn");
  const authResultDiv = document.getElementById("auth-result");

  // Vehicle recognition elements
  const vehicleVideoFeed = document.getElementById("vehicle-video-feed");
  const vehicleAuthResultDiv = document.getElementById("vehicle-auth-result");
  const expectedPlateNumberDisplay = document.getElementById("expectedPlateNumberDisplay");
  const recognizedPlateDisplay = document.getElementById("recognizedPlateDisplay");
  const verificationStatus = document.getElementById("verificationStatus");
  const scanPlateBtn = document.getElementById("scanPlateBtn");

  let currentVisitorId = null;
  let currentStream = null; // To store the webcam stream

  // ----- Helper Functions -----
  function escapeHtml(s) {
    if (!s) return "";
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function showTab(tabId) {
    const tabTrigger = document.querySelector(`#visitorTab button[data-bs-target="#${tabId}"]`);
    if (tabTrigger) {
      const tab = new bootstrap.Tab(tabTrigger);
      tab.show();
    }
  }

  // Function to stop any active camera stream
  function stopCamera() {
    if (currentStream) {
      currentStream.getTracks().forEach(track => track.stop());
      currentStream = null;
    }
    if (authVideoFeed) authVideoFeed.style.display = 'none';
    if (vehicleVideoFeed) vehicleVideoFeed.style.display = 'none';
  }

  // ----- Camera Control Functions -----

  // --- Facial Camera ---
  async function startFacialWebcam() {
    stopCamera(); // Stop any other streams
    try {
      currentStream = await navigator.mediaDevices.getUserMedia({ video: true });
      if (authVideoFeed) {
        authVideoFeed.srcObject = currentStream;
        authVideoFeed.style.display = 'block';
      }
    } catch (err) {
      console.error("Error accessing webcam for facial recognition: ", err);
      if (authResultDiv) authResultDiv.innerHTML = `<div class="alert alert-danger">Error: Could not access webcam.</div>`;
    }
  }

  // --- Vehicle Camera ---
  async function startVehicleWebcam() {
    stopCamera();
    try {
      currentStream = await navigator.mediaDevices.getUserMedia({ video: true });
      if (vehicleVideoFeed) {
        vehicleVideoFeed.srcObject = currentStream;
        vehicleVideoFeed.style.display = 'block';
      }
    } catch (err) {
      console.error("Error accessing webcam for vehicle recognition: ", err);
      if (vehicleAuthResultDiv) vehicleAuthResultDiv.innerHTML = `<div class="alert alert-danger">Error: Could not access webcam.</div>`;
    }
  }

  async function fetchVisitorDetails(visitorId) {
    try {
      const res = await fetch(`fetch_visitor_details.php?id=${encodeURIComponent(visitorId)}`);
      const visitor = await res.json();
      if (!visitor.success) {
        showNotification(visitor.message || "Visitor data not found", "error");
        return null;
      }
      return visitor.data;
    } catch (err) {
      console.error(err);
      showNotification("Failed to fetch visitor details.", "error");
      return null;
    }
  }

  function showVisitorDetails(visitor) {
    const fullName = [visitor.first_name, visitor.middle_name, visitor.last_name].filter(Boolean).join(' ');
    document.getElementById("visitorNameCell").textContent = escapeHtml(fullName);
    document.getElementById("visitorAddressCell").textContent = escapeHtml(visitor.address);
    document.getElementById("visitorContactCell").textContent = escapeHtml(visitor.contact_number);
    document.getElementById("visitorEmailCell").textContent = escapeHtml(visitor.email);
    document.getElementById("visitorDateCell").textContent = escapeHtml(visitor.date || '');
    document.getElementById("visitorTimeCell").textContent = escapeHtml(visitor.time_in || '');
    document.getElementById("visitorPersonnelCell").textContent = escapeHtml(visitor.personnel_related || '');
    document.getElementById("visitorOfficeCell").textContent = escapeHtml(visitor.office_to_visit || '');
    document.getElementById("vehicleOwnerCell").textContent = escapeHtml(visitor.vehicle_owner || fullName);
    document.getElementById("vehicleBrandCell").textContent = escapeHtml(visitor.vehicle_brand || 'N/A');
    document.getElementById("vehicleModelCell").textContent = escapeHtml(visitor.vehicle_model || 'N/A');
    document.getElementById("vehicleColorCell").textContent = escapeHtml(visitor.vehicle_color || 'N/A');
    document.getElementById("plateNumberCell").textContent = escapeHtml(visitor.plate_number || '');
    document.getElementById("visitorIDPhoto").src = `fetch_request_image.php?request_id=${visitor.request_id}&type=id`;
    document.getElementById("visitorSelfie").src = `fetch_request_image.php?request_id=${visitor.request_id}&type=selfie`;
    document.getElementById("expectedPlateNumberDisplay").textContent = visitor.plate_number || '';
    idTabImage.src = `fetch_request_image.php?request_id=${visitor.request_id}&type=id`;
    currentVisitorId = visitor.id;

    const hasVehicle = visitor.plate_number && visitor.plate_number.trim() !== "";
    const vehicleColumns = document.querySelectorAll(".visitor-vehicle-column");
    const vehicleHeaders = ["visitorVehicleOwnerHeader", "visitorVehicleBrandHeader", "visitorVehicleModelHeader", "visitorVehicleColorHeader", "visitorPlateNumberHeader"];
    vehicleColumns.forEach(col => col.style.display = hasVehicle ? "table-cell" : "none");
    vehicleHeaders.forEach(headerId => {
      const header = document.getElementById(headerId);
      if (header) header.style.display = hasVehicle ? "table-cell" : "none";
    });

    const isReadOnly = visitor.status.toLowerCase() === "inside" || visitor.status.toLowerCase() === "exited";
    const interactiveElements = [
        document.querySelector('#visitorTab button[data-bs-target="#verify"]'),
        document.querySelector('#visitorTab button[data-bs-target="#facial"]'),
        document.querySelector('#visitorTab button[data-bs-target="#vehicle"]'),
        document.querySelector('#visitorTab button[data-bs-target="#id"]'),
        nextToVerifyBtn, nextToFacialBtn, nextToVehicleBtn
    ];
    interactiveElements.forEach(el => {
        if (el) el.style.display = isReadOnly ? 'none' : 'block';
    });

    const detailsTabContent = document.getElementById('details');
    if (detailsTabContent) detailsTabContent.style.display = isReadOnly ? 'none' : 'block';
    
    const visitorTabContent = document.getElementById('visitorTabContent');
    if(visitorTabContent) visitorTabContent.style.display = isReadOnly ? 'none' : 'block';


    const detailsTabTriggerEl = document.querySelector('#details-tab');
    if (detailsTabTriggerEl) {
      const tab = bootstrap.Tab.getInstance(detailsTabTriggerEl) || new bootstrap.Tab(detailsTabTriggerEl);
      tab.show();
    }

    document.querySelector('#visitorTab button[data-bs-target="#vehicle"]').disabled = true;
    document.querySelector('#visitorTab button[data-bs-target="#id"]').disabled = true;
    if (nextToVehicleBtn) nextToVehicleBtn.disabled = true;

    new bootstrap.Modal(document.getElementById("visitorDetailsModal")).show();
    const visitorDetailsSection = document.getElementById('visitorDetailsSection');
    if (visitorDetailsSection) visitorDetailsSection.style.display = 'block';
  }

  async function markEntry(visitorId) {
    try {
      const res = await fetch("mark_entry_visitor.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `visitor_id=${encodeURIComponent(visitorId)}`
      });
      const data = await res.json();
      if (data.success) {
        showNotification("Visitor marked as inside.", "success");
        // Close the modal
        const modal = bootstrap.Modal.getInstance(document.getElementById("visitorDetailsModal"));
        if (modal) modal.hide();
        // Refresh the page
        location.reload();
      } else showNotification(data.message || "Failed to mark entry.", "error");
    } catch (err) {
      console.error(err);
      showNotification("Error while marking entry.", "error");
    }
  }

  async function markExit(visitorId) {
    try {
      const res = await fetch("mark_exit_visitor.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `visitor_id=${encodeURIComponent(visitorId)}`
      });
      const data = await res.json();
      if (data.success) {
        showNotification("Visitor marked as exited.", "success");
        location.reload();
      } else showNotification(data.message || "Failed to mark exit.", "error");
    } catch (err) {
      console.error(err);
      showNotification("Error while marking exit.", "error");
    }
  }

  async function loadTable(url, tbody, columns) {
    if (!tbody) {
      console.error(`Error: tbody element not found for URL: ${url}`);
      return;
    }
    try {
      const res = await fetch(url);
      const data = await res.json();
      tbody.innerHTML = "";
      if (!Array.isArray(data) || data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${columns}" class="text-center">No records found</td></tr>`;
        return;
      }
      data.forEach(v => {
        const tr = document.createElement("tr");
        tr.innerHTML = `
          <td>${escapeHtml(v.first_name || "")}</td>
          <td>${escapeHtml(v.middle_name || "")}</td>
          <td>${escapeHtml(v.last_name || "")}</td>
          <td>${escapeHtml(v.contact_number || "")}</td>
          ${v.date !== undefined ? `<td>${escapeHtml(v.date || "")}</td>` : ""}
          ${v.key_card_number !== undefined ? `<td>${escapeHtml(v.key_card_number || "")}</td>` : ""}
          ${v.time_in !== undefined ? `<td>${escapeHtml(v.time_in || "")}</td>` : ""}
          ${v.time_out !== undefined ? `<td>${escapeHtml(v.time_out || "")}</td>` : ""}
          <td>${escapeHtml(v.status)}</td>
          <td>
            <button class="btn btn-info btn-sm view-btn" data-id="${v.id}">View</button>
            ${v.time_out == null && v.time_in != null ? `<button class="btn btn-danger btn-sm exit-btn" data-id="${v.id}">Mark Exit</button>` : ""}
          </td>
        `;
        tbody.appendChild(tr);
      });
    } catch (err) {
      console.error(`Error loading table: ${url}`, err);
      tbody.innerHTML = `<tr><td colspan="${columns}" class="text-center text-danger">Failed to load data</td></tr>`;
    }
  }

  const loadExpectedVisitors = () => loadTable("fetch_expected_visitors.php", expectedVisitorsTbody, 7);
  const loadInsideVisitors = () => loadTable("fetch_inside_visitors.php", insideVisitorsTbody, 9);
  const loadExitedVisitors = () => loadTable("fetch_exited_visitors.php", exitedVisitorsTbody, 9);

  [nextToVerifyBtn, nextToFacialBtn, nextToVehicleBtn, nextToIdBtn, skipVehicleBtn].forEach(btn => {
    if (!btn) return;
    btn.addEventListener("click", () => showTab(btn.dataset.targetTab || btn.id.replace("nextTo", "").toLowerCase()));
  });

  document.getElementById('visitorTab').addEventListener('shown.bs.tab', async function (event) {
    const target = event.target.getAttribute('data-bs-target');
    const visitorDetailsSection = document.getElementById('visitorDetailsSection');
    if (visitorDetailsSection) {
      visitorDetailsSection.style.display = (target === '#details') ? 'block' : 'none';
    }

    stopCamera();
    const delay = ms => new Promise(res => setTimeout(res, ms));
    await delay(150); // A short delay to ensure camera resources are released

    if (target === '#facial') {
      startFacialWebcam();
      // Pre-register face from selfie
      authenticateBtn.disabled = true; // Disable button during preparation
      authenticateBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Preparing...';
      authResultDiv.innerHTML = `<div class="alert alert-info">Preparing for authentication...</div>`;
      if (currentVisitorId) {
        try {
          const regResponse = await fetch('register_face_from_selfie.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ visitor_id: currentVisitorId })
          });
          const regResult = await regResponse.json();
          if (regResponse.ok && regResult.success) {
            authResultDiv.innerHTML = `<div class="alert alert-light">Ready for live authentication.</div>`;
            authenticateBtn.disabled = false; // Re-enable for live authentication
            authenticateBtn.innerHTML = 'Authenticate';
          } else {
            throw new Error(regResult.message || "Failed to pre-register from selfie.");
          }
        } catch (error) {
          console.error("Auto-registration error:", error);
          authResultDiv.innerHTML = `<div class="alert alert-danger">Error preparing: ${escapeHtml(error.message)}</div>`;
          authenticateBtn.disabled = true; // Keep disabled on error
          authenticateBtn.innerHTML = 'Authenticate';
        }
      } else {
         authResultDiv.innerHTML = `<div class="alert alert-warning">Visitor ID not found. Cannot prepare for authentication.</div>`;
         authenticateBtn.disabled = true; // Keep disabled if no visitor ID
         authenticateBtn.innerHTML = 'Authenticate';
      }
    } else if (target === '#vehicle') {
      const expectedPlate = expectedPlateNumberDisplay.textContent.trim();
      const vehicleRecognitionContainer = document.getElementById("vehicleRecognitionContainer");
      document.querySelector('#visitorTab button[data-bs-target="#id"]').disabled = false;

      if (!expectedPlate || expectedPlate === "N/A") {
        if (vehicleRecognitionContainer) vehicleRecognitionContainer.style.display = 'none';
        if (skipVehicleBtn) skipVehicleBtn.style.display = 'inline-block';
        if (nextToIdBtn) nextToIdBtn.style.display = 'none';
        showTab('id');
      } else {
        if (vehicleRecognitionContainer) vehicleRecognitionContainer.style.display = 'block';
        if (skipVehicleBtn) skipVehicleBtn.style.display = 'none';
        if (nextToIdBtn) nextToIdBtn.style.display = 'inline-block';
        startVehicleWebcam();
      }
    } else if (target === '#id' && idTabImage.src) {
      runOCR(idTabImage.src);
    }
  });

  async function captureFacialFrame() {
    let videoElement = authVideoFeed;
    if (videoElement && videoElement.readyState >= 2) {
      const canvas = document.createElement('canvas');
      canvas.width = videoElement.videoWidth;
      canvas.height = videoElement.videoHeight;
      canvas.getContext('2d').drawImage(videoElement, 0, 0, canvas.width, canvas.height);
      return new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg'));
    } else {
      throw new Error("No active facial camera feed to capture from.");
    }
  }

  authenticateBtn?.addEventListener('click', async () => {
    authenticateBtn.disabled = true;
    authenticateBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Authenticating...';
    authResultDiv.innerHTML = `<div class="alert alert-info">Authenticating...</div>`;
    try {
      const frameBlob = await captureFacialFrame();
      const formData = new FormData();
      formData.append('visitor_id', currentVisitorId);
      formData.append('image', frameBlob, 'live_capture.jpg');

      const response = await fetch('authenticate_face_php.php', { method: 'POST', body: formData });
      const result = await response.json();

      if (response.ok && result.success) {
        authResultDiv.innerHTML = `<div class="alert alert-success">${escapeHtml(result.message)}</div>`;
        if (nextToVehicleBtn) nextToVehicleBtn.disabled = false;
        document.querySelector('#visitorTab button[data-bs-target="#vehicle"]').disabled = false;
      } else {
        authResultDiv.innerHTML = `<div class="alert alert-warning">${escapeHtml(result.message || 'Unknown error')}</div>`;
      }
    } catch (error) {
      console.error("Authentication error:", error);
      authResultDiv.innerHTML = `<div class="alert alert-danger">Error: ${escapeHtml(error.message)}</div>`;
    } finally {
      // authenticateBtn.disabled = false; // Keep button disabled after authentication
      authenticateBtn.innerHTML = 'Authenticate';
    }
  });

  async function captureVehicleFrame() {
    let videoElement = vehicleVideoFeed;
    if (videoElement && videoElement.readyState >= 2) {
        const canvas = document.createElement('canvas');
        canvas.width = videoElement.videoWidth;
        canvas.height = videoElement.videoHeight;
        canvas.getContext('2d').drawImage(videoElement, 0, 0, canvas.width, canvas.height);
        return new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg'));
    } else {
        throw new Error("No active vehicle camera feed to capture from.");
    }
  }

  scanPlateBtn?.addEventListener("click", async () => {
    scanPlateBtn.disabled = true;
    scanPlateBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Scanning...';
    verificationStatus.textContent = "Capturing and scanning...";
    verificationStatus.className = "text-info";
    try {
        const frameBlob = await captureVehicleFrame();
        const formData = new FormData();
        formData.append('image', frameBlob, 'capture.jpg');

        const response = await fetch('scan_plate.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (!response.ok || data.error) {
            let errorMsg = data.error || `HTTP error! status: ${response.status}`;
            if (data.details) {
                errorMsg += ` - ${data.details}`;
            }
            throw new Error(errorMsg);
        }

        const recognizedPlate = data.license_plate_number || "Not Found";
        const expectedPlate = expectedPlateNumberDisplay.textContent.trim();
        recognizedPlateDisplay.textContent = recognizedPlate;
        document.getElementById('recognizedVehicleTypeDisplay').textContent = data.vehicle_type || "Not Found";

        if (expectedPlate && recognizedPlate.toLowerCase() === expectedPlate.toLowerCase()) {
            verificationStatus.textContent = "Match!";
            verificationStatus.className = "text-success";
        } else {
            verificationStatus.textContent = `No Match: Found ${escapeHtml(recognizedPlate)}`;
            verificationStatus.className = "text-danger";
        }
    } catch (error) {
        console.error("Error during plate recognition:", error);
        verificationStatus.textContent = `Error: ${escapeHtml(error.message)}`;
        verificationStatus.className = "text-danger";
    } finally {
        scanPlateBtn.disabled = false;
        scanPlateBtn.innerHTML = 'Scan Plate';
    }
  });


  document.addEventListener("click", async e => {
    const id = e.target.dataset.id;
    if (!id) return;

    if (e.target.classList.contains("view-btn")) {
      const visitor = await fetchVisitorDetails(id);
      if (visitor) showVisitorDetails(visitor);
    } else if (e.target.classList.contains("exit-btn")) {
      markExit(id);
    }
  });

  markEntryBtn?.addEventListener("click", () => {
    if (currentVisitorId) markEntry(currentVisitorId);
  });

  logoutLink?.addEventListener("click", (e) => {
    e.preventDefault();
    const confirmModal = document.getElementById("confirmModal");
    const confirmMessage = document.getElementById("confirmMessage");
    const confirmYes = document.getElementById("confirmYes");
    const confirmNo = document.getElementById("confirmNo");

    confirmMessage.textContent = "Are you sure you want to log out?";
    confirmModal.classList.add("show");

    confirmYes.onclick = () => {
      confirmModal.classList.remove("show");
      window.location.href = "logout.php";
    };

    confirmNo.onclick = () => {
      confirmModal.classList.remove("show");
    };
  });

  async function runOCR(imageUrl) {
    ocrContent.innerHTML = '<p class="text-muted">Processing image...</p>';
    try {
      const response = await fetch(imageUrl);
      if (!response.ok) throw new Error(`Failed to fetch image: ${response.statusText}`);
      const blob = await response.blob();
      const formData = new FormData();
      formData.append("image", blob, "id_image.png");
      const ocrResponse = await fetch("process_ocr.php", {
        method: "POST",
        body: formData,
      });
      if (!ocrResponse.ok) throw new Error(`Server error: ${ocrResponse.status} ${ocrResponse.statusText}`);
      const result = await ocrResponse.json();
      if (result.success) {
        const { data: extracted, id_type } = result;
        let html = `<h5>ID Type: ${id_type.replace(/_/g, ' ').toUpperCase()}</h5><ul class='list-group'>`;
        for (const [key, value] of Object.entries(extracted)) {
          if (value) {
            const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            html += `<li class="list-group-item"><strong>${label}:</strong> ${value}</li>`;
          }
        }
        html += "</ul>";
        ocrContent.innerHTML = html;
      } else {
        throw new Error(result.message || "OCR process failed.");
      }
    } catch (error) {
      console.error("Error during OCR request:", error);
      ocrContent.innerHTML = `<p class="text-danger">Error processing image: ${error.message}</p>`;
    }
  }

// ----- Load Data on Page Ready -----
loadExpectedVisitors();
loadInsideVisitors();
loadExitedVisitors();

});
