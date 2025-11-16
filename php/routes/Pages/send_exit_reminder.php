<?php
require 'db_connect.php';
require '../../SMS module/sms_module.php';

// Set the default timezone to ensure correct time comparisons
date_default_timezone_set('Asia/Manila'); // Adjust to your local timezone

$current_time = new DateTime();
$reminder_time = new DateTime('18:30:00'); // 6:30 PM
$exit_time = new DateTime('19:00:00'); // 7:00 PM

// Check if current time is exactly 6:30 PM (or within a small window to account for script execution time)
// For a scheduled task, this script would ideally run exactly at 18:30.
// We'll add a small buffer for robustness if it's run manually or slightly off schedule.
$time_diff = $current_time->diff($reminder_time);
$minutes_diff = $time_diff->days * 24 * 60;
$minutes_diff += $time_diff->h * 60;
$minutes_diff += $time_diff->i;

// If the script is run by a scheduler at 18:30, this check ensures it only sends once.
// For manual testing, you might want to adjust this condition.
if ($current_time->format('H:i') === $reminder_time->format('H:i')) {
    try {
        $stmt = $pdo->prepare("SELECT phone_number FROM visitors WHERE status = 'Inside' AND DATE(time_in) = CURDATE()");
        $stmt->execute();
        $visitors_inside = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($visitors_inside as $visitor) {
            $phone_number = $visitor['phone_number'];
            $message = "Reminder: You need to leave Basa Air Base before 7:00 PM.";
            send_sms($phone_number, $message);
            // Log or echo for debugging purposes
            // echo "Reminder SMS sent to: " . $phone_number . "\n";
        }
        echo "Reminder script executed successfully. SMS sent to " . count($visitors_inside) . " visitors.\n";

    } catch (Exception $e) {
        error_log("Error in send_exit_reminder.php: " . $e->getMessage());
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "Script executed outside of reminder time (18:30). Current time: " . $current_time->format('H:i') . "\n";
}

?>