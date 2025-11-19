import os
import cv2

class Camera:
    def __init__(self, source=0):
        self.running = False
        self.cap = None

        # Try different sources/backends in order of preference
        sources_to_try = []

        # If source is a string, treat it as webcam index or URL
        if isinstance(source, str):
            if source == 'webcam':
                # Try multiple webcam indices
                sources_to_try = [
                    (0, cv2.CAP_DSHOW),
                    (0, cv2.CAP_MSMF),
                    (1, cv2.CAP_DSHOW),
                    (1, cv2.CAP_MSMF),
                    (2, cv2.CAP_DSHOW),
                    (2, cv2.CAP_MSMF),
                ]
            else:
                # Treat as URL
                sources_to_try = [(source, cv2.CAP_FFMPEG)]
        else:
            # Numeric source (webcam index)
            sources_to_try = [
                (source, cv2.CAP_DSHOW),
                (source, cv2.CAP_MSMF),
            ]

        for src, backend in sources_to_try:
            try:
                self.cap = cv2.VideoCapture(src, backend)
                if self.cap.isOpened():
                    # Test reading a frame to ensure it's working
                    ret, test_frame = self.cap.read()
                    if ret and test_frame is not None:
                        self.running = True
                        print(f"Successfully opened camera with source: {src}, backend: {backend}")
                        break
                    else:
                        self.cap.release()
                        self.cap = None
                else:
                    self.cap = None
            except Exception as e:
                print(f"Failed to open camera with source {src}, backend {backend}: {e}")
                if self.cap:
                    self.cap.release()
                    self.cap = None

        if not self.running:
            print(f"Warning: Could not open any video source for camera initialization")

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
        return False, f"Invalid camera type '{camera_type}'"

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
        cam_index = 1  # Vehicle camera uses index 1 to avoid conflict

    # Initialize the new camera
    try:
        new_source = cam_index if source == 'webcam' else source
        new_camera = Camera(new_source)

        if new_camera.running:
            if camera_type == "facial":
                camera_facial = new_camera
            else: # vehicle
                camera_vehicle = new_camera
            return True, f"Successfully set {camera_type} camera to source '{source}'"
        else:
            # Fallback to dummy if initialization failed
            print(f"Camera failed to open for source '{source}'. Setting to dummy camera.")
            if camera_type == "facial":
                camera_facial = DummyCamera()
            else: # vehicle
                camera_vehicle = DummyCamera()
            return True, f"Set {camera_type} camera to dummy as source '{source}' is not available"

    except Exception as e:
        error_message = f"Error setting {camera_type} camera to source '{source}': {e}"
        print(error_message)
        if camera_type == "facial":
            camera_facial = DummyCamera()
        else: # vehicle
            camera_vehicle = DummyCamera()
        return True, f"Set {camera_type} camera to dummy due to error: {e}"

# --- New: Smart Initialization ---
# On a server, we don't want to assume a webcam is present.
# We will let the cameras start as DummyCamera.
# They can be configured later via the /camera/source API endpoint
# with a real IP camera URL.
# If you are developing locally and want webcams on by default,
# you can uncomment the two lines below.

# set_camera_source("facial", "webcam")
# set_camera_source("vehicle", "webcam")
