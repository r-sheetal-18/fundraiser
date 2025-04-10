<?php
require 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "<p class='text-danger text-center'>You must be logged in to edit a campaign.</p>";
    exit();
}

$campaign_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$campaign_id) {
    echo "<p class='text-danger text-center'>Invalid campaign ID.</p>";
    exit();
}

$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $goal_amount = $_POST['goal_amount'];
    $end_date = $_POST['end_date'];

    $stmt = $conn->prepare("UPDATE campaigns SET goal_amount = ?, end_date = ? WHERE campaign_id = ? AND user_id = ?");
    $stmt->bind_param("dsii", $goal_amount, $end_date, $campaign_id, $user_id);

    if ($stmt->execute()) {
        $success = "Campaign updated successfully!";
        echo "<script>
                setTimeout(function() {
                    window.location.href = 'my_campaigns.php';
                }, 2000);
              </script>";
    } else {
        $error = "Error updating campaign.";
    }
    $stmt->close();
}

// Fetch campaign details
$stmt = $conn->prepare("SELECT title, goal_amount, end_date FROM campaigns WHERE campaign_id = ? AND user_id = ?");
$stmt->bind_param("ii", $campaign_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$campaign = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Campaign</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 600px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h3 class="text-center text-info mb-4">Edit Campaign</h3>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success text-center" id="success-msg"><?= $success ?></div>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($campaign): ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Campaign Title</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($campaign['title']) ?>" disabled>
            </div>

            <div class="mb-3">
                <label for="goal_amount" class="form-label">Goal Amount (₹)</label>
                <input type="number" step="0.01" name="goal_amount" id="goal_amount" class="form-control" required value="<?= $campaign['goal_amount'] ?>">
            </div>

            <div class="mb-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" id="end_date" class="form-control" required value="<?= $campaign['end_date'] ?>">
            </div>

            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="my_campaigns.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    <?php else: ?>
        <p class="text-danger text-center">Campaign not found or you don't have permission to edit it.</p>
    <?php endif; ?>
</div>

</body>
</html>
