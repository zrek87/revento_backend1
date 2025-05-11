<?php

$allowed_origin = "https://revento.mhzrek.com/";

if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] === $allowed_origin) {
    header("Access-Control-Allow-Origin: $allowed_origin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

// Set cookie parameters BEFORE starting the session
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '.217.65.145.182.sslip.io',  // Shared domain for cross-subdomain cookies
    'secure' => false, // Set to true if using HTTPS
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default session state
if (!isset($_SESSION['loggedin'])) {
    $_SESSION['loggedin'] = false;
}

// Session timeout logic
$timeout_duration = 1800;    // 30 mins inactivity
$absolute_timeout = 28800;   // 8 hours max session

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    session_start();
}

if (isset($_SESSION['session_start_time']) && (time() - $_SESSION['session_start_time']) > $absolute_timeout) {
    session_unset();
    session_destroy();
    session_start();
}

// Update last activity time
if ($_SESSION['loggedin']) {
    $_SESSION['last_activity'] = time();
}

// Regenerate session ID every 10 minutes
function regenerateSession() {
    if ($_SESSION['loggedin'] && (!isset($_SESSION['session_regenerated']) || (time() - $_SESSION['session_regenerated']) > 600)) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated'] = time();
    }
}
regenerateSession();
?>
