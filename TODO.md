# TODO: Fix Face Authentication in Visitor Modal

## Current Issue
Face authentication in the visitor modal is incorrectly using global face embeddings from visitors.json instead of comparing against the specific visitor's selfie image.

## Tasks
- [x] Modify /authenticate/face endpoint in app/main.py to handle visitor-specific authentication
- [x] Update endpoint to accept visitor_id and fetch selfie path from database
- [x] Use DeepFace.verify to compare captured image against visitor's selfie
- [x] Return appropriate success/failure responses
- [x] Remove "Authenticated as" messages from authentication responses
- [ ] Test authentication in visitor modal
- [ ] Verify error handling for missing selfies or face detection failures

## Files to Edit
- app/main.py: Update /authenticate/face endpoint

## Followup Steps
- Test the authentication functionality
- Ensure it correctly matches against the visitor's selfie
- Verify proper error handling
