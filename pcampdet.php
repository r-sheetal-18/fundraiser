<?php 
require 'connection.php'; 
session_start();  

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id'])) {     
    echo "<p>Error: User not logged in.</p>";     
    exit(); 
}

// Handle campaign status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $campaign_id = $_POST['campaign_id'] ?? 0;
    
    // Validate input
    $campaign_id = intval($campaign_id);
    
    if ($campaign_id > 0 && in_array($action, ['approve', 'reject', 'refer'])) {
        // Determine new status and document verification status
        $new_campaign_status = ($action === 'approve') ? 'Approved' : 
                               (($action === 'reject') ? 'Rejected' : 'Referred to Hospital');
        $new_doc_status = ($action === 'approve') ? 'Verified' : 
                          (($action === 'reject') ? 'Rejected' : 'Referral Initiated');
        
        // Start a transaction
        $conn->begin_transaction();
        
        try {
            // Update campaign status
            $update_campaign_query = "UPDATE campaigns SET status = ? WHERE campaign_id = ?";
            $campaign_stmt = $conn->prepare($update_campaign_query);
            $campaign_stmt->bind_param("si", $new_campaign_status, $campaign_id);
            $campaign_stmt->execute();
            
            // Update all related documents' verification status
            $update_docs_query = "UPDATE documents SET verification_status = ? WHERE campaign_id = ?";
            $docs_stmt = $conn->prepare($update_docs_query);
            $docs_stmt->bind_param("si", $new_doc_status, $campaign_id);
            $docs_stmt->execute();
            
            // Commit the transaction
            $conn->commit();
            
            // Redirect with success message
            header("Location: allcamp.php?status=updated");
            exit();
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            $conn->rollback();
            $error_message = "Failed to update campaign status: " . $e->getMessage();
        }
    }
}

// Validate and sanitize campaign ID from URL
$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($campaign_id <= 0) {
    echo "<p>Error: Invalid campaign ID.</p>";
    exit();
}

// Fetch campaign details with user information
$query = "SELECT c.*, u.username, u.full_name, u.email, u.phone, u.profile_image 
          FROM campaigns c
          LEFT JOIN users u ON c.user_id = u.user_id
          WHERE c.campaign_id = ? AND c.status = 'Pending'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $campaign_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>Error: Pending campaign not found.</p>";
    exit();
}

$campaign = $result->fetch_assoc();

// Fetch campaign documents with detailed verification info
$doc_query = "SELECT document_id, document_type, document_url, verification_status, uploaded_at 
              FROM documents 
              WHERE campaign_id = ? 
              ORDER BY uploaded_at";
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
    <title>Pending Campaign Review - <?= htmlspecialchars($campaign['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="cstyles6.css">
    <style>
        .document-card {
            border: 1px solid #ddd;
            margin-bottom: 10px;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header bg-warning text-center">
                <h2>Pending Campaign Review</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <h3><?= htmlspecialchars($campaign['title']) ?></h3>
                        <p><?= htmlspecialchars($campaign['description']) ?></p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <p><strong>Category:</strong> <?= htmlspecialchars($campaign['category']) ?></p>
                        <p>
                            <strong>Campaign Duration:</strong><br>
                            <?= $start_date ?> to <?= $end_date ?>
                        </p>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <h4>Campaign Creator Details</h4>
                        <?php if (!empty($campaign['profile_image'])): ?>
                            <img src="<?= htmlspecialchars($campaign['profile_image']) ?>" 
                                 alt="Profile" 
                                 class="img-fluid rounded-circle mb-3" 
                                 style="max-width: 150px;">
                        <?php endif; ?>
                        <div>
                            <p>
                                <strong>Full Name:</strong> <?= htmlspecialchars($campaign['full_name']) ?><br>
                                <strong>Username:</strong> <?= htmlspecialchars($campaign['username']) ?><br>
                                <strong>Email:</strong> <?= htmlspecialchars($campaign['email']) ?><br>
                                <strong>Phone:</strong> <?= htmlspecialchars($campaign['phone'] ?? 'N/A') ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h4>Financial Details</h4>
                        <p>
                            <strong>Goal Amount:</strong> ₹<?= number_format($campaign['goal_amount'], 2) ?><br>
                            <strong>Current Progress:</strong> <?= number_format($progress, 2) ?>%
                        </p>
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?= $progress ?>%"
                                 aria-valuenow="<?= $progress ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h4>Supporting Documents</h4>
                    <?php if ($documents->num_rows > 0): ?>
                        <div class="row">
                            <?php while ($doc = $documents->fetch_assoc()): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="document-card">
                                        <h5><?= htmlspecialchars($doc['document_type']) ?></h5>
                                        <p>
                                            <strong>Uploaded:</strong> 
                                            <?= date("d M Y H:i", strtotime($doc['uploaded_at'])) ?><br>
                                            <strong>Status:</strong> 
                                            <?= htmlspecialchars($doc['verification_status']) ?>
                                        </p>
                                        <a href="<?= htmlspecialchars($doc['document_url']) ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-primary">
                                            View Document
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">No supporting documents uploaded</div>
                    <?php endif; ?>
                </div>

                <div class="text-center mt-4">
                    <form method="POST" action="" class="d-inline">
                        <input type="hidden" name="campaign_id" value="<?= $campaign_id ?>">
                        <button type="submit" name="action" value="reject" 
                                class="btn btn-danger btn-lg me-2"
                                onclick="return confirm('Are you sure you want to REJECT this campaign?');">
                            Reject Campaign
                        </button>
                        <button type="submit" name="action" value="approve" 
                                class="btn btn-success btn-lg me-2"
                                onclick="return confirm('Are you sure you want to APPROVE this campaign?');">
                            Approve Campaign
                        </button>
                        <?php if (strtolower($campaign['category']) === 'medical'): ?>
                            <button type="submit" name="action" value="refer" 
                                    class="btn btn-info btn-lg"
                                    onclick="return confirm('Are you sure you want to REFER this campaign to a hospital?');">
                                Refer to Hospital
                            </button>
                        <?php endif; ?>
                    </form>
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
