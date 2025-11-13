<?php
require 'auth_check.php';
require '../database/db_connect.php';

// Function to send reminder email
function sendReminderEmail($contactNumber, $idPass, $validityEnd) {
    $to = $contactNumber; // Assuming contactNumber is email
    $subject = "Reminder: Your Visitor ID/Pass Expires Soon";
    $message = "Dear Visitor,\n\nYour ID/Pass number: $idPass\n";
    $message .= "Your access expires at: $validityEnd\n\n";
    $message .= "If you need to extend your visit, please contact the base administration.\n";
    $message .= "Thank you.\n";
    $headers = "From: no-reply@isecure.com\r\n" .
               "Reply-To: no-reply@isecure.com\r\n" .
               "X-Mailer: PHP/" . phpversion();

    return mail($to, $subject, $message, $headers);
}

// Function to send clearance badge expiry reminder email
function sendClearanceReminderEmail($email, $badgeNumber, $validityEnd) {
    $to = $email;
    $subject = "Reminder: Your Clearance Badge Expires Soon";
    $message = "Dear Visitor,\n\nYour clearance badge number: $badgeNumber\n";
    $message .= "Your clearance access expires at: $validityEnd\n\n";
    $message .= "Please renew your clearance badge before expiry.\n";
    $message .= "Thank you.\n";
    $headers = "From: no-reply@isecure.com\r\n" .
               "Reply-To: no-reply@isecure.com\r\n" .
               "X-Mailer: PHP/" . phpversion();

    return mail($to, $subject, $message, $headers);
}

try {
    // Find visitors whose validity_end is within 1 hour from now
    $stmt = $pdo->prepare("
        SELECT * FROM visitors
        WHERE validity_end BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
        AND status = 'Inside'
        AND id_pass_number IS NOT NULL
    ");
    $stmt->execute();
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($visitors as $visitor) {
        $contactNumber = $visitor['contact_number'];
        if ($contactNumber) {
            $sent = sendReminderEmail($contactNumber, $visitor['id_pass_number'], $visitor['validity_end']);
            if ($sent) {
                // Log successful send (optional)
                error_log("Reminder sent to visitor ID: " . $visitor['id'] . " at " . $contactNumber);
            } else {
                error_log("Failed to send reminder to visitor ID: " . $visitor['id']);
            }
        }
    }

    // Find clearance badges expiring within 1 hour
    $stmt = $pdo->prepare("
        SELECT cb.id, cb.badge_number, cb.validity_end, v.email
        FROM clearance_badges cb
        JOIN visitors v ON cb.visitor_id = v.id
        WHERE cb.validity_end BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 HOUR)
        AND cb.status = 'active'
    ");
    $stmt->execute();
    $badges = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($badges as $badge) {
        $email = $badge['email'];
        if ($email) {
            $sent = sendClearanceReminderEmail($email, $badge['badge_number'], $badge['validity_end']);
            if ($sent) {
                error_log("Clearance reminder sent to visitor with badge ID: " . $badge['id'] . " at " . $email);
            } else {
                error_log("Failed to send clearance reminder to badge ID: " . $badge['id']);
            }
        }
    }

    echo "Reminder check completed.\n";
} catch (Exception $e) {
    error_log("Error in send_reminders.php: " . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
}