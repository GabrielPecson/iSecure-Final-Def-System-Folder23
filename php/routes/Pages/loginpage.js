document.addEventListener("DOMContentLoaded", function () {
    // Show login error modal automatically if exists
    var loginErrorModal = document.getElementById("loginErrorModal");
    if (loginErrorModal) {
      var modal = new bootstrap.Modal(loginErrorModal);
      modal.show();
    }
});

function logAction(action) {
  fetch("audit_log.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "action=" + encodeURIComponent(action)
  }).catch(err => console.error("Audit log failed:", err));
}

document.addEventListener("DOMContentLoaded", function () {
  // log every button and link click
  document.querySelectorAll("button, a").forEach(el => {
    el.addEventListener("click", function () {
      let label =
        this.innerText.trim() ||
        this.getAttribute("aria-label") ||
        this.getAttribute("title") ||
        this.id ||
        "Unnamed element";

      logAction("Clicked: " + label);
    });
  });

  // log modal events
  document.querySelectorAll(".modal").forEach(modal => {
    modal.addEventListener("show.bs.modal", function () {
      logAction("Opened modal: " + (this.id || "Unnamed modal"));
    });
    modal.addEventListener("hide.bs.modal", function () {
      logAction("Closed modal: " + (this.id || "Unnamed modal"));
    });
  });


});