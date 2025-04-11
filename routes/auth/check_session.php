<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../../includes/session.php');
include('../../includes/functions.php');

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    exit(0);
}

$absolute_timeout = 28800; // 8 hours max session duration
$inactivity_timeout = 1800; // 30 minutes inactivity timeout

//Ensure session_start_time is set before checking expiration
if (!isset($_SESSION['session_start_time'])) {
    $_SESSION['session_start_time'] = time();
}

error_log("Checking Session Role: " . ($_SESSION['role'] ?? "Not Set"));
error_log("Session Data in check_session.php: " . print_r($_SESSION, true));

//Check session expiration
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $current_time = time();
    $session_duration = $current_time - $_SESSION['session_start_time'];
    $inactive_duration = $current_time - ($_SESSION['last_activity'] ?? $current_time);

    if ($session_duration > $absolute_timeout || $inactive_duration > $inactivity_timeout) {
        error_log("Session Expired: Destroying session");
        session_unset();
        session_destroy();
        sendJsonResponse(false, "Session expired. Please log in again.");
        exit;
    }

    //Extend session only if active
    $_SESSION['last_activity'] = $current_time;

    sendJsonResponse(true, "Session is active", [
        "user_uuid" => $_SESSION['user_uuid'],
        "username" => $_SESSION['username'],
        "role" => $_SESSION['role']
    ]);
} else {
    error_log("Session Expired: No active session found.");
    sendJsonResponse(false, "Session expired.");
}
?>
