<?php
session_start();
require 'connection.php'; // Include database connection file  

// Use $_REQUEST to pick up values whether they come via GET or POST.
$order_id      = isset($_REQUEST['order_id']) ? htmlspecialchars($_REQUEST['order_id']) : ''; 
$amount        = isset($_REQUEST['amount']) ? htmlspecialchars($_REQUEST['amount']) : ''; 
$customer_name = isset($_REQUEST['customer_name']) ? htmlspecialchars($_REQUEST['customer_name']) : ''; 
$description   = isset($_REQUEST['description']) ? htmlspecialchars($_REQUEST['description']) : ''; 

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the submitted transaction id
    $transaction_id = isset($_POST['transaction_id']) ? htmlspecialchars($_POST['transaction_id']) : '';

    // For anonymous users, get email from the form; for logged in users, use the email from session
    if (!$is_logged_in) {
        $anonymous_email = isset($_POST['anonymous_email']) ? filter_var($_POST['anonymous_email'], FILTER_VALIDATE_EMAIL) : '';
        if (!$anonymous_email) {
            echo "<script>
                    alert('Please enter a valid email address.');
                    window.history.back();
                  </script>";
            exit();
        }
        $email_to_save = $anonymous_email;
    } else {
        $email_to_save = isset($_SESSION['email']) ? $_SESSION['email'] : '';
    }
    
    // Set the new payment status
    $status = 'pending';
    
    // Update the existing record using the order_id
    $stmt = $conn->prepare("UPDATE payments SET transaction_id = ?, status = ?, payment_reference = ? WHERE order_id = ?");
    $stmt->bind_param("ssss", $transaction_id, $status, $email_to_save, $order_id);
    $stmt->execute();
    $stmt->close();

    // Alert and redirect based on login status
    if ($is_logged_in) {
        echo "<script>
                alert('Your payment is under processing. A PDF receipt will be sent to your registered email.');
                window.location.href = 'index.html';
              </script>";
    } else {
        echo "<script>
                alert('Your donation has been submitted. A PDF receipt will be sent to your email: $email_to_save');
                window.location.href = 'home.html';
              </script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification</title>
     <style>
        body {
            font-family: 'Inter', 'Arial', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #00008B 0%, #000080 100%);
            margin: 0;
            background-attachment: fixed;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 400px;
            max-width: 95%;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
        }

        h2 {
            color: #00008B;
            margin-bottom: 25px;
            font-weight: 700;
            position: relative;
        }

        h2::after {
            content: '';
            position: absolute;
            width: 60px;
            height: 3px;
            background-color: #6c757d;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        input:focus, textarea:focus {
            border-color: #00008B;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 0, 128, 0.1);
        }

        input:read-only, textarea:read-only {
            background-color: #f8f9fa;
            cursor: not-allowed;
            color: #6c757d;
        }

        .btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn:hover {
            background-color: #545b62;
            transform: translateY(-2px);
        }

        .btn:active {
            transform: translateY(0);
        }

        @media (max-width: 480px) {
            .container {
                width: 90%;
                padding: 25px;
            }

            input, textarea {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verify Payment</h2>
        <form action="" method="post">
            <input type="text" name="order_id" value="<?php echo $order_id; ?>" placeholder="Order ID" readonly>
            <input type="text" name="transaction_id" placeholder="Transaction ID" required>
            <input type="text" name="amount" value="<?php echo $amount; ?>" placeholder="Amount" readonly>
            <input type="text" name="customer_name" value="<?php echo $customer_name; ?>" placeholder="Customer Name" readonly>
            <textarea name="description" placeholder="Description" readonly><?php echo $description; ?></textarea>
            <?php if (!$is_logged_in): ?>
                <!-- Extra email input for anonymous users -->
                <input type="email" name="anonymous_email" placeholder="Enter your email for receipt" required>
            <?php endif; ?>
            <button type="submit" class="btn">Submit Verification</button>
        </form>
    </div>
</body>
</html>
