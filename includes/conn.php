<?php
$host = "localhost";
$db_name = "revento_app";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password, [
        PDO::ATTR_PERSISTENT => true, 
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false, 
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC 
    ]);
} catch (PDOException $e) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $e->getMessage()]));
}
?>
