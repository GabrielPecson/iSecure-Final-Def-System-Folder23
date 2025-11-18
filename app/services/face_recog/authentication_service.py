from deepface import DeepFace
import cv2
import numpy as np
from app.db import get_db_connection
import os
import traceback
import sys

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

def authenticate_face(frame):
    """
    Authenticates a face by comparing it against the selfies of all visitors currently 'Inside'.
    """
    _log("authenticate_face called")
    db_connection = get_db_connection()
    try:
        with db_connection.cursor() as cursor:
            # Fetch the selfie paths and names of all visitors with 'Inside' status
            sql = "SELECT selfie_photo_path, first_name, last_name FROM visitors WHERE status = 'Inside'"
            cursor.execute(sql)
            inside_visitors = cursor.fetchall()
    except Exception as e:
        _log("DB error: " + str(e))
        _log(traceback.format_exc())
        raise
    finally:
        try:
            db_connection.close()
        except Exception:
            pass

    if not inside_visitors:
        _log("No visitors inside")
        return {"authenticated": False, "message": "No visitors are currently inside."}

    for visitor in inside_visitors:
        selfie_path = visitor['selfie_photo_path']
        full_name = f"{visitor['first_name']} {visitor['last_name']}"
        _log(f"Checking visitor: {full_name}, selfie_path: {selfie_path}")

        if not os.path.exists(selfie_path):
            _log(f"Selfie not found: {selfie_path}")
            continue

        try:
            if isinstance(frame, (np.ndarray,)):
                img1 = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
                result = DeepFace.verify(img1=img1, img2_path=selfie_path,
                                         model_name="Facenet512", detector_backend="mtcnn")
            elif isinstance(frame, (str, os.PathLike)):
                result = DeepFace.verify(img1_path=str(frame), img2_path=selfie_path,
                                         model_name="Facenet512", detector_backend="mtcnn")
            else:
                _log(f"Unknown frame type: {type(frame)}")
                continue
            
            _log(f"DeepFace result for {full_name}: {result}")
            if result.get('verified'):
                _log(f"Authenticated: {full_name}")
                return {"authenticated": True, "visitor_name": full_name}
        except Exception as e:
            _log("DeepFace error during authentication: " + str(e))
            _log(traceback.format_exc())
            continue

    _log("Authentication failed: Unknown visitor")
<<<<<<< HEAD
    return {"authenticated": False, "message": "Unknown visitor."}
=======
    return {"authenticated": False, "message": "Unknown visitor."}
>>>>>>> 67eab98 (Initial commit: local files before connecting to GitHub)
