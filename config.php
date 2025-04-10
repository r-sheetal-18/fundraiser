<?php
// Enable error reporting for debugging
// Comment these lines in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";  // Change if needed
$password = "";      // Change if needed
$dbname = "projectcamp";

$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");

// Function to safely output debug information
function debug_to_console($data) {
    if(is_array($data) || is_object($data)) {
        echo("<script>console.log('".json_encode($data)."');</script>");
    } else {
        echo("<script>console.log('".$data."');</script>");
    }
}
?>