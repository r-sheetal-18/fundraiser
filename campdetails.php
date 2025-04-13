<?php 
// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'connection.php'; 
session_start();  

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {     
    die("<div class='alert alert-danger'>Error: User not logged in. <a href='login.php'>Please login</a></div>");
}  

// Validate and sanitize campaign ID from URL
$campaign_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$ref = isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : 'allcamp';

if ($campaign_id <= 0) {
    die("<div class='alert alert-danger'>Error: Invalid campaign ID.</div>");
}

try {
    // Fetch campaign details
    $query = "SELECT c.*, u.full_name AS user_full_name, u.profile_image, u.email 
              FROM campaigns c
              LEFT JOIN users u ON c.user_id = u.user_id
              WHERE c.campaign_id = ?";
    
    if (!$stmt = $conn->prepare($query)) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $campaign_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        die("<div class='alert alert-danger'>Error: Campaign not found.</div>");
    }

    $campaign = $result->fetch_assoc();

    // Fetch campaign documents
    $doc_query = "SELECT * FROM documents WHERE campaign_id = ? ORDER BY document_type";
    $doc_stmt = $conn->prepare($doc_query);
    $doc_stmt->bind_param("i", $campaign_id);
    $doc_stmt->execute();
    $documents = $doc_stmt->get_result();

    // Calculate progress
    $progress = ($campaign['goal_amount'] > 0) ? ($campaign['raised_amount'] / $campaign['goal_amount']) * 100 : 0;
    $progress = min(100, max(0, $progress)); // Ensure between 0-100

    // Format dates safely
    $start_date = 'Invalid date';
    $end_date = 'Invalid date';
    
    if (!empty($campaign['start_date']) && $campaign['start_date'] != '0000-00-00') {
        $start_date = date("d M Y", strtotime($campaign['start_date']));
    }
    
    if (!empty($campaign['end_date']) && $campaign['end_date'] != '0000-00-00') {
        $end_date = date("d M Y", strtotime($campaign['end_date']));
    }

    // Determine back button URL
    $back_url = 'campaigns.php'; // Default
    if ($ref === 'admin') {
        $back_url = 'admin.php';
    } elseif ($ref === 'user') {
        $back_url = 'my_campaigns.php';
    }

} catch (Exception $e) {
    die("<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Details - <?= htmlspecialchars($campaign['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .campaign-details-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .progress {
            height: 25px;
            border-radius: 5px;
        }
        .progress-bar {
            background-color: #4CAF50;
            line-height: 25px;
        }
        .document-table {
            background-color: #f8f9fa;
        }
        .status {
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
        }
        .status.pending {
            background-color: #ffc107;
            color: #000;
        }
        .status.approved {
            background-color: #28a745;
            color: #fff;
        }
        .status.rejected {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="campaign-details-card p-4 mb-4">
                    <!-- Back button -->
                    <a href="<?= $back_url ?>" class="btn btn-outline-secondary mb-3">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                    
                    <!-- Campaign Header -->
                    <div class="d-flex align-items-center mb-4">
                        <?php if (!empty($campaign['profile_image'])): ?>
                            <img src="<?= htmlspecialchars($campaign['profile_image']) ?>" 
                                 alt="Profile" 
                                 class="rounded-circle me-3" 
                                 width="60" height="60">
                        <?php endif; ?>
                        <div>
                            <h1 class="h3 mb-0"><?= htmlspecialchars($campaign['title']) ?></h1>
                            <p class="text-muted mb-0">Created by <?= htmlspecialchars($campaign['user_full_name'] ?? 'Unknown') ?></p>
                        </div>
                    </div>
                    
                    <!-- Campaign Status Badge -->
                    <div class="mb-3">
                        <span class="status <?= strtolower($campaign['status']) ?>">
                            <?= htmlspecialchars($campaign['status']) ?>
                        </span>
                    </div>
                    
                    <!-- Campaign Meta -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><i class="fas fa-tag me-2"></i> <strong>Category:</strong> <?= htmlspecialchars($campaign['category']) ?></p>
                            <p><i class="fas fa-calendar-alt me-2"></i> <strong>Duration:</strong> <?= $start_date ?> to <?= $end_date ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><i class="fas fa-envelope me-2"></i> <strong>Contact:</strong> <?= htmlspecialchars($campaign['email']) ?></p>
                            <p><i class="fas fa-wallet me-2"></i> <strong>UPI ID:</strong> <?= htmlspecialchars($campaign['upiid']) ?></p>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-4">
                        <h3 class="h5"><i class="fas fa-align-left me-2"></i>Description</h3>
                        <p class="text-justify"><?= nl2br(htmlspecialchars($campaign['description'])) ?></p>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <h3 class="h5"><i class="fas fa-chart-line me-2"></i>Funding Progress</h3>
                        <div class="progress mb-2">
                            <div class="progress-bar progress-bar-striped" 
                                 role="progressbar" 
                                 style="width: <?= $progress ?>%"
                                 aria-valuenow="<?= $progress ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?= number_format($progress, 2) ?>%
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p><i class="fas fa-bullseye me-2"></i> <strong>Goal:</strong> ₹<?= number_format($campaign['goal_amount'], 2) ?></p>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <p><i class="fas fa-hand-holding-usd me-2"></i> <strong>Raised:</strong> ₹<?= number_format($campaign['raised_amount'], 2) ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Documents -->
                    <div class="mb-4">
                        <h3 class="h5"><i class="fas fa-file-alt me-2"></i>Supporting Documents</h3>
                        <?php if ($documents->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover document-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($doc = $documents->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($doc['document_type'] ?? 'Unknown') ?></td>
                                                <td>
                                                    <span class="status <?= strtolower($doc['verification_status']) ?>">
                                                        <?= htmlspecialchars($doc['verification_status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="<?= htmlspecialchars($doc['document_url']) ?>" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye me-1"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">No supporting documents uploaded.</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?= $back_url ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back
                        </a>
                        <?php if ($campaign['status'] == 'Approved'): ?>
                            <a href="index.php?campaign_id=<?= $campaign_id ?>" class="btn btn-success">
                                <i class="fas fa-donate me-1"></i> Donate Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php 
// Close statements and connection
if (isset($stmt)) $stmt->close();
if (isset($doc_stmt)) $doc_stmt->close();
if (isset($conn)) $conn->close(); 
?>