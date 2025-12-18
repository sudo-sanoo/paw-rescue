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
// We ensure the user is actually the assigned rescuer (rescuer_transport = user_id)
$sql = "UPDATE emergencies 
        SET status = ?, updated_at = NOW() 
        WHERE emergency_id = ? AND rescuer_transport = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $new_status, $emergency_id, $user_id);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
    // If no rows affected, either ID is wrong, User is wrong, or Status was already set
    echo json_encode(['success' => false, 'message' => 'Update failed. Mission may not exist or you are not assigned.']);
}

$stmt->close();
$conn->close();
?>