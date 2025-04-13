<?php
require_once 'connection.php';
require_once 'config/constants.php';
require 'vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Validate inputs
$amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
$customer_name = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_SPECIAL_CHARS);
$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS);
$campaign_id = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);

if (!$amount || !$customer_name || !$campaign_id) {
    die("Error: Invalid input parameters.");
}

// Generate order ID and temporary transaction ID
$order_id = 'ORDER_' . time() . rand(1000, 9999);
$transaction_id = 'TEMP_' . time(); // Temporary transaction ID

// Save payment to database (with transaction_id)
$stmt = $conn->prepare("INSERT INTO payments (
    order_id, 
    amount, 
    customer_name, 
    description, 
    campaign_id,
    transaction_id,
    status
) VALUES (?, ?, ?, ?, ?, ?, 'pending')");

$stmt->bind_param(
    "sdssss", 
    $order_id, 
    $amount, 
    $customer_name, 
    $description, 
    $campaign_id,
    $transaction_id
);

if (!$stmt->execute()) {
    die("Error saving payment: " . $conn->error);
}
$stmt->close();

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

$upiLink = generateUPILink($amount, MERCHANT_UPI_ID, MERCHANT_NAME, $description, $order_id);

// Generate QR Code
$qrCode = QrCode::create($upiLink)
    ->setSize(300)
    ->setMargin(10);
$writer = new PngWriter();
$result = $writer->write($qrCode);
$qrDataUri = $result->getDataUri();

include 'includes/header.php';
?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-6">Payment QR Code</h2>
    
    <div class="mb-4">
        <p class="text-gray-700"><strong>Amount:</strong> ₹<?= number_format($amount, 2) ?></p>
        <p class="text-gray-700"><strong>For:</strong> <?= htmlspecialchars($description) ?></p>
        <p class="text-gray-700"><strong>Order ID:</strong> <?= htmlspecialchars($order_id) ?></p>
    </div>
    
    <div class="qr-container mb-6 p-4 bg-gray-100 rounded-lg flex justify-center">
        <img src="<?= $qrDataUri ?>" alt="Scan to Pay" class="w-64 h-64">
    </div>
    
    <div class="instructions bg-blue-50 p-4 rounded-lg mb-6">
        <h3 class="font-bold text-blue-800 mb-2">Instructions:</h3>
        <ol class="list-decimal list-inside text-sm text-blue-700">
            <li>Show this QR code to the donor</li>
            <li>Ask them to scan it with any UPI app</li>
            <li>Verify payment completion in the system</li>
        </ol>
    </div>
    
    <a href="admin_dashboard.php" class="block w-full bg-blue-500 text-white text-center py-2 px-4 rounded-lg hover:bg-blue-600">
        Back to Dashboard
    </a>
</div>

<?php include 'includes/footer.php'; ?>