// mykad_service.js
// Style : modal popup camera capture, preview, and attach to form hidden inputs.

// ---- Config ----
const PY_CONFIG = {
    // Not used here, use browser camera. Kept for reference.
};

// ---- Modal markup injection ----
(function ensureModalExists(){
    if (document.getElementById("mykad-camera-modal")) return;

    const modalHtml = `
    <div id="mykad-camera-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/90 backdrop-blur-sm transition-opacity duration-300 px-4">
        <div class="bg-white rounded-2xl overflow-hidden shadow-2xl w-full max-w-md flex flex-col max-h-[90vh]">
            
            <!-- Header -->
            <div class="p-4 bg-white border-b border-gray-100 flex items-center justify-between z-10">
                <div class="font-bold text-lg text-gray-800 flex items-center gap-2">
                    <span class="bg-orange-100 text-orange-600 p-1.5 rounded-lg text-sm">
                        <i class="fa-solid fa-camera"></i>
                    </span>
                    Capture Document
                </div>
                <button id="mykad-cancel-btn" class="text-gray-400 hover:text-gray-800 transition p-2 rounded-full hover:bg-gray-100">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <!-- Camera Viewport -->
            <div class="relative bg-black flex-1 flex items-center justify-center overflow-hidden aspect-[4/3] group">
                
                <!-- Flash Animation Layer -->
                <div id="camera-flash" class="flash-effect"></div>

                <!-- Live Video Feed (Object Cover acts as the viewport "window") -->
                <video id="mykad-video" autoplay playsinline class="w-full h-full object-cover"></video>
                
                <!-- Hidden Processing Canvas -->
                <canvas id="mykad-canvas" class="hidden"></canvas>

                <!-- Captured Image Preview (Object Contain to show the whole cropped card) -->
                <img id="mykad-captured-img" src="" class="absolute inset-0 w-full h-full object-contain hidden z-20 bg-black" />

                <!-- Guide Overlay (Visible during capture) -->
                <div id="mykad-guide" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center p-8 z-10">
                    
                    <!-- TARGET FRAME: We use the ID to calculate exact crop coordinates -->
                    <div id="mykad-guide-frame" class="w-full aspect-[1.58/1] border-2 border-white/80 rounded-lg shadow-[0_0_0_9999px_rgba(0,0,0,0.5)] relative">
                        
                        <!-- Corner Accents -->
                        <div class="absolute top-0 left-0 w-4 h-4 border-t-4 border-l-4 border-orange-500 -mt-0.5 -ml-0.5 rounded-tl-sm"></div>
                        <div class="absolute top-0 right-0 w-4 h-4 border-t-4 border-r-4 border-orange-500 -mt-0.5 -mr-0.5 rounded-tr-sm"></div>
                        <div class="absolute bottom-0 left-0 w-4 h-4 border-b-4 border-l-4 border-orange-500 -mb-0.5 -ml-0.5 rounded-bl-sm"></div>
                        <div class="absolute bottom-0 right-0 w-4 h-4 border-b-4 border-r-4 border-orange-500 -mb-0.5 -mr-0.5 rounded-br-sm"></div>
                    </div>

                    <div class="mt-4 px-3 py-1.5 bg-black/60 backdrop-blur-md rounded-full text-white text-xs font-medium">
                        Position card within the frame
                    </div>
                </div>
            </div>

            <!-- Controls / Footer -->
            <div class="p-6 bg-white border-t border-gray-100">
                
                <!-- Capture State Controls -->
                <div id="controls-capture" class="flex flex-col items-center justify-center">
                    <button id="mykad-capture-btn" class="group relative flex items-center justify-center">
                        <div class="w-16 h-16 rounded-full border-4 border-gray-200 group-hover:border-orange-200 transition-colors"></div>
                        <div class="absolute w-12 h-12 bg-orange-600 rounded-full group-hover:scale-90 transition-transform shadow-lg"></div>
                    </button>
                </div>

                <!-- Review State Controls (Hidden by default) -->
                <div id="controls-review" class="hidden flex items-center gap-3">
                    <button id="mykad-retake-btn" class="flex-1 py-2.5 px-4 rounded-xl border border-gray-200 text-gray-600 font-semibold hover:bg-gray-50 transition active:scale-95">
                        Retake
                    </button>
                    <button id="mykad-done-btn" class="flex-1 py-2.5 px-4 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition shadow-lg active:scale-95 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-check text-sm"></i> Use Photo
                    </button>
                </div>

            </div>
        </div>
    </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHtml);

    // Attach Close Event
    document.getElementById('mykad-cancel-btn').addEventListener('click', closeMyKadCamera);
})();

// ---- State ----
let mykadStream = null;
let mykadCurrentSide = null; // 'front' or 'back'

// ---- Camera control functions ----
async function openMyKadCamera(side = 'front') {
    mykadCurrentSide = side;
    
    const modal = document.getElementById('mykad-camera-modal');
    const video = document.getElementById('mykad-video');
    const captureBtn = document.getElementById('mykad-capture-btn');
    const controlsCapture = document.getElementById('controls-capture');
    const controlsReview = document.getElementById('controls-review');
    const previewImg = document.getElementById('mykad-captured-img');
    const guide = document.getElementById('mykad-guide');

    // Reset UI State
    previewImg.classList.add('hidden');
    previewImg.src = '';
    controlsReview.classList.add('hidden');
    controlsCapture.classList.remove('hidden');
    guide.classList.remove('hidden');
    
    // Show Modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    // Start Camera
    try {
        // Prefer rear camera (environment), high resolution
        const constraints = { 
            video: { 
                facingMode: "environment", 
                width: { ideal: 1920 }, 
                height: { ideal: 1080 } 
            }, 
            audio: false 
        };
        mykadStream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = mykadStream;
        await video.play();
    } catch (err) {
        console.error("Camera Error:", err);
        alert("Unable to access camera. Please ensure you have granted camera permissions.");
        closeMyKadCamera();
        return;
    }

    // Hook Event Listeners
    captureBtn.onclick = captureFromVideo;
    
    document.getElementById('mykad-retake-btn').onclick = () => {
        // Reset to capture mode
        previewImg.classList.add('hidden');
        controlsReview.classList.add('hidden');
        controlsCapture.classList.remove('hidden');
        guide.classList.remove('hidden');
        previewImg.src = '';
    };

    document.getElementById('mykad-done-btn').onclick = closeMyKadCamera;
}

function closeMyKadCamera() {
    const modal = document.getElementById('mykad-camera-modal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');

    // Stop stream
    if (mykadStream) {
        mykadStream.getTracks().forEach(t => t.stop());
        mykadStream = null;
    }

    const video = document.getElementById('mykad-video');
    if(video) video.srcObject = null;
}

function captureFromVideo() {
    const video = document.getElementById('mykad-video');
    const guideFrame = document.getElementById('mykad-guide-frame');
    const canvas = document.getElementById('mykad-canvas');
    const previewImg = document.getElementById('mykad-captured-img');
    const controlsCapture = document.getElementById('controls-capture');
    const controlsReview = document.getElementById('controls-review');
    const guide = document.getElementById('mykad-guide');
    const flash = document.getElementById('camera-flash');

    // 1. Flash Effect
    flash.classList.add('animate-flash');
    setTimeout(() => flash.classList.remove('animate-flash'), 300);

    // 2. Math for Accurate Cropping (WYSIWYG)
    // We need to calculate where the Guide Frame sits within the actual Video Stream
    
    // A. Get Dimensions
    const vidW = video.videoWidth;
    const vidH = video.videoHeight;
    const videoRect = video.getBoundingClientRect(); // CSS size of video element
    const guideRect = guideFrame.getBoundingClientRect(); // CSS size of guide frame

    // B. Determine how object-cover is scaling the video
    // object-cover fills the container, cropping either width or height
    const vidRatio = vidW / vidH;
    const elRatio = videoRect.width / videoRect.height;

    let renderW, renderH, scale, offsetX, offsetY;

    if (elRatio > vidRatio) {
        // Container is wider than video aspect -> Video fits Width, Height is cropped
        scale = videoRect.width / vidW;
        renderW = videoRect.width;
        renderH = vidH * scale;
        offsetX = 0;
        offsetY = (renderH - videoRect.height) / 2; // top crop amount in CSS pixels
    } else {
        // Container is taller than video aspect -> Video fits Height, Width is cropped
        scale = videoRect.height / vidH;
        renderH = videoRect.height;
        renderW = vidW * scale;
        offsetX = (renderW - videoRect.width) / 2; // left crop amount in CSS pixels
        offsetY = 0;
    }

    // C. Map Guide Coordinates to Video Source Coordinates
    // 1. Where is the guide relative to the video element top-left?
    const guideX_in_el = guideRect.left - videoRect.left;
    const guideY_in_el = guideRect.top - videoRect.top;

    // 2. Add the offset caused by object-cover cropping (hidden parts of video)
    const guideX_render = guideX_in_el + offsetX;
    const guideY_render = guideY_in_el + offsetY;

    // 3. Convert CSS pixels back to Video Media pixels
    const sourceX = guideX_render / scale;
    const sourceY = guideY_render / scale;
    const sourceW = guideRect.width / scale;
    const sourceH = guideRect.height / scale;

    // 3. Set canvas to the CROP size
    canvas.width = sourceW;
    canvas.height = sourceH;
    const ctx = canvas.getContext('2d');
    
    // 4. Draw ONLY the cropped area
    // ctx.drawImage(image, sx, sy, sWidth, sHeight, dx, dy, dWidth, dHeight)
    ctx.drawImage(video, sourceX, sourceY, sourceW, sourceH, 0, 0, canvas.width, canvas.height);

    // 5. Convert to JPEG
    const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
    previewImg.src = dataUrl;

    // 6. Update Modal UI for Review Mode
    guide.classList.add('hidden');
    previewImg.classList.remove('hidden');
    controlsCapture.classList.add('hidden');
    controlsReview.classList.remove('hidden');

    // 7. Update Form Data Immediately
    updateFormPreview(dataUrl);
}

function updateFormPreview(dataUrl) {
    // Determine elements based on side
    const previewId = mykadCurrentSide === 'front' ? 'mykad-preview-front' : 'mykad-preview-back';
    // NEW: The 'placeholder' is now the box itself
    const boxId = mykadCurrentSide === 'front' ? 'mykad-box-front' : 'mykad-box-back';
    const inputId = mykadCurrentSide === 'front' ? 'mykad_front_base64' : 'mykad_back_base64';

    const previewEl = document.getElementById(previewId);
    const boxEl = document.getElementById(boxId);
    const inputEl = document.getElementById(inputId);

    if(previewEl && boxEl && inputEl) {
        // Set the image src
        previewEl.src = dataUrl;
        // Show the image
        previewEl.classList.remove('hidden');
        // Hide the upload box
        boxEl.classList.add('hidden');
        
        // Store raw base64 (remove data prefix if backend requires just the string)
        inputEl.value = dataUrl.split(',')[1]; 
    }
}

// NOW HANDLED IN member_templates.js :)
// // ---- Submission: collect form + images and post to backend ----
// async function submitVolunteerApplication() {
//     // Get the form element
//     const form = document.getElementById('volunteer-form');

//     // If your form currently prevents default submission, we will submit via fetch here.
//     // Collect the form fields into FormData
//     const fd = new FormData(form);

//     // Ensure images are present (optional check)
//     const front = fd.get('mykad_front_base64');
//     const back = fd.get('mykad_back_base64');

//     // Optional: Validate required fields before sending
//     if (!front || !back) {
//         if (!confirm("You have not captured both front and back of MyKad. Continue submitting anyway?")) {
//             return;
//         }
//     }

//     // Post to backend endpoint
//     try {
//         const resp = await fetch('/app/api/process_mykad.php', {
//             method: 'POST',
//             body: fd,
//             credentials: 'same-origin'
//         });

//         const text = await resp.text();

//         if (!resp.ok) {
//             alert("Upload failed: " + text);
//             return;
//         }

//         // Expect JSON from server ideally. If it's plain text, show it.
//         try {
//             const json = JSON.parse(text);
//             if (json.success) {
//                 alert("Application submitted successfully.");
//                 // Optionally redirect to dashboard or step to confirmation
//                 window.location.href = '/app/templates/member/member_dashboard.php';
//             } else {
//                 alert("Submission error: " + (json.error || text));
//             }
//         } catch (e) {
//             // non-json reply
//             alert("Server response: " + text);
//         }

//     } catch (err) {
//         console.error(err);
//         alert("Failed to submit application. Check your network and try again.");
//     }
// }
