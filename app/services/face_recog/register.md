   1. Capture Image: On the visitation form's "Facial Scanning" tab, when you click the button (which now functions as a "Register" button), the JavaScript captures the image from the
      webcam.

   2. Send to Backend: The captured image, along with the visitor's ID and first name (taken from the details you are viewing), is sent to a new, specific endpoint I created:
      /register/visitor.

   3. Perform Registration: This endpoint triggers the register_visitor function in face_authenticator.py.
       * The image file is saved in public/uploads/selfies/ and named after the visitor's first name (e.g., John.jpg).
       * The register_visitor function analyzes this image, draws the facial nodes on a copy of the image for verification, extracts the facial embedding, and saves that embedding into
         the app/services/face_recog/visitors.json file, using the visitor's unique ID as the key.