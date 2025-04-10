<?php
require 'connection.php'; // Include database connection

// Initialize filters
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';

// Build query
$sql = "SELECT campaign_id, title, description, goal_amount, raised_amount, category FROM campaigns WHERE status = 'Approved'";

if (!empty($searchTerm)) {
    $sql .= " AND (title LIKE '%$searchTerm%' OR description LIKE '%$searchTerm%')";
}

if (!empty($category)) {
    $sql .= " AND category = '$category'";
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaigns</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .campaign-card {
            transition: transform 0.3s ease-in-out;
        }
        .campaign-card:hover {
            transform: scale(1.03);
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Campaign Organizer</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="home.html">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="#">Campaigns</a></li>
                <li class="nav-item"><a class="nav-link" href="login.html">Login</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- Back Button -->
<div class="container mt-3">
    <a href="home.html" class="btn btn-secondary">Back</a>
</div>

<!-- Search & Filter -->
<div class="container mt-4">
    <form class="row g-3" method="GET" action="">
        <div class="col-md-6">
            <input type="text" class="form-control" name="search" placeholder="Search campaigns..." value="<?= htmlspecialchars($searchTerm) ?>">
        </div>
        <div class="col-md-4">
            <select class="form-select" name="category">
                <option value="">All Categories</option>
                <option value="Medical" <?= $category == 'Medical' ? 'selected' : '' ?>>Medical</option>
                <option value="Education" <?= $category == 'Education' ? 'selected' : '' ?>>Education</option>
                <option value="Disaster Relief" <?= $category == 'Disaster Relief' ? 'selected' : '' ?>>Disaster Relief</option>
                
                <option value="Other" <?= $category == 'Other' ? 'selected' : '' ?>>Other</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>
</div>

<!-- Campaign Listings -->
<div class="container my-5">
    <h2 class="text-center mb-4">Active Campaigns</h2>
    <div class="row">
        <?php
        if ($result->num_rows > 0) {
            while ($campaign = $result->fetch_assoc()) {
                $progress = ($campaign['raised_amount'] / $campaign['goal_amount']) * 100;
                echo '
                <div class="col-md-4 mb-4">
                    <div class="card campaign-card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">' . htmlspecialchars($campaign['title']) . '</h5>
                            <p class="card-text">' . substr(htmlspecialchars($campaign['description']), 0, 100) . '...</p>
                            <p><strong>Category:</strong> ' . htmlspecialchars($campaign['category']) . '</p>
                            <p><strong>Goal:</strong> $' . number_format($campaign['goal_amount'], 2) . 
                            ' | <strong>Raised:</strong> $' . number_format($campaign['raised_amount'], 2) . '</p>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: ' . $progress . '%;">' . round($progress, 1) . '%</div>
                            </div>
                            <a href="hcampdet.php?id=' . $campaign['campaign_id'] . '" class="btn btn-primary mt-2">View Details</a>
                        </div>
                    </div>
                </div>';
            }
        } else {
            echo '<p class="text-center text-danger">No campaigns found matching your criteria.</p>';
        }
        ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
