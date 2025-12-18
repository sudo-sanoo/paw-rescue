<?php
// api/update_rescuer_location.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helper_func.php';
require_once __DIR__ . '/../includes/session_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

$emergency_id = $_POST['emergency_id'] ?? null;
$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;

if (!$emergency_id || !$lat || !$lng) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

// Update the rescuer's live location
$stmt = $conn->prepare("UPDATE emergencies SET rescuer_lat = ?, rescuer_lng = ? WHERE emergency_id = ?");
$stmt->bind_param("dds", $lat, $lng, $emergency_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'DB Error']);
}
?>