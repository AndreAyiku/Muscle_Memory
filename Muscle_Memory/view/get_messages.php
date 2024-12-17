<?php
session_start();
require_once '../db/config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['contact_id'])) {
    http_response_code(400);
    exit();
}

$contact_id = intval($_GET['contact_id']);

$query = "SELECT * FROM MM_Messages 
          WHERE (sender_id = ? AND receiver_id = ?) 
          OR (sender_id = ? AND receiver_id = ?)
          ORDER BY created_at ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $_SESSION['user_id'], $contact_id, $contact_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

header('Content-Type: application/json');
echo json_encode($messages);