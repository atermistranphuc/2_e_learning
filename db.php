<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parent_portal";

$conn = new mysqli($servername, $username, $password, $dbname);
mysqli_set_charset($conn, 'UTF8');

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>