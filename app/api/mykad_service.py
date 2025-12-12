# Python Service is not required, but kept for future scaling
from flask import Flask, jsonify
import cv2
import base64

app = Flask(__name__)

def get_roi_coords(w, h):
    box_w = int(w * 0.60)
    box_h = int(box_w / 1.58)

    cx, cy = w // 2, h // 2
    tl_x = cx - (box_w // 2)
    tl_y = cy - (box_h // 2)
    br_x = cx + (box_w // 2)
    br_y = cy + (box_h // 2)

    return (tl_x, tl_y, br_x, br_y)

def capture_mykad():
    cap = cv2.VideoCapture(0)

    cap.set(cv2.CAP_PROP_FRAME_WIDTH, 1920)
    cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 1080)

    ret, frame = cap.read()
    cap.release()

    if not ret:
        return None, "Failed to capture frame"

    h, w = frame.shape[:2]
    tl_x, tl_y, br_x, br_y = get_roi_coords(w, h)

    # Crop exactly to the guide box
    roi_crop = frame[tl_y:br_y, tl_x:br_x]

    if roi_crop.size == 0:
        return None, "ROI crop failed"

    # Convert to JPEG → Base64 for web transfer
    _, buffer = cv2.imencode(".jpg", roi_crop)
    encoded = base64.b64encode(buffer).decode("utf-8")

    return encoded, None

@app.get("/capture")
def api_capture():
    base64_img, error = capture_mykad()

    if error:
        return jsonify({"success": False, "error": error}), 500

    return jsonify({
        "success": True,
        "image": base64_img
    })

@app.get("/health")
def health():
    return jsonify({"status": "running"})

# Optional: Just preview (not cropped)
@app.get("/preview")
def api_preview():
    cap = cv2.VideoCapture(0)
    ret, frame = cap.read()
    cap.release()

    if not ret:
        return jsonify({"success": False, "error": "Camera error"}), 500

    _, buffer = cv2.imencode(".jpg", frame)
    encoded = base64.b64encode(buffer).decode("utf-8")

    return jsonify({
        "success": True,
        "image": encoded
    })

if __name__ == "__main__":
    print("MyKad camera service running at http://127.0.0.1:5000")
    print(" - /health")
    print(" - /capture")
    print(" - /preview")
    app.run(host="127.0.0.1", port=5000)
