<?php
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$user_id = $_SESSION['user_id'];
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);
$app_id = $data['application_id'] ?? '';

if (empty($app_id)) {
    echo json_encode(['success' => false, 'message' => 'Application ID missing']);
    exit;
}

// Update only if it belongs to this user
$stmt = $conn->prepare("UPDATE rescuer_applications SET is_resolved = 'yes' WHERE application_id = ? AND user_id = ?");
$stmt->bind_param("ss", $app_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
$stmt->close();
?>