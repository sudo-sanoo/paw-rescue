<?php
// process_rescue_application.php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}
$user_id = $_SESSION['user_id'];

// 2. Helper Function: Save Base64
function save_base64($base64_raw, $destPath) {
    // Remove data URI scheme header if present (e.g. "data:image/png;base64,")
    if (strpos($base64_raw, ',') !== false) {
        $base64_raw = explode(',', $base64_raw)[1];
    }
    // Fix whitespace issues that can occur during HTTP transmission
    $base64_raw = str_replace(' ', '+', $base64_raw);
    
    $decoded = base64_decode($base64_raw);
    if ($decoded === false) return false;
    
    return file_put_contents($destPath, $decoded);
}

// 3. Prepare Directory Structure
// Relative path from app/api/ to app/images/uploads/
$baseDir = __DIR__ . '/../images/uploads/';

// Define specific paths per your instructions
$dirs = [
    'mykad'          => $baseDir . 'mykad/' . $user_id . '/',
    'driver_license' => $baseDir . 'driver_license/' . $user_id . '/',
    'signature'      => $baseDir . 'signatures/' . $user_id . '/'
];

// Create directories if they don't exist
foreach ($dirs as $key => $path) {
    if (!file_exists($path)) {
        if (!mkdir($path, 0775, true)) {
            echo json_encode(['success' => false, 'error' => "Failed to create directory for $key"]); 
            exit;
        }
    }
}

// --- 4. Process Inputs ---

// --- Generate application_id (Pattern: APPLyymmXXXXX) ---
$year = date('y');      // 2 digits (e.g., 23)
$month = date('m');     // 2 digits (e.g., 11)
$prefix = 'APPL' . $year . $month; // Result: APPL2311

// Get last sequence for this specific month/year prefix
$stmt = $conn->prepare("SELECT application_id FROM rescuer_applications WHERE application_id LIKE ? ORDER BY application_id DESC LIMIT 1");
$like_pattern = $prefix . '%';
$stmt->bind_param('s', $like_pattern);
$stmt->execute();
$res = $stmt->get_result();
$last_id = $res->fetch_assoc()['application_id'] ?? null;
$stmt->close();

if ($last_id) {
    // "APPL" is 4 chars, "yymm" is 4 chars. Total prefix length is 8.
    // We slice from index 8 to get the number part.
    $last_seq = intval(substr($last_id, 8)); 
    $next_seq = $last_seq + 1;
} else {
    $next_seq = 1;
}

// Pad with zeros to ensures 5 digits (e.g., 00001)
$seq_padded = str_pad($next_seq, 5, '0', STR_PAD_LEFT);
$application_id = $prefix . $seq_padded; // Final: APPL231100001

// B. Process Base64 Images (MyKad & Signature)
$mykad_front_name = null;
$mykad_back_name  = null;
$signature_name   = null;

// Save MyKad Front
if (!empty($_POST['mykad_front_base64'])) {
    $mykad_front_name = "mykad_front_" . time() . ".jpg";
    if (!save_base64($_POST['mykad_front_base64'], $dirs['mykad'] . $mykad_front_name)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save MyKad Front file']); exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing MyKad Front image']); exit;
}

// Save MyKad Back
if (!empty($_POST['mykad_back_base64'])) {
    $mykad_back_name = "mykad_back_" . time() . ".jpg";
    if (!save_base64($_POST['mykad_back_base64'], $dirs['mykad'] . $mykad_back_name)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save MyKad Back file']); exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing MyKad Back image']); exit;
}

// Save Signature
if (!empty($_POST['signature_base64'])) {
    $signature_name = "sig_" . time() . ".png";
    if (!save_base64($_POST['signature_base64'], $dirs['signature'] . $signature_name)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save Signature file']); exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing Signature']); exit;
}

// C. Process Standard File Uploads (Driver's License)
$license_front_name = null;
$license_back_name  = null;

// License Front
if (isset($_FILES['driver_license_front']) && $_FILES['driver_license_front']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['driver_license_front']['name'], PATHINFO_EXTENSION);
    $license_front_name = "license_front_" . time() . "." . $ext;
    if (!move_uploaded_file($_FILES['driver_license_front']['tmp_name'], $dirs['driver_license'] . $license_front_name)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save Driver License Front']); exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing Driver License Front upload']); exit;
}

// License Back
if (isset($_FILES['driver_license_back']) && $_FILES['driver_license_back']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['driver_license_back']['name'], PATHINFO_EXTENSION);
    $license_back_name = "license_back_" . time() . "." . $ext;
    if (!move_uploaded_file($_FILES['driver_license_back']['tmp_name'], $dirs['driver_license'] . $license_back_name)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save Driver License Back']); exit;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing Driver License Back upload']); exit;
}

// D. Process Text / Enum / Boolean Inputs
$license_status     = $_POST['license_status'] ?? 'full'; // Default fallback
$vehicle_avail      = $_POST['vehicle_availability'] ?? 'car';
$experience         = $_POST['animal_handling_experience'] ?? '';
$certifications     = $_POST['training_certifications'] ?? '';
$conviction_details = $_POST['conviction_details'] ?? '';

// Cast to Integer (0 or 1) for TinyInt
$consent    = (int)($_POST['has_background_check_consent'] ?? 0);
$conviction = (int)($_POST['has_prior_conviction'] ?? 0);


// --- 5. Database Insertion ---

$conn->autocommit(false); // Start Transaction
try {
    $sql = "INSERT INTO rescuer_applications (
        application_id, user_id, 
        mykad_front, mykad_back, 
        has_background_check_consent, has_prior_conviction, conviction_details,
        license_status, vehicle_availability, 
        driver_license_front, driver_license_back, 
        animal_handling_experience, training_certifications, 
        signature_image, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

    $stmt = $conn->prepare($sql);
    
    // Bind Parameters
    // Types: s = string, i = integer
    // Order must match the VALUES (?,?,...) exactly
    $stmt->bind_param("ssssiissssssss", 
        $application_id, 
        $user_id,
        $mykad_front_name, 
        $mykad_back_name,
        $consent, 
        $conviction, 
        $conviction_details,
        $license_status, 
        $vehicle_avail,
        $license_front_name, 
        $license_back_name,
        $experience, 
        $certifications,
        $signature_name
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $stmt->close();
    $conn->commit(); // Save changes

    echo json_encode([
        'success' => true, 
        'message' => 'Application submitted successfully',
        'application_id'  => $application_id
    ]);

} catch (Exception $e) {
    $conn->rollback(); // Undo changes on error
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>