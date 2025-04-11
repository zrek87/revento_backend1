<?php
include('../../includes/session.php'); // Ensure session starts

// Static CORS
header("Access-Control-Allow-Origin: http://ckkso0s04080wkgskwkowwso.217.65.145.182.sslip.io");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_SESSION = [];
session_unset();
session_destroy();

// Set expired cookies
$domain = "xgwc4g0kssoc4w8sgso0wkw4.217.65.145.182.sslip.io"; // backend domain
setcookie(session_name(), '', time() - 3600, '/', $domain, false, true);
setcookie("auth_token", '', time() - 3600, '/', $domain, false, true);

echo json_encode([
    "success" => true,
    "message" => "Logged out successfully."
]);
exit;
?>
