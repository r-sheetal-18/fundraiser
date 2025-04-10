<?php
require 'connection.php'; // Ensure this file correctly establishes a DB connection
require 'src/PHPMailer.php';
require 'src/SMTP.php';
require 'src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form values
    $hospital_name = trim($_POST['hospital_name']);
    $license_number = trim($_POST['license_number']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $created_at = date("Y-m-d H:i:s");

    // Generate a unique hospital_id (e.g., H01, H02, etc.)
    $query = "SELECT MAX(CAST(SUBSTRING(hospital_id, 2) AS UNSIGNED)) AS max_id FROM hospitals";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $new_id = isset($row['max_id']) ? $row['max_id'] + 1 : 1;
    $hospital_id = 'H' . str_pad($new_id, 2, '0', STR_PAD_LEFT);

    try {
        $conn->begin_transaction();

        // Check if email already exists in hospitals table
        $stmt = $conn->prepare("SELECT email FROM hospitals WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            throw new Exception("Email already registered!");
        }
        $stmt->close();

        // Check if license number already exists
        $stmt = $conn->prepare("SELECT license_number FROM hospitals WHERE license_number = ?");
        $stmt->bind_param("s", $license_number);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            throw new Exception("License number already registered!");
        }
        $stmt->close();

        // Insert hospital data into the database
        $stmt = $conn->prepare("INSERT INTO hospitals (hospital_id, hospital_name, license_number, email, phone, address, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $hospital_id, $hospital_name, $license_number, $email, $phone, $address, $created_at);

        if (!$stmt->execute()) {
            throw new Exception("Error registering hospital: " . $stmt->error);
        }

        $conn->commit();

        // Send confirmation email using PHPMailer
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
            $mail->addAddress($email, $hospital_name);
            $mail->isHTML(true);
            $mail->Subject = "Welcome to Hospital Registration";
            $mail->Body = "
            <html>
            <head>
                <title>Welcome</title>
            </head>
            <body>
                <h2>Welcome to our Hospital Registration System!</h2>
                <p>Thank you for registering your hospital: <strong>{$hospital_name}</strong>.</p>
                <p>Your Hospital ID is: <strong>{$hospital_id}</strong>.</p>
            </body>
            </html>";

            $mail->send();
        } catch (Exception $e) {
            throw new Exception("Email could not be sent. Error: " . $mail->ErrorInfo);
        }

        $stmt->close();
        $conn->close();

        echo "<script>alert('Registration successful! A confirmation email has been sent.'); window.location.href='hospdash.html';</script>";
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.history.back();</script>";
        exit();
    }
}
?>
