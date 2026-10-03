<?php
session_start();

// Cloudflare Turnstile Secret Key
// IMPORTANT: Replace this with your actual secret key from Cloudflare dashboard
$secretKey = '0x4AAAAAAB9BjYmNTrMhZbz4kcRNzr_rHTg';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../index.php");
    exit();
}

// Get the token from POST data
$token = $_POST['cf-turnstile-response'] ?? '';

if (empty($token)) {
    header("Location: ../index.php?error=verification_failed");
    exit();
}

// Verify the token with Cloudflare
$verifyUrl = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

$data = [
    'secret' => $secretKey,
    'response' => $token,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
];

// Initialize cURL
$ch = curl_init($verifyUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check if request was successful
if ($httpCode !== 200 || !$response) {
    header("Location: ../index.php?error=verification_failed");
    exit();
}

// Decode response
$result = json_decode($response, true);

// Check if verification was successful
if (isset($result['success']) && $result['success'] === true) {
    // Verification successful - set session variable
    $_SESSION['turnstile_verified'] = true;
    $_SESSION['turnstile_timestamp'] = time();
    
    // Redirect back to index.php (which will show login form)
    header("Location: ../index.php");
    exit();
} else {
    // Verification failed
    error_log('Turnstile verification failed: ' . json_encode($result));
    header("Location: ../index.php?error=verification_failed");
    exit();
}
?>