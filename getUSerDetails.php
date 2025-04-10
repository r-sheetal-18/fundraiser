<?php
session_start();
header('Content-Type: application/json');

// Check if the user is logged in via session
if (isset($_SESSION['user_id'])) {
    // Return the user's details as JSON
    echo json_encode([
        'loggedIn'  => true,
        'full_name' => isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User'
    ]);
} else {
    echo json_encode(['loggedIn' => false]);
}
?>
