<?php
$host = 'localhost'; // Database host
$user = 'andre.ayiku'; // Database username
$password = 'Andkroku11.'; // Database password
$dbName = 'webtech_fall2024_andre_ayiku'; // Database name

// Create a connection
$conn = new mysqli($host, $user, $password, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
