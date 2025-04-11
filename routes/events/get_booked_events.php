<?php
require_once "../../includes/conn.php";
require_once "../../includes/functions.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

$username = $_GET['username'] ?? null;

if (!$username) {
    sendJsonResponse(false, "Username is required.");
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
    sendJsonResponse(false, "Database error: " . $e->getMessage());
}
?>
