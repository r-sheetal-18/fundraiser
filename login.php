<?php
session_start();
header("Content-Type: application/json");

require "connection.php";

// Create database connection
// $conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = trim($_POST["username"]);
    $pass = trim($_POST["password"]);

    // Validate input fields
    if (empty($user) || empty($pass)) {
        echo json_encode(["status" => "error", "message" => "Please fill in all fields"]);
        exit();
    }

    // Prepare SQL statement
    $stmt = $conn->prepare("SELECT user_id, username, password_hash FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Verify password
        if (password_verify($pass, $row["password_hash"])) {
            $_SESSION["user_id"] = $row["user_id"];
            $_SESSION["username"] = $row["username"];

            // Redirect admin or normal user
            $redirect = ($row["username"] === "admin") ? "admin.php" : "index.html";
echo json_encode(["status" => "success", "redirect" => $redirect]);

        } else {
            echo json_encode(["status" => "error", "message" => "Invalid username or password"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }

    $stmt->close();
}

$conn->close();
?>
