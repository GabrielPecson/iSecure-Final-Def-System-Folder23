# iSecure - Visitor Management System

## System Overview

iSecure is a comprehensive visitor management system designed to streamline the process of visitor registration, authentication, and tracking within a facility. It integrates facial recognition, vehicle license plate recognition (OCR), and ID document OCR to enhance security and efficiency.

## Core Features

*   **Visitor Pre-registration:** Visitors can submit requests with their details, including vehicle information and photos.
*   **Personnel/Admin Dashboard:** Secure access for staff to manage visitors, review requests, and monitor activity.
*   **Multi-factor Visitor Verification:** A robust process combining:
    *   Facial Authentication
    *   Vehicle License Plate Recognition
    *   ID Document OCR
*   **Visitor Entry/Exit Management:** Tools for personnel to mark visitor entry and exit, and manage key cards.
*   **Audit Logging:** Records of all significant actions and visitor movements.

## System Flow: Detailed Workflow

### 1. Visitor Request/Registration

*   **User Action:** A visitor accesses the public portal to submit a visitation request. They provide personal details, purpose of visit, personnel/office to visit, and optionally, vehicle information (owner, brand, model, color, plate number) and upload a valid ID photo and a selfie.
*   **System Action:** The system records the request in the database with a "Pending" status.

### 2. Personnel/Admin Login

*   **User Action:** Security personnel or administrators log into the iSecure system using their credentials.
*   **System Action:** The system authenticates the user and directs them to their respective dashboard (`personnel_dashboard.php` or `maindashboard.php`).

### 3. Visitor Verification (Personnel Workflow)

This is the primary workflow for security personnel when a visitor arrives.

*   **User Action:** A security guard navigates to the **Visitors** page (`personnel_visitors.php` or `visitors.php`).
*   **User Action:** A visitor arrives at the gate. The guard locates the visitor in the "Expected Visitors" table and clicks the **"View"** button to open the `visitorDetailsModal`.

*   **System Action (Modal Initialization):**
    *   The modal opens, defaulting to the **"Facial"** tab.
    *   The "Vehicle" and "ID" tabs are initially **disabled**.
    *   The "Next" button on the "Facial" tab is initially **disabled**.

*   **3.1. Facial Authentication:**
    *   **User Action:** The guard selects the camera source (webcam or CCTV) and clicks the **"Authenticate"** button.
    *   **System Action (Backend):** The frontend sends a captured frame to the `/authenticate/face` endpoint. The backend uses `camera_facial` (defaulting to webcam 0) to capture a single frame, compares it to the visitor's registered selfie, and returns a match result.
    *   **System Action (Frontend):**
        *   If authentication is **successful**: A success message is displayed. The "Next" button on the "Facial" tab is **enabled**. The "Vehicle" tab is **unlocked**.
        *   If authentication **fails**: A failure message is displayed. The "Next" button remains disabled.

*   **3.2. Vehicle Verification (Conditional):**
    *   **User Action:** Upon successful facial authentication, the guard clicks the **"Next"** button on the "Facial" tab.
    *   **System Action (Frontend):** The system navigates to the **"Vehicle"** tab.
    *   **System Action (Conditional Logic):**
        *   **If the visitor HAS a registered vehicle (plate number exists):**
            *   The "Expected Plate Number" is displayed.
            *   The live camera feed from `camera_vehicle` (defaulting to webcam 1) is displayed.
            *   The "Scan Plate" button is visible.
            *   **User Action:** The guard aims the camera at the vehicle's license plate and clicks **"Scan Plate"**.
            *   **System Action (Backend):** The frontend sends the `expected_plate_number` to the `/camera/recognize_and_compare_plate` endpoint. The backend captures a frame from `camera_vehicle`, performs OCR, compares it to the expected plate, and returns the result.
            *   **System Action (Frontend):** The "Recognized Plate" and "Status" (Match/No Match) are displayed.
            *   **User Action:** The guard reviews the result and clicks **"Next"** to proceed. The "ID" tab is **unlocked**.
        *   **If the visitor DOES NOT have a registered vehicle:**
            *   The vehicle verification UI is hidden.
            *   The system automatically navigates the user directly to the **"ID"** tab. The "ID" tab is **unlocked**.

*   **3.3. ID Document OCR:**
    *   **System Action (Frontend):** The system navigates to the **"ID"** tab.
    *   **System Action (Backend):** The frontend automatically triggers an OCR process on the visitor's uploaded ID image by sending it to the `/ocr/id` endpoint.
    *   **System Action (Frontend):** The extracted ID details are displayed.
    *   **User Action:** The guard reviews the extracted details.

### 4. Visitor Entry/Exit Management

*   **User Action:** On the "ID" tab, after all verifications, the guard clicks the **"Mark Entry"** button.
*   **System Action:** The system records the visitor's entry time and updates their status to "Inside."
*   **User Action:** When the visitor exits, the guard locates them in the "Inside Visitors" table and clicks **"Mark Exit"**.
*   **System Action:** The system records the visitor's exit time and updates their status to "Exited."
# iSecure-Final-Def-System-Folder
*   **System Action:** The system records the visitor's exit time and updates their status to "Exited."
# iSecure-Final-Def-System-Folder23
