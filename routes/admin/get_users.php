<?php
include('../../includes/session.php');
include('../../includes/conn.php');
include('../../includes/functions.php');

// CORS for deployed frontend
header("Access-Control-Allow-Origin: https://revento.mhzrek.com");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Debug log
error_log("Session Data: " . json_encode($_SESSION));

// Auth check
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    error_log("Access denied: User not logged in.");
    sendJsonResponse(false, "Access denied. Please log in.");
    exit;
}

// Admin role check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    error_log("Access denied: User is not an admin."); 
    sendJsonResponse(false, "Access denied. Admins only.");
    exit;
}

// Fetch user list
try {
    $stmt = $conn->query("SELECT HEX(uuid) AS uuid, username, email, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendJsonResponse(true, "Users fetched successfully", ["users" => $users]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    sendJsonResponse(false, "Database error: " . $e->getMessage());
}
?>
