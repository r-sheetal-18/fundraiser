<?php
session_start();
include 'connection.php'; // Ensure this file contains the database connection

// if (!isset($_SESSION['admin_id'])) {
//     echo "<p class='text-danger text-center'>Error: Admin not logged in.</p>";
//     exit();
// }

// $admin_id = $_SESSION['user_id'];

// // Query to check if the admin_id corresponds to the username "admin"
// $sql = "SELECT username FROM users WHERE user_id = ? AND username = 'admin'";
// $stmt = $conn->prepare($sql);
// $stmt->bind_param("i", $admin_id);
// $stmt->execute();
// $result = $stmt->get_result();

// if ($result->num_rows === 0) {
//     echo "<p class='text-danger text-center'>Error: Unauthorized access.</p>";
//     exit();
// }


// Fetch dashboard statistics
$campaigns_query = "SELECT COUNT(*) AS total FROM campaigns WHERE status = 'Approved'";
//$donations_query = "SELECT SUM(amount) AS total FROM donations";
$pending_verifications_query = "SELECT COUNT(*) AS total FROM campaigns WHERE status = 'Pending'";

$campaigns_result = $conn->query($campaigns_query);
$donations_result = $conn->query($donations_query);
$pending_result = $conn->query($pending_verifications_query);

$total_campaigns = ($campaigns_result->fetch_assoc())['total'] ?? 0;
$total_donations = ($donations_result->fetch_assoc())['total'] ?? 0;
$pending_verifications = ($pending_result->fetch_assoc())['total'] ?? 0;

// Fetch all campaigns
$campaign_list_query = "SELECT campaign_id, title, description, status FROM campaigns";
$campaign_list_result = $conn->query($campaign_list_query);
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
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-chart-line me-2"></i> Dashboard</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-bullhorn me-2"></i> Campaigns</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-users me-2"></i> Users</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-hand-holding-usd me-2"></i> Donations</a></li>
                <li class="nav-item"><a href="#" class="nav-link"><i class="fas fa-file-alt me-2"></i> Reports</a></li>
                <li class="nav-item"><a href="logout.php" class="nav-link text-danger"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Page Content -->
        <div id="page-content-wrapper" class="p-4 w-100">
            <nav class="navbar navbar-expand-lg bg-white shadow-sm mb-3 p-3 rounded">
                <button class="btn btn-primary" id="menu-toggle">☰ Menu</button>
            </nav>
            <div class="container-fluid">
                <h3 class="mb-4">Welcome, Admin</h3>
                
                <!-- Dashboard Cards -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card dashboard-card bg-dark text-white mb-3">
                            <div class="card-header">Active Campaigns</div>
                            <div class="card-body">
                                <h4 class="card-title"><?= $total_campaigns ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card bg-dark text-white mb-3">
                            <div class="card-header">Total Donations</div>
                            <div class="card-body">
                                <h4 class="card-title">₹<?= number_format($total_donations, 2) ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card bg-dark text-white mb-3">
                            <div class="card-header">Pending Verifications</div>
                            <div class="card-body">
                                <h4 class="card-title"><?= $pending_verifications ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Campaign Listings -->
                <div class="container mt-4">
                    <h2 class="text-center">All Campaigns</h2>
                    <div class="row">
                        <?php if ($campaign_list_result->num_rows > 0): ?>
                            <?php while ($row = $campaign_list_result->fetch_assoc()): ?>
                                <div class="col-md-4">
                                    <div class="campaign-card p-3 mb-3 rounded">
                                        <h5><?= htmlspecialchars($row['title']) ?></h5>
                                        <p><?= htmlspecialchars($row['description']) ?></p>
                                        <span class="badge bg-<?php echo ($row['status'] == 'Approved') ? 'success' : 'warning'; ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                        <a href="campaign_details.php?id=<?= $row['campaign_id'] ?>" class="btn btn-dark btn-sm">View Details</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-center text-danger">No campaigns found.</p>
                        <?php endif; ?>
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

<?php
$conn->close();
?>
