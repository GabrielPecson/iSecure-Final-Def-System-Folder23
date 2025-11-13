<?php
$filename = basename('public/uploads/ids/1762566813_id.jfif');
echo 'Basename: ' . $filename . PHP_EOL;
$path1 = __DIR__ . '/../uploads/ids/' . $filename;
echo 'Path1: ' . $path1 . PHP_EOL;
echo 'Exists: ' . (file_exists($path1) ? 'Yes' : 'No') . PHP_EOL;
