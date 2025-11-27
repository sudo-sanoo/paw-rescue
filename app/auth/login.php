<?php
// app/auth/login.php
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

$phone_input = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

if ($phone_input === '' || $password === '') {
    $_SESSION['auth_status'] = 'error';
    $_SESSION['auth_message'] = 'Please enter both phone number and password.';
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

try {
    $stmt = $conn->prepare("SELECT user_id, full_name, password_hash, role, status FROM users WHERE phone = ?");
    $stmt->bind_param('s', $phone_normalized);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $_SESSION['auth_status'] = 'error';
        $_SESSION['auth_message'] = 'No account found with that phone number.';
        $stmt->close();
        header('Location: ../index.php#auth');
        exit;
    }

    $user = $res->fetch_assoc();
    $stmt->close();

    if ($user['status'] !== 'active') {
        $_SESSION['auth_status'] = 'error';
        $_SESSION['auth_message'] = 'Your account is not active. Contact support.';
        header('Location: ../index.php#auth');
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        $_SESSION['auth_status'] = 'error';
        $_SESSION['auth_message'] = 'Incorrect password. Please try again.';
        header('Location: ../index.php#auth');
        exit;
    }

    // Login success
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['phone'] = $phone_normalized;
    $_SESSION['role'] = $user['role'];

    $_SESSION['auth_status'] = 'success';
    $_SESSION['auth_message'] = 'Login successful! Redirecting to dashboard...';
    if ($user['role'] === 'admin') {
        header('Location: ../templates/admin_dashboard.php');
    } else {
        header('Location: ../templates/member_dashboard.php');
    }
    exit;

} catch (Exception $e) {
    $_SESSION['auth_status'] = 'error';
    $_SESSION['auth_message'] = 'Login failed. Please try again later.';
    header('Location: ../index.php#auth');
    exit;
}
