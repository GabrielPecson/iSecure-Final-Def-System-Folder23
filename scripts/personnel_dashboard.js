document.addEventListener('DOMContentLoaded', function() {
    // Fetch and display notifications
    fetchNotifications();

    // Notification bell click
    const bell = document.getElementById('notification-bell');
    const menu = document.getElementById('notification-menu');

    bell.addEventListener('click', function() {
        menu.classList.toggle('show');
        // Mark as read when opened
        markNotificationsAsRead();
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!bell.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.remove('show');
        }
    });
});

function fetchNotifications() {
    fetch('fetch_notification.php')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }
            displayNotifications(data);
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

function displayNotifications(notifications) {
    const list = document.getElementById('notification-list');
    const fullList = document.getElementById('full-notification-list');

    if (notifications.length === 0) {
        if (list) list.innerHTML = '<div class="notification-item">No notifications</div>';
        if (fullList) fullList.innerHTML = '<p>No notifications</p>';
        return;
    }

    let listHtml = '';
    let fullHtml = '';

    notifications.forEach(notification => {
        const date = new Date(notification.created_at).toLocaleString();
        const unreadClass = notification.read_status === 'Unread' ? 'unread' : '';

        listHtml += `
            <div class="notification-item ${unreadClass}">
                <p>${notification.message}</p>
                <small>${date}</small>
            </div>
        `;

        fullHtml += `
            <div class="notification-item-full ${unreadClass}">
                <p>${notification.message}</p>
                <small>${date}</small>
            </div>
        `;
    });

    if (list) list.innerHTML = listHtml;
    if (fullList) fullList.innerHTML = fullHtml;
}

function markNotificationsAsRead() {
    // This could be implemented to mark notifications as read via AJAX
    // For now, just refetch to update display
    fetchNotifications();
}
