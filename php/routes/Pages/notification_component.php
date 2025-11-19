<?php
// This component expects $_SESSION['notification_message'] and $_SESSION['notification_type']
// to be set for a notification to display.

if (isset($_SESSION['notification_message']) && isset($_SESSION['notification_type'])):
    $message = htmlspecialchars($_SESSION['notification_message']);
    $type = htmlspecialchars($_SESSION['notification_type']); // e.g., 'success', 'error', 'warning', 'info'
?>
<div class="notification-container">
    <div id="session-notification-alert" class="notification notification-<?= $type ?>">
        <div class="notification-content">
            <?= $message ?>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const alert = document.getElementById('session-notification-alert');
        if (alert) {
            // Add 'show' class to trigger the animation
            setTimeout(() => alert.classList.add('show'), 100);
            // Automatically remove after 5 seconds
            setTimeout(() => alert.remove(), 5000);
        }
    });
</script>
<?php
    // Clear the message so it doesn't show again on refresh
    unset($_SESSION['notification_message']);
    unset($_SESSION['notification_type']);
endif;
?>