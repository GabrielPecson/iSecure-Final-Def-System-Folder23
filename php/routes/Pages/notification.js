/**
 * Displays a toast notification in the corner of the screen.
 * @param {string} message - The message to display.
 * @param {string} type - The type of notification (e.g., 'success', 'error', 'info', 'warning').
 * @param {number} duration - How long the notification should be visible in milliseconds. Default is 5000.
 */
function showNotification(message, type = 'info', duration = 5000) {
  const notificationContainer = document.getElementById('notification-container');
  if (!notificationContainer) {
    console.error('Notification container not found. Please add <div id="notification-container"></div> to your HTML.');
    return;
  }

  const notification = document.createElement('div');
  notification.classList.add('notification', `notification-${type}`);
  notification.innerHTML = `
    <div class="notification-content">${message}</div>
    <button class="notification-close">&times;</button>
  `;

  notificationContainer.appendChild(notification);

  // Force reflow to enable CSS transition
  void notification.offsetWidth;
  notification.classList.add('show');

  const closeButton = notification.querySelector('.notification-close');
  closeButton.addEventListener('click', () => {
    hideNotification(notification);
  });

  if (duration > 0) {
    setTimeout(() => hideNotification(notification), duration);
  }
}

function hideNotification(notification) {
  notification.classList.remove('show');
  notification.addEventListener('transitionend', () => {
    notification.remove();
  }, { once: true });
}

// Add a container for notifications to the body if it doesn't exist
document.addEventListener('DOMContentLoaded', () => {
  if (!document.getElementById('notification-container')) {
    const container = document.createElement('div');
    container.id = 'notification-container';
    document.body.appendChild(container);
  }
});
