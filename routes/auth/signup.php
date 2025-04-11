<?php
include('../../includes/session.php');
include('../../includes/conn.php');
include('../../includes/functions.php');

header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['fullname']) || empty($data['username']) || empty($data['email']) || empty($data['password'])) {
    sendJsonResponse(false, "All fields are required.");
    exit;
}


$uuidString = generateUUID(); 
$uuidBinary = pack("H*", str_replace('-', '', strtolower($uuidString)));

$fullname = sanitizeInput($data['fullname']);
$username = sanitizeInput($data['username']);
$email = sanitizeInput($data['email']);
$password = password_hash($data['password'], PASSWORD_DEFAULT);
$role = "user";


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJsonResponse(false, "Invalid email format.");
    exit;
}

try {

    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email OR username = :username");
    $checkStmt->execute([':email' => $email, ':username' => $username]);

    if ($checkStmt->fetchColumn() > 0) {
        http_response_code(409);
        sendJsonResponse(false, "Email or Username is already registered.");
        exit;
    }

  
    $stmt = $conn->prepare("INSERT INTO users (uuid, fullname, username, email, password, role) 
                            VALUES (:uuid, :fullname, :username, :email, :password, :role)");

    if ($stmt->execute([
        ':uuid' => $uuidBinary,
        ':fullname' => $fullname,
        ':username' => $username,
        ':email' => $email,
        ':password' => $password,
        ':role' => $role
    ])) {
    
        session_regenerate_id(true);

        $_SESSION['user_uuid'] = bin2hex($uuidBinary);
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;
        $_SESSION['loggedin'] = true;

        sendJsonResponse(true, "User registered successfully!", [
            "user_id" => bin2hex($uuidBinary),
            "username" => $username,
            "email" => $email,
            "role" => $role
        ]);
    } else {
        http_response_code(500);
        sendJsonResponse(false, "Error: Could not register user.");
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    sendJsonResponse(false, "An unexpected error occurred. Please try again.");
}
?>
