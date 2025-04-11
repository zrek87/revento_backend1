<?php
// CORS HEADERS (adjust frontend URL as needed)
$allowed_origin = "http://ckkso0s04080wkgskwkowwso.217.65.145.182.sslip.io";

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

// SESSION SETUP
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params([
        'lifetime' => 86400, 
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']), 
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

if (!isset($_SESSION['loggedin'])) {
    $_SESSION['loggedin'] = false;
}

// SESSION TIMEOUT HANDLING
$timeout_duration = 1800; // 30 mins inactivity
$absolute_timeout = 28800; // 8 hours max session

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

// Last activity update
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
