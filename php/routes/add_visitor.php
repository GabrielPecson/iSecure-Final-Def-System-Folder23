<?php
require 'auth_check.php';
require '../database/db_connect.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $idPhoto = trim($_POST['id_photo_path'] ?? '');
    $selfiePhoto = trim($_POST['selfie_photo_path'] ?? '');

    if (empty($fullName) || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Full name and reason are required.']);
        exit;
    }

    try {
        // Store data in plain text
        $fullNameEnc = $fullName;
        $contactNumberEnc = $contactNumber;
        $emailEnc = $email;
        $addressEnc = $address;

        $stmt = $pdo->prepare("
            INSERT INTO visitors
                (full_name, contact_number, email, address, reason, id_photo_path, selfie_photo_path, date, time_in, status)
            VALUES
                (:full_name, :contact_number, :email, :address, :reason, :id_photo_path, :selfie_photo_path, CURDATE(), NOW(), 'Inside')
        ");
        $stmt->execute([
            ':full_name' => $fullNameEnc,
            ':contact_number' => $contactNumberEnc,
            ':email' => $emailEnc,
            ':address' => $addressEnc,
            ':reason' => $reason,
            ':id_photo_path' => $idPhoto,
            ':selfie_photo_path' => $selfiePhoto
        ]);

        echo json_encode(['success' => true, 'message' => 'Visitor added successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
