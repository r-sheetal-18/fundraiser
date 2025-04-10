<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<p class='text-danger text-center'>Error: User not logged in.</p>";
    exit();
}

$organizer_id = $_SESSION['user_id'];

// Fetch Approved Campaigns
$queryApproved = "SELECT campaign_id, title, description, category, goal_amount, 
                         COALESCE(raised_amount, 0.00) AS raised_amount, start_date, end_date, status 
                  FROM campaigns 
                  WHERE user_id = ? AND status = 'Approved'";

$stmtApproved = $conn->prepare($queryApproved);
$stmtApproved->bind_param("i", $organizer_id);
$stmtApproved->execute();
$resultApproved = $stmtApproved->get_result();

// Fetch Pending Campaigns
$queryPending = "SELECT campaign_id, title, description, category, goal_amount, 
                         COALESCE(raised_amount, 0.00) AS raised_amount, start_date, end_date, status 
                  FROM campaigns 
                  WHERE user_id = ? AND status = 'Pending'";

$stmtPending = $conn->prepare($queryPending);
$stmtPending->bind_param("i", $organizer_id);
$stmtPending->execute();
$resultPending = $stmtPending->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Campaigns</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="cstyles5.css">
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center text-info">My Campaigns</h2>
        
        <!-- Buttons: Back & View All Campaigns -->
        <div class="mb-3 d-flex justify-content-between">
            <button class="btn btn-secondary" onclick="history.back()"> Back</button>
            <button class="btn btn-dark" onclick="window.location.href='campaigns.php'">View All Campaigns</button>
        </div>

        <!-- Approved Campaigns Section -->
        <?php if ($resultApproved->num_rows > 0): ?>
            <div class="accordion" id="approvedCampaigns">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button bg-success text-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseApproved">
                            Approved Campaigns
                        </button>
                    </h2>
                    <div id="collapseApproved" class="accordion-collapse collapse show">
                        <div class="accordion-body">
                            <div class="row">
                                <?php while ($row = $resultApproved->fetch_assoc()): 
                                    $progress = ($row['goal_amount'] > 0) ? ($row['raised_amount'] / $row['goal_amount']) * 100 : 0;
                                ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="campaign-card p-3">
                                            <h4 class="text-primary"><?= htmlspecialchars($row['title']) ?></h4>
                                            <p class="text-muted"><?= htmlspecialchars($row['description']) ?></p>
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
                                            <div class="d-flex gap-2">
                                                <a href="campdetails.php?id=<?= $row['campaign_id'] ?>&ref=user" class="btn btn-outline-primary btn-sm flex-fill">View Details</a>
                                                <a href="edit_campaign.php?id=<?= $row['campaign_id'] ?>" class="btn btn-outline-primary btn-sm flex-fill">Edit</a>
                                            </div>




                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <p class="text-center text-danger">No approved campaigns found.</p>
        <?php endif; ?>

        <!-- Pending Campaigns Section -->
        <?php if ($resultPending->num_rows > 0): ?>
            <div class="accordion mt-4" id="pendingCampaigns">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button bg-warning text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePending">
                            Pending Campaigns
                        </button>
                    </h2>
                    <div id="collapsePending" class="accordion-collapse collapse show">
                        <div class="accordion-body">
                            <div class="row">
                                <?php while ($row = $resultPending->fetch_assoc()): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="campaign-card p-3">
                                            <h4 class="text-primary"><?= htmlspecialchars($row['title']) ?></h4>
                                            <p class="text-muted"><?= htmlspecialchars($row['description']) ?></p>
                                            <p><strong>Category:</strong> <?= htmlspecialchars($row['category']) ?></p>
                                            <p><strong>Goal:</strong> ₹<?= number_format($row['goal_amount'], 2) ?></p>
                                            <p class="text-warning"><strong>Status:</strong> Pending Approval</p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <p class="text-center text-danger">No pending campaigns found.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmtApproved->close();
$stmtPending->close();
$conn->close();
?>
