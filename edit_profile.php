<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<p class='text-danger text-center'>Error: User not logged in.</p>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$query = "SELECT username, email, full_name, phone, address, profile_image FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // Handle profile image upload
    if (!empty($_FILES["profile_image"]["name"])) {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
        move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);

        $updateQuery = "UPDATE users SET full_name=?, phone=?, address=?, profile_image=? WHERE user_id=?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ssssi", $full_name, $phone, $address, $target_file, $user_id);
    } else {
        $updateQuery = "UPDATE users SET full_name=?, phone=?, address=? WHERE user_id=?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssi", $full_name, $phone, $address, $user_id);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Profile updated successfully!'); window.location.href='index.html';</script>"; // Redirect to dashboard
    } else {
        echo "<script>alert('Error updating profile.');</script>";
    }
    
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="cstyles9.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center text-info">Edit Personal Details</h2>
        <button class="btn btn-dark mb-3" onclick="window.location.href='index.html'">Back </button>

        <div class="profile-form p-4">
            <form method="POST" enctype="multipart/form-data">
                <div class="text-center mb-3">
                    <img src="<?= $user['profile_image'] ?: 'uploads/default.png' ?>" class="profile-img" alt="Profile Image">
                </div>
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email (Cannot be changed)</label>
                    <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control"><?= htmlspecialchars($user['address']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Profile Image</label>
                    <input type="file" name="profile_image" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Update Details</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
