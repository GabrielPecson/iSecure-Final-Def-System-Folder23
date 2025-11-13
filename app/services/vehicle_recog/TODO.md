# Vehicle Recognition Integration TODO

This document outlines the steps to integrate the `license_scanner.py` script with the main application's vehicle verification tab.

## Integration Plan

1.  **Modify Python Script (`license_scanner.py`):**
    -   Update the script to accept an image file path as a command-line argument.
    -   Change the output format to print a single JSON object to standard output (`stdout`). This JSON will contain the recognized `license_plate_number` and `vehicle_type`.

2.  **Create Backend Handler (`scan_plate.php`):**
    -   Create a new PHP script in `php/routes/` to handle image uploads.
    -   This script will save the captured image to a designated folder (e.g., `ID' Data for ocr/`).
    -   It will execute the modified `license_scanner.py` using `shell_exec()`, passing the saved image's path as an argument.
    -   It will capture the JSON output from the Python script and echo it back as the response.

3.  **Enhance Frontend UI (`visitors.php`):**
    -   Add a dedicated camera modal that will be triggered by the "Scan Plate" button. This modal will contain the video feed and a "Capture" button.
    -   Add a new field in the "Vehicle" tab to display the recognized vehicle type, alongside the existing field for the license plate.

4.  **Implement Frontend Logic (`visitors.js`):**
    -   Rewrite the `scanPlateBtn` event listener.
    -   On click, it will launch the camera modal and start the user's webcam.
    -   The "Capture" button will take a snapshot, convert it to a blob, and send it to `scan_plate.php` via a `fetch` request.
    -   The success callback of the `fetch` request will parse the returned JSON and populate the "Recognized Plate" and "Vehicle Type" fields in the UI.