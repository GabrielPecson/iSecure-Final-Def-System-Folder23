document.addEventListener("DOMContentLoaded", () => {
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

  /* ---- Camera frames are now streaming via MJPEG ---- */

  const faceRecogImg = document.getElementById('face_recog');
  if (faceRecogImg) {
    faceRecogImg.src = 'http://localhost:8000/camera/facial/frame';
  }

  const vehicleDetectImg = document.getElementById('vehicle_detect');
  if (vehicleDetectImg) {
    vehicleDetectImg.src = 'http://localhost:8000/camera/vehicle/frame';
  }

  const ocrIdImg = document.getElementById('ocr_id');
  if (ocrIdImg) {
    // TODO: Clarify which camera feed to use for ocr_id
    // ocrIdImg.src = 'http://localhost:8000/camera/some_other_frame';
  }
});
