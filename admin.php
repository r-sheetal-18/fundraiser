<?php
session_start();
require "connection.php"; // Database connection file

// Fetch total number of campaigns
$campaignQuery = "SELECT COUNT(*) as total_campaigns FROM campaigns WHERE status='Approved'";
$campaignResult = mysqli_query($conn, $campaignQuery);
$campaignData = mysqli_fetch_assoc($campaignResult);
$totalCampaigns = $campaignData['total_campaigns'];

// Fetch total donations amount
$donationQuery = "SELECT SUM(amount) as total_donations FROM donations";
$donationResult = mysqli_query($conn, $donationQuery);
$donationData = mysqli_fetch_assoc($donationResult);
$totalDonations = $donationData['total_donations'] ?? 0;

// Fetch total pending verifications
$pendingCampaignsQuery = "SELECT COUNT(*) as pending_campaigns FROM campaigns WHERE status = 'pending'";
$pendingCampaignsResult = mysqli_query($conn, $pendingCampaignsQuery);
$pendingCampaignsData = mysqli_fetch_assoc($pendingCampaignsResult);
$pendingVerifications = $pendingCampaignsData['pending_campaigns'];


// Fetch all campaigns
$campaignsQuery = "SELECT * FROM campaigns ORDER BY created_at DESC";
$campaignsResult = mysqli_query($conn, $campaignsQuery);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="sidebar vh-100 p-3" id="sidebar-wrapper">
            <h2 class="text-center mb-4">Admin Panel</h2>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="hospreg.html" class="nav-link"><i class="fas fa-file-alt me-2"></i> Hospital Registration</a></li>
                <li class="nav-item"><a href="allcamp.php" class="nav-link"><i class="fas fa-bullhorn me-2"></i> Campaigns</a></li>
                <li class="nav-item"><a href="userdisplay.php" class="nav-link"><i class="fas fa-users me-2"></i> Users</a></li>
                <li class="nav-item"><a href="donations.php" class="nav-link"><i class="fas fa-hand-holding-usd me-2"></i> Donations</a></li>
                <li class="nav-item"><a href="paymentver.php" class="nav-link"><i class="fas fa-file-alt me-2"></i> Payment verification</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Page Content -->
        <div id="page-content-wrapper" class="p-4 w-100">
            <nav class="navbar navbar-expand-lg bg-white shadow-sm mb-3 p-3 rounded">
                <!-- <button class="btn btn-primary" id="menu-toggle">☰ Menu</button> -->
            </nav>
            <div class="container-fluid">
                <h3 class="mb-4">Welcome, Admin</h3>
                
                <!-- Dashboard Cards -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card dashboard-card bg-grey text-white mb-3">
                            <div class="card-header">Active Campaigns</div>
                            <div class="card-body">
                                <h4 class="card-title"><?= $totalCampaigns ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card bg-grey text-white mb-3">
                            <div class="card-header">Total Donations</div>
                            <div class="card-body">
                                <h4 class="card-title">$<?= number_format($totalDonations, 2) ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
    <a href="verredirect.php" style="text-decoration: none;">
        <div class="card dashboard-card bg-grey text-white mb-3">
            <div class="card-header">Pending Verifications</div>
            <div class="card-body">
                <h4 class="card-title"><?= $pendingVerifications ?></h4>
            </div>
        </div>
    </a>
</div>

                </div>
                
               <!-- Campaign Listings -->
               <div class="container mt-4">
                    <h2 class="text-center">All Campaigns</h2>
                    <div class="row">
                        <?php while ($campaign = mysqli_fetch_assoc($campaignsResult)) { ?>
                            <div class="col-md-4">
                                <div class="campaign-card p-3 mb-3 rounded">
                                    <h5><?= htmlspecialchars($campaign['title']) ?></h5>
                                    <p><?= substr(htmlspecialchars($campaign['description']), 0, 100) ?>...</p>
                                    
                                    <?php
                                        $status = $campaign['status'];
                                        $badgeColor = 'secondary';

                                        switch ($status) {
                                            case 'Ongoing':
                                                $badgeColor = 'success';
                                                break;
                                            case 'Pending':
                                                $badgeColor = 'warning';
                                                break;
                                            case 'Approved':
                                                $badgeColor = 'primary';
                                                break;
                                            case 'Rejected':
                                                $badgeColor = 'danger';
                                                break;
                                            case 'Referred to Hospital':
                                                $badgeColor = 'info';
                                                break;
                                        }
                                    ?>
                                    <span class="badge bg-<?= $badgeColor ?>">
                                        <?= htmlspecialchars($status) ?>
                                    </span>

                                    <a href="campdetails.php?id=<?= $campaign['campaign_id'] ?>&ref=admin" class="btn btn-dark btn-sm mt-2">View Details</a>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>


            </div>
        </div>
    </div>

    <script>
        $(document).ready(function(){
            $("#menu-toggle").click(function(e){
                e.preventDefault();
                $("#wrapper").toggleClass("toggled");
            });
        });
    </script>
</body>
</html>
