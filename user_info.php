<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "User not logged in"]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details (full_name instead of username)
$query = "SELECT full_name, profile_image FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$profile_image = $user['profile_image'] ? $user['profile_image'] : "uploads/default.png";

echo json_encode([
    "full_name" => $user['full_name'],
    "profile_image" => $profile_image
]);

$stmt->close();
$conn->close();
?>
