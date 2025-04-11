<?php
require_once "../../includes/conn.php"; 
require_once "../../includes/functions.php"; 

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

$user_uuid = $_GET['user_uuid'] ?? null;

if (!$user_uuid) {
    sendJsonResponse(false, "User ID is required.");
}

//Call AI Recommendation API
$ai_api_url = "http://127.0.0.1:5000/recommend?user_uuid=" . urlencode($user_uuid);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ai_api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);

$recommendations = json_decode($response, true);

if (!$recommendations || !isset($recommendations['recommendations']) || empty($recommendations['recommendations'])) {
    sendJsonResponse(false, "AI recommendation failed or no events found.");
}

sendJsonResponse(true, "Recommended events retrieved successfully!", ["events" => $recommendations['recommendations']]);
?>
