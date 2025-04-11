<?php
include('../../includes/session.php');
include('../../includes/conn.php');
include('../../includes/functions.php');

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

error_log("Session Data: " . json_encode($_SESSION)); 

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

//Ensure session is started
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    error_log("Access denied: User not logged in.");
    sendJsonResponse(false, "Access denied. Please log in.");
    exit;
}

// Ensure user is an admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    error_log("Access denied: User is not an admin."); 
    sendJsonResponse(false, "Access denied. Admins only.");
    exit;
}

try {
    $stmt = $conn->query("SELECT HEX(uuid) AS uuid, username, email, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendJsonResponse(true, "Users fetched successfully", ["users" => $users]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    sendJsonResponse(false, "Database error: " . $e->getMessage());
}
?>
