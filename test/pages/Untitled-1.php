<?php
// filepath: c:\xampp\htdocs\test\db_config.php

// Database connection details
$db_host = "localhost";
$db_user = "root"; // Change to your MySQL username
$db_pass = ""; // Change to your MySQL password
$db_name = "HospitalDB";

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>