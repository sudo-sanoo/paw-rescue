<?php
// api/update_mission_status.php
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helper_func.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$emergency_id = $_POST['emergency_id'] ?? null;
$new_status = $_POST['status'] ?? null;
$vet_id = $_POST['vet_id'] ?? null; // <--- NEW PARAMETER

// 1. Validation
if (!$emergency_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$allowed_statuses = ['transporting', 'treating', 'treated'];
if (!in_array($new_status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status transition']);
    exit;
}

// 2. Update Database
$sql = "";
$stmt = null;

// LOGIC: If handing off to vet ('treating'), we must update vet_treatment column
if ($new_status === 'treating' && $vet_id) {
    $sql = "UPDATE emergencies 
            SET status = ?, vet_treatment = ?, updated_at = NOW() 
            WHERE emergency_id = ? AND rescuer_transport = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $new_status, $vet_id, $emergency_id, $user_id);
} else {
    // Standard update for other statuses
    $sql = "UPDATE emergencies 
            SET status = ?, updated_at = NOW() 
            WHERE emergency_id = ? AND rescuer_transport = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $new_status, $emergency_id, $user_id);
}

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    // Determine if it failed or if row just wasn't changed
    if ($stmt->errno) {
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $stmt->error]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Status updated (No changes made or ID mismatch)']);
    }
}
?>