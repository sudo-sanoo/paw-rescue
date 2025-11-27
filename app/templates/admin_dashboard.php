<?php
require_once __DIR__ . '/../includes/session_check.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: member_dashboard.php");
    exit;
}

// debugging purpose during development
echo "you are admin";

// this is a shared layout
?>
<!-- HTML -->