<?php
// app/auth/register.php
session_start();
require_once __DIR__ . '/../includes/db.php';

function normalizePhoneToPlus60($phone) {
    // 1. Remove all non-digits except the leading plus
    $p = preg_replace('/[^\d\+]/', '', $phone);
    $digits = ltrim($p, '+');

    // 2. Standardize to raw digits by handling the prefix
    if (strpos($digits, '60') === 0) {
        $raw = substr($digits, 2);
    } elseif (strpos($digits, '0') === 0) {
        $raw = substr($digits, 1);
    } else {
        $raw = $digits;
    }

    // 3. Re-prepend +60 and validate the resulting format
    $final = '+60' . $raw;
    return isValidMYPhonePlus60($final) ? $final : null;
}

function isValidMYPhonePlus60($phonePlus) {
    // Remove the plus to work with digits
    $digits = ltrim($phonePlus, '+');

    /**
     * Regex Breakdown:
     * ^60 - Starts with country code 60
     * (
     *   11\d{8}      | // Mobile: 011 + 8 digits (11 total)
     *   15\d{8}      | // VoIP: 015 + 8 digits (11 total)
     *   1[0,2-4,6-9]\d{7} | // Mobile: 010, 012-014, 016-019 + 7 digits (10 total)
     *   3\d{8}       | // Landline: 03 (Selangor/KL) + 8 digits (10 total)
     *   [4-7,9]\d{7} | // Landline: 04, 05, 06, 07, 09 + 7 digits (9 total)
     *   8[2-9]\d{6}  | // Landline: 082-089 (East MY) + 6 digits (9 total)
     *   81\d{7}        // Fixed Wireless/Others: 081 + 7 digits
     * )
     */
    $pattern = '/^60(11\d{8}|15\d{8}|1[0,2-4,6-9]\d{7}|3\d{8}|[4-7,9]\d{7}|8[2-9]\d{6}|81\d{7})$/';

    return (bool) preg_match($pattern, $digits);
}

$name = trim($_POST['name'] ?? '');
$phone_input = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

// --- Validation ---
if ($name === '' || $phone_input === '' || $password === '') {
    $_SESSION['auth_status'] = 'error';
    $_SESSION['auth_message'] = 'Please fill out all required fields.';
    header('Location: ../index.php#auth');
    exit;
}

if (strlen($name) > 30) {
    $_SESSION['auth_status'] = 'error';
    $_SESSION['auth_message'] = 'Full name cannot exceed 30 characters.';
    header('Location: ../index.php#auth');
    exit;
}

$phone_normalized = normalizePhoneToPlus60($phone_input);
if ($phone_normalized === null || !isValidMYPhonePlus60($phone_normalized)) {
    $_SESSION['auth_status'] = 'error';
    $_SESSION['auth_message'] = 'Please enter a valid Malaysian mobile number.';
    header('Location: ../index.php#auth');
    exit;
}

// Password strength: min 8 chars, at least 1 uppercase, 1 number, 1 special char
$pattern = '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
if (!preg_match($pattern, $password)) {
    $_SESSION['auth_status'] = 'error';
    $_SESSION['auth_message'] = 'Password must be at least 8 characters long, contain at least one uppercase letter, one number, and one special character.';
    header('Location: ../index.php#auth');
    exit;
}

try {
    // Check if phone already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE phone = ?");
    $stmt->bind_param('s', $phone_normalized);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $_SESSION['auth_status'] = 'error';
        $_SESSION['auth_message'] = 'Phone number already registered. Please login or use a different number.';
        $stmt->close();
        header('Location: ../index.php#auth');
        exit;
    }
    $stmt->close();

    // --- Generate user_id ---
    $year = date('y');        // last 2 digits of year
    $month = date('m');       // 2 digits month
    $prefix = $year . 'PAWR' . $month;

    // Get last sequence for this month
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id LIKE ? ORDER BY user_id DESC LIMIT 1");
    $like_pattern = $prefix . '%';
    $stmt->bind_param('s', $like_pattern);
    $stmt->execute();
    $res = $stmt->get_result();
    $last_user_id = $res->fetch_assoc()['user_id'] ?? null;
    $stmt->close();

    if ($last_user_id) {
        $last_seq = intval(substr($last_user_id, 8)); // last 5 digits
        $next_seq = $last_seq + 1;
    } else {
        $next_seq = 1;
    }

    $seq_padded = str_pad($next_seq, 5, '0', STR_PAD_LEFT);
    $user_id = $prefix . $seq_padded;

    // Insert user
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $conn->prepare("INSERT INTO users (user_id, full_name, phone, password_hash) VALUES (?, ?, ?, ?)");
    $insert->bind_param('ssss', $user_id, $name, $phone_normalized, $password_hash);
    $insert->execute();

    // Create session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['full_name'] = $name;
    $_SESSION['phone'] = $phone_normalized;
    $_SESSION['role'] = 'member';

    // Success message
    $_SESSION['auth_status'] = 'success';
    $_SESSION['auth_message'] = 'Account created successfully! Please log in to continue.';
    header('Location: ../index.php#auth');
    exit;

} catch (Exception $e) {
    $_SESSION['auth_status'] = 'error';
    $_SESSION['auth_message'] = 'Registration failed. Please try again later.';
    header('Location: ../index.php#auth');
    exit;
}
