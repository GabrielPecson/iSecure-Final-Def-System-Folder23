<?php
$host = "localhost";
$dbname = "isecure";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Create the card_holder table if it doesn't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS card_holder (
            holder_id INT NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            PRIMARY KEY (holder_id)
        ) ENGINE=InnoDB;
    ");

    echo 'Table card_holder created successfully.';
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
