<?php
// process_mykad.php
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helper_func.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}
$user_id = $_SESSION['user_id'];

// Helper: save base64 to file, return relative filepath or false
function save_base64_image($base64_raw, $destPath) {
    $decoded = base64_decode($base64_raw);
    if ($decoded === false) return false;
    if (!file_put_contents($destPath, $decoded)) return false;
    return true;
}

// Get POST values
$front_b64 = isset($_POST['mykad_front_base64']) ? trim($_POST['mykad_front_base64']) : '';
$back_b64  = isset($_POST['mykad_back_base64']) ? trim($_POST['mykad_back_base64']) : '';

// Optional: other form fields (experience, certifications, etc.)
// For example:
$experience = isset($_POST['experience']) ? $_POST['experience'] : null;

// Prepare upload directory: app/images/uploads/mykad/{user_id}/
$uploadBase = __DIR__ . '/../images/uploads/mykad/';
$userDir = $uploadBase . $user_id . '/';
if (!file_exists($userDir)) {
    if (!mkdir($userDir, 0775, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to create upload directory']);
        exit;
    }
}

$frontFilename = null;
$backFilename = null;

if ($front_b64 !== '') {
    $frontFilename = "front.jpg";
    $frontPath = $userDir . $frontFilename;
    if (!save_base64_image($front_b64, $frontPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save front image']);
        exit;
    }
}

if ($back_b64 !== '') {
    $backFilename = "back.jpg";
    $backPath = $userDir . $backFilename;
    if (!save_base64_image($back_b64, $backPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save back image']);
        exit;
    }
}

// Insert an application record into `rescuer_applications`. If you prefer update existing application, you can change logic.
$conn->autocommit(false);
try {
    // Ensure table exists. We will attempt to insert.
    $stmt = $conn->prepare("INSERT INTO rescuer_applications (user_id, mykad_front, mykad_back, experience, status) VALUES (?, ?, ?, ?, ?)");
    $status = 'pending';
    $stmt->bind_param("issss", $user_id, $frontFilename, $backFilename, $experience, $status);
    if (!$stmt->execute()) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'DB insert failed: ' . $stmt->error]);
        exit;
    }
    $stmt->close();
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Application saved']);
    exit;
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
    exit;
}
