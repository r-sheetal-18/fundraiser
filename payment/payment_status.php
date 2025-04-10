<?php
require_once 'connection.php';

$order_id = filter_input(INPUT_GET, 'order_id', FILTER_SANITIZE_STRING);

try {
    $stmt = $conn->prepare("SELECT * FROM payments WHERE order_id = ?");
    $stmt->execute([$order_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    include 'includes/header.php';
?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-6">Payment Status</h2>
    
    <?php if ($payment): ?>
        <div class="payment-status status-<?php echo $payment['status']; ?>">
            <p class="font-bold">Status: <?php echo ucfirst($payment['status']); ?></p>
        </div>
        
        <div class="mb-6">
            <p class="text-gray-600 mb-2">Amount: ₹<?php echo number_format($payment['amount'], 2); ?></p>
            <p class="text-gray-600 mb-2">Order ID: <?php echo $payment['order_id']; ?></p>
            <p class="text-gray-600 mb-2">Customer: <?php echo $payment['customer_name']; ?></p>
        </div>
        
        <?php if ($payment['status'] === 'pending'): ?>
            <button onclick="checkStatus('<?php echo $payment['order_id']; ?>')" 
                    class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600">
                Refresh Status
            </button>
        <?php endif; ?>
    <?php else: ?>
        <p class="text-red-500">Payment not found.</p>
    <?php endif; ?>
</div>

<?php
    include 'includes/footer.php';
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
