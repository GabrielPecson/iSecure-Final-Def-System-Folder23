<?php
require 'database/db_connect.php';

echo "<h1>Verifying Visitor Image Paths</h1>";

// Query to get all id_photo_path from visitors
$stmt = $pdo->prepare("SELECT id, id_photo_path FROM visitors WHERE id_photo_path IS NOT NULL AND id_photo_path != ''");
$stmt->execute();
$visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Visitor ID</th><th>Path in DB</th><th>File Exists</th><th>Corrected Path</th></tr>";

foreach ($visitors as $visitor) {
    $path = $visitor['id_photo_path'];
    $fullPath = __DIR__ . '/../' . $path; // Assuming uploads is at project root
    $exists = file_exists($fullPath) ? 'Yes' : 'No';

    // Try to find the file by filename
    $filename = basename($path);
    $uploadsDir = __DIR__ . '/../uploads/';
    $foundPath = null;
    if (is_dir($uploadsDir)) {
        $files = scandir($uploadsDir);
        foreach ($files as $file) {
            if ($file === $filename) {
                $foundPath = 'uploads/' . $file;
                break;
            }
        }
    }

    echo "<tr>";
    echo "<td>{$visitor['id']}</td>";
    echo "<td>{$path}</td>";
    echo "<td>{$exists}</td>";
    echo "<td>" . ($foundPath ? $foundPath : 'Not found') . "</td>";
    echo "</tr>";

    // If found and different, update DB
    if ($foundPath && $foundPath !== $path) {
        $updateStmt = $pdo->prepare("UPDATE visitors SET id_photo_path = :new_path WHERE id = :id");
        $updateStmt->execute([':new_path' => $foundPath, ':id' => $visitor['id']]);
        echo "<td>Updated</td>";
    } else {
        echo "<td></td>";
    }
    echo "</tr>";
}

echo "</table>";
?>
