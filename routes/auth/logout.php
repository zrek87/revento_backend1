<?php
include('../../includes/session.php'); // Ensure session starts

// Static CORS
header("Access-Control-Allow-Origin: https://revento.mhzrek.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Make sure session is active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Clear session variables
$_SESSION = [];
session_unset();
session_destroy();

// Expire cookies on the shared domain
$domain = ".revento.mhzrek.com"; // ✅ shared across frontend and backend
setcookie(session_name(), '', time() - 3600, '/', $domain, false, true);
setcookie("auth_token", '', time() - 3600, '/', $domain, false, true);
setcookie("user_role", '', time() - 3600, '/', $domain, false, false); // If set earlier

// Response
echo json_encode([
    "success" => true,
    "message" => "Logged out successfully."
]);
exit;
?>
