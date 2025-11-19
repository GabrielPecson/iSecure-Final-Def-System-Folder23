from deepface import DeepFace
import cv2
import numpy as np
import os
import json
import traceback
import sys
import warnings

warnings.filterwarnings('ignore')
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'  # Suppress TensorFlow logging
os.environ['TF_ENABLE_ONEDNN_OPTS'] = '0'  # Disable oneDNN custom operations

# make LOG_DIR absolute and ensure it exists, create empty log file at import time
LOG_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "logs"))
os.makedirs(LOG_DIR, exist_ok=True)
LOG_FILE = os.path.join(LOG_DIR, "face_auth.log")
try:
    # ensure the file exists and add a module load marker
    with open(LOG_FILE, "a", encoding="utf-8") as f:
        f.write("authentication_service loaded\n")
except Exception:
    pass

def _log(msg):
    try:
        with open(LOG_FILE, "a", encoding="utf-8") as f:
            f.write(msg + "\n")
    except Exception:
        pass
    try:
        # also print to stderr so PHP/shell capture shows it
        print(msg, file=sys.stderr)
    except Exception:
        pass

# Get the absolute path to the project root, assuming this script is in app/services/face_recog/
project_root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..', '..'))
DB_FILE = os.path.join(project_root, "app", "services", "face_recog", "visitors.json")

MODEL = "Facenet512"
DETECTOR_BACKEND = "mtcnn"
THRESHOLD = 0.3  # This threshold might need tuning

def load_database():
    if not os.path.exists(os.path.dirname(DB_FILE)):
        os.makedirs(os.path.dirname(DB_FILE))
    if os.path.exists(DB_FILE):
        try:
            with open(DB_FILE, 'r') as f:
                data = json.load(f)
                for k, v in data.items():
                    data[k] = np.array(v)
                return data
        except (json.JSONDecodeError, FileNotFoundError):
            return {}
    return {}

def authenticate_face(frame):
    _log("authenticate_face called")

    db = load_database()
    if not db:
        _log("No registered visitors in the database (visitors.json).")
        return {"authenticated": False, "message": "No registered visitors in the database."}

    try:
        # Step 1: Represent the input frame to get its embedding
        if isinstance(frame, (np.ndarray,)):
            # Convert BGR to RGB for DeepFace if it's a numpy array
            img_rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
            representations = DeepFace.represent(img_rgb, model_name=MODEL, detector_backend=DETECTOR_BACKEND, enforce_detection=True)
        elif isinstance(frame, (str, os.PathLike)):
            representations = DeepFace.represent(str(frame), model_name=MODEL, detector_backend=DETECTOR_BACKEND, enforce_detection=True)
        else:
            _log(f"Unknown frame type: {type(frame)}")
            return {"authenticated": False, "message": "Invalid input frame type."}
        
        if not representations:
            return {"authenticated": False, "message": "No face detected in the input frame."}
        
        input_embedding = np.array(representations[0]['embedding'])

        # Step 2: Compare with stored embeddings
        min_dist = float('inf')
        best_match_name = None

        for visitor_name, stored_embedding in db.items():
            # DeepFace.verify internally uses cosine similarity for Facenet512
            # We will replicate the distance calculation (1 - cosine_similarity)
            # as seen in face_authenticator.py
            cos_sim = np.dot(stored_embedding, input_embedding) / (np.linalg.norm(stored_embedding) * np.linalg.norm(input_embedding))
            distance = 1 - cos_sim

            _log(f"Comparing with {visitor_name}: Distance = {distance:.3f} (Threshold: {THRESHOLD})")

            if distance < min_dist:
                min_dist = distance
                best_match_name = visitor_name
        
        if min_dist < THRESHOLD:
            _log(f"Authenticated: {best_match_name} (Distance: {min_dist:.3f})")
            return {"authenticated": True, "visitor_name": best_match_name}
        else:
            _log(f"Authentication failed: Closest match {best_match_name} (Distance: {min_dist:.3f}) - Above threshold.")
            return {"authenticated": False, "message": "Unknown visitor."}

    except Exception as e:
        _log("DeepFace error during authentication: " + str(e))
        _log(traceback.format_exc())
        if "Face could not be detected" in str(e):
            return {"authenticated": False, "message": "Authentication failed: No face could be detected in the input."}
        return {"authenticated": False, "message": f"Authentication error: {str(e)}"}
