<?php
require_once "../../includes/conn.php";
require_once "../../includes/functions.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    sendJsonResponse(false, "Invalid request method.");
}

//Sanitize inputs
$title = sanitizeInput($_POST['title'] ?? '');
$description = sanitizeInput($_POST['description'] ?? '');
$date_time = sanitizeInput($_POST['date'] ?? ''); 
$location = sanitizeInput($_POST['location'] ?? '');
$category = sanitizeInput($_POST['category'] ?? '');
$subcategory = sanitizeInput($_POST['subcategory'] ?? '');
$price = sanitizeInput($_POST['price'] ?? '0.00');

//Ensure subcategories are formatted correctly
$subcategory = implode(", ", array_map('trim', explode(",", $subcategory)));

//Validate required fields
if (!$title || !$description || !$date_time || !$location || !$category || empty($subcategory)) {
    sendJsonResponse(false, "Missing required fields.");
}

//Create a sanitized folder name
$eventFolderName = preg_replace('/[^a-zA-Z0-9_-]/', '_', strtolower($title));
$eventFolderPath = __DIR__ . "/../../uploads/" . $eventFolderName;

//Ensure event folder exists
if (!is_dir($eventFolderPath)) {
    mkdir($eventFolderPath, 0777, true);
}

//Handle Image Upload
$event_photo = null;
if (!empty($_FILES['event_photo']['name'])) {
    $fileName = time() . "_" . basename($_FILES["event_photo"]["name"]);
    $targetFilePath = $eventFolderPath . "/" . $fileName;

    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($_FILES['event_photo']['type'], $allowedTypes)) {
        sendJsonResponse(false, "Invalid image type. Use JPG, JPEG, or PNG.");
    }

    if (move_uploaded_file($_FILES["event_photo"]["tmp_name"], $targetFilePath)) {
        $event_photo = $eventFolderName . "/" . $fileName;
    } else {
        sendJsonResponse(false, "Failed to upload image.");
    }
}

//Insert into database (allow `NULL` for event_photo)
try {
    $stmt = $conn->prepare("INSERT INTO events (title, description, date_time, location, category, subcategory, price, event_photo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $date_time, $location, $category, $subcategory, $price, $event_photo]);

    sendJsonResponse(true, "Event added successfully!", ["event_id" => $conn->lastInsertId(), "photo_path" => $event_photo]);
} catch (PDOException $e) {
    sendJsonResponse(false, "Database error: " . $e->getMessage());
}
?>
