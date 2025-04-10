<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<p class='text-danger text-center'>Error: User not logged in.</p>";
    exit();
}

// Handle delete user request
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $delete_query = "DELETE FROM users WHERE user_id = ? AND username != 'admin'";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    header("Location: users_list.php");
    exit();
}

// Fetch users excluding admin
$query = "SELECT user_id, username, email, full_name, phone, address, profile_image, created_at 
          FROM users 
          WHERE username != 'admin'";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center text-info">Users List</h2>

        <!-- Back Button -->
        <div class="mb-3">
            <!-- <button class="btn btn-secondary" onclick="history.back();"> Back</button> -->
            <a href="admin.php?id=123" style="display: inline-block; padding: 8px 16px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">Back</a>


        </div>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Full Name</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Profile Image</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['user_id']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['phone'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($row['address'] ?? 'N/A') ?></td>
                            <td>
                                <?php if (!empty($row['profile_image'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($row['profile_image']) ?>" alt="Profile Image" width="50">
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?= date("d M Y", strtotime($row['created_at'])) ?></td>
                            <td>
                                <a href="edit_user.php?id=<?= $row['user_id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="users_list.php?delete=<?= $row['user_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" class="text-center text-muted">No users found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$conn->close();
?>
