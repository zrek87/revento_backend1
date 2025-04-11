<?php
include('../../middleware/authMiddleware.php');

//Return user details
sendJsonResponse(true, "Authenticated successfully!", [
    "user_id" => $userId,
    "username" => $username
]);
?>
