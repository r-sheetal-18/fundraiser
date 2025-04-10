<?php
require 'connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) {
        echo "Error: User not logged in.";
        exit();
    }

    $organizer_id = $_SESSION['user_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $upiid = isset($_POST['upiid']) ? mysqli_real_escape_string($conn, $_POST['upiid']) : ''; // Fix applied
    $goal_amount = mysqli_real_escape_string($conn, $_POST['goal_amount']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    $start_date = date('Y-m-d'); // Current date
    $raised_amount = 0; // Default
    $status = "Pending"; // Default status

    try {
        $conn->begin_transaction();

        // Insert campaign details with UPI ID
        $stmt = $conn->prepare("INSERT INTO campaigns (user_id, title, description, category, upiid, goal_amount, raised_amount, start_date, end_date, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issssdssss", $organizer_id, $title, $description, $category, $upiid, $goal_amount, $raised_amount, $start_date, $end_date, $status);

        if (!$stmt->execute()) {
            throw new Exception("Error inserting campaign: " . $stmt->error);
        }

        $campaign_id = $stmt->insert_id;

        // Handle multiple file uploads
        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['files']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = $_FILES['files']['name'][$key];
                $file_tmp = $_FILES['files']['tmp_name'][$key];
                $document_type = pathinfo($file_name, PATHINFO_EXTENSION);
                $document_url = 'uploads/' . uniqid() . '_' . basename($file_name);
                $verification_status = 'pending';

                // Move file to uploads directory
                if (move_uploaded_file($file_tmp, $document_url)) {
                    $doc_stmt = $conn->prepare("INSERT INTO documents (campaign_id, user_id, document_type, document_url, verification_status, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $doc_stmt->bind_param("iisss", $campaign_id, $organizer_id, $document_type, $document_url, $verification_status);
                    $doc_stmt->execute();
                    $doc_stmt->close();
                } else {
                    throw new Exception("Error uploading document: " . $file_name);
                }
            }
        }

        $conn->commit();
        echo "Campaign created successfully!";
        
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>
