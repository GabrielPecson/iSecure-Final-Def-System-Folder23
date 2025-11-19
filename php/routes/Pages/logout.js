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

      if (!modal || !msgEl || !yes || !no) {
        console.error("Logout modal elements not found!");
        return;
      }

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
            }, 1000); // 1 second delay before redirect
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
});
