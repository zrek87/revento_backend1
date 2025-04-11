<?php
require_once __DIR__ . '/session.php';

function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID); 
}

function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateUUID() {
    try {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    } catch (Exception $e) {
        return null;
    }
}

function sendJsonResponse($success, $message, $data = []) {
    header("Content-Type: application/json");
    echo json_encode(array_merge(["success" => $success, "message" => $message], $data));
    exit;
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>
