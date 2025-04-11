<?php
include(__DIR__ . '/../includes/session.php'); 
include(__DIR__ . '/../includes/functions.php'); 

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

//Ensure session is active before accessing `$_SESSION`
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    sendJsonResponse(false, "Access denied. Please log in.");
    exit;
}

//Get user details from session
$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'] ?? "user"; // Default to "user" if role is missing

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
