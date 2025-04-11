<?php
include(__DIR__ . '/../includes/session.php'); 
include(__DIR__ . '/../includes/functions.php'); 

// Dynamic CORS
$allowed_origins = [
    "http://localhost:3000",
    "http://ckkso0s04080wkgskwkowwso.217.65.145.182.sslip.io"
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
}
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ensure session is active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    sendJsonResponse(false, "Access denied. Please log in.");
    exit;
}

// Get user details
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? "user";

if (isset($_GET['adminOnly']) && $_GET['adminOnly'] === "true" && $role !== "admin") {
    sendJsonResponse(false, "Access denied. Admins only.");
    exit;
}

sendJsonResponse(true, "User authenticated.", [
    "user_id" => $userId,
    "username" => $username,
    "role" => $role
]);
?>
