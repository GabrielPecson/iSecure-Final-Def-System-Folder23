import argparse
import os
import tempfile
import json
from mindee import ClientV2, InferenceParameters, PathInput
from dateutil import parser as date_parser
import re

def call_mindee_api(image_path, api_key, model_id):
    """Send image to Mindee OCR API."""
    # Init a new client
    mindee_client = ClientV2(api_key)

    # Set inference parameters
    params = InferenceParameters(
        model_id=model_id,
        rag=None,
        raw_text=True,
        polygon=None,
        confidence=None,
    )

    # Load a file from disk
    input_source = PathInput(image_path)

    # Send for processing using polling
    response = mindee_client.enqueue_and_get_inference(input_source, params)

    return response

def extract_id_info(image_bytes):
    """
    Main function to be called by the API.
    Takes image bytes, saves to a temporary file, and processes with Mindee.
    """
    # Hardcoded credentials for Mindee
    api_key = "md_wSasrvkkiuFg06GG7bY1X8TI0PxHAEZD"
    model_id = "8beb1990-b527-4e62-bbab-73bc0d4fae3c"

    temp_file_path = None
    try:
        # Create a temporary file to store the image bytes
        with tempfile.NamedTemporaryFile(delete=False, suffix=".jpg") as temp_file:
            temp_file.write(image_bytes)
            temp_file_path = temp_file.name

        # Call Mindee API with the path to the temporary file
        response = call_mindee_api(temp_file_path, api_key, model_id)

        # Extract fields from the response
        extracted, detected_type = extract_fields(response)

        return {"success": True, "id_type": detected_type, "data": extracted}

    except Exception as e:
        return {"success": False, "message": str(e)}
    finally:
        # Clean up the temporary file
        if temp_file_path and os.path.exists(temp_file_path):
            os.unlink(temp_file_path)

def extract_phil_id_fields(raw_text):
    """Extract fields specific to Philippine National ID (PhilSys)."""
    extracted = {}

    # Use non-greedy matching and stop at next label
    extracted['last_name'] = extract_from_text(raw_text, r'Apelyido/Last Name\s*\n(.*?)\s*Mga Pangalan/Given Names', 'last_name')
    extracted['first_name'] = extract_from_text(raw_text, r'Mga Pangalan/Given Names\s*\n(.*?)\s*Gitnang Apelyido/Middle Name', 'first_name')
    extracted['middle_name'] = extract_from_text(raw_text, r'Gitnang Apelyido/Middle Name\s*\n(.*?)\s*Petsa ng Kapanganakan/Date of Birth', 'middle_name')
    extracted['date_of_birth'] = extract_from_text(raw_text, r'Petsa ng Kapanganakan/Date of Birth\s*\n(.*?)\s*Tirahan/Address', 'date_of_birth')
    extracted['address'] = extract_from_text(raw_text, r'Tirahan/Address\s*\n(.*)', 'address')

    # For ID number, look for number after "Philippine Identification Card"
    id_match = re.search(r'Philippine Identification Card\s*\n(\d{4}-\d{4}-\d{4}-\d{4})', raw_text, re.IGNORECASE)
    extracted['id_number'] = id_match.group(1) if id_match else ''

    return extracted

def extract_umid_fields(raw_text):
    """Extract fields specific to Unified Multi-Purpose ID (UMID)."""
    extracted = {}
    lines = raw_text.split('\n')
    for i, line in enumerate(lines):
        line = line.strip()
        if line.lower() == 'surname':
            extracted['last_name'] = lines[i+1].strip() if i+1 < len(lines) else ''
        elif line.lower() == 'given name':
            extracted['first_name'] = lines[i+1].strip() if i+1 < len(lines) else ''
        elif line.lower() == 'middle name':
            extracted['middle_name'] = lines[i+1].strip() if i+1 < len(lines) else ''
        elif line.lower() == 'date of birth':
            extracted['date_of_birth'] = lines[i+1].strip() if i+1 < len(lines) else ''
        elif line.lower() == 'address':
            # Collect all lines until next label or end
            address_lines = []
            j = i + 1
            while j < len(lines) and not any(label in lines[j].lower() for label in ['id number', 'sex', 'signature']):
                if lines[j].strip():
                    address_lines.append(lines[j].strip())
                j += 1
            extracted['address'] = ' '.join(address_lines)

    # For ID number, SSS number pattern
    id_match = re.search(r'(\d{2}-\d{7}-\d{1})', raw_text)
    extracted['id_number'] = id_match.group(1) if id_match else ''

    return extracted

