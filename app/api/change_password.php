<?php
// app/api/change_password.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['success'=>false,'error'=>'Method not allowed']); exit;
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); echo json_encode(['success'=>false,'error'=>'Not authenticated']); exit;
}
$user_id = $_SESSION['user_id'];

$new_password = $_POST['new_password'] ?? '';
if (!$new_password) {
    http_response_code(422); echo json_encode(['success'=>false,'error'=>'Password required']); exit;
}
// same strength: min 8 chars, 1 uppercase, 1 number, 1 special
if (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $new_password)) {
    http_response_code(422); echo json_encode(['success'=>false,'error'=>'Password must be at least 8 characters long, contain at least one uppercase letter, one number, and one special character.']); exit;
}

$hash = password_hash($new_password, PASSWORD_DEFAULT);
try {
    $stmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ? LIMIT 1");
    $stmt->bind_param('ss', $hash, $user_id);
    $ok = $stmt->execute();
    $stmt->close();
    if (!$ok) {
        http_response_code(500); echo json_encode(['success'=>false,'error'=>'Failed to update password']); exit;
    }
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['success'=>false,'error'=>'Database error']); exit;
}

echo json_encode(['success'=>true, 'message'=>'Password updated']);
exit;
