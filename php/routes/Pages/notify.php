<?php

function notify_admin_about_visitor_history($pdo, $visitor_data) {
    // Extract visitor details
    $first_name = $visitor_data['first_name'] ?? '';
    $middle_name = $visitor_data['middle_name'] ?? '';
    $last_name = $visitor_data['last_name'] ?? '';
    $email = $visitor_data['email'] ?? '';
    $contact_number = $visitor_data['contact_number'] ?? '';

    // Build full name
    $full_name = trim(implode(' ', array_filter([$first_name, $middle_name, $last_name])));

    // Check for visitor history in 'visitors' table
    $history_query = "
        SELECT COUNT(*) as history_count
        FROM visitors
        WHERE (email = :email AND email != '')
        OR (first_name = :first_name AND last_name = :last_name)
        OR (contact_number = :contact_number AND contact_number != '')
    ";
    $history_stmt = $pdo->prepare($history_query);
    $history_stmt->execute([
        ':email' => $email,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':contact_number' => $contact_number
    ]);
    $history_result = $history_stmt->fetch(PDO::FETCH_ASSOC);

    // Also check in 'visitation_requests' table
    $history_query2 = "
        SELECT COUNT(*) as history_count
        FROM visitation_requests
        WHERE (email = :email AND email != '')
        OR (first_name = :first_name AND last_name = :last_name)
        OR (contact_number = :contact_number AND contact_number != '')
    ";
    $history_stmt2 = $pdo->prepare($history_query2);
    $history_stmt2->execute([
        ':email' => $email,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':contact_number' => $contact_number
    ]);
    $history_result2 = $history_stmt2->fetch(PDO::FETCH_ASSOC);

    $total_history = ($history_result['history_count'] ?? 0) + ($history_result2['history_count'] ?? 0);

    if ($total_history > 0) {
        // Visitor has history, notify admins and personnel
        $message = "Visitor {$full_name} (Email: {$email}, Contact: {$contact_number}) has submitted a visitation request. This visitor has previous visit history.";

        // Get all admin and user IDs
        $user_query = "SELECT id FROM users WHERE role IN ('Admin', 'User')";
        $user_stmt = $pdo->prepare($user_query);
        $user_stmt->execute();
        $users = $user_stmt->fetchAll(PDO::FETCH_ASSOC);

        // Insert notification for each user
        $notify_query = "INSERT INTO notifications (user_id, message) VALUES (:user_id, :message)";
        $notify_stmt = $pdo->prepare($notify_query);

        foreach ($users as $user) {
            $notify_stmt->execute([
                ':user_id' => $user['id'],
                ':message' => $message
            ]);
        }
    }
}

?>
