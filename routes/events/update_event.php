<?php
require_once "../../includes/conn.php"; 
require_once "../../includes/functions.php"; 

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$event_id = $_POST['event_id'] ?? null;
$title = sanitizeInput($_POST['title'] ?? '');
$description = sanitizeInput($_POST['description'] ?? '');
$date_time = sanitizeInput($_POST['date_time'] ?? '');
$location = sanitizeInput($_POST['location'] ?? '');
$category = sanitizeInput($_POST['category'] ?? '');
$price = sanitizeInput($_POST['price'] ?? '');
$remove_photo = $_POST['remove_photo'] ?? null;

if (!$event_id || !$title || !$description || !$date_time || !$location || !$category) {
    sendJsonResponse(false, "Missing required fields.");
}

try {
    //Get current event details (to retrieve the old photo)
    $stmt = $conn->prepare("SELECT event_photo, title FROM events WHERE event_id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $oldPhoto = $event['event_photo'] ?? null;
    $oldTitle = $event['title'];

    //Generate correct event folder path
    $eventFolderName = preg_replace('/[^A-Za-z0-9]/', '_', strtolower($title));
    $eventFolder = "../../uploads/" . $eventFolderName . "/";

    //If event title is changed, move images to the new folder
    if ($oldTitle !== $title && file_exists("../../uploads/" . preg_replace('/[^A-Za-z0-9]/', '_', strtolower($oldTitle)))) {
        rename("../../uploads/" . preg_replace('/[^A-Za-z0-9]/', '_', strtolower($oldTitle)), $eventFolder);
    }

    // ✅ Delete old photo if requested
    if ($remove_photo && $oldPhoto) {
        $oldPhotoPath = "../../uploads/" . $oldPhoto;
        if (file_exists($oldPhotoPath)) {
            unlink($oldPhotoPath);
        }
        $oldPhoto = null;
    }

    //Handle new photo upload
    $event_photo = $oldPhoto;
    if (!empty($_FILES['event_photo']['name'])) {
        //Ensure event folder exists
        if (!file_exists($eventFolder)) {
            mkdir($eventFolder, 0777, true);
        }

        //Generate new file name
        $fileName = time() . "_" . basename($_FILES["event_photo"]["name"]);
        $targetFilePath = $eventFolder . $fileName;

        //Move new photo to correct event folder
        if (move_uploaded_file($_FILES["event_photo"]["tmp_name"], $targetFilePath)) {
            //Delete old photo if it exists
            if ($oldPhoto) {
                $oldPhotoPath = "../../uploads/" . $oldPhoto;
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }
            $event_photo = $eventFolderName . "/" . $fileName;
        }
    }

    //Update event details
    $stmt = $conn->prepare("UPDATE events SET title=?, description=?, date_time=?, location=?, category=?, price=?, event_photo=? WHERE event_id=?");
    $stmt->execute([$title, $description, $date_time, $location, $category, $price, $event_photo, $event_id]);

    sendJsonResponse(true, "Event updated successfully!");
} catch (PDOException $e) {
    sendJsonResponse(false, "Database error: " . $e->getMessage());
}
?>