def extract_driver_license_fields(raw_text):
    """Extract fields specific to Philippine Driver's License."""
    # Debug: print raw text
    # print("DEBUG: Raw text for driver's license:")
    # print(raw_text)
    # print("DEBUG: End raw text")

    extracted = {}

    # Extract name from the line after "Last Name. First Name, Middle Name" or "Last Name, First Name. Middle Name"
    name_match = re.search(r'Last Name[\.,] First Name[\.,] Middle Name\s*\n(.+)', raw_text, re.IGNORECASE)
    if name_match:
        full_name = name_match.group(1).strip()
        # Split by comma, assuming format: LASTNAME, FIRSTNAME MIDDLENAME
        parts = [p.strip() for p in full_name.split(',')]
        if len(parts) >= 2:
            extracted['last_name'] = parts[0]
            name_parts = parts[1].split()
            if name_parts:
                # Differentiate first name and middle name
                # If there are 3 or more parts and the second last part is a preposition like 'de', treat last two as middle name
                if len(name_parts) >= 3 and name_parts[-2].lower() in ['de', 'del', 'dela', 'van', 'von']:
                    extracted['first_name'] = ' '.join(name_parts[:-2])
                    extracted['middle_name'] = ' '.join(name_parts[-2:])
                else:
                    # Default: last word is middle name, rest is first name
                    extracted['first_name'] = ' '.join(name_parts[:-1]) if len(name_parts) > 1 else name_parts[0]
                    extracted['middle_name'] = name_parts[-1] if len(name_parts) > 1 else ''

    # Extract date of birth from the line after "Nationality Sex Date of Birth Weight (kg) Height(m)"
    dob_match = re.search(r'Nationality Sex Date of Birth Weight \(kg\) Height\(m\)\s*\n(.+)', raw_text, re.IGNORECASE)
    if dob_match:
        info_line = dob_match.group(1).strip()
        # Split by spaces, date is the third element
        parts = info_line.split()
        if len(parts) >= 3:
            extracted['date_of_birth'] = parts[2]

    # Extract address
    address_match = re.search(r'Address\s*\n(.+?)(?=\nLicense No\.|\n[A-Z]{1,2}\d{2}-\d{2}-\d{6})', raw_text, re.IGNORECASE | re.DOTALL)
    if address_match:
        extracted['address'] = re.sub(r'\s+', ' ', address_match.group(1).strip())

        # Clean address if it contains date/sex info from OCR errors
        if 'date of birth' in extracted['address'].lower() or 'sex' in extracted['address'].lower():
            date_match = re.search(r'(\d{4}/\d{2}/\d{2})', extracted['address'])
            if date_match:
                extracted['address'] = extracted['address'][date_match.end():].strip()
                # Remove leading numbers (weight) and dots
                extracted['address'] = re.sub(r'^\d+(\.\d+)?\s*', '', extracted['address'])

    # For ID number, license number pattern
    id_match = re.search(r'([A-Z]\d{2}-\d{2}-\d{6})', raw_text)
    extracted['id_number'] = id_match.group(1) if id_match else ''

    return extracted

def detect_id_type(raw_text):
    """Automatically detect the ID type from raw text."""
    if re.search(r'Philippine Identification Card', raw_text, re.IGNORECASE):
        return 'phil_id'
    elif re.search(r'Unified Multi-Purpose ID', raw_text, re.IGNORECASE) or re.search(r'\d{2}-\d{7}-\d{1}', raw_text):
        return 'umid'
    elif re.search(r'DRIVER\'S LICENSE', raw_text, re.IGNORECASE) or re.search(r'License No\.', raw_text, re.IGNORECASE):
        return 'driver_license'
    else:
        return 'unknown'

def extract_fields(response, id_type=None):
    """Extract relevant fields from raw text in Mindee response."""
    # Get raw text from result
    raw_text_obj = response.inference.result.raw_text
    raw_text = raw_text_obj.content if hasattr(raw_text_obj, 'content') else str(raw_text_obj)

    if id_type is None:
        id_type = detect_id_type(raw_text)

    if id_type == 'phil_id':
        extracted = extract_phil_id_fields(raw_text)
    elif id_type == 'umid':
        extracted = extract_umid_fields(raw_text)
    elif id_type == 'driver_license':
        extracted = extract_driver_license_fields(raw_text)
    else:
        extracted = {}

    # Other fields not mentioned, set to empty
    extracted['nationality'] = ''
    extracted['issuing_authority'] = ''

    # Confidence - placeholder
    extracted['confidence'] = 0.0

    return extracted, id_type

def extract_from_text(text, pattern, field_name):
    """Extract field using regex from OCR text."""
    match = re.search(pattern, text, re.IGNORECASE | re.DOTALL)
    if match:
        value = match.group(1).strip()
        # For address, replace multiple spaces/newlines with single space
        if field_name == 'address':
            value = re.sub(r'\s+', ' ', value)
        return value
    return ''

