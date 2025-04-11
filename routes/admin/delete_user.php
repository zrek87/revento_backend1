<?php
include('../../includes/session.php');
include('../../includes/conn.php');
include('../../includes/functions.php');

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    sendJsonResponse(false, "Access denied. Please log in.");
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    sendJsonResponse(false, "Access denied. Admins only.");
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || empty($data['uuid'])) {
    sendJsonResponse(false, "User ID is required.");
    exit;
}

$uuidBinary = pack("H*", str_replace('-', '', $data['uuid']));

try {
    $stmt = $conn->prepare("DELETE FROM users WHERE uuid = ?");
    $stmt->execute([$uuidBinary]);

    sendJsonResponse(true, "User deleted successfully.");
} catch (PDOException $e) {
    sendJsonResponse(false, "Database error: " . $e->getMessage());
}
?>
