<?php
// app/api/update_profile.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Must be authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}
$user_id = $_SESSION['user_id'];

// --- Helpers ---
function normalizePhoneToPlus60($phone) {
    $p = preg_replace('/[^\d\+]/', '', $phone);
    $digits = ltrim($p, '+');
    if (strpos($digits, '60') === 0) return '+' . $digits;
    if (strpos($digits, '0') === 0) return '+60' . substr($digits, 1);
    if (preg_match('/^1[0-9]{7,8}$/', $digits)) return '+60' . $digits;
    return null;
}
function isValidMYPhonePlus60($phonePlus) {
    $digits = ltrim($phonePlus, '+');
    return (bool) preg_match('/^60(1[0-9]{7,8})$/', $digits);
}

// --- Read input ---
$name = trim($_POST['full_name'] ?? '');
$phone_input = trim($_POST['phone'] ?? '');
$email_raw = trim($_POST['email'] ?? '');
$avatar_data = $_POST['avatar'] ?? ''; // dataURL if present
$avatar_changed = isset($_POST['avatar_changed']) && $_POST['avatar_changed'] === '1';
$remove_avatar = isset($_POST['remove_avatar']) && $_POST['remove_avatar'] === '1';
$original_avatar = trim($_POST['original_avatar'] ?? ''); // server-side relative path

// --- Validation ---
$errors = [];
if ($name === '') $errors[] = 'Full name cannot be empty';
if ($phone_input === '') $errors[] = 'Phone cannot be empty';

// normalize phone
$phone_normalized = normalizePhoneToPlus60($phone_input);
if ($phone_normalized === null || !isValidMYPhonePlus60($phone_normalized)) {
    $errors[] = 'Invalid Malaysian phone number';
}

// email optional
$email = ($email_raw === '') ? null : $email_raw;
if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// --- Check phone uniqueness ---
$stmt = $conn->prepare("SELECT user_id FROM users WHERE phone = ? AND user_id != ? LIMIT 1");
$stmt->bind_param('ss', $phone_normalized, $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => 'Phone number already used by another account']);
    exit;
}
$stmt->close();

// --- Handle avatar ---
$uploadDir = __DIR__ . '/../images/uploads/avatars';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$profile_photo_path = null;

// Remove avatar if requested
if ($remove_avatar && $original_avatar) {
    $prev = realpath(__DIR__ . '/../' . $original_avatar);
    $uploadsRoot = realpath($uploadDir);
    if ($prev && $uploadsRoot && strpos($prev, $uploadsRoot) === 0 && is_file($prev)) {
        @unlink($prev);
    }
    $profile_photo_path = null;
}

// Handle new avatar
if ($avatar_changed && $avatar_data && strpos($avatar_data, 'data:') === 0) {
    if (!preg_match('/^data:(image\/(png|jpeg|jpg));base64,(.+)$/', $avatar_data, $m)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Unsupported avatar image format']);
        exit;
    }
    $ext = $m[2] === 'jpeg' ? 'jpg' : $m[2];
    $decoded = base64_decode($m[3]);
    if ($decoded === false) {
        http_response_code(422);
        echo json_encode(['success' => false, 'error' => 'Failed to decode avatar image']);
        exit;
    }
    if (strlen($decoded) > 4 * 1024 * 1024) {
        http_response_code(413);
        echo json_encode(['success' => false, 'error' => 'Avatar image too large (max 4MB)']);
        exit;
    }

    $filename = $user_id . '-' . time() . '.' . $ext;
    $filePath = $uploadDir . '/' . $filename;
    if (file_put_contents($filePath, $decoded) === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save avatar image']);
        exit;
    }

    $profile_photo_path = 'images/uploads/avatars/' . $filename;

    // Delete old avatar if exists
    if ($original_avatar) {
        $prev = realpath(__DIR__ . '/../' . $original_avatar);
        if ($prev && strpos($prev, $uploadDir) === 0 && is_file($prev)) {
            if (basename($prev) !== $filename) @unlink($prev);
        }
    }
}

// --- Build update query ---
$fields = ['full_name = ?', 'phone = ?'];
$params = [$name, $phone_normalized];
$types = 'ss';

if ($email !== null) {
    $fields[] = 'email = ?';
    $params[] = $email;
    $types .= 's';
} else {
    $fields[] = 'email = ?';
    $params[] = null;
    $types .= 's';
}

if ($avatar_changed) {
    $fields[] = 'profile_photo = ?';
    $params[] = $profile_photo_path; // can be null
    $types .= 's';
} elseif ($remove_avatar) {
    $fields[] = 'profile_photo = ?';
    $params[] = null;
    $types .= 's';
}


$setClause = implode(', ', $fields);
$sql = "UPDATE users SET $setClause, updated_at = NOW() WHERE user_id = ? LIMIT 1";
$params[] = $user_id;
$types .= 's';

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
    exit;
}
$stmt->close();

// --- Return updated user info ---
echo json_encode([
    'success' => true,
    'user' => [
        'user_id' => $user_id,
        'full_name' => $name,
        'phone' => $phone_normalized,
        'email' => $email,
        'profile_photo' => $profile_photo_path
    ]
]);
exit;
