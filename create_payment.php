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

//  Retrieve campaign_id correctly from POST
$campaign_id = filter_input(INPUT_POST, 'campaign_id', FILTER_VALIDATE_INT);

if (!$campaign_id) {
    die("Error: Campaign ID is missing or invalid.");
}

// Check if campaign exists
$stmt = $conn->prepare("SELECT goal_amount, raised_amount FROM campaigns WHERE campaign_id = ?");
$stmt->bind_param("i", $campaign_id);
$stmt->execute();
$stmt->bind_result($goal_amount, $raised_amount);
if (!$stmt->fetch()) {
    header('Location: index.php?error=campaign_not_found');
    exit;
}
$stmt->close();

//Ensure values are not NULL
$goal_amount = $goal_amount ?? 0;
$raised_amount = $raised_amount ?? 0;

$new_total = $raised_amount + $amount;
if ($new_total > $goal_amount) {
    $excess_amount = $new_total - $goal_amount;
    echo "<script>
        alert('The entered amount exceeds the goal limit by ₹" . number_format($excess_amount, 2) . ". Please enter a lower amount.');
        window.location.href = 'index.php?campaign_id=" . urlencode($campaign_id) . "';
    </script>";
    exit;
}


//  Save payment to database
$order_id = 'ORDER_' . time() . rand(1000, 9999);
$stmt = $conn->prepare("INSERT INTO payments (order_id, amount, customer_name, description, campaign_id) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sdssi", $order_id, $amount, $customer_name, $description, $campaign_id);
$stmt->execute();
$stmt->close();

//Generate UPI link
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
    
    <a href="verify.php?order_id=<?php echo urlencode($order_id); ?>&amount=<?php echo urlencode($amount); ?>&customer_name=<?php echo urlencode($customer_name); ?>&description=<?php echo urlencode($description); ?>&campaign_id=<?php echo urlencode((string)$campaign_id); ?>" 
   class="block w-full bg-blue-500 text-white text-center py-2 px-4 rounded-lg hover:bg-blue-600">
    Next
</a>
</div>

<?php include 'includes/footer.php'; ?>
