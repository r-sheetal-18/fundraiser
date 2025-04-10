<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<p class='text-danger text-center'>Error: User not logged in.</p>";
    exit();
}

$organizer_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'all';

// Query only selecting approved campaigns
if ($type === "my") {
    $query = "SELECT campaign_id, user_id, title, description, category, goal_amount, 
                     COALESCE(raised_amount, 0.00) AS raised_amount, start_date, end_date, status 
              FROM campaigns 
              WHERE user_id = ? AND status = 'Approved'";
} else {
    $query = "SELECT campaign_id, user_id, title, description, category, goal_amount, 
                     COALESCE(raised_amount, 0.00) AS raised_amount, start_date, end_date, status 
              FROM campaigns 
              WHERE status = 'Approved'";
}

$stmt = $conn->prepare($query);
if ($type === "my") {
    $stmt->bind_param("i", $organizer_id);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approved Campaigns</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="cstyles5.css">
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center text-info">All Campaigns</h2>
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <?php 
                        $progress = ($row['goal_amount'] > 0) ? ($row['raised_amount'] / $row['goal_amount']) * 100 : 0;
                        $start_date = date("d M Y", strtotime($row['start_date']));
                        $end_date = date("d M Y", strtotime($row['end_date']));
                    ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="campaign-card p-3">
                            <h4 class="text-primary"><?= htmlspecialchars($row['title']) ?></h4>
                            <p><strong>Category:</strong> <?= htmlspecialchars($row['category']) ?></p>
                            <p><strong>Goal:</strong> ₹<?= number_format($row['goal_amount'], 2) ?> | 
                               <strong>Raised:</strong> ₹<?= number_format($row['raised_amount'], 2) ?>
                            </p>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress ?>%" 
                                     aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                            <p><strong>Status:</strong> <?= htmlspecialchars($row['status']) ?></p>
                            <a href="campdetails.php?id=<?= $row['campaign_id'] ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center text-danger">No approved campaigns found.</p>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4">
            <a href="my_campaigns.php" class="btn btn-secondary">Back</a>
            <a href="index.html" class="btn btn-secondary">Home</a>
        </div>
     
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
