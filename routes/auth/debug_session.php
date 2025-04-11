<?php
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");

session_start();

$user_uuid = $_SESSION['user_uuid'] ?? "Not Set";
if ($user_uuid !== "Not Set" && strlen($user_uuid) === 16) {
    $user_uuid = strtoupper(bin2hex($user_uuid));
}

echo json_encode([
    "session_id" => session_id(),
    "user_uuid" => $user_uuid,
    "loggedin" => $_SESSION['loggedin'] ?? false,
    "last_activity" => $_SESSION['last_activity'] ?? "Not Set",
    "session_start_time" => $_SESSION['session_start_time'] ?? "Not Set"
]);
?>
