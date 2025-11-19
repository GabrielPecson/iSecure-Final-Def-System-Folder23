import cv2

for i in range(5):  # test camera indices 0-4
    # Try DirectShow backend first
    cap = cv2.VideoCapture(i, cv2.CAP_DSHOW)
    if cap.isOpened():
        print(f"Camera {i} works! (DSHOW)")
        cap.release()
    else:
        # Fallback to MSMF
        cap = cv2.VideoCapture(i, cv2.CAP_MSMF)
        if cap.isOpened():
            print(f"Camera {i} works! (MSMF)")
            cap.release()
        else:
            print(f"Camera {i} failed")