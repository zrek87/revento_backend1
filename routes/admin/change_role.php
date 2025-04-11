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
if (!$data || empty($data['uuid']) || empty($data['role'])) {
    sendJsonResponse(false, "Invalid request. UUID and role are required.");
    exit;
}

$uuidBinary = pack("H*", str_replace('-', '', sanitizeInput($data['uuid'])));
$newRole = sanitizeInput($data['role']);

try {
    $stmt = $conn->prepare("UPDATE users SET role = :role WHERE uuid = :uuid");
    $stmt->execute([':role' => $newRole, ':uuid' => $uuidBinary]);

    //If the logged in user is updating their own role, update session
    if ($_SESSION['user_uuid'] === strtoupper(bin2hex($uuidBinary))) {
        $_SESSION['role'] = $newRole;
    }

    sendJsonResponse(true, "User role updated successfully.", [
        "updated_role" => $newRole,
        "current_session_role" => $_SESSION['role']
    ]);
} catch (PDOException $e) {
    sendJsonResponse(false, "Error updating role: " . $e->getMessage());
}
?>
