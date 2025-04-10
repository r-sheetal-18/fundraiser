<?php
session_start();
require_once 'dbconnect.php';

$database = new Database();
$pendingPayments = $database->getPendingPayments();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Verification</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; }
        table { width: 80%; margin: 20px auto; border-collapse: collapse; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: center; }
        button { padding: 8px 12px; margin: 5px; border: none; cursor: pointer; }
        .approve { background-color: #4CAF50; color: white; }
        .reject { background-color: #FF5733; color: white; }
    </style>
</head>
<body>

<h1>Payment Verification</h1>

<table>
    <thead>
        <tr>
            <th>Payment ID</th>
            <th>Transaction ID</th>
            <th>Customer Name</th>
            <th>Amount</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pendingPayments as $payment): ?>
            <tr id="row-<?php echo $payment['payment_id']; ?>">
                <td><?php echo htmlspecialchars($payment['payment_id']); ?></td>
                <td><?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($payment['description'] ?? 'N/A'); ?></td>
                <td>
                    <button class="approve" onclick="updatePayment(<?php echo $payment['payment_id']; ?>, 'approved')">Approve</button>
                    <button class="reject" onclick="updatePayment(<?php echo $payment['payment_id']; ?>, 'rejected')">Reject</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
function updatePayment(paymentId, action) {
    if (confirm("Are you sure you want to " + action + " this payment?")) {
        $.ajax({
            url: "update_payment.php",
            type: "POST",
            data: { payment_id: paymentId, action: action },
            success: function(response) {
                alert(response);
                if (response.includes("success")) {
                    $("#row-" + paymentId).remove();
                }
            },
            error: function(xhr, status, error) {
                alert("Error: " + xhr.responseText);
            }
        });
    }
}
</script>

</body>
</html>
