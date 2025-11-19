import sys
import os
import logging
from logging.handlers import RotatingFileHandler
from werkzeug.exceptions import HTTPException # Import HTTPException for specific handling

# Ensure the project root is in the Python path for module imports
project_root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
if project_root not in sys.path:
    sys.path.insert(0, project_root)

from flask import Flask, request, jsonify, Response, stream_with_context, abort
from app.db import get_db_connection
from app.config import set_camera_source, camera_facial, camera_vehicle
from app.services.face_recog.authentication_service import authenticate_face
from app.services.vehicle_recog.license_scanner import detect_vehicle_plate
from app.services.ocr.id_scanner import extract_id_info
from flask_cors import CORS
import asyncio
import cv2
import numpy as np
import time
import tempfile
import json
import threading

app = Flask(__name__)

# --- Logging Setup ---
log_dir = os.path.join(project_root, 'logs')
os.makedirs(log_dir, exist_ok=True)
log_file = os.path.join(log_dir, 'app.log')

# Create a rotating file handler
# Max size of 5MB, 5 backup files
handler = RotatingFileHandler(log_file, maxBytes=5*1024*1024, backupCount=5)
handler.setFormatter(logging.Formatter(
    '%(asctime)s %(levelname)s: %(message)s [in %(pathname)s:%(lineno)d]'
))

# Set the logger level
app.logger.setLevel(logging.INFO)
# Handler is added below in __main__ block based on debug status


# Allow PHP frontend (Apache) to access this API
CORS(app, resources={r"/*": {"origins": "*"}})

def preload_models():
    """
    Pre-loads the deepface models into memory when the server starts.
    This prevents long delays and timeouts on the first API call.
    """
    try:
        app.logger.info("Pre-loading face recognition models...")
        from deepface import DeepFace
        # This call will download and cache the model if it's not already present
        DeepFace.build_model("Facenet512")
        app.logger.info("Face recognition models loaded successfully.")
    except Exception as e:
        app.logger.error(f"Could not pre-load models. First request may be slow. Error: {e}", exc_info=True)



@app.route("/recognize/vehicle", methods=["POST"])
def recognize_vehicle():
    if 'file' not in request.files:
        abort(400, description="No file part")
    file = request.files['file']
    if file.filename == '':
        abort(400, description="No selected file")
    
    try:
        # Read the file content directly
        contents = file.read()
        recognition_result = detect_vehicle_plate(contents)

        # Check if the recognition service returned an error
        if "error" in recognition_result:
            # Forward the error from the recognition service
            return jsonify(recognition_result), 400

        # If successful, return the entire result dictionary
        return jsonify(recognition_result)
    except Exception as e:
        app.logger.error(f"Error in /recognize/vehicle: {e}", exc_info=True)
        abort(500, description=str(e))

@app.route("/ocr/id", methods=["POST"])
def ocr_id():
    if 'file' not in request.files:
        abort(400, description="No file part")
    file = request.files['file']
    if file.filename == '':
        abort(400, description="No selected file")
    
    try:
        contents = file.read()
        result = extract_id_info(contents)
        return jsonify(result)
    except Exception as e:
        app.logger.error(f"Error in /ocr/id: {e}", exc_info=True)
        abort(500, description=str(e))

@app.route("/welcome", methods=["GET"])
def welcome():
    app.logger.info(f"Request received: {request.method} {request.path}")
    return jsonify({"message": "Welcome to the Flask API Service!"})

@app.route("/", methods=["GET"])
def health_check():
    return jsonify({"status": "API running"})

def generate_frames(camera):
    while True:
        try:
            frame = camera.read_frame()
            if frame is not None:
                ret, buffer = cv2.imencode('.jpg', frame)
                if ret:
                    yield (b'--frame\r\n'
                           b'Content-Type: image/jpeg\r\n\r\n' + buffer.tobytes() + b'\r\n')
            else:
                # Yield a blank frame if no frame is available
                blank = np.zeros((480, 640, 3), dtype=np.uint8)
                ret, buffer = cv2.imencode('.jpg', blank)
                if ret:
                    yield (b'--frame\r\n'
                           b'Content-Type: image/jpeg\r\n\r\n' + buffer.tobytes() + b'\r\n')
            time.sleep(0.1)
        except Exception as e:
            app.logger.error(f"Error generating camera frames: {e}", exc_info=True)
            # You might want to break the loop or handle the error in another way
            break

