<?php
require 'php/routes/Pages/db_connect.php';

try {
    // Update status for visitors where time_in is set but status is empty
    $pdo->exec("UPDATE visitors SET status = 'Inside' WHERE time_in IS NOT NULL AND time_out IS NULL AND (status IS NULL OR status = '')");

    // Update status for visitors where time_out is set but status is empty
    $pdo->exec("UPDATE visitors SET status = 'Exited' WHERE time_in IS NOT NULL AND time_out IS NOT NULL AND (status IS NULL OR status = '')");

    // Update status for visitors with no time_in and status is empty
    $pdo->exec("UPDATE visitors SET status = 'Expected' WHERE time_in IS NULL AND (status IS NULL OR status = '')");

    echo "Status updates completed.\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
