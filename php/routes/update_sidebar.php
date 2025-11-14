<?php
require 'db_connect.php';

function resizeImage($source, $dest, $width, $height, $cropX = 0.5, $cropY = 0.5, $zoomLevel = 1.0) {
    list($origWidth, $origHeight, $type) = getimagesize($source);

    // Create source image based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($source);
            break;
        default:
            return false; // Unsupported type
    }

    // Calculate scaling factors for cover crop
    $scaleX = $width / $origWidth;
    $scaleY = $height / $origHeight;
    $scale = max($scaleX, $scaleY) * $zoomLevel;

    // Source dimensions to sample (zoomed/crop area in original coords)
    $srcWidth = $width / $scale;
    $srcHeight = $height / $scale;

    // Calculate crop start positions in original image (0-1 normalized)
    $srcX = ($origWidth - $srcWidth) * $cropX;
    $srcY = ($origHeight - $srcHeight) * $cropY;

    // Create destination image
    $destImage = imagecreatetruecolor($width, $height);

    // Resize and crop from original
    imagecopyresampled(
        $destImage, $sourceImage,
        0, 0, $srcX, $srcY,
        $width, $height, $srcWidth, $srcHeight
    );

    // Save based on original type
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($destImage, $dest, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($destImage, $dest, 6);
            break;
    }

    // Clean up
    imagedestroy($sourceImage);
    imagedestroy($destImage);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sectionType = $_POST['section_type'] ?? null;
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $cropX = (float)($_POST['crop_x'] ?? 0.5);
    $cropY = (float)($_POST['crop_y'] ?? 0.5);
    $zoomLevel = (float)($_POST['zoom_level'] ?? 1.0);
    $imagePath = null;

    // Handle image upload if provided
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/sidebar/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFile = $targetDir . $fileName;
        $tmpFile = $_FILES["image"]["tmp_name"];

        if (move_uploaded_file($tmpFile, $targetFile)) {
            // Resize the image to 300x200 to match sidebar container
            resizeImage($targetFile, $targetFile, 300, 200, $cropX, $cropY, $zoomLevel);
            $imagePath = $targetFile;
        }
    }

    if ($sectionType) {
        // Fetch current sidebar data for archiving
        $stmt = $pdo->prepare("SELECT title, content, image_path FROM landing_sidebar_sections WHERE section_type = :type");
        $stmt->execute([':type' => $sectionType]);
        $current = $stmt->fetch();

        if ($current) {
            // Archive old data (assuming landing_archives has sidebar columns; adjust if needed)
            $archiveStmt = $pdo->prepare("
                INSERT INTO landing_archives (sidebar_title, sidebar_content, sidebar_image)
                VALUES (:title, :content, :image)
            ");
            $archiveStmt->execute([
                ':title' => $current['title'],
                ':content' => $current['content'],
                ':image' => $current['image_path']
            ]);
        }

        // If no new image, keep old image
        if (!$imagePath && $current) {
            $imagePath = $current['image_path'];
        }

        // Update existing record using REPLACE (since section_type is UNIQUE)
        $replace = $pdo->prepare("
            REPLACE INTO landing_sidebar_sections
            SET section_type = :type, title = :title, content = :content, image_path = :image
        ");
        $replace->execute([
            ':type' => $sectionType,
            ':title' => $title,
            ':content' => $content,
            ':image' => $imagePath
        ]);
    }

    $activeTab = $_POST['active_tab'] ?? 'sidebar';
    $msg = 'Sidebar section updated';
    header("Location: customizelanding.php?active_tab=" . urlencode($activeTab) . "&msg=" . urlencode($msg));
    exit;
}
?>