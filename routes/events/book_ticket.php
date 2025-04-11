<?php
// Full error reporting (good for debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../../includes/session.php');  // ✅ Use shared session handling
include('../../includes/conn.php');
include('../../includes/functions.php');

// CORS for deployed frontend
header("Access-Control-Allow-Origin: http://ckkso0s04080wkgskwkowwso.217.65.145.182.sslip.io");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 🔐 Auth check
if (!isset($_SESSION['user_uuid']) || empty($_SESSION['user_uuid'])) {
    sendJsonResponse(false, "User is not authenticated.");
    exit;
}

$user_uuid_hex = $_SESSION['user_uuid'];
$data = json_decode(file_get_contents("php://input"));

if (!isset($data->event_id)) {
    sendJsonResponse(false, "Missing event ID.");
    exit;
}

function hexToBinaryUUID($uuid) {
    return pack("H*", $uuid);
}

$user_uuid_binary = hexToBinaryUUID($user_uuid_hex);
$event_id = (int) $data->event_id;
$booking_date = date('Y-m-d H:i:s');

try {
    // Check if user exists
    $stmt = $conn->prepare("SELECT uuid FROM users WHERE uuid = UNHEX(?) LIMIT 1");
    $stmt->execute([$user_uuid_hex]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        sendJsonResponse(false, "User does not exist. Please check your session.");
        exit;
    }

    // Check if event exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    if ($stmt->fetchColumn() === 0) {
        sendJsonResponse(false, "Event does not exist.");
        exit;
    }

    // Check for existing booking
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE user_uuid = ? AND event_id = ?");
    $stmt->execute([$user_uuid_binary, $event_id]);
    if ($stmt->fetchColumn() > 0) {
        sendJsonResponse(false, "You have already booked this event.");
        exit;
    }

    // Insert booking
    $stmt = $conn->prepare("INSERT INTO bookings (user_uuid, event_id, booking_date) VALUES (?, ?, ?)");
    $stmt->execute([$user_uuid_binary, $event_id, $booking_date]);

    sendJsonResponse(true, "Booking successful.");
} catch (PDOException $e) {
    error_log("Booking error: " . $e->getMessage());
    sendJsonResponse(false, "Database error occurred.");
}
?>
