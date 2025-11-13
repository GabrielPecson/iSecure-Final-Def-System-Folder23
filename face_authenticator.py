# Install required packages (run these commands in your terminal):
# pip install deepface opencv-python mtcnn numpy dlib imutils

import cv2
import numpy as np
from deepface import DeepFace
import json
import os

# Constants
MODEL = "Facenet512"  # High accuracy model
DETECTOR_BACKEND = "mtcnn"  # Accurate face detection
THRESHOLD = 0.4  # Cosine distance threshold for authentication (lower = stricter)
DB_FILE = "visitors.json"  # Local database file for storing embeddings

def load_database():
    """
    Load the visitor database from JSON file.
    Returns a dict of visitor_id: embedding (numpy array).
    """
    if os.path.exists(DB_FILE):
        with open(DB_FILE, 'r') as f:
            data = json.load(f)
            # Convert lists back to numpy arrays
            for k, v in data.items():
                data[k] = np.array(v)
            return data
    return {}

def save_database(db):
    """
    Save the visitor database to JSON file.
    Converts numpy arrays to lists for JSON serialization.
    """
    data = {k: v.tolist() for k, v in db.items()}
    with open(DB_FILE, 'w') as f:
        json.dump(data, f)

def register_visitor(visitor_id, frame):
    """
    Register a new visitor by extracting facial embedding from the frame.
    Assumes a face is present in the frame.
    """
    try:
        # Extract embedding using DeepFace
        result = DeepFace.represent(frame, model_name=MODEL, detector_backend=DETECTOR_BACKEND)
        if result:
            embedding = np.array(result[0]['embedding'])
            db = load_database()
            db[visitor_id] = embedding
            save_database(db)
            print(f"Successfully registered visitor: {visitor_id}")
        else:
            print("No face detected in the frame. Registration failed.")
    except Exception as e:
        print(f"Error during registration: {e}")

def authenticate_visitor(frame):
    """
    Authenticate a visitor by comparing the face in the frame to stored embeddings.
    Returns authentication result string.
    """
    try:
        # Extract embedding from the frame
        result = DeepFace.represent(frame, model_name=MODEL, detector_backend=DETECTOR_BACKEND)
        if not result:
            return "No face detected"
        embedding = np.array(result[0]['embedding'])

        db = load_database()
        if not db:
            return "No registered visitors in database"

        min_dist = float('inf')
        best_id = None
        for vid, emb in db.items():
            # Compute cosine distance
            cos_sim = np.dot(emb, embedding) / (np.linalg.norm(emb) * np.linalg.norm(embedding))
            dist = 1 - cos_sim
            if dist < min_dist:
                min_dist = dist
                best_id = vid

        if min_dist < THRESHOLD:
            return f"Authenticated as {best_id} (distance: {min_dist:.3f})"
        else:
            return f"Rejected (min distance: {min_dist:.3f})"
    except Exception as e:
        return f"Error during authentication: {e}"

def main():
    """
    Main function to run the face authenticator.
    Supports register and authenticate modes using webcam.
    """
    cap = cv2.VideoCapture(0)
    if not cap.isOpened():
        print("Error: Could not open webcam.")
        return

    mode = input("Enter mode ('register', 'authenticate', or 'delete'): ").strip().lower()
    if mode not in ['register', 'authenticate', 'delete']:
        print("Invalid mode. Exiting.")
        cap.release()
        return

    if mode == 'register':
        visitor_id = input("Enter visitor ID: ").strip()
        print("Press 'c' to capture and register the face. Press 'q' to quit.")
        while True:
            ret, frame = cap.read()
            if not ret:
                break
            # Detect and draw faces
            try:
                faces = DeepFace.extract_faces(frame, detector_backend=DETECTOR_BACKEND)
                for face in faces:
                    x, y, w, h = face['facial_area']['x'], face['facial_area']['y'], face['facial_area']['w'], face['facial_area']['h']
                    cv2.rectangle(frame, (x, y), (x + w, y + h), (0, 255, 0), 2)
            except:
                pass  # Skip if detection fails
            cv2.imshow('Register Mode - Press c to capture', frame)
            key = cv2.waitKey(1) & 0xFF
            if key == ord('c'):
                register_visitor(visitor_id, frame)
                break
            elif key == ord('q'):
                break

    elif mode == 'authenticate':
        print("Press 'a' to authenticate the current face. Press 'q' to quit.")
        while True:
            ret, frame = cap.read()
            if not ret:
                break
            # Detect and draw faces
            try:
                faces = DeepFace.extract_faces(frame, detector_backend=DETECTOR_BACKEND)
                for face in faces:
                    x, y, w, h = face['facial_area']['x'], face['facial_area']['y'], face['facial_area']['w'], face['facial_area']['h']
                    cv2.rectangle(frame, (x, y), (x + w, y + h), (0, 255, 0), 2)
            except:
                pass
            cv2.imshow('Authenticate Mode - Press a to verify', frame)
            key = cv2.waitKey(1) & 0xFF
            if key == ord('a'):
                result = authenticate_visitor(frame)
                print(result)
                # Display result on frame
                cv2.putText(frame, result, (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 255), 2)
                cv2.imshow('Authenticate Mode - Press a to verify', frame)
                cv2.waitKey(3000)  # Show result for 3 seconds
            elif key == ord('q'):
                break

    elif mode == 'delete':
        visitor_id = input("Enter visitor ID to delete: ").strip()
        db = load_database()
        if visitor_id in db:
            del db[visitor_id]
            save_database(db)
            print(f"Successfully deleted visitor: {visitor_id}")
        else:
            print(f"Visitor ID '{visitor_id}' not found in database.")

    cap.release()
    cv2.destroyAllWindows()

if __name__ == "__main__":
    main()
