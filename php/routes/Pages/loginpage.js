document.addEventListener("DOMContentLoaded", function () {

    // === Show login error modal automatically if exists ===
    var loginErrorModal = document.getElementById("loginErrorModal");
    if (loginErrorModal && typeof bootstrap !== "undefined") {
        var modal = new bootstrap.Modal(loginErrorModal);
        modal.show();
    }

    // === Function to send actions to audit_log.php ===
    function logAction(action) {
        fetch("audit_log.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "action=" + encodeURIComponent(action)
        }).catch(err =>
            console.error("Audit log failed:", err)
        );
    }

    // === Log every button and link click ===
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

    // === Log modal open/close events ===
    document.querySelectorAll(".modal").forEach(modal => {
        modal.addEventListener("show.bs.modal", function () {
            logAction("Opened modal: " + (this.id || "Unnamed modal"));
        });
        modal.addEventListener("hide.bs.modal", function () {
            logAction("Closed modal: " + (this.id || "Unnamed modal"));
        });
    });

});
