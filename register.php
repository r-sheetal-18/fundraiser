<?php
require 'connection.php';
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get form data
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $created_at = date("Y-m-d H:i:s"); // Timestamp for user creation

    // Handle profile image upload
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $profile_image = file_get_contents($_FILES['profile_image']['tmp_name']);
    }

    try {
        // Start transaction
        $conn->begin_transaction();

        // Check if email exists
        $check_email = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Email already registered!");
        }

        // Check if username exists
        $check_username = $conn->prepare("SELECT username FROM users WHERE username = ?");
        $check_username->bind_param("s", $username);
        $check_username->execute();
        $result = $check_username->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Username already taken!");
        }

        // Insert user into database
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, phone, address, profile_image, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $username, $email, $password, $full_name, $phone, $address, $profile_image, $created_at);

        if (!$stmt->execute()) {
            throw new Exception("Error registering user: " . $stmt->error);
        }

        // Commit transaction
        $conn->commit();

        // Send verification email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'campaignorganizersystem@gmail.com'; // Update your email
            $mail->Password = 'wvpy wqnu kyuv xbdv'; // Update your password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Sender and recipient
            $mail->setFrom('noreply@campaignorganizer.com', 'Campaign Organizer');
            $mail->addAddress($email, $full_name);
            $mail->isHTML(true);
            $mail->Subject = "Welcome to Campaign Organizer";
            
            $mail->Body = "
            <html>
            <head>
                <title>Welcome</title>
            </head>
            <body>
                <h2>Welcome to Campaign Organizer!</h2>
                <p>Thank you for registering.</p>
                <p>You have successfully registered</p>
            </body>
            </html>";

            $mail->send();
        } catch (Exception $e) {
            throw new Exception("Email could not be sent. Error: " . $mail->ErrorInfo);
        }

        // Close statements and connection
        $stmt->close();
        $check_email->close();
        $check_username->close();
        $conn->close();

        // Redirect to login or success page
        header("Location: index.html");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.history.back();</script>";
        exit();
    }
}
?>
