<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ Dynamic CORS
$allowed_origins = [
    "http://localhost:3000",
    "http://ckkso0s04080wkgskwkowwso.217.65.145.182.sslip.io"
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

// ✅ Preflight check
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

include('../../includes/session.php');
include('../../includes/functions.php');

// Set session_start_time if not set
if (!isset($_SESSION['session_start_time'])) {
    $_SESSION['session_start_time'] = time();
}

// Debugging
error_log("Checking Session Role: " . ($_SESSION['role'] ?? "Not Set"));
error_log("Session Data in check_session.php: " . print_r($_SESSION, true));

// Timeouts
$absolute_timeout = 28800; // 8h
$inactivity_timeout = 1800; // 30min

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

    // Update last activity timestamp
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
