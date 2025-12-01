<?php
require_once __DIR__ . '/../includes/session_check.php';

if ($_SESSION['role'] !== 'member') {
    header("Location: admin_dashboard.php");
    exit;
}

// debugging purpose during development
// echo "you are member";

// this is a shared layout
?>
<!-- HTML -->