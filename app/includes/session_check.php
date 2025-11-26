<?php
// app/includes/session_check.php
session_start();

if (!isset($_SESSION['user_id'])) {
    // Optionally set a message
    $_SESSION['error'] = 'Please log in to access that page.';
    header('Location: ../index.php#auth');
    exit;
}