@app.route("/camera/facial/frame", methods=["GET"])
def get_camera_facial_frame():
    return Response(stream_with_context(generate_frames(camera_facial)), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route("/camera/vehicle/frame", methods=["GET"])
def get_camera_vehicle_frame():
    return Response(stream_with_context(generate_frames(camera_vehicle)), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route("/camera/facial/single_frame", methods=["GET"])
def get_single_facial_frame():
    try:
        frame = camera_facial.read_frame()
        if frame is not None:
            ret, buffer = cv2.imencode('.jpg', frame)
            if ret:
                return Response(buffer.tobytes(), mimetype='image/jpeg')
        blank = np.zeros((480, 640, 3), dtype=np.uint8)
        ret, buffer = cv2.imencode('.jpg', blank)
        return Response(buffer.tobytes(), mimetype='image/jpeg')
    except Exception as e:
        app.logger.error(f"Error in /camera/facial/single_frame: {e}", exc_info=True)
        abort(500)

@app.route("/camera/recognize_vehicle", methods=["GET"])
def recognize_vehicle_endpoint():
    try:
        frame = camera_vehicle.read_frame()
        if frame is None:
            return jsonify({"error": "Could not get frame from vehicle camera"}), 500
        
        return jsonify({"message": "Vehicle recognition triggered with vehicle camera."})
    except Exception as e:
        app.logger.error(f"Error in /camera/recognize_vehicle: {e}", exc_info=True)
        abort(500, description=str(e))

@app.route("/camera/source", methods=["POST"])
def set_camera_source_endpoint():
    data = request.get_json()
    camera_type = data.get('camera_type')
    source = data.get('source')
    if not all([camera_type, source]):
        abort(400, description="`camera_type` and `source` are required.")

    try:
        success, message = set_camera_source(camera_type, source)
        if success:
            return jsonify({"message": message})
        else:
            abort(500, description=message) # This might be a 500 from an internal error in set_camera_source
    except Exception as e:
        app.logger.error(f"Error in /camera/source: {e}", exc_info=True)
        abort(500, description=str(e))

@app.route("/authenticate/face", methods=["POST"])
def authenticate_face_endpoint():
    temp_frame_path = None
    if 'file' not in request.files:
        abort(400, description="No file part")
    file = request.files['file']
    if file.filename == '':
        abort(400, description="No selected file")

    visitor_id = request.form.get('visitor_id')
    if not visitor_id:
        abort(400, description="Visitor ID is required for authentication")

    try:
        with tempfile.NamedTemporaryFile(delete=False, suffix=".jpg") as temp_frame:
            content = file.read()
            temp_frame.write(content)
            temp_frame_path = temp_frame.name

        db_connection = get_db_connection()
        selfie_path = None
        visitor_name = None
        try:
            with db_connection.cursor() as cursor:
                sql = "SELECT vr.selfie_photo_path, vr.first_name, vr.last_name FROM visitation_requests vr WHERE vr.id = %s"
                cursor.execute(sql, (visitor_id,))
                result = cursor.fetchone()
                if result:
                    selfie_path = result['selfie_photo_path']
                    visitor_name = f"{result['first_name']} {result['last_name']}"
        finally:
            if db_connection:
                db_connection.close()

        if not selfie_path or not visitor_name:
            # Removed os.unlink(temp_frame_path) here as it's in the finally block
            return jsonify({"success": False, "message": "Visitor not found or no selfie available."})

        absolute_selfie_path = os.path.join(project_root, selfie_path)

        if not os.path.exists(absolute_selfie_path):
            app.logger.error(f"Visitor selfie file not found at path: {absolute_selfie_path}")
            return jsonify({"success": False, "message": "Visitor selfie file not found."})

        from deepface import DeepFace
        result = DeepFace.verify(
            img1_path=temp_frame_path,
            img2_path=absolute_selfie_path,
            model_name="Facenet512",
            detector_backend="mtcnn",
            enforce_detection=False
        )

        if result.get('verified'):
            return jsonify({"success": True, "message": "Authentication successful", "visitor_id": visitor_id})
        else:
            return jsonify({"success": False, "message": "Face authentication failed: Face does not match visitor's selfie."})

    except Exception as e:
        app.logger.error(f"Error in /authenticate/face for visitor_id {request.form.get('visitor_id')}: {e}", exc_info=True)
        if "Face could not be detected" in str(e):
            return jsonify({"success": False, "message": "Face authentication failed: No face detected in captured image."})
        abort(500, description=f"An internal error occurred during face authentication: {e}")
    finally:
        if temp_frame_path and os.path.exists(temp_frame_path):
            os.unlink(temp_frame_path)

def process_face_registration_in_background(session_token, absolute_file_path, relative_file_path):
    with app.app_context():
        try:
            app.logger.info(f"Background registration started for token: {session_token}")
            from app.services.face_recog.face_authenticator import register_visitor
            register_visitor(session_token, absolute_file_path)

            db_connection = get_db_connection()
            try:
                with db_connection.cursor() as cursor:
                    sql = "INSERT INTO visitor_sessions (user_token, selfie_photo_path, expires_at) VALUES (%s, %s, NOW() + INTERVAL 1 HOUR) ON DUPLICATE KEY UPDATE selfie_photo_path = VALUES(selfie_photo_path)"
                    cursor.execute(sql, (session_token, relative_file_path))
                db_connection.commit()
                app.logger.info(f"Background registration completed for token: {session_token}")
            finally:
                if db_connection:
                    db_connection.close()
        except Exception as e:
            app.logger.error(f"Error in background face registration for token {session_token}: {e}", exc_info=True)

@app.route("/register/face", methods=["POST"])
def register_face_endpoint():
    session_token = request.form.get('session_token')
    if not session_token:
        abort(400, description="Session token is required.")

    if 'file' not in request.files:
        abort(400, description="No file part")
    file = request.files['file']
    if file.filename == '':
        abort(400, description="No selected file")

    try:
        relative_upload_dir = "php/routes/Pages/uploads/selfies"
        absolute_upload_dir = os.path.join(project_root, relative_upload_dir)
        os.makedirs(absolute_upload_dir, exist_ok=True)
        file_extension = file.filename.split(".")[-1] if "." in file.filename else "jpg"
        file_name = f"{session_token}.{file_extension}"
        absolute_file_path = os.path.join(absolute_upload_dir, file_name)
        relative_file_path = f"{relative_upload_dir}/{file_name}"

        with open(absolute_file_path, "wb") as buffer:
            content = file.read()
            buffer.write(content)

        thread = threading.Thread(
            target=process_face_registration_in_background,
            args=(session_token, absolute_file_path, relative_file_path)
        )
        thread.start()
        
        return jsonify({"message": "Face registration is processing in the background.", "file_path": relative_file_path})
    except Exception as e:
        app.logger.error(f"Failed to register face: {e}", exc_info=True)
        abort(500, description=f"Failed to register face: {str(e)}")

@app.route("/register/from_selfie", methods=["POST"])
def register_from_selfie_endpoint():
    data = request.get_json()
    visitor_id = data.get('visitor_id') # Keep visitor_id for potential future use
    visitor_name = data.get('visitor_name')
    selfie_path = data.get('selfie_path')

    if not all([visitor_id, visitor_name, selfie_path]):
        abort(400, description="Visitor ID and selfie_path are required.")
    
    try:
        absolute_selfie_path = os.path.join(project_root, selfie_path)

        if not os.path.exists(absolute_selfie_path):
            return jsonify({"message": f"Selfie file not found at: {absolute_selfie_path}"}), 404

        from app.services.face_recog.face_authenticator import register_visitor
        success, message = register_visitor(visitor_name, absolute_selfie_path)

        if success:
            return jsonify({"success": True, "message": message})
        else:
            return jsonify({"success": False, "message": message}), 400
    except Exception as e:
        app.logger.error(f"An unexpected error occurred in /register/from_selfie: {e}", exc_info=True)
        return jsonify({"message": f"An unexpected error occurred: {str(e)}"}), 500

@app.route("/register/visitor", methods=["POST"])
def register_visitor_endpoint():
    visitor_id = request.form.get('visitor_id')
    first_name = request.form.get('first_name')

    if not all([visitor_id, first_name]):
        abort(400, description="Visitor ID and first name are required.")

    if 'file' not in request.files:
        abort(400, description="No file part")
    file = request.files['file']
    if file.filename == '':
        abort(400, description="No selected file")

    try:
        relative_upload_dir = "php/routes/Pages/uploads/selfies"
        absolute_upload_dir = os.path.join(project_root, relative_upload_dir)
        os.makedirs(absolute_upload_dir, exist_ok=True)
        file_extension = file.filename.split(".")[-1] if "." in file.filename else "jpg"
        file_name = f"{first_name}.{file_extension}"
        absolute_file_path = os.path.join(absolute_upload_dir, file_name)
        relative_file_path = f"{relative_upload_dir}/{file_name}"

        with open(absolute_file_path, "wb") as buffer:
            content = file.read()
            buffer.write(content)

        from app.services.face_recog.face_authenticator import register_visitor
        success, message = register_visitor(visitor_id, absolute_file_path)

        if success:
            return jsonify({"message": message, "file_path": relative_file_path})
        else:
            return jsonify({"message": message}), 400
    except Exception as e:
        app.logger.error(f"Failed to register visitor: {e}", exc_info=True)
        abort(500, description=f"Failed to register visitor: {str(e)}")

@app.route("/camera/recognize_and_compare_plate", methods=["POST"])
def recognize_and_compare_plate():
    data = request.get_json()
    expected_plate = data.get('expected_plate_number')
    if not expected_plate:
        abort(400, description="Expected plate number is required.")

    try:
        frame = camera_vehicle.read_frame()
        if frame is None:
            abort(500, description="Could not capture frame from vehicle camera.")

        relative_output_dir = "php/routes/Pages/uploads/vehicle_captures"
        absolute_output_dir = os.path.join(project_root, relative_output_dir)
        os.makedirs(absolute_output_dir, exist_ok=True)
        timestamp = time.strftime("%Y%m%d-%H%M%S")
        filename = f"{timestamp}_capture.jpg"
        filepath = os.path.join(absolute_output_dir, filename)
        cv2.imwrite(filepath, frame)

        ret, buffer = cv2.imencode('.jpg', frame)
        if not ret:
            abort(500, description="Could not encode frame.")
        
        image_bytes = buffer.tobytes()
        recognition_result = detect_vehicle_plate(image_bytes)
        
        if "error" in recognition_result:
            return jsonify({"match": False, "message": f"Plate recognition failed: {recognition_result['error']}"}), 400

        recognized_plate = recognition_result.get('license_plate_number')
        if recognized_plate is None:
            return jsonify({"match": False, "recognized_plate": "Not found", "message": "Could not detect a license plate."})

        import re
        # Re-assert robust normalization: remove all non-alphanumeric characters and convert to uppercase
        # This ensures matching is insensitive to spaces, hyphens, or any other symbols
        normalized_recognized = re.sub(r'[^a-zA-Z0-9]', '', recognized_plate).upper()
        normalized_expected = re.sub(r'[^a-zA-Z0-9]', '', expected_plate).upper()
        match = (normalized_recognized == normalized_expected)
        
        return jsonify({"match": match, "recognized_plate": recognized_plate})
    except Exception as e:
        app.logger.error(f"Error in /camera/recognize_and_compare_plate: {e}", exc_info=True)
        abort(500, description=str(e))

@app.route("/camera/vehicle/capture", methods=["POST"])
def capture_vehicle_image():
    try:
        frame = camera_vehicle.read_frame()
        if frame is None:
            abort(500, description="Could not capture frame from vehicle camera.")

        relative_output_dir = "php/routes/Pages/uploads/vehicle_captures"
        absolute_output_dir = os.path.join(project_root, relative_output_dir)
        os.makedirs(absolute_output_dir, exist_ok=True)
        timestamp = time.strftime("%Y%m%d-%H%M%S")
        filename = f"{timestamp}_vehicle_capture.jpg"
        filepath = os.path.join(absolute_output_dir, filename)
        cv2.imwrite(filepath, frame)

        return jsonify({"message": "Vehicle image captured successfully.", "filepath": os.path.join(relative_output_dir, filename)})
    except Exception as e:
        app.logger.error(f"Error in /camera/vehicle/capture: {e}", exc_info=True)
        abort(500, description=str(e))

if __name__ == '__main__':
    # Pre-load models before starting the server
    preload_models()

    import ssl

    # Create SSL context
    context = ssl.SSLContext(ssl.PROTOCOL_TLS)

    # Load your Let's Encrypt certificate + private key
    context.load_cert_chain(
        '/etc/letsencrypt/live/isecured.online/fullchain.pem',
        '/etc/letsencrypt/live/isecured.online/privkey.pem'
    )

    # Run Flask with HTTPS manually
    app.run(
        host='0.0.0.0',
        port=8000,
        debug=True,
        ssl_context=context
    )
