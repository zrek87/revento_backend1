<?php
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

//Session Timeout Handling
$timeout_duration = 1800; // 30 minutes inactivity timeout
$absolute_timeout = 28800; // 8 hours max session duration

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

//last activity timestamp only if session is active
if ($_SESSION['loggedin']) {
    $_SESSION['last_activity'] = time();
}

//regenerate session ID every 10 minutes
function regenerateSession() {
    if ($_SESSION['loggedin'] && (!isset($_SESSION['session_regenerated']) || (time() - $_SESSION['session_regenerated']) > 600)) {
        session_regenerate_id(true);
        $_SESSION['session_regenerated'] = time();
    }
}
regenerateSession();
?>
