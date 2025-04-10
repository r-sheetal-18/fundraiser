<?php
require 'connection.php';

$query = "SELECT campaign_id, title, description, goal_amount, raised_amount, status FROM campaigns WHERE status = 'Approved'";
$result = $conn->query($query);

$campaigns = [];
while ($row = $result->fetch_assoc()) {
    $campaigns[] = $row;
}

echo json_encode($campaigns);
?>
