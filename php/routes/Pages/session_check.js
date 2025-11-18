// Server-sent event listener for session termination
const evtSource = new EventSource("session_watch.php");

evtSource.addEventListener("logout", function(e) {
  showNotification("⚠️ Your session was terminated from the server. Please login again.", "warning", 0);
  window.location.href = "login-page.php";
});

// Client-side idle timeout
(function() {
  let idleTimer;
  const idleTimeoutDuration = 30 * 60 * 1000; // 30 minutes in milliseconds

  function resetIdleTimer() {
    clearTimeout(idleTimer);
    idleTimer = setTimeout(logoutUser, idleTimeoutDuration);
  }

  function logoutUser() {
    showNotification("⚠️ Your session has timed out due to inactivity. Please login again.", "warning", 0);
    // Call the logout script on the server to properly destroy the session
    fetch('logout.php', { method: 'POST' })
      .then(() => {
        window.location.href = "login-page.php";
      })
      .catch(error => {
        console.error('Failed to logout:', error);
        // Fallback redirect even if server logout fails
        window.location.href = "login-page.php";
      });
  }

  // Events that indicate user activity
  const activityEvents = ['mousemove', 'mousedown', 'keypress', 'scroll', 'touchstart'];

  activityEvents.forEach(function(eventName) {
    window.addEventListener(eventName, resetIdleTimer, true);
  });

  // Initialize the timer when the script loads
  resetIdleTimer();
})();