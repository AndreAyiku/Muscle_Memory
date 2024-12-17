<?php
session_start();
require_once '../db/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['receiver_id']) || !isset($data['message'])) {
    http_response_code(400);
    exit();
}

$query = "INSERT INTO MM_Messages (sender_id, receiver_id, message_text) 
          VALUES (?, ?, ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("iis", $_SESSION['user_id'], $data['receiver_id'], $data['message']);

if ($stmt->execute()) {
    http_response_code(200);
} else {
    http_response_code(500);
}