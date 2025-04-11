<?php
require_once "../../includes/conn.php"; 
require_once "../../includes/functions.php"; 

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

//Check if filters are applied
$latest = isset($_GET['latest']);
$event_id = $_GET['id'] ?? null;
$category = $_GET['category'] ?? null;
$title = $_GET['title'] ?? null;
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;

try {
    if ($event_id) {
        //Fetch event by ID 
        $stmt = $conn->prepare("SELECT event_id, title, description, date_time, location, category, price, event_photo 
                                FROM events WHERE event_id = ? LIMIT 1");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            sendJsonResponse(true, "Event retrieved successfully!", ["event" => $event]);
        } else {
            sendJsonResponse(false, "Event not found.");
        }
    } elseif ($title) {
        //Fetch events by title
        $stmt = $conn->prepare("SELECT event_id, title, description, date_time, location, category, price, event_photo 
                                FROM events WHERE title LIKE ? 
                                ORDER BY date_time DESC LIMIT ?");
        $stmt->execute(["%$title%", $limit]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($events) {
            sendJsonResponse(true, "Events matching '$title' retrieved!", ["events" => $events]);
        } else {
            sendJsonResponse(false, "No events found for '$title'.");
        }
    } elseif ($category) {
        //Fetch events by category
        $stmt = $conn->prepare("SELECT event_id, title, description, date_time, location, category, price, event_photo 
                                FROM events WHERE category = ? 
                                ORDER BY date_time DESC LIMIT ?");
        $stmt->execute([$category, $limit]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendJsonResponse(true, "Events for category '$category' retrieved!", ["events" => $events]);
    } elseif ($latest) {
        //Fetch the latest events
        $stmt = $conn->prepare("SELECT event_id, title, description, date_time, location, category, price, event_photo 
                                FROM events ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendJsonResponse(true, "Latest events retrieved!", ["events" => $events]);
    } else {
        //Fetch all events
        $stmt = $conn->prepare("SELECT event_id, title, description, date_time, location, category, price, event_photo 
                                FROM events ORDER BY date_time DESC LIMIT ?");
        $stmt->execute([$limit]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        sendJsonResponse(true, "Events retrieved successfully!", ["events" => $events]);
    }
} catch (PDOException $e) {
    sendJsonResponse(false, "Database error: " . $e->getMessage());
}
?>
