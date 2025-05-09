<?php
// manage_campaigns.php
session_start();
require 'connection.php'; // your DB connection; provides $conn

// Handle Approve / Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campaign_id'], $_POST['action'])) {
    $campaign_id = filter_var($_POST['campaign_id'], FILTER_VALIDATE_INT);
    $action      = $_POST['action'] === 'approve' ? 'Approved' : 'Rejected';

    if ($campaign_id) {
        $stmt = $conn->prepare("UPDATE campaigns SET status = ? WHERE campaign_id = ?");
        $stmt->bind_param("si", $action, $campaign_id);
        if ($stmt->execute()) {
            $flash = "Campaign #{$campaign_id} marked “{$action}.”";
        } else {
            $flash = "Error updating campaign: " . $conn->error;
        }
        $stmt->close();
    } else {
        $flash = "Invalid campaign ID.";
    }
}

// Fetch all campaigns “referred to hospital”
$result = $conn->query("
    SELECT campaign_id, user_id, title, category, upiid, goal_amount, raised_amount, start_date, end_date, created_at
    FROM campaigns
    WHERE status = 'referred to hospital'
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage “Referred to Hospital” Campaigns</title>
  <style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    table { width: 100%; border-collapse: collapse; margin-top: 1em; }
    th, td { padding: 8px; border: 1px solid #ccc; text-align: left; }
    th { background: #f4f4f4; }
    .flash { padding: 10px; background: #e0ffe0; border: 1px solid #0c0; margin-bottom: 1em; }
    .btn { padding: 6px 12px; text-decoration: none; border: none; cursor: pointer; border-radius: 4px; }
    .approve { background: #4CAF50; color: white; }
    .reject  { background: #f44336; color: white; }
  </style>
</head>
<body>

  <h1>Campaigns Referred to Hospital</h1>

  <?php if (!empty($flash)): ?>
    <div class="flash"><?php echo htmlspecialchars($flash); ?></div>
  <?php endif; ?>

  <?php if ($result && $result->num_rows > 0): ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Category</th>
          <th>UPI ID</th>
          <th>Goal / Raised</th>
          <th>Dates</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
          <tr>
            <td><?php echo $row['campaign_id']; ?></td>
            <td><?php echo htmlspecialchars($row['title']); ?></td>
            <td><?php echo htmlspecialchars($row['category']); ?></td>
            <td><?php echo htmlspecialchars($row['upiid']); ?></td>
            <td>₹<?php echo number_format($row['goal_amount'],2); ?> / ₹<?php echo number_format($row['raised_amount'],2); ?></td>
            <td>
              <?php echo $row['start_date']; ?> → <?php echo $row['end_date']; ?>
            </td>
            <td>
              <form style="display:inline" method="post">
                <input type="hidden" name="campaign_id" value="<?php echo $row['campaign_id']; ?>">
                <button type="submit" name="action" value="approve" class="btn approve">
                  Approve
                </button>
              </form>
              <form style="display:inline" method="post">
                <input type="hidden" name="campaign_id" value="<?php echo $row['campaign_id']; ?>">
                <button type="submit" name="action" value="reject" class="btn reject">
                  Reject
                </button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No campaigns are currently “referred to hospital.”</p>
  <?php endif; ?>

</body>
</html>
