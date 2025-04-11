<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require_once "../../includes/conn.php";

if (!isset($_SESSION['user_uuid']) || empty($_SESSION['user_uuid'])) {
    echo json_encode(["success" => false, "message" => "User is not authenticated."]);
    exit;
}

$user_uuid_hex = $_SESSION['user_uuid'];

$data = json_decode(file_get_contents("php://input"));

if (!isset($data->event_id)) {
    echo json_encode(["success" => false, "message" => "Missing event ID"]);
    exit;
}


function hexToBinaryUUID($uuid) {
    return pack("H*", $uuid);
}

$user_uuid_binary = hexToBinaryUUID($user_uuid_hex);
$event_id = (int) $data->event_id;
$booking_date = date('Y-m-d H:i:s');

try {
    //Check if the user exists in `users` table
    $stmt = $conn->prepare("SELECT uuid FROM users WHERE uuid = UNHEX(?) LIMIT 1");
    $stmt->execute([$user_uuid_hex]);
    $userExists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userExists) {
        echo json_encode(["success" => false, "message" => "User does not exist. Please check your session."]);
        exit;
    }

    //Check if event exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $eventExists = $stmt->fetchColumn();

    if (!$eventExists) {
        echo json_encode(["success" => false, "message" => "Event does not exist"]);
        exit;
    }

    //Check if the user has already booked this event
    $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE user_uuid = ? AND event_id = ?");
    $stmt->execute([$user_uuid_binary, $event_id]);

    if ($stmt->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "You have already booked this event."]);
        exit;
    }

    //Insert booking
    $stmt = $conn->prepare("INSERT INTO bookings (user_uuid, event_id, booking_date) VALUES (?, ?, ?)");
    $stmt->execute([$user_uuid_binary, $event_id, $booking_date]);

    echo json_encode(["success" => true, "message" => "Booking successful"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()]);
}

$conn = null;
?>
