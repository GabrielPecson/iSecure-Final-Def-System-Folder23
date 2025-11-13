import os
import cv2

class Camera:
    def __init__(self, source=0):
        # Try DirectShow backend first, as MSMF can cause issues on some Windows systems
        self.cap = cv2.VideoCapture(source, cv2.CAP_DSHOW)
        if not self.cap.isOpened():
            # Fallback to MSMF if DSHOW fails
            self.cap = cv2.VideoCapture(source, cv2.CAP_MSMF)
            if not self.cap.isOpened():
                # Don't raise an error, just mark as not running
                self.running = False
                print(f"Warning: Could not open video source: {source}")
            else:
                self.running = True
        else:
            self.running = True

    def read_frame(self):
        if not self.running or not self.cap.isOpened():
            return None
        ret, frame = self.cap.read()
        if not ret:
            return None
        return frame

    def stop(self):
        if self.running and self.cap and self.cap.isOpened():
            self.cap.release()
        self.running = False

class DummyCamera:
    def read_frame(self):
        return None
    def stop(self):
        pass

# --- New: Two independent camera objects ---
camera_facial = DummyCamera()
camera_vehicle = DummyCamera()

# --- New: Dictionary to hold camera configurations ---
camera_sources = {
    "facial": "webcam",  # Default to webcam
    "vehicle": "webcam"
}

def set_camera_source(camera_type: str, source: str):
    """
    Sets the active camera source for either the facial or vehicle camera.
    'camera_type' can be 'facial' or 'vehicle'.
    'source' can be 'webcam' or a URL.
    """
    global camera_facial, camera_vehicle, camera_sources

    if camera_type not in camera_sources:
        print(f"Error: Invalid camera type '{camera_type}'")
        return

    # Stop the existing camera for this type
    if camera_type == "facial" and not isinstance(camera_facial, DummyCamera):
        camera_facial.stop()
    elif camera_type == "vehicle" and not isinstance(camera_vehicle, DummyCamera):
        camera_vehicle.stop()

    # Update the source configuration
    camera_sources[camera_type] = source

    # Determine the integer index for webcams
    if camera_type == "facial":
        cam_index = 0  # Facial camera uses index 0
    else:  # vehicle
        cam_index = 0  # Vehicle camera uses index 1 to avoid conflict

    # Initialize the new camera
    try:
        new_source = cam_index if source == 'webcam' else source
        new_camera = Camera(new_source)

        if new_camera.running:
            if camera_type == "facial":
                camera_facial = new_camera
            else: # vehicle
                camera_vehicle = new_camera
        else:
            # Fallback to dummy if initialization failed
            raise ValueError("Camera failed to open.")

    except Exception as e:
        print(f"Error setting {camera_type} camera to source '{source}': {e}")
        if camera_type == "facial":
            camera_facial = DummyCamera()
        else: # vehicle
            camera_vehicle = DummyCamera()

# Initialize default cameras on startup
set_camera_source("facial", "webcam")
set_camera_source("vehicle", "webcam")
