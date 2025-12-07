<?php
// app/includes/session_check.php
session_start();

/**
 * Require user to be logged in.
 * Redirects to login page if not authenticated.
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = 'Please log in to access that page.';
        header('Location: ../index.php#auth');
        exit;
    }
}

/**
 * Require the user to have *one of* the allowed roles.
 * @param array $allowedRoles Example: ['admin', 'member']
 */
function requireRole(array $allowedRoles) {
    requireLogin(); // Ensure authenticated first

    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowedRoles)) {
        header('Location: permission_denied.php');
        exit;
    }
}