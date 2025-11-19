import re
from mindee import ClientV2, InferenceParameters, PathInput, BytesInput
import json
import os
import sys
import tempfile

def clean_and_format_license_plate(plate_number):
    if not plate_number:
        return None
    
    # First, remove all non-alphanumeric characters and convert to uppercase.
    # This creates a compact, clean version of the plate.
    compact_plate = re.sub(r'[^a-zA-Z0-9]', '', plate_number).upper()

    # Now, try to insert a space if it fits common patterns.
    # Pattern 1: One or more letters followed by one or more digits (e.g., "YEC652" -> "YEC 652")
    match_letters_then_digits = re.match(r'^([A-Z]+)(\d+)$', compact_plate)
    if match_letters_then_digits:
        return f"{match_letters_then_digits.group(1)} {match_letters_then_digits.group(2)}"
    
    # If no specific pattern is matched, return the cleaned compact plate.
    # This avoids inserting incorrect spaces for arbitrary plate formats.
    return compact_plate

def detect_vehicle_plate(image_source):
    """
    Detects vehicle license plate and type from an image.

    :param image_source: Can be a file path (string) or image bytes.
    :return: A dictionary containing the recognized data or an error.
    """
    try:
        # API credentials
        api_key = "md_wSasrvkkiuFg06GG7bY1X8TI0PxHAEZD"
        model_id = "f538247d-0f42-4491-bd0c-3fdd2898ad5f"

        # Init a new client
        mindee_client = ClientV2(api_key)
        params = InferenceParameters(model_id=model_id)

        input_source = None
        if isinstance(image_source, str):
            # If a file path is provided, check if it exists
            if not os.path.exists(image_source):
                return {"error": f"Image file not found at: {image_source}"}
            input_source = PathInput(image_source)
        elif isinstance(image_source, bytes):
            input_source = BytesInput(image_source, "capture.jpg")
        else:
            return {"error": "Invalid image source type. Must be path or bytes."}

        # Send for processing
        response = mindee_client.enqueue_and_get_inference(input_source, params)
        fields = response.inference.result.fields

        # Prepare the result
        result = {
            "license_plate_number": clean_and_format_license_plate(fields.get('license_plate_number').value) if fields.get('license_plate_number') else None,
            "vehicle_type": fields.get('vehicle_type').value if fields.get('vehicle_type') else None
        }
        return result

    except Exception as e:
        return {"error": str(e)}

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No image path provided."}))
        sys.exit(1)

    image_path = sys.argv[1]
    output = detect_vehicle_plate(image_path)
    print(json.dumps(output))
