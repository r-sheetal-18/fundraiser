<?php
require_once 'connection.php';
require_once 'config/constants.php';
require 'vendor/autoload.php'; // For QR code generation

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$customer_name = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_SPECIAL_CHARS);
$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);

$order_id = 'ORDER_' . time() . rand(1000, 9999);

// Generate UPI link
function generateUPILink($amount, $upiId, $name, $description, $orderId) {
    $params = [
        'pa' => $upiId,
        'pn' => urlencode($name),
        'tn' => urlencode($description),
        'am' => $amount,
        'cu' => 'INR',
        'tr' => $orderId
    ];
    
    return "upi://pay?" . http_build_query($params);
}

// Save to database
try {
    $stmt = $conn->prepare("INSERT INTO payments (order_id, amount, customer_name, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$order_id, $amount, $customer_name, $description]);
    
    $upiLink = generateUPILink(
        $amount,
        MERCHANT_UPI_ID,
        MERCHANT_NAME,
        $description,
        $order_id
    );
    
    // Generate QR Code
    $qrCode = QrCode::create($upiLink);
    $writer = new PngWriter();
    $result = $writer->write($qrCode);
    $qrDataUri = $result->getDataUri();
    
    include 'includes/header.php';
?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-6">Payment Details</h2>
    
    <div class="mb-6">
        <p class="text-gray-600 mb-2">Amount: ₹<?php echo number_format($amount, 2); ?></p>
        <p class="text-gray-600 mb-2">Order ID: <?php echo $order_id; ?></p>
    </div>
    
    <div class="qr-code mb-6">
        <img src="<?php echo $qrDataUri; ?>" alt="Payment QR Code">
    </div>
    
    <!-- <a href="<?php echo $upiLink; ?>" class="block w-full bg-green-500 text-white text-center py-2 px-4 rounded-lg hover:bg-green-600 mb-4">
       Next
    </a> -->
    
    <a href="verification.php?order_id=<?php echo urlencode($order_id); ?>&amount=<?php echo urlencode($amount); ?>&customer_name=<?php echo urlencode($customer_name); ?>&description=<?php echo urlencode($description); ?>" 
   class="block w-full bg-blue-500 text-white text-center py-2 px-4 rounded-lg hover:bg-blue-600">
    Next
</a>


</div>

<?php
    include 'includes/footer.php';
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>