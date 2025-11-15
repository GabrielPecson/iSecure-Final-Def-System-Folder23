<?php
$filename = basename('routes/uploads/1762566813_id.jfif');
echo 'Basename: ' . $filename . PHP_EOL;
$path1 = __DIR__ . '/../uploads/ids/' . $filename;
echo 'Path1: ' . $path1 . PHP_EOL;
echo 'Exists: ' . (file_exists($path1) ? 'Yes' : 'No') . PHP_EOL;
