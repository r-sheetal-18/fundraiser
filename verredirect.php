<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<p>Error: User not logged in.</p>";
    exit();
}

$category = $_POST['category'] ?? null;

$pendingCampaigns = [];
if ($category) {
    $stmt = $conn->prepare("SELECT * FROM campaigns WHERE category = ? AND status = 'Pending'");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $pendingCampaigns[] = $row;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Verifications</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --navy-blue: #172B4D;
            --dark-grey: #333333;
            --white: #FFFFFF;
        }

        body {
            background-color: var(--white);
            font-family: 'Arial', sans-serif;
            color: var(--navy-blue);
        }

        .header {
            background-color: var(--navy-blue);
            color: var(--white);
            padding: 20px;
            text-align: center;
            margin-bottom: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .back-button {
            background-color: var(--dark-grey);
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s ease;
            position: absolute;
            top: 20px;
            left: 20px;
        }

        .back-button:hover {
            background-color: var(--navy-blue);
            transform: translateY(-3px);
        }

        .category-button {
            background-color: var(--navy-blue);
            color: var(--white);
            border: none;
            padding: 15px 30px;
            margin: 10px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }

        .category-button:hover {
            background-color: var(--dark-grey);
            transform: translateY(-5px);
        }

        .button-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .button-container {
                grid-template-columns: 1fr;
            }
        }

        .campaign-card {
            border: 1px solid #ccc;
            border-radius: 12px;
            padding: 20px;
            background-color: #f9f9f9;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="header">
    <a href="admin.php">
        <button class="back-button" id="backBtn">Back</button>
    </a>
    <h1>Pending Verifications</h1>
</div>

<div class="container">
    <form method="POST" class="button-container">
        <button type="submit" name="category" value="Medical" class="category-button">Medical</button>
        <button type="submit" name="category" value="Disaster Relief" class="category-button">Disaster Relief</button>
        <button type="submit" name="category" value="Education" class="category-button">Education</button>
        <button type="submit" name="category" value="Others" class="category-button">Others</button>
    </form>

    <?php if ($category): ?>
        <h3 class="mb-4">Pending Campaigns - <?= htmlspecialchars($category) ?></h3>
        <?php if (!empty($pendingCampaigns)): ?>
            <?php foreach ($pendingCampaigns as $row): ?>
                <?php 
                    $progress = ($row['goal_amount'] > 0) ? 
                        ($row['raised_amount'] / $row['goal_amount']) * 100 : 0;
                ?>
                <div class="campaign-card">
                    <h4><?= htmlspecialchars($row['title']) ?></h4>
                    <p><strong>Description:</strong> <?= htmlspecialchars($row['description']) ?></p>
                    <p><strong>Goal:</strong> ₹<?= number_format($row['goal_amount'], 2) ?> | 
                       <strong>Raised:</strong> ₹<?= number_format($row['raised_amount'], 2) ?></p>
                    <div class="progress mb-2">
                        <div class="progress-bar" role="progressbar" 
                            style="width: <?= $progress ?>%" 
                            aria-valuenow="<?= $progress ?>" 
                            aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <a href="pcampdet.php?id=<?= $row['campaign_id'] ?>" class="btn btn-sm btn-primary">View Details</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No pending campaigns found under <?= htmlspecialchars($category) ?>.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
