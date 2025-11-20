<?php

// Use an absolute path to require the database configuration. This makes the include reliable.


function send_sms($number, $message, $sendername = 'SEMAPHORE') {
    $ch = curl_init();
    $parameters = array(
        'apikey' => '4c8de78e2a781ffdadb3f3c2ad90eeda', // Your API KEY
        'number' => $number,
        'message' => $message,
        'sendername' => 'Semaphore'
    );
    curl_setopt($ch, CURLOPT_URL, 'https://semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_POST, 1);
    // Send the parameters set above with the request
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
    // Receive response from server
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    // Return the server response
    return $output;
}

// Example usage:
// $response = send_sms('09998887777', 'Test message from PHP');
// echo $response;

