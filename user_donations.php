<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<p class='text-danger text-center'>Error: User not logged in.</p>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch donations with campaign details
// $query = "SELECT d.amount, d.verified_at, c.title, c.description, c.goal_amount, c.raised_amount, c.status 
//           FROM donations d
//           JOIN campaigns c ON d.campaign_id = c.campaign_id
//           WHERE d.user_id = ?
//           ORDER BY d.verified_at DESC";
$query = "SELECT d.amount, d.verified_at, c.campaign_id, c.title, c.description, 
                 c.goal_amount, c.raised_amount, c.status 
          FROM donations d
          JOIN campaigns c ON d.campaign_id = c.campaign_id
          WHERE d.user_id = ?
          ORDER BY d.verified_at DESC";


$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Donations</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="cstyles7.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center text-info" style="color:rgb(13, 23, 117)">My Donations</h2>
        <button class="btn btn-dark mb-3" onclick="window.location.href='index.html'">Back to Dashboard</button>
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php 
                        $progress = ($row['goal_amount'] > 0) ? ($row['raised_amount'] / $row['goal_amount']) * 100 : 0;
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="donation-card p-4">
                            <h4 class="text-primary"><?= htmlspecialchars($row['title']) ?></h4>
                            <p class="text-muted"><?= htmlspecialchars(substr($row['description'], 0, 100)) ?>...</p>
                            <p><strong>Donated:</strong> ₹<?= number_format($row['amount'], 2) ?></p>
                            <p><strong>Goal:</strong> ₹<?= number_format($row['goal_amount'], 2) ?> | 
                               <strong>Raised:</strong> ₹<?= number_format($row['raised_amount'], 2) ?>
                            </p>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress ?>%" 
                                     aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <p><strong>Status:</strong> <span class="status <?= strtolower($row['status']) ?>"><?= ucfirst($row['status']) ?></span></p>
                            <p><i class="fas fa-clock"></i> Donated on: <?= date("d M Y, H:i", strtotime($row['verified_at'])) ?></p>
                            <a href="campaign_details.php?id=<?= $row['campaign_id'] ?>" class="btn btn-outline-primary btn-sm">View Campaign</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center text-danger">No donations found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
