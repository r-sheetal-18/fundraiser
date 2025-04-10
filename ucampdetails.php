<?php 
require 'connection.php'; 
session_start();  

if (!isset($_SESSION['user_id'])) {     
    echo "<p>Error: User not logged in.</p>";     
    exit(); 
}  

// Validate and sanitize campaign ID from URL
$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($campaign_id <= 0) {
    echo "<p>Error: Invalid campaign ID.</p>";
    exit();
}

// Fetch campaign details
$query = "SELECT c.*, u.full_name AS user_full_name, u.profile_image 
          FROM campaigns c
          LEFT JOIN users u ON c.user_id = u.user_id
          WHERE c.campaign_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $campaign_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>Error: Campaign not found.</p>";
    exit();
}

$campaign = $result->fetch_assoc();

// Fetch campaign documents
$doc_query = "SELECT * FROM documents 
              WHERE campaign_id = ? 
              ORDER BY document_type";
$doc_stmt = $conn->prepare($doc_query);
$doc_stmt->bind_param("i", $campaign_id);
$doc_stmt->execute();
$documents = $doc_stmt->get_result();

// Calculate progress
$progress = ($campaign['goal_amount'] > 0) ? 
    ($campaign['raised_amount'] / $campaign['goal_amount']) * 100 : 0;

// Format dates
$start_date = date("d M Y", strtotime($campaign['start_date']));
$end_date = date("d M Y", strtotime($campaign['end_date']));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Details - <?= htmlspecialchars($campaign['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="cstyles6.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="campaign-details-card p-4">
                    <div class="campaign-header mb-4 d-flex align-items-center">
                        <?php if (!empty($campaign['profile_image'])): ?>
                            <img src="<?= htmlspecialchars($campaign['profile_image']) ?>" 
                                 alt="Campaign Creator" 
                                 class="rounded-circle me-3" 
                                 style="width: 60px; height: 60px; object-fit: cover;">
                        <?php endif; ?>
                        <h2 class="mb-0"><?= htmlspecialchars($campaign['title']) ?></h2>
                    </div>
                    
                    <div class="campaign-overview mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Category:</strong> <?= htmlspecialchars($campaign['category']) ?></p>
                                <p><strong>Campaign Creator:</strong> <?= htmlspecialchars($campaign['user_full_name'] ?? 'Unknown') ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p><strong>Campaign Status:</strong> <?= htmlspecialchars($campaign['status']) ?></p>
                                <p><strong>Duration:</strong> <?= $start_date ?> - <?= $end_date ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="campaign-description mb-4">
                        <h4>Campaign Description</h4>
                        <p><?= htmlspecialchars($campaign['description']) ?></p>
                    </div>

                    <div class="campaign-financials mb-4">
                        <h4>Financial Details</h4>
                        <div class="progress mb-2">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?= $progress ?>%"
                                 aria-valuenow="<?= $progress ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?= number_format($progress, 2) ?>%
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Goal Amount:</strong> ₹<?= number_format($campaign['goal_amount'], 2) ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p><strong>Amount Raised:</strong> ₹<?= number_format($campaign['raised_amount'], 2) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="campaign-documents mb-4">
                        <h4>Supporting Documents</h4>
                        <?php if ($documents->num_rows > 0): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Document Type</th>
                                        <th>Verification Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($doc = $documents->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($doc['document_type']) ?></td>
                                            <td><?= htmlspecialchars($doc['verification_status']) ?></td>
                                            <td>
                                                <a href="<?= htmlspecialchars($doc['document_url']) ?>" 
                                                   target="_blank" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    View Document
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-center">No supporting documents uploaded.</p>
                        <?php endif; ?>
                    </div>

                    <div class="text-center mt-4">
                        <a href="campaigns.php" class="btn btn-secondary me-2">Back to Campaigns</a>
                        <?php if ($campaign['status'] == 'Approved'): ?>
                            <a href="donate.php?campaign_id=<?= $campaign_id ?>" class="btn btn-primary">Donate Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php 
$stmt->close();
$doc_stmt->close();
$conn->close(); 
?>