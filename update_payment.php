<?php
require_once 'dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id']) && isset($_POST['action'])) {
    $payment_id = $_POST['payment_id'];
    $action = $_POST['action'];

    if (!in_array($action, ['approved', 'rejected'])) {
        die("Invalid action.");
    }

    $database = new Database();
    $result = $database->updatePaymentStatus($payment_id, $action);

    if ($result) {
        if ($action === 'approved') {
            echo "Payment approved and recorded in donations!";
        } else {
            echo "Payment successfully rejected.";
        }
    } else {
        error_log("Failed to update payment ID: $payment_id with action: $action");
        echo "Failed to update payment. Check logs for details.";
    }
} else {
    error_log("Invalid request: " . json_encode($_POST));
    echo "Invalid request.";
}
?>
