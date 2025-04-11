<?php
require_once "../../includes/conn.php"; 
require_once "../../includes/functions.php"; 

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$data = json_decode(file_get_contents("php://input"), true);
$event_id = $data['event_id'] ?? null;

if (!$event_id) {
    sendJsonResponse(false, "Event ID is required.");
}

try {
    //Get event details
    $stmt = $conn->prepare("SELECT title, event_photo FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        sendJsonResponse(false, "Event not found.");
    }

    $eventFolderName = preg_replace('/[^A-Za-z0-9]/', '_', strtolower($event['title']));
    $eventFolderPath = "../../uploads/" . $eventFolderName . "/";

    //Delete event from database
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);

    //Delete event folder & images
    if (file_exists($eventFolderPath)) {
        $files = glob($eventFolderPath . '*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($eventFolderPath);
    }

    sendJsonResponse(true, "Event deleted successfully!");
} catch (PDOException $e) {
    sendJsonResponse(false, "Database error: " . $e->getMessage());
}
?>
