<?php
// api/get_rescuer_location.php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helper_func.php';
require_once __DIR__ . '/../includes/session_check.php';

header('Content-Type: application/json');

if (!isset($_GET['emergency_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$emergency_id = $_GET['emergency_id'];

// Fetch only the coordinates
$stmt = $conn->prepare("SELECT rescuer_lat, rescuer_lng FROM emergencies WHERE emergency_id = ?");
$stmt->bind_param("s", $emergency_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if ($data && $data['rescuer_lat'] && $data['rescuer_lng']) {
    echo json_encode([
        'success' => true,
        'lat' => (float)$data['rescuer_lat'],
        'lng' => (float)$data['rescuer_lng']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Location not available']);
}
?>