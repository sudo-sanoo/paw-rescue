<?php
// app/auth/register.php
session_start();
require_once __DIR__ . '/../includes/db.php';

function normalizePhoneToPlus60($phone) {
    // Remove everything except digits and plus
    $p = preg_replace('/[^\d\+]/', '', $phone);
    $digits = ltrim($p, '+');

    if (strpos($digits, '60') === 0) {
        return '+' . $digits; // Already in +60 format
    }
    
    if (strpos($digits, '0') === 0) {
        return '+60' . substr($digits, 1); // Leading 0 -> +60
    }

    if (preg_match('/^1[0-9]{7,8}$/', $digits)) {
        return '+60' . $digits; // User types just 123456789
    }

    return null; // Invalid format
}

function isValidMYPhonePlus60($phonePlus) {
    $digits = ltrim($phonePlus, '+');
    return (bool) preg_match('/^60(1[0-9]{7,8})$/', $digits);
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
    $_SESSION['auth_message'] = 'Account created successfully! Redirecting to dashboard...';
    header('Location: ../templates/member_dashboard.php');
    exit;

} catch (Exception $e) {
    $_SESSION['auth_status'] = 'error';
    $_SESSION['auth_message'] = 'Registration failed. Please try again later.';
    header('Location: ../index.php#auth');
    exit;
}
