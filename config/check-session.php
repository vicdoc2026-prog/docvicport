<?php
// Set session cookie lifetime and garbage collection for 3 days
$timeout_seconds = 3 * 24 * 60 * 60; // 3 days = 259200 seconds
ini_set('session.gc_maxlifetime', $timeout_seconds); // server-side session data lifetime
session_set_cookie_params([
    'lifetime' => $timeout_seconds, // cookie lifetime
    'path' => '/',                  // available across the whole domain
    'secure' => isset($_SERVER['HTTPS']), // use secure cookie if HTTPS
    'httponly' => true,             // JS cannot access session cookie
    'samesite' => 'Strict'          // helps prevent CSRF
]);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: ./index.php");
    exit();
}

// Session timeout: 3 days
$inactive_timeout = $timeout_seconds; // 3 days

// Check if session has expired due to inactivity
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive_timeout)) {
    // Session expired, destroy session
    session_unset();
    session_destroy();
    header("Location: ./index.php?timeout=1");
    exit();
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();
?>
