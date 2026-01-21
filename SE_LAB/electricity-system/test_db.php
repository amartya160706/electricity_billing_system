<?php
// Test database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'electricity_db';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connected successfully";
$conn->close();
 