<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    // User is not logged in, redirect to login page
    header("Location: ../../index.php");
    exit();
}

// Optional: Check if session has expired (e.g., 30 minutes of inactivity)
$inactive_timeout = 1800; // 30 minutes in seconds

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive_timeout)) {
    // Session expired, destroy and redirect
    session_unset();
    session_destroy();
    header("Location: ../../index.php?timeout=1");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../../../config/role-access.php';
docVicRequirePortalAccess('stii');
?>