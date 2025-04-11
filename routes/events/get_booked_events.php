<?php
require_once "../../includes/session.php";   // ✅ Use session to protect access
require_once "../../includes/conn.php";
require_once "../../includes/functions.php";

// CORS setup
header("Access-Control-Allow-Origin: http://ckkso0s04080wkgskwkowwso.217.65.145.182.sslip.io");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Require login
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    sendJsonResponse(false, "Access denied. Please log in.");
    exit;
}

// Get username from session, not query param for security
$username = $_SESSION['username'] ?? null;

if (!$username) {
    sendJsonResponse(false, "Username is missing from session.");
    exit;
}

try {
    $stmt = $conn->prepare("SELECT uuid FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendJsonResponse(false, "User not found.");
        exit;
    }

    $user_uuid = $user['uuid'];

    $stmt = $conn->prepare("
        SELECT e.event_id, e.title, e.date_time, e.location, e.category, e.price, e.event_photo 
        FROM bookings b
        JOIN events e ON b.event_id = e.event_id
        WHERE b.user_uuid = ?
    ");
    $stmt->execute([$user_uuid]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$events) {
        sendJsonResponse(false, "No booked events found.", ["events" => []]);
    } else {
        sendJsonResponse(true, "Booked events retrieved successfully!", ["events" => $events]);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    sendJsonResponse(false, "Database error occurred.");
}
?>