def extract_date_from_text(text, keyword):
    """Extract date from text near a keyword."""
    # Simple date pattern
    date_pattern = r'\b(\d{1,2}[/-]\d{1,2}[/-]\d{2,4})\b'
    lines = text.split('\n')
    for line in lines:
        if keyword.lower() in line.lower():
            match = re.search(date_pattern, line)
            if match:
                return normalize_date(match.group(1))
    return ''

def normalize_date(date_str):
    """Normalize date to YYYY-MM-DD format."""
    if not date_str:
        return ''
    try:
        parsed = date_parser.parse(date_str)
        return parsed.strftime('%Y-%m-%d')
    except:
        return date_str

def print_output(extracted, id_type):
    """Print extracted information in formatted way."""
    print("------------------------------")
    if id_type == 'driver_license':
        print("ID TYPE: Philippine Driver's License")
    elif id_type == 'umid':
        print("ID TYPE: Unified Multi-Purpose ID (UMID)")
    else:
        print("ID TYPE: Philippine National ID (PhilSys)")
    print("------------------------------")

    # Only print the required fields
    required_fields = ['last_name', 'first_name', 'middle_name', 'date_of_birth', 'address', 'id_number']
    for key in required_fields:
        if key in extracted:
            label = key.replace('_', ' ').title()
            # Clean value by replacing newlines with spaces and stripping
            clean_value = extracted[key].replace('\n', ' ').strip()
            print(f"{label}: {clean_value}")

def save_json(extracted, image_path, id_type):
    """Save extracted data as JSON."""
    # Create subfolder based on id_type
    subfolder = id_type
    os.makedirs(subfolder, exist_ok=True)

    base_name = os.path.splitext(os.path.basename(image_path))[0]
    json_path = os.path.join(subfolder, f"{base_name}.json")

    data = {
        "id_type": id_type,
        **extracted
    }

    with open(json_path, 'w') as f:
        json.dump(data, f, indent=2)

    print(f"Saved as {json_path}")

def main():
    parser = argparse.ArgumentParser(description="ID Scanner using Mindee OCR API")
    parser.add_argument('--file', help='Path to the ID image (optional, if not provided, scans all in ID\' Data for ocr folder)')
    parser.add_argument('--type', choices=['driver_license', 'phil_id', 'umid'], help='Type of ID (optional, auto-detect if not provided)')
    parser.add_argument('--no-save', action='store_true', help='Do not save output as JSON (default is to save)')
    parser.add_argument('--json-output', action='store_true', help='Output extracted data as JSON to stdout and suppress other output.')

    args = parser.parse_args()

    folder_path = "ID' Data for ocr"
    image_extensions = ('.png', '.jpg', '.jpeg', '.bmp', '.tiff')

    if args.file:
        # Single file mode
        files_to_scan = [args.file]
    else:
        # Scan all images in the folder
        if not os.path.exists(folder_path):
            print(f"Error: Folder '{folder_path}' does not exist.")
            return
        files_to_scan = [os.path.join(folder_path, f) for f in os.listdir(folder_path) if f.lower().endswith(image_extensions)]

    if not files_to_scan:
        print("No image files found to scan.")
        return

    # Hardcoded credentials for Mindee
    api_key = "md_wSasrvkkiuFg06GG7bY1X8TI0PxHAEZD"
    model_id = "8beb1990-b527-4e62-bbab-73bc0d4fae3c"

    for file_path in files_to_scan:
        try:
            # Call API
            response = call_mindee_api(file_path, api_key, model_id)

            # Extract fields
            extracted, detected_type = extract_fields(response, args.type)

            # Use detected type if not provided
            id_type = args.type if args.type else detected_type

            if args.json_output:
                # If --json-output is specified, print JSON and exit
                print(json.dumps({"success": True, "id_type": id_type, "data": extracted}))
                return # Exit after first file in JSON output mode

            # Print output
            print(f"Scanning {file_path}...")
            print_output(extracted, id_type)

            # Save by default unless --no-save is specified
            if not args.no_save:
                save_json(extracted, file_path, id_type)

        except Exception as e:
            if args.json_output:
                print(json.dumps({"success": False, "message": f"Error scanning {file_path}: {str(e)}"}))
                return # Exit after first file in JSON output mode
            else:
                print(f"Error scanning {file_path}: {str(e)}")
        if not args.json_output: # Only print blank line if not in JSON output mode
            print()  # Blank line between scans

if __name__ == "__main__":
    main()
