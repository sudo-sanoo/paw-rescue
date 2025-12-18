<?php
// app/auth/login.php
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
