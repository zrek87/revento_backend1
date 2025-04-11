<?php
include('../../includes/session.php');  
include('../../includes/conn.php');     
include('../../includes/functions.php'); 

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    error_log("Access denied: User not logged in.");
    sendJsonResponse(false, "Access denied. Please log in.");
    exit;
}

//Validate username parameter
if (!isset($_GET['username']) || empty($_GET['username'])) {
    error_log("Error: Username is required.");
    sendJsonResponse(false, "Error: Username is required.");
    exit;
}

$username = trim($_GET['username']);

try {
    $stmt = $conn->prepare("
        SELECT username, COALESCE(fullname, 'No Name Provided') AS fullname, email
        FROM users WHERE username = :username
    ");
    $stmt->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        error_log("Fetched user: " . json_encode($user));

        sendJsonResponse(true, "User fetched successfully", ["user" => $user]);
    } else {
        error_log("User not found: " . $username);
        sendJsonResponse(false, "User not found.");
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    sendJsonResponse(false, "Database error: " . $e->getMessage());
}
?>